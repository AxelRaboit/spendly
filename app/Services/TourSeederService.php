<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BudgetSection;
use App\Enums\TransactionType;
use App\Enums\WalletRole;
use App\Models\Budget;
use App\Models\BudgetItem;
use App\Models\BudgetPreset;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletMember;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TourSeederService
{
    public function seed(User $user): Wallet
    {
        $existing = Wallet::where('user_id', $user->id)->where('is_demo', true)->first();

        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($user) {
            $locale = $user->locale ?? 'fr';
            $labels = $this->labels($locale);

            $wallet = Wallet::create([
                'user_id' => $user->id,
                'name' => $labels['wallet_name'],
                'start_balance' => 1500.00,
                'is_demo' => true,
            ]);

            WalletMember::create([
                'wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'role' => WalletRole::Owner,
            ]);

            $categories = $this->createCategories($user, $wallet, $labels);

            $prevMonth = Carbon::now()->subMonth()->startOfMonth();
            $currentMonth = Carbon::now()->startOfMonth();

            $prevBudget = Budget::create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'month' => $prevMonth->format('Y-m-d'),
                'notes' => $labels['notes_prev'],
            ]);

            $currentBudget = Budget::create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'month' => $currentMonth->format('Y-m-d'),
                'notes' => $labels['notes_current'],
            ]);

            $blueprint = $this->blueprint($labels);

            $this->createBudgetItems($prevBudget, $currentBudget, $categories, $blueprint);
            $this->createTransactions($user, $wallet, $categories, $blueprint, $prevMonth, $currentMonth);
            $this->createPresets($user, $labels);

            return $wallet;
        });
    }

    public function cleanup(User $user): void
    {
        // Always remove demo presets regardless of wallet state
        BudgetPreset::where('user_id', $user->id)
            ->where('is_demo', true)
            ->delete();

        $wallet = Wallet::where('user_id', $user->id)->where('is_demo', true)->first();

        if (! $wallet) {
            return;
        }

        DB::transaction(function () use ($wallet) {
            $wallet->transactions()->delete();
            $budgetIds = $wallet->budgets()->pluck('id');
            BudgetItem::whereIn('budget_id', $budgetIds)->delete();
            $wallet->budgets()->delete();
            Category::where('wallet_id', $wallet->id)->delete();
            $wallet->members()->delete();
            $wallet->delete();
        });
    }

    private function createPresets(User $user, array $labels): void
    {
        $presets = $labels['presets'] ?? [];
        $position = 0;

        foreach ($presets as $preset) {
            BudgetPreset::create([
                'user_id' => $user->id,
                'label' => $preset['label'],
                'type' => $preset['type'],
                'planned_amount' => $preset['amount'],
                'position' => $position++,
                'is_demo' => true,
            ]);
        }
    }

    private function createCategories(User $user, Wallet $wallet, array $labels): array
    {
        $map = [];

        foreach ($labels['categories'] as $key => $name) {
            $map[$key] = Category::create([
                'name' => $name,
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
            ]);
        }

        return $map;
    }

    private function createBudgetItems(Budget $prev, Budget $current, array $categories, array $blueprint): void
    {
        $position = 0;

        foreach ($blueprint as $item) {
            $categoryId = isset($item['category']) && isset($categories[$item['category']])
                ? $categories[$item['category']]->id
                : null;

            $base = [
                'type' => $item['type'],
                'label' => $item['label'],
                'planned_amount' => $item['planned'],
                'category_id' => $categoryId,
                'position' => $position,
                'repeat_next_month' => true,
            ];

            BudgetItem::create(array_merge($base, ['budget_id' => $prev->id]));
            BudgetItem::create(array_merge($base, ['budget_id' => $current->id]));

            $position++;
        }
    }

    private function createTransactions(User $user, Wallet $wallet, array $categories, array $blueprint, Carbon $prevMonth, Carbon $currentMonth): void
    {
        foreach ($blueprint as $item) {
            if (! isset($item['category'])) {
                continue;
            }

            if (! isset($categories[$item['category']])) {
                continue;
            }

            $category = $categories[$item['category']];
            $txType = $item['type'] === BudgetSection::Income->value
                ? TransactionType::Income
                : TransactionType::Expense;

            // Previous month: full transactions
            foreach ($item['tx_prev'] as $tx) {
                Transaction::create([
                    'user_id' => $user->id,
                    'wallet_id' => $wallet->id,
                    'category_id' => $category->id,
                    'type' => $txType,
                    'amount' => $tx['amount'],
                    'description' => $tx['desc'],
                    'date' => $prevMonth->copy()->addDays($tx['day'] - 1)->format('Y-m-d'),
                ]);
            }

            // Current month: partial transactions
            foreach ($item['tx_current'] as $tx) {
                Transaction::create([
                    'user_id' => $user->id,
                    'wallet_id' => $wallet->id,
                    'category_id' => $category->id,
                    'type' => $txType,
                    'amount' => $tx['amount'],
                    'description' => $tx['desc'],
                    'date' => $currentMonth->copy()->addDays($tx['day'] - 1)->format('Y-m-d'),
                ]);
            }
        }
    }

    private function blueprint(array $labels): array
    {
        $transactionLabels = $labels['transactions'];

        return [
            // ── Income ──
            [
                'type' => 'income', 'category' => 'salary', 'label' => $labels['categories']['salary'],
                'planned' => 2500,
                'tx_prev' => [['amount' => 2500, 'desc' => $transactionLabels['salary'], 'day' => 1]],
                'tx_current' => [['amount' => 2500, 'desc' => $transactionLabels['salary'], 'day' => 1]],
            ],
            [
                'type' => 'income', 'category' => 'freelance', 'label' => $labels['categories']['freelance'],
                'planned' => 400,
                'tx_prev' => [['amount' => 350, 'desc' => $transactionLabels['freelance_project'], 'day' => 15]],
                'tx_current' => [],
            ],

            // ── Bills ──
            [
                'type' => 'bills', 'category' => 'rent', 'label' => $labels['categories']['rent'],
                'planned' => 800,
                'tx_prev' => [['amount' => 800, 'desc' => $transactionLabels['rent'], 'day' => 1]],
                'tx_current' => [['amount' => 800, 'desc' => $transactionLabels['rent'], 'day' => 1]],
            ],
            [
                'type' => 'bills', 'category' => 'electricity', 'label' => $labels['categories']['electricity'],
                'planned' => 65,
                'tx_prev' => [['amount' => 58, 'desc' => $transactionLabels['electricity'], 'day' => 10]],
                'tx_current' => [['amount' => 62, 'desc' => $transactionLabels['electricity'], 'day' => 10]],
            ],
            [
                'type' => 'bills', 'category' => 'internet', 'label' => $labels['categories']['internet'],
                'planned' => 30,
                'tx_prev' => [['amount' => 30, 'desc' => $transactionLabels['internet'], 'day' => 5]],
                'tx_current' => [['amount' => 30, 'desc' => $transactionLabels['internet'], 'day' => 5]],
            ],
            [
                'type' => 'bills', 'category' => 'insurance', 'label' => $labels['categories']['insurance'],
                'planned' => 45,
                'tx_prev' => [['amount' => 45, 'desc' => $transactionLabels['insurance'], 'day' => 8]],
                'tx_current' => [['amount' => 45, 'desc' => $transactionLabels['insurance'], 'day' => 8]],
            ],

            // ── Expenses ──
            [
                'type' => 'expenses', 'category' => 'groceries', 'label' => $labels['categories']['groceries'],
                'planned' => 400,
                'tx_prev' => [
                    ['amount' => 65, 'desc' => $transactionLabels['supermarket'], 'day' => 2],
                    ['amount' => 42, 'desc' => $transactionLabels['supermarket'], 'day' => 6],
                    ['amount' => 58, 'desc' => $transactionLabels['market'], 'day' => 9],
                    ['amount' => 71, 'desc' => $transactionLabels['supermarket'], 'day' => 13],
                    ['amount' => 35, 'desc' => $transactionLabels['bakery'], 'day' => 17],
                    ['amount' => 55, 'desc' => $transactionLabels['supermarket'], 'day' => 21],
                    ['amount' => 54, 'desc' => $transactionLabels['market'], 'day' => 26],
                ],
                'tx_current' => [
                    ['amount' => 72, 'desc' => $transactionLabels['supermarket'], 'day' => 1],
                    ['amount' => 48, 'desc' => $transactionLabels['market'], 'day' => 5],
                    ['amount' => 55, 'desc' => $transactionLabels['supermarket'], 'day' => 9],
                    ['amount' => 45, 'desc' => $transactionLabels['bakery'], 'day' => 12],
                ],
            ],
            [
                'type' => 'expenses', 'category' => 'transport', 'label' => $labels['categories']['transport'],
                'planned' => 75,
                'tx_prev' => [
                    ['amount' => 40, 'desc' => $transactionLabels['transit_pass'], 'day' => 1],
                    ['amount' => 15, 'desc' => $transactionLabels['taxi'], 'day' => 14],
                    ['amount' => 15, 'desc' => $transactionLabels['fuel'], 'day' => 22],
                ],
                'tx_current' => [
                    ['amount' => 40, 'desc' => $transactionLabels['transit_pass'], 'day' => 1],
                    ['amount' => 12, 'desc' => $transactionLabels['taxi'], 'day' => 8],
                ],
            ],
            [
                'type' => 'expenses', 'category' => 'restaurants', 'label' => $labels['categories']['restaurants'],
                'planned' => 120,
                'tx_prev' => [
                    ['amount' => 32, 'desc' => $transactionLabels['restaurant'], 'day' => 4],
                    ['amount' => 28, 'desc' => $transactionLabels['restaurant'], 'day' => 11],
                    ['amount' => 45, 'desc' => $transactionLabels['dinner_friends'], 'day' => 18],
                    ['amount' => 30, 'desc' => $transactionLabels['restaurant'], 'day' => 25],
                ],
                'tx_current' => [
                    ['amount' => 35, 'desc' => $transactionLabels['restaurant'], 'day' => 3],
                    ['amount' => 25, 'desc' => $transactionLabels['restaurant'], 'day' => 10],
                ],
            ],
            [
                'type' => 'expenses', 'category' => 'leisure', 'label' => $labels['categories']['leisure'],
                'planned' => 100,
                'tx_prev' => [
                    ['amount' => 15, 'desc' => $transactionLabels['cinema'], 'day' => 7],
                    ['amount' => 12, 'desc' => $transactionLabels['streaming'], 'day' => 10],
                    ['amount' => 35, 'desc' => $transactionLabels['concert'], 'day' => 20],
                    ['amount' => 23, 'desc' => $transactionLabels['books'], 'day' => 27],
                ],
                'tx_current' => [
                    ['amount' => 12, 'desc' => $transactionLabels['streaming'], 'day' => 1],
                    ['amount' => 28, 'desc' => $transactionLabels['cinema'], 'day' => 6],
                ],
            ],

            // ── Savings ──
            [
                'type' => 'savings', 'category' => 'savings', 'label' => $labels['categories']['savings'],
                'planned' => 200,
                'tx_prev' => [['amount' => 200, 'desc' => $transactionLabels['savings_transfer'], 'day' => 2]],
                'tx_current' => [['amount' => 200, 'desc' => $transactionLabels['savings_transfer'], 'day' => 2]],
            ],

            // ── Debt ──
            [
                'type' => 'debt', 'category' => 'student_loan', 'label' => $labels['categories']['student_loan'],
                'planned' => 150,
                'tx_prev' => [['amount' => 150, 'desc' => $transactionLabels['loan_payment'], 'day' => 5]],
                'tx_current' => [['amount' => 150, 'desc' => $transactionLabels['loan_payment'], 'day' => 5]],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function labels(string $locale): array
    {
        $map = [
            'fr' => [
                'wallet_name' => 'Mon budget exemple',
                'notes_prev' => 'Bon mois ! Restaurants un peu au-dessus du budget, mais l\'épargne est respectée.',
                'notes_current' => 'Mois en cours — certaines dépenses restent à venir.',
                'categories' => [
                    'salary' => 'Salaire', 'freelance' => 'Freelance',
                    'rent' => 'Loyer', 'electricity' => 'Électricité', 'internet' => 'Internet', 'insurance' => 'Assurance',
                    'groceries' => 'Alimentation', 'transport' => 'Transport', 'restaurants' => 'Restaurants', 'leisure' => 'Loisirs',
                    'savings' => 'Épargne', 'student_loan' => 'Prêt étudiant',
                ],
                'transactions' => [
                    'salary' => 'Salaire mensuel', 'freelance_project' => 'Projet freelance',
                    'rent' => 'Loyer', 'electricity' => 'EDF', 'internet' => 'Free box', 'insurance' => 'Assurance habitation',
                    'supermarket' => 'Carrefour', 'market' => 'Marché', 'bakery' => 'Boulangerie',
                    'transit_pass' => 'Navigo', 'taxi' => 'Uber', 'fuel' => 'Essence',
                    'restaurant' => 'Restaurant', 'dinner_friends' => 'Dîner entre amis',
                    'cinema' => 'Cinéma', 'streaming' => 'Netflix', 'concert' => 'Concert', 'books' => 'Livres',
                    'savings_transfer' => 'Virement épargne', 'loan_payment' => 'Remboursement prêt',
                ],
                'presets' => [
                    ['label' => 'Salaire', 'type' => 'income', 'amount' => 2500],
                    ['label' => 'Loyer', 'type' => 'bills', 'amount' => 800],
                    ['label' => 'Électricité', 'type' => 'bills', 'amount' => 65],
                    ['label' => 'Internet', 'type' => 'bills', 'amount' => 30],
                    ['label' => 'Alimentation', 'type' => 'expenses', 'amount' => 400],
                    ['label' => 'Transport', 'type' => 'expenses', 'amount' => 75],
                    ['label' => 'Épargne', 'type' => 'savings', 'amount' => 200],
                ],
            ],
            'en' => [
                'wallet_name' => 'My example budget',
                'notes_prev' => 'Good month! Restaurants slightly over budget, but savings on track.',
                'notes_current' => 'Current month — some expenses still to come.',
                'categories' => [
                    'salary' => 'Salary', 'freelance' => 'Freelance',
                    'rent' => 'Rent', 'electricity' => 'Electricity', 'internet' => 'Internet', 'insurance' => 'Insurance',
                    'groceries' => 'Groceries', 'transport' => 'Transport', 'restaurants' => 'Restaurants', 'leisure' => 'Leisure',
                    'savings' => 'Savings', 'student_loan' => 'Student loan',
                ],
                'transactions' => [
                    'salary' => 'Monthly salary', 'freelance_project' => 'Freelance project',
                    'rent' => 'Rent', 'electricity' => 'Electric bill', 'internet' => 'Internet bill', 'insurance' => 'Home insurance',
                    'supermarket' => 'Supermarket', 'market' => 'Farmers market', 'bakery' => 'Bakery',
                    'transit_pass' => 'Transit pass', 'taxi' => 'Uber', 'fuel' => 'Gas station',
                    'restaurant' => 'Restaurant', 'dinner_friends' => 'Dinner with friends',
                    'cinema' => 'Cinema', 'streaming' => 'Netflix', 'concert' => 'Concert', 'books' => 'Books',
                    'savings_transfer' => 'Savings transfer', 'loan_payment' => 'Loan payment',
                ],
                'presets' => [
                    ['label' => 'Salary', 'type' => 'income', 'amount' => 2500],
                    ['label' => 'Rent', 'type' => 'bills', 'amount' => 800],
                    ['label' => 'Electricity', 'type' => 'bills', 'amount' => 65],
                    ['label' => 'Internet', 'type' => 'bills', 'amount' => 30],
                    ['label' => 'Groceries', 'type' => 'expenses', 'amount' => 400],
                    ['label' => 'Transport', 'type' => 'expenses', 'amount' => 75],
                    ['label' => 'Savings', 'type' => 'savings', 'amount' => 200],
                ],
            ],
            'de' => [
                'wallet_name' => 'Mein Beispielbudget',
                'notes_prev' => 'Guter Monat! Restaurants etwas über Budget, aber Sparziel erreicht.',
                'notes_current' => 'Laufender Monat — einige Ausgaben stehen noch aus.',
                'categories' => [
                    'salary' => 'Gehalt', 'freelance' => 'Freelance',
                    'rent' => 'Miete', 'electricity' => 'Strom', 'internet' => 'Internet', 'insurance' => 'Versicherung',
                    'groceries' => 'Lebensmittel', 'transport' => 'Transport', 'restaurants' => 'Restaurants', 'leisure' => 'Freizeit',
                    'savings' => 'Sparen', 'student_loan' => 'Studienkredit',
                ],
                'transactions' => [
                    'salary' => 'Monatsgehalt', 'freelance_project' => 'Freelance-Projekt',
                    'rent' => 'Miete', 'electricity' => 'Stromrechnung', 'internet' => 'Internetrechnung', 'insurance' => 'Hausratversicherung',
                    'supermarket' => 'Supermarkt', 'market' => 'Wochenmarkt', 'bakery' => 'Bäckerei',
                    'transit_pass' => 'Monatskarte', 'taxi' => 'Uber', 'fuel' => 'Tankstelle',
                    'restaurant' => 'Restaurant', 'dinner_friends' => 'Abendessen mit Freunden',
                    'cinema' => 'Kino', 'streaming' => 'Netflix', 'concert' => 'Konzert', 'books' => 'Bücher',
                    'savings_transfer' => 'Sparüberweisung', 'loan_payment' => 'Kreditrate',
                ],
                'presets' => [
                    ['label' => 'Gehalt', 'type' => 'income', 'amount' => 2500],
                    ['label' => 'Miete', 'type' => 'bills', 'amount' => 800],
                    ['label' => 'Strom', 'type' => 'bills', 'amount' => 65],
                    ['label' => 'Internet', 'type' => 'bills', 'amount' => 30],
                    ['label' => 'Lebensmittel', 'type' => 'expenses', 'amount' => 400],
                    ['label' => 'Transport', 'type' => 'expenses', 'amount' => 75],
                    ['label' => 'Sparen', 'type' => 'savings', 'amount' => 200],
                ],
            ],
            'es' => [
                'wallet_name' => 'Mi presupuesto de ejemplo',
                'notes_prev' => '¡Buen mes! Restaurantes un poco por encima del presupuesto, pero el ahorro se cumplió.',
                'notes_current' => 'Mes en curso — algunos gastos aún por llegar.',
                'categories' => [
                    'salary' => 'Salario', 'freelance' => 'Freelance',
                    'rent' => 'Alquiler', 'electricity' => 'Electricidad', 'internet' => 'Internet', 'insurance' => 'Seguro',
                    'groceries' => 'Alimentación', 'transport' => 'Transporte', 'restaurants' => 'Restaurantes', 'leisure' => 'Ocio',
                    'savings' => 'Ahorro', 'student_loan' => 'Préstamo estudiantil',
                ],
                'transactions' => [
                    'salary' => 'Salario mensual', 'freelance_project' => 'Proyecto freelance',
                    'rent' => 'Alquiler', 'electricity' => 'Factura eléctrica', 'internet' => 'Factura internet', 'insurance' => 'Seguro hogar',
                    'supermarket' => 'Supermercado', 'market' => 'Mercado', 'bakery' => 'Panadería',
                    'transit_pass' => 'Abono transporte', 'taxi' => 'Uber', 'fuel' => 'Gasolinera',
                    'restaurant' => 'Restaurante', 'dinner_friends' => 'Cena con amigos',
                    'cinema' => 'Cine', 'streaming' => 'Netflix', 'concert' => 'Concierto', 'books' => 'Libros',
                    'savings_transfer' => 'Transferencia ahorro', 'loan_payment' => 'Pago préstamo',
                ],
                'presets' => [
                    ['label' => 'Salario', 'type' => 'income', 'amount' => 2500],
                    ['label' => 'Alquiler', 'type' => 'bills', 'amount' => 800],
                    ['label' => 'Electricidad', 'type' => 'bills', 'amount' => 65],
                    ['label' => 'Internet', 'type' => 'bills', 'amount' => 30],
                    ['label' => 'Alimentación', 'type' => 'expenses', 'amount' => 400],
                    ['label' => 'Transporte', 'type' => 'expenses', 'amount' => 75],
                    ['label' => 'Ahorro', 'type' => 'savings', 'amount' => 200],
                ],
            ],
        ];

        return $map[$locale] ?? $map['fr'];
    }
}
