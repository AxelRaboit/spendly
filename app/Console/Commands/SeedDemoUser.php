<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\PlanType;
use App\Enums\WalletRole;
use App\Models\Budget;
use App\Models\BudgetItem;
use App\Models\BudgetPreset;
use App\Models\CategorizationRule;
use App\Models\Category;
use App\Models\Goal;
use App\Models\RecurringTransaction;
use App\Models\ScheduledTransaction;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletMember;
use App\Services\BudgetService;
use App\Services\WalletTransferService;
use Carbon\Carbon;
use Faker\Factory as FakerFactory;
use Faker\Generator as FakerGenerator;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('demo:seed {--email= : Demo user email (default: DEMO_USER_EMAIL env or demo@spendly.app)} {--password= : Demo user password (default: DEMO_USER_PASSWORD env or demo)} {--force : Reset if user already exists without confirmation}')]
#[Description('Create (or reset) a demo user with realistic fixture data')]
class SeedDemoUser extends Command
{
    private const array BUDGET_TEMPLATE = [
        ['type' => 'income',   'label' => 'Salaire',            'planned' => 2800.00, 'cat' => 'Salaire',           'target_type' => null,       'target_amount' => null,   'notes' => null],
        ['type' => 'income',   'label' => 'Freelance',          'planned' => 400.00,  'cat' => 'Freelance',          'target_type' => null,       'target_amount' => null,   'notes' => 'Variable selon les missions du mois'],
        ['type' => 'savings',  'label' => 'Épargne vacances',   'planned' => 200.00,  'cat' => 'Épargne',            'target_type' => 'saving',   'target_amount' => 200.00, 'notes' => 'Objectif Japon — virer le 2 du mois'],
        ['type' => 'bills',    'label' => 'Loyer',              'planned' => 850.00,  'cat' => 'Loyer',              'target_type' => 'spending', 'target_amount' => 850.00, 'notes' => null],
        ['type' => 'bills',    'label' => 'Électricité / Gaz',  'planned' => 80.00,   'cat' => 'Électricité',        'target_type' => 'spending', 'target_amount' => 100.00, 'notes' => 'Plus élevé en hiver (nov–fév)'],
        ['type' => 'bills',    'label' => 'Internet',           'planned' => 35.00,   'cat' => 'Internet',           'target_type' => 'spending', 'target_amount' => 35.00,  'notes' => null],
        ['type' => 'bills',    'label' => 'Téléphone',          'planned' => 20.00,   'cat' => 'Téléphone',          'target_type' => 'spending', 'target_amount' => 20.00,  'notes' => null],
        ['type' => 'bills',    'label' => 'Assurance',          'planned' => 60.00,   'cat' => 'Assurance',          'target_type' => 'spending', 'target_amount' => 65.00,  'notes' => null],
        ['type' => 'expenses', 'label' => 'Courses',            'planned' => 400.00,  'cat' => 'Alimentation',       'target_type' => 'spending', 'target_amount' => 400.00, 'notes' => null],
        ['type' => 'expenses', 'label' => 'Transport',          'planned' => 80.00,   'cat' => 'Transport',          'target_type' => 'spending', 'target_amount' => 100.00, 'notes' => null],
        ['type' => 'expenses', 'label' => 'Restaurants',        'planned' => 150.00,  'cat' => 'Restaurants',        'target_type' => 'spending', 'target_amount' => 150.00, 'notes' => 'Inclut déjeuners pro remboursables'],
        ['type' => 'expenses', 'label' => 'Loisirs',            'planned' => 100.00,  'cat' => 'Loisirs',            'target_type' => 'spending', 'target_amount' => 120.00, 'notes' => null],
        ['type' => 'expenses', 'label' => 'Santé',              'planned' => 50.00,   'cat' => 'Santé',              'target_type' => null,       'target_amount' => null,   'notes' => null],
        ['type' => 'expenses', 'label' => 'Vêtements',          'planned' => 60.00,   'cat' => 'Vêtements',          'target_type' => 'spending', 'target_amount' => 80.00,  'notes' => null],
        ['type' => 'debt',     'label' => 'Remboursement prêt', 'planned' => 250.00,  'cat' => 'Remboursement prêt', 'target_type' => 'spending', 'target_amount' => 250.00, 'notes' => 'Fin du prêt dans 18 mois'],
    ];

    private FakerGenerator $faker;

    public function handle(BudgetService $budgetService, WalletTransferService $transferService): int
    {
        $this->faker = FakerFactory::create('fr_FR');
        $email = $this->option('email') ?? config('demo.email');
        $password = $this->option('password') ?? config('demo.password');

        $existing = User::where('email', $email)->first();

        if ($existing) {
            if (! $this->option('force') && ! $this->confirm(sprintf('User [%s] already exists. Delete and re-seed?', $email))) {
                $this->info('Aborted.');

                return 0;
            }

            $existing->delete();
            $this->line('  <comment>Deleted existing demo user.</comment>');
        }

        $this->info(sprintf('Seeding demo user [%s]...', $email));

        $user = User::factory()->create([
            'name' => 'Demo User',
            'email' => $email,
            'password' => bcrypt($password),
            'plan' => PlanType::Pro,
            'is_demo' => true,
        ]);
        $user->assignRole('ROLE_USER');

        $wallet = $this->createWallet($user, 'Compte courant', 2500.00, true);
        $livretA = $this->createWallet($user, 'Livret A', 0.00);
        $assuranceVie = $this->createWallet($user, 'Assurance vie', 0.00);

        $categories = $this->createCategories($user, $wallet);

        $months = collect(range(5, 0))->map(fn ($n) => now()->subMonths($n)->startOfMonth())->all();

        foreach ($months as $month) {
            $transferService->create($user, [
                'from_wallet_id' => $wallet->id,
                'to_wallet_id' => $livretA->id,
                'amount' => 300.00,
                'description' => 'Virement Livret A',
                'date' => Carbon::create($month->year, $month->month, 2)->toDateString(),
            ]);
            $transferService->create($user, [
                'from_wallet_id' => $wallet->id,
                'to_wallet_id' => $assuranceVie->id,
                'amount' => 100.00,
                'description' => 'Virement Assurance vie',
                'date' => Carbon::create($month->year, $month->month, 2)->toDateString(),
            ]);
        }

        foreach ($months as $i => $month) {
            $budget = $budgetService->getOrCreate($wallet, $month);
            $this->createBudgetItems($budget, $categories, $i > 0);
            $this->createMonthTransactions($user, $wallet, $month, $categories);
            $budgetService->computeCarryOver($budget, $month);
        }

        $this->createGoals($user, $wallet);
        $this->createRecurringTransactions($user, $wallet, $categories);
        $this->createScheduledTransactions($user, $wallet, $categories);
        $this->createBudgetPresets($user);
        $this->createCategorizationRules($user, $categories);

        $this->newLine();
        $this->info('✅ Demo user created successfully.');
        $this->table(['Field', 'Value'], [
            ['Email',    $email],
            ['Password', $password],
            ['Plan',     'Pro'],
        ]);

        return 0;
    }

    private function createWallet(User $user, string $name, float $startBalance, bool $isFavorite = false): Wallet
    {
        $wallet = Wallet::create([
            'user_id' => $user->id,
            'name' => $name,
            'start_balance' => $startBalance,
            'is_favorite' => $isFavorite,
        ]);

        WalletMember::create([
            'wallet_id' => $wallet->id,
            'user_id' => $user->id,
            'role' => WalletRole::Owner,
        ]);

        return $wallet;
    }

    private function createCategories(User $user, Wallet $wallet): array
    {
        $names = [
            'Salaire', 'Freelance', 'Épargne',
            'Loyer', 'Électricité', 'Internet', 'Téléphone', 'Assurance',
            'Alimentation', 'Transport', 'Restaurants', 'Loisirs', 'Santé', 'Vêtements',
            'Remboursement prêt',
        ];

        $map = [];
        foreach ($names as $name) {
            $map[$name] = Category::create(['user_id' => $user->id, 'wallet_id' => $wallet->id, 'name' => $name]);
        }

        return $map;
    }

    private function createBudgetItems(Budget $budget, array $categories, bool $repeatFromPrevious): void
    {
        foreach (self::BUDGET_TEMPLATE as $position => $item) {
            BudgetItem::create([
                'budget_id' => $budget->id,
                'type' => $item['type'],
                'label' => $item['label'],
                'planned_amount' => $item['planned'],
                'category_id' => $categories[$item['cat']]->id,
                'position' => $position,
                'repeat_next_month' => $repeatFromPrevious,
                'target_type' => $item['target_type'],
                'target_amount' => $item['target_amount'],
                'notes' => $item['notes'],
            ]);
        }
    }

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

        $tx('Salaire', 2800.00, 27, 'Virement salaire', 'income', ['salaire']);
        if ($month->month !== now()->month) {
            $tx('Freelance', $this->faker->randomFloat(2, 280, 520), 15, 'Facture client', 'income', ['freelance']);
        }

        $tx('Épargne', 200.00, 2, 'Virement épargne vacances', 'expense', ['épargne']);
        $tx('Loyer', 850.00, 1, 'Loyer', 'expense', ['logement']);
        $tx('Remboursement prêt', 250.00, 3, 'Prêt auto', 'expense', ['crédit']);
        $tx('Assurance', $this->faker->randomFloat(2, 58, 62), 8, 'Assurance habitation', 'expense', ['logement']);
        $tx('Électricité', $this->faker->randomFloat(2, 68, 94), 10, 'Edf', 'expense', ['logement', 'facture']);
        $tx('Internet', 34.99, 5, 'Freebox', 'expense', ['abonnement']);
        $tx('Téléphone', 19.99, 5, 'Forfait mobile', 'expense', ['abonnement']);

        foreach ([4, 9, 16, 22, 28] as $day) {
            $tx('Alimentation', $this->faker->randomFloat(2, 55, 100), $day,
                $this->faker->randomElement(['Lidl', 'Carrefour', 'Leclerc', 'Monoprix', 'Aldi']),
                'expense', ['courses']);
        }

        $tx('Transport', $this->faker->randomFloat(2, 18, 30), 3, 'Navigo / Carburant', 'expense', ['transport']);
        $tx('Transport', $this->faker->randomFloat(2, 15, 25), 14, 'Trajet', 'expense', ['transport']);
        if ($this->faker->boolean(60)) {
            $tx('Transport', $this->faker->randomFloat(2, 10, 20), 22, 'Uber', 'expense', ['transport', 'vtc']);
        }

        foreach ([7, 13, 19, 25] as $day) {
            if ($this->faker->boolean(75)) {
                $tx('Restaurants', $this->faker->randomFloat(2, 18, 48), $day,
                    $this->faker->randomElement(['Déjeuner', 'Dîner', 'Brasserie', 'Sushi', 'Pizza']),
                    'expense', ['resto']);
            }
        }

        $tx('Loisirs', $this->faker->randomFloat(2, 35, 65), 11,
            $this->faker->randomElement(['Netflix', 'Spotify', 'Cinéma', 'Concert', 'Sport']),
            'expense', ['loisirs', 'abonnement']);
        if ($this->faker->boolean(50)) {
            $tx('Loisirs', $this->faker->randomFloat(2, 20, 55), 20,
                $this->faker->randomElement(['Livre', 'Jeu', 'Exposition', 'Sortie']),
                'expense', ['loisirs']);
        }

        if ($this->faker->boolean(60)) {
            $tx('Santé', $this->faker->randomFloat(2, 22, 65), $this->faker->numberBetween(5, 25),
                $this->faker->randomElement(['Pharmacie', 'Médecin', 'Dentiste', 'Opticien']),
                'expense', ['santé']);
        }

        if ($this->faker->boolean(40)) {
            $tx('Vêtements', $this->faker->randomFloat(2, 30, 90), $this->faker->numberBetween(8, 25),
                $this->faker->randomElement(['H&m', 'Zara', 'Asos', 'Decathlon']),
                'expense', ['shopping']);
        }
    }

    private function createGoals(User $user, Wallet $wallet): void
    {
        $goals = [
            ['name' => 'Voyage au Japon',  'target_amount' => 3500.00, 'saved_amount' => 1200.00, 'deadline' => now()->addMonths(10)->toDateString(), 'color' => '#6366f1'],
            ['name' => "Fonds d'urgence",  'target_amount' => 5000.00, 'saved_amount' => 3750.00, 'deadline' => null,                                  'color' => '#22c55e'],
            ['name' => 'Nouveau vélo',     'target_amount' => 800.00,  'saved_amount' => 800.00,  'deadline' => null,                                  'color' => '#f59e0b'],
            ['name' => 'MacBook Pro',      'target_amount' => 2400.00, 'saved_amount' => 600.00,  'deadline' => now()->addMonths(6)->toDateString(),    'color' => '#3b82f6'],
        ];

        foreach ($goals as $data) {
            Goal::create(['user_id' => $user->id, 'wallet_id' => $wallet->id, 'category_id' => null, ...$data]);
        }
    }

    private function createRecurringTransactions(User $user, Wallet $wallet, array $categories): void
    {
        $recurring = [
            ['description' => 'Netflix',                  'amount' => 17.99, 'day_of_month' => 8,  'type' => 'expense', 'active' => true,  'cat' => 'Loisirs'],
            ['description' => 'Spotify',                  'amount' => 10.99, 'day_of_month' => 8,  'type' => 'expense', 'active' => true,  'cat' => 'Loisirs'],
            ['description' => 'Salle de sport',           'amount' => 29.90, 'day_of_month' => 1,  'type' => 'expense', 'active' => true,  'cat' => 'Santé'],
            ['description' => 'Salaire',                  'amount' => 2800,  'day_of_month' => 27, 'type' => 'income',  'active' => true,  'cat' => 'Salaire'],
            ['description' => 'Ancien abonnement presse', 'amount' => 12.99, 'day_of_month' => 15, 'type' => 'expense', 'active' => false, 'cat' => 'Loisirs'],
        ];

        foreach ($recurring as $data) {
            RecurringTransaction::create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'category_id' => $categories[$data['cat']]->id,
                'description' => $data['description'],
                'amount' => $data['amount'],
                'day_of_month' => $data['day_of_month'],
                'type' => $data['type'],
                'active' => $data['active'],
            ]);
        }
    }

    private function createScheduledTransactions(User $user, Wallet $wallet, array $categories): void
    {
        $scheduled = [
            ['description' => "Billet d'avion Tokyo", 'amount' => 650.00,  'type' => 'expense', 'scheduled_date' => now()->addWeeks(3)->toDateString(),                              'cat' => 'Loisirs'],
            ['description' => 'Prime annuelle',        'amount' => 1500.00, 'type' => 'income',  'scheduled_date' => now()->addMonths(2)->startOfMonth()->addDays(14)->toDateString(), 'cat' => 'Salaire'],
            ['description' => 'Contrôle technique',   'amount' => 79.00,   'type' => 'expense', 'scheduled_date' => now()->addMonth()->addDays(10)->toDateString(),                   'cat' => 'Transport'],
        ];

        foreach ($scheduled as $data) {
            ScheduledTransaction::create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'category_id' => $categories[$data['cat']]->id,
                'description' => $data['description'],
                'amount' => $data['amount'],
                'type' => $data['type'],
                'scheduled_date' => $data['scheduled_date'],
            ]);
        }
    }

    private function createBudgetPresets(User $user): void
    {
        $presets = [
            ['type' => 'income',   'label' => 'Salaire',            'planned_amount' => 2800.00],
            ['type' => 'income',   'label' => 'Freelance',          'planned_amount' => 400.00],
            ['type' => 'savings',  'label' => 'Épargne vacances',   'planned_amount' => 200.00],
            ['type' => 'bills',    'label' => 'Loyer',              'planned_amount' => 850.00],
            ['type' => 'bills',    'label' => 'Électricité / Gaz',  'planned_amount' => 80.00],
            ['type' => 'bills',    'label' => 'Internet',           'planned_amount' => 35.00],
            ['type' => 'bills',    'label' => 'Téléphone',          'planned_amount' => 20.00],
            ['type' => 'bills',    'label' => 'Assurance',          'planned_amount' => 60.00],
            ['type' => 'expenses', 'label' => 'Courses',            'planned_amount' => 400.00],
            ['type' => 'expenses', 'label' => 'Transport',          'planned_amount' => 80.00],
            ['type' => 'expenses', 'label' => 'Restaurants',        'planned_amount' => 150.00],
            ['type' => 'expenses', 'label' => 'Loisirs',            'planned_amount' => 100.00],
            ['type' => 'expenses', 'label' => 'Santé',              'planned_amount' => 50.00],
            ['type' => 'debt',     'label' => 'Remboursement prêt', 'planned_amount' => 250.00],
        ];

        foreach ($presets as $position => $preset) {
            BudgetPreset::create(['user_id' => $user->id, 'position' => $position, ...$preset]);
        }
    }

    private function createCategorizationRules(User $user, array $categories): void
    {
        $rules = [
            ['pattern' => 'lidl|carrefour|leclerc|monoprix|aldi|intermarché|super u', 'cat' => 'Alimentation', 'hits' => 14],
            ['pattern' => 'netflix',                                                   'cat' => 'Loisirs',       'hits' => 6],
            ['pattern' => 'spotify',                                                   'cat' => 'Loisirs',       'hits' => 6],
            ['pattern' => 'freebox|sfr|bouygues|orange',                               'cat' => 'Internet',      'hits' => 6],
            ['pattern' => 'sncf|ratp|navigo|uber|bolt|blablacar',                      'cat' => 'Transport',     'hits' => 9],
            ['pattern' => 'pharmacie|médecin|docteur|dentiste|opticien',               'cat' => 'Santé',         'hits' => 4],
            ['pattern' => 'edf|engie|totalenergies',                                   'cat' => 'Électricité',   'hits' => 6],
            ['pattern' => 'salaire|virement employeur',                                'cat' => 'Salaire',       'hits' => 6],
        ];

        foreach ($rules as $rule) {
            CategorizationRule::create([
                'user_id' => $user->id,
                'category_id' => $categories[$rule['cat']]->id,
                'pattern' => $rule['pattern'],
                'hits' => $rule['hits'],
            ]);
        }
    }
}
