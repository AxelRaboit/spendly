<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Budget;
use App\Services\BudgetService;
use App\Models\BudgetItem;
use App\Models\Category;
use App\Models\Goal;
use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Carbon\Carbon;
use App\Enums\PlanType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    // Budget mensuel de référence — lié aux catégories par nom
    private const BUDGET_TEMPLATE = [
        ['type' => 'income',   'label' => 'Salaire',            'planned' => 2800.00, 'cat' => 'Salaire'],
        ['type' => 'income',   'label' => 'Freelance',          'planned' => 400.00, 'cat' => 'Freelance'],
        ['type' => 'bills',    'label' => 'Loyer',              'planned' => 850.00, 'cat' => 'Loyer'],
        ['type' => 'bills',    'label' => 'Électricité / Gaz',  'planned' => 80.00, 'cat' => 'Électricité'],
        ['type' => 'bills',    'label' => 'Internet',           'planned' => 35.00, 'cat' => 'Internet'],
        ['type' => 'bills',    'label' => 'Téléphone',          'planned' => 20.00, 'cat' => 'Téléphone'],
        ['type' => 'bills',    'label' => 'Assurance',          'planned' => 60.00, 'cat' => 'Assurance'],
        ['type' => 'expenses', 'label' => 'Courses',            'planned' => 400.00, 'cat' => 'Alimentation'],
        ['type' => 'expenses', 'label' => 'Transport',          'planned' => 80.00, 'cat' => 'Transport'],
        ['type' => 'expenses', 'label' => 'Restaurants',        'planned' => 150.00, 'cat' => 'Restaurants'],
        ['type' => 'expenses', 'label' => 'Loisirs',            'planned' => 100.00, 'cat' => 'Loisirs'],
        ['type' => 'expenses', 'label' => 'Santé',              'planned' => 50.00,  'cat' => 'Santé'],
        ['type' => 'expenses', 'label' => 'Vêtements',          'planned' => 60.00,  'cat' => 'Vêtements'],
        ['type' => 'debt',     'label' => 'Remboursement prêt', 'planned' => 250.00, 'cat' => 'Remboursement prêt'],
    ];

    public function run(): void
    {
        $users = collect([
            ['name' => 'Alice Dupont', 'email' => 'alice@example.com', 'plan' => PlanType::Pro],
            ['name' => 'Bob Martin',   'email' => 'bob@example.com', 'plan' => PlanType::Free],
            ['name' => 'Test User',    'email' => 'test@example.com', 'plan' => PlanType::Pro],
        ])->map(fn ($data) => User::factory()->create([
            ...$data,
            'password' => bcrypt('password'),
        ]));

        $budgetService = app(BudgetService::class);

        foreach ($users as $user) {
            $categories = $this->createCategories($user);
            $isPro = $user->plan->value === 'pro';

            $wallet = Wallet::create([
                'user_id'       => $user->id,
                'name'          => 'Compte courant',
                'start_balance' => 2500.00,
                'is_favorite'   => true,
            ]);

            $wallets = [$wallet];
            if ($isPro) {
                $livretA = Wallet::create([
                    'user_id'       => $user->id,
                    'name'          => 'Livret A',
                    'start_balance' => 0.00,
                ]);

                $assuranceVie = Wallet::create([
                    'user_id'       => $user->id,
                    'name'          => 'Assurance vie',
                    'start_balance' => 0.00,
                ]);

                $wallets = [$wallet, $livretA, $assuranceVie];
            }

            $months = [
                now()->subMonths(2)->startOfMonth(),
                now()->subMonth()->startOfMonth(),
                now()->startOfMonth(),
            ];

            // Transfers first so seedTransferItem finds them when budgets are created
            if ($isPro) {
                foreach ($months as $month) {
                    $this->createMonthTransfer($user, $wallet, $wallets[1], $month, 300.00, 'Virement Livret A');
                    $this->createMonthTransfer($user, $wallet, $wallets[2], $month, 100.00, 'Virement Assurance vie');
                }
            }

            foreach ($months as $month) {
                $budget = $budgetService->getOrCreate($wallet, $month);
                $this->createBudgetItems($budget, $categories);
                $this->createMonthTransactions($user, $wallet, $month, $categories);
            }

            $this->createGoals($user, $wallet, $categories);
            $this->createRecurringTransactions($user, $wallet, $categories);
        }
    }

    /** Crée les catégories fixes pour un user, retourne un tableau indexé par nom. */
    private function createCategories(User $user): array
    {
        $names = [
            'Salaire', 'Freelance',
            'Loyer', 'Électricité', 'Internet', 'Téléphone', 'Assurance',
            'Alimentation', 'Transport', 'Restaurants', 'Loisirs', 'Santé', 'Vêtements',
            'Remboursement prêt',
        ];

        $map = [];
        foreach ($names as $name) {
            $map[$name] = Category::create(['user_id' => $user->id, 'name' => $name]);
        }

        return $map;
    }

    /** Crée les lignes budget pour un mois donné. */
    private function createBudgetItems(Budget $budget, array $categories): void
    {
        foreach (self::BUDGET_TEMPLATE as $position => $item) {
            BudgetItem::create([
                'budget_id'      => $budget->id,
                'type'           => $item['type'],
                'label'          => $item['label'],
                'planned_amount' => $item['planned'],
                'category_id'    => $item['cat'] ? $categories[$item['cat']]->id : null,
                'position'       => $position,
            ]);
        }
    }

    /**
     * Crée des transactions réalistes pour un mois donné.
     *
     * Salaire le 27, charges en début de mois, dépenses réparties sur le mois.
     * Le freelance n'est pas toujours présent (2 mois sur 3).
     */
    private function createMonthTransactions(User $user, Wallet $wallet, Carbon $month, array $categories): void
    {
        $y = $month->year;
        $m = $month->month;

        $tx = function (string $cat, float $amount, int $day, string $desc = '', string $type = 'expense', array $tags = []) use ($user, $wallet, $month, $categories, $y, $m): void {
            $maxDay = min($day, $month->copy()->endOfMonth()->day);
            Transaction::create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'category_id' => $categories[$cat]->id,
                'type' => $type,
                'amount' => round($amount, 2),
                'description' => $desc,
                'date' => Carbon::create($y, $m, $maxDay)->toDateString(),
                'tags' => $tags ?: null,
            ]);
        };

        // ── Revenus ──────────────────────────────────────────────────────────
        $tx('Salaire', 2800.00, 27, 'Virement salaire', 'income', ['salaire']);

        // Freelance 2 mois sur 3 (pas le mois le plus récent pour varier)
        if ($month->month !== now()->month) {
            $tx('Freelance', fake()->randomFloat(2, 280, 520), 15, 'Facture client', 'income', ['freelance']);
        }

        // ── Charges fixes ────────────────────────────────────────────────────
        $tx('Loyer', 850.00, 1, 'Loyer', 'expense', ['logement']);
        $tx('Remboursement prêt', 250.00, 3, 'Prêt auto', 'expense', ['crédit']);
        $tx('Assurance', fake()->randomFloat(2, 58, 62), 8, 'Assurance habitation', 'expense', ['logement']);
        $tx('Électricité', fake()->randomFloat(2, 68, 94), 10, 'EDF', 'expense', ['logement', 'facture']);
        $tx('Internet', 34.99, 5, 'Freebox', 'expense', ['abonnement']);
        $tx('Téléphone', 19.99, 5, 'Forfait mobile', 'expense', ['abonnement']);

        // ── Courses (4-5 passages) ────────────────────────────────────────────
        $coursesDays = [4, 9, 16, 22, 28];
        $coursesAmounts = [
            fake()->randomFloat(2, 65, 95),
            fake()->randomFloat(2, 50, 85),
            fake()->randomFloat(2, 70, 100),
            fake()->randomFloat(2, 60, 90),
            fake()->randomFloat(2, 55, 80),
        ];
        foreach ($coursesDays as $i => $day) {
            $tx('Alimentation', $coursesAmounts[$i], $day, fake()->randomElement(['Lidl', 'Carrefour', 'Leclerc', 'Monoprix', 'Aldi']), 'expense', ['courses']);
        }

        // ── Transport ────────────────────────────────────────────────────────
        $tx('Transport', fake()->randomFloat(2, 18, 30), 3, 'Navigo / Carburant', 'expense', ['transport']);
        $tx('Transport', fake()->randomFloat(2, 15, 25), 14, 'Trajet', 'expense', ['transport']);
        if (fake()->boolean(60)) {
            $tx('Transport', fake()->randomFloat(2, 10, 20), 22, 'Uber', 'expense', ['transport', 'vtc']);
        }

        // ── Restaurants ──────────────────────────────────────────────────────
        $restoDays = [7, 13, 19, 25];
        foreach ($restoDays as $day) {
            if (fake()->boolean(75)) {
                $tx('Restaurants', fake()->randomFloat(2, 18, 48), $day,
                    fake()->randomElement(['Déjeuner', 'Dîner', 'Brasserie', 'Sushi', 'Pizza']),
                    'expense', ['resto']);
            }
        }

        // ── Loisirs ──────────────────────────────────────────────────────────
        $tx('Loisirs', fake()->randomFloat(2, 35, 65), 11,
            fake()->randomElement(['Netflix', 'Spotify', 'Cinéma', 'Concert', 'Sport']),
            'expense', ['loisirs', 'abonnement']);
        if (fake()->boolean(50)) {
            $tx('Loisirs', fake()->randomFloat(2, 20, 55), 20,
                fake()->randomElement(['Livre', 'Jeu', 'Exposition', 'Sortie']),
                'expense', ['loisirs']);
        }

        // ── Santé (aléatoire) ─────────────────────────────────────────────────
        if (fake()->boolean(60)) {
            $tx('Santé', fake()->randomFloat(2, 22, 65), fake()->numberBetween(5, 25),
                fake()->randomElement(['Pharmacie', 'Médecin', 'Dentiste', 'Opticien']),
                'expense', ['santé']);
        }

        // ── Dépenses ponctuelles ──────────────────────────────────────────────
        if (fake()->boolean(40)) {
            $tx('Vêtements', fake()->randomFloat(2, 30, 90), fake()->numberBetween(8, 25),
                fake()->randomElement(['H&M', 'Zara', 'ASOS', 'Decathlon']),
                'expense', ['shopping']);
        }
    }

    /** Crée un virement mensuel entre deux wallets. */
    private function createMonthTransfer(User $user, Wallet $from, Wallet $to, Carbon $month, float $amount, string $description): void
    {
        $transferService = app(\App\Services\WalletTransferService::class);
        $transferService->create($user, [
            'from_wallet_id' => $from->id,
            'to_wallet_id'   => $to->id,
            'amount'         => $amount,
            'description'    => $description,
            'date'           => Carbon::create($month->year, $month->month, 2)->toDateString(),
        ]);
    }

    /** Crée des objectifs d'épargne pour un user. */
    private function createGoals(User $user, Wallet $wallet, array $categories): void
    {
        $isPro = $user->plan->value === 'pro';

        $goals = [
            [
                'name'          => 'Voyage au Japon',
                'wallet_id'     => $wallet->id,
                'category_id'   => null,
                'target_amount' => 3500.00,
                'saved_amount'  => 1200.00,
                'deadline'      => now()->addMonths(10)->toDateString(),
                'color'         => '#6366f1',
            ],
            [
                'name'          => 'Fonds d\'urgence',
                'wallet_id'     => $wallet->id,
                'category_id'   => null,
                'target_amount' => 5000.00,
                'saved_amount'  => 3750.00,
                'deadline'      => null,
                'color'         => '#22c55e',
            ],
        ];

        if ($isPro) {
            $goals[] = [
                'name'          => 'Nouveau vélo',
                'wallet_id'     => $wallet->id,
                'category_id'   => null,
                'target_amount' => 800.00,
                'saved_amount'  => 800.00,
                'deadline'      => null,
                'color'         => '#f59e0b',
            ];

            $goals[] = [
                'name'          => 'MacBook Pro',
                'wallet_id'     => $wallet->id,
                'category_id'   => null,
                'target_amount' => 2400.00,
                'saved_amount'  => 600.00,
                'deadline'      => now()->addMonths(6)->toDateString(),
                'color'         => '#3b82f6',
            ];
        }

        foreach ($goals as $data) {
            Goal::create(['user_id' => $user->id, ...$data]);
        }
    }

    /** Crée des transactions récurrentes pour un user. */
    private function createRecurringTransactions(User $user, Wallet $wallet, array $categories): void
    {
        $recurring = [
            [
                'description' => 'Netflix',
                'amount'      => 17.99,
                'day_of_month' => 8,
                'type'        => 'expense',
                'active'      => true,
                'cat'         => 'Loisirs',
            ],
            [
                'description' => 'Spotify',
                'amount'      => 10.99,
                'day_of_month' => 8,
                'type'        => 'expense',
                'active'      => true,
                'cat'         => 'Loisirs',
            ],
            [
                'description' => 'Salle de sport',
                'amount'      => 29.90,
                'day_of_month' => 1,
                'type'        => 'expense',
                'active'      => true,
                'cat'         => 'Santé',
            ],
            [
                'description' => 'Salaire',
                'amount'      => 2800.00,
                'day_of_month' => 27,
                'type'        => 'income',
                'active'      => true,
                'cat'         => 'Salaire',
            ],
            [
                'description' => 'Ancien abonnement presse',
                'amount'      => 12.99,
                'day_of_month' => 15,
                'type'        => 'expense',
                'active'      => false,
                'cat'         => 'Loisirs',
            ],
        ];

        foreach ($recurring as $data) {
            RecurringTransaction::create([
                'user_id'     => $user->id,
                'wallet_id'   => $wallet->id,
                'category_id' => $categories[$data['cat']]->id,
                'description' => $data['description'],
                'amount'      => $data['amount'],
                'day_of_month' => $data['day_of_month'],
                'type'        => $data['type'],
                'active'      => $data['active'],
            ]);
        }
    }
}
