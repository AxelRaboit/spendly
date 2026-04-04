<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Budget;
use App\Models\BudgetItem;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    // Budget mensuel de référence — lié aux catégories par nom
    private const BUDGET_TEMPLATE = [
        ['type' => 'income',   'label' => 'Salaire',            'planned' => 2800.00, 'cat' => 'Salaire'],
        ['type' => 'income',   'label' => 'Freelance',          'planned' => 400.00, 'cat' => 'Freelance'],
        ['type' => 'savings',  'label' => 'Livret A',           'planned' => 300.00, 'cat' => 'Épargne'],
        ['type' => 'savings',  'label' => 'Assurance vie',      'planned' => 100.00, 'cat' => null],
        ['type' => 'bills',    'label' => 'Loyer',              'planned' => 850.00, 'cat' => 'Loyer'],
        ['type' => 'bills',    'label' => 'Électricité / Gaz',  'planned' => 80.00, 'cat' => 'Électricité'],
        ['type' => 'bills',    'label' => 'Internet',           'planned' => 35.00, 'cat' => 'Internet'],
        ['type' => 'bills',    'label' => 'Téléphone',          'planned' => 20.00, 'cat' => 'Téléphone'],
        ['type' => 'bills',    'label' => 'Assurance',          'planned' => 60.00, 'cat' => 'Assurance'],
        ['type' => 'expenses', 'label' => 'Courses',            'planned' => 400.00, 'cat' => 'Alimentation'],
        ['type' => 'expenses', 'label' => 'Transport',          'planned' => 80.00, 'cat' => 'Transport'],
        ['type' => 'expenses', 'label' => 'Restaurants',        'planned' => 150.00, 'cat' => 'Restaurants'],
        ['type' => 'expenses', 'label' => 'Loisirs',            'planned' => 100.00, 'cat' => 'Loisirs'],
        ['type' => 'expenses', 'label' => 'Santé',              'planned' => 50.00, 'cat' => 'Santé'],
        ['type' => 'debt',     'label' => 'Remboursement prêt', 'planned' => 250.00, 'cat' => 'Remboursement prêt'],
    ];

    public function run(): void
    {
        $users = collect([
            ['name' => 'Alice Dupont', 'email' => 'alice@example.com'],
            ['name' => 'Bob Martin',   'email' => 'bob@example.com'],
            ['name' => 'Test User',    'email' => 'test@example.com'],
        ])->map(fn ($data) => User::factory()->create([
            ...$data,
            'password' => bcrypt('password'),
        ]));

        foreach ($users as $user) {
            $categories = $this->createCategories($user);

            $wallet = Wallet::create([
                'user_id' => $user->id,
                'name' => 'Compte courant',
                'start_balance' => 2500.00,
            ]);

            $months = [
                Carbon::now()->subMonths(2)->startOfMonth(),
                Carbon::now()->subMonth()->startOfMonth(),
                Carbon::now()->startOfMonth(),
            ];

            foreach ($months as $month) {
                $budget = Budget::create([
                    'user_id' => $user->id,
                    'wallet_id' => $wallet->id,
                    'month' => $month->toDateString(),
                ]);

                $this->createBudgetItems($budget, $categories);
                $this->createMonthTransactions($user, $wallet, $month, $categories);
            }
        }
    }

    /** Crée les catégories fixes pour un user, retourne un tableau indexé par nom. */
    private function createCategories(User $user): array
    {
        $names = [
            'Salaire', 'Freelance',
            'Loyer', 'Électricité', 'Internet', 'Téléphone', 'Assurance',
            'Alimentation', 'Transport', 'Restaurants', 'Loisirs', 'Santé', 'Vêtements',
            'Épargne', 'Remboursement prêt',
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
                'budget_id' => $budget->id,
                'type' => $item['type'],
                'label' => $item['label'],
                'planned_amount' => $item['planned'],
                'category_id' => $item['cat'] ? $categories[$item['cat']]->id : null,
                'position' => $position,
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

        $tx = function (string $cat, float $amount, int $day, string $desc = '', string $type = 'expense') use ($user, $wallet, $month, $categories, $y, $m): void {
            $maxDay = min($day, $month->copy()->endOfMonth()->day);
            Transaction::create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'category_id' => $categories[$cat]->id,
                'type' => $type,
                'amount' => round($amount, 2),
                'description' => $desc,
                'date' => Carbon::create($y, $m, $maxDay)->toDateString(),
            ]);
        };

        // ── Revenus ──────────────────────────────────────────────────────────
        $tx('Salaire', 2800.00, 27, 'Virement salaire', 'income');

        // Freelance 2 mois sur 3 (pas le mois le plus récent pour varier)
        if ($month->month !== Carbon::now()->month) {
            $tx('Freelance', fake()->randomFloat(2, 280, 520), 15, 'Facture client', 'income');
        }

        // ── Épargne ──────────────────────────────────────────────────────────
        $tx('Épargne', 300.00, 2, 'Virement Livret A');

        // ── Charges fixes ────────────────────────────────────────────────────
        $tx('Loyer', 850.00, 1, 'Loyer');
        $tx('Remboursement prêt', 250.00, 3, 'Prêt auto');
        $tx('Assurance', fake()->randomFloat(2, 58, 62), 8, 'Assurance habitation');
        $tx('Électricité', fake()->randomFloat(2, 68, 94), 10, 'EDF');
        $tx('Internet', 34.99, 5, 'Freebox');
        $tx('Téléphone', 19.99, 5, 'Forfait mobile');

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
            $tx('Alimentation', $coursesAmounts[$i], $day, fake()->randomElement(['Lidl', 'Carrefour', 'Leclerc', 'Monoprix', 'Aldi']));
        }

        // ── Transport ────────────────────────────────────────────────────────
        $tx('Transport', fake()->randomFloat(2, 18, 30), 3, 'Navigo / Carburant');
        $tx('Transport', fake()->randomFloat(2, 15, 25), 14, 'Trajet');
        if (fake()->boolean(60)) {
            $tx('Transport', fake()->randomFloat(2, 10, 20), 22, 'Uber');
        }

        // ── Restaurants ──────────────────────────────────────────────────────
        $restoDays = [7, 13, 19, 25];
        foreach ($restoDays as $day) {
            if (fake()->boolean(75)) {
                $tx('Restaurants', fake()->randomFloat(2, 18, 48), $day,
                    fake()->randomElement(['Déjeuner', 'Dîner', 'Brasserie', 'Sushi', 'Pizza']));
            }
        }

        // ── Loisirs ──────────────────────────────────────────────────────────
        $tx('Loisirs', fake()->randomFloat(2, 35, 65), 11, fake()->randomElement(['Netflix', 'Spotify', 'Cinéma', 'Concert', 'Sport']));
        if (fake()->boolean(50)) {
            $tx('Loisirs', fake()->randomFloat(2, 20, 55), 20, fake()->randomElement(['Livre', 'Jeu', 'Exposition', 'Sortie']));
        }

        // ── Santé (aléatoire) ─────────────────────────────────────────────────
        if (fake()->boolean(60)) {
            $tx('Santé', fake()->randomFloat(2, 22, 65), fake()->numberBetween(5, 25),
                fake()->randomElement(['Pharmacie', 'Médecin', 'Dentiste', 'Opticien']));
        }

        // ── Dépenses ponctuelles ──────────────────────────────────────────────
        if (fake()->boolean(40)) {
            $tx('Vêtements', fake()->randomFloat(2, 30, 90), fake()->numberBetween(8, 25),
                fake()->randomElement(['H&M', 'Zara', 'ASOS', 'Decathlon']));
        }
    }
}
