<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PlanType;
use App\Models\Budget;
use App\Models\BudgetItem;
use App\Models\Category;
use App\Models\Goal;
use App\Models\BudgetPreset;
use App\Models\Note;
use App\Models\RecurringTransaction;
use App\Models\ScheduledTransaction;
use App\Models\Transaction;
use App\Models\User;
use App\Enums\WalletRole;
use App\Models\Wallet;
use App\Models\WalletMember;
use App\Services\BudgetService;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    private const BUDGET_TEMPLATE = [
        ['type' => 'income',   'label' => 'Salaire',            'planned' => 2800.00, 'cat' => 'Salaire',            'target_type' => null,       'target_amount' => null],
        ['type' => 'income',   'label' => 'Freelance',          'planned' => 400.00,  'cat' => 'Freelance',           'target_type' => null,       'target_amount' => null],
        ['type' => 'savings',  'label' => 'Épargne vacances',   'planned' => 200.00,  'cat' => 'Épargne',             'target_type' => 'saving',   'target_amount' => 200.00],
        ['type' => 'bills',    'label' => 'Loyer',              'planned' => 850.00,  'cat' => 'Loyer',               'target_type' => 'spending', 'target_amount' => 850.00],
        ['type' => 'bills',    'label' => 'Électricité / Gaz',  'planned' => 80.00,   'cat' => 'Électricité',         'target_type' => 'spending', 'target_amount' => 100.00],
        ['type' => 'bills',    'label' => 'Internet',           'planned' => 35.00,   'cat' => 'Internet',            'target_type' => 'spending', 'target_amount' => 35.00],
        ['type' => 'bills',    'label' => 'Téléphone',          'planned' => 20.00,   'cat' => 'Téléphone',           'target_type' => 'spending', 'target_amount' => 20.00],
        ['type' => 'bills',    'label' => 'Assurance',          'planned' => 60.00,   'cat' => 'Assurance',           'target_type' => 'spending', 'target_amount' => 65.00],
        ['type' => 'expenses', 'label' => 'Courses',            'planned' => 400.00,  'cat' => 'Alimentation',        'target_type' => 'spending', 'target_amount' => 400.00],
        ['type' => 'expenses', 'label' => 'Transport',          'planned' => 80.00,   'cat' => 'Transport',           'target_type' => 'spending', 'target_amount' => 100.00],
        ['type' => 'expenses', 'label' => 'Restaurants',        'planned' => 150.00,  'cat' => 'Restaurants',         'target_type' => 'spending', 'target_amount' => 150.00],
        ['type' => 'expenses', 'label' => 'Loisirs',            'planned' => 100.00,  'cat' => 'Loisirs',             'target_type' => 'spending', 'target_amount' => 120.00],
        ['type' => 'expenses', 'label' => 'Santé',              'planned' => 50.00,   'cat' => 'Santé',               'target_type' => null,       'target_amount' => null],
        ['type' => 'expenses', 'label' => 'Vêtements',          'planned' => 60.00,   'cat' => 'Vêtements',           'target_type' => 'spending', 'target_amount' => 80.00],
        ['type' => 'debt',     'label' => 'Remboursement prêt', 'planned' => 250.00,  'cat' => 'Remboursement prêt',  'target_type' => 'spending', 'target_amount' => 250.00],
    ];

    public function run(): void
    {
        $users = collect([
            ['name' => 'Alice Dupont', 'email' => 'alice@example.com', 'plan' => PlanType::Pro,  'trial_ends_at' => null],
            ['name' => 'Bob Martin',   'email' => 'bob@example.com',   'plan' => PlanType::Free, 'trial_ends_at' => null],
            ['name' => 'Test User',    'email' => 'test@example.com',  'plan' => PlanType::Pro,  'trial_ends_at' => now()->addDays(15)],
        ])->map(fn ($data) => User::factory()->create([
            ...$data,
            'password' => bcrypt('password'),
        ]));

        $budgetService = app(BudgetService::class);

        foreach ($users as $user) {
            $isPro = $user->plan->value === 'pro';

            $wallet = $this->createWallet($user, 'Compte courant', 2500.00, true);
            $categories = $this->createCategories($user, $wallet);

            $wallets = [$wallet];
            if ($isPro) {
                $livretA = $this->createWallet($user, 'Livret A', 0.00);
                $assuranceVie = $this->createWallet($user, 'Assurance vie', 0.00);

                $wallets = [$wallet, $livretA, $assuranceVie];
            }

            $months = [
                now()->subMonths(2)->startOfMonth(),
                now()->subMonth()->startOfMonth(),
                now()->startOfMonth(),
            ];

            if ($isPro) {
                foreach ($months as $month) {
                    $this->createMonthTransfer($user, $wallet, $wallets[1], $month, 300.00, 'Virement Livret A');
                    $this->createMonthTransfer($user, $wallet, $wallets[2], $month, 100.00, 'Virement Assurance vie');
                }
            }

            foreach ($months as $i => $month) {
                $budget = $budgetService->getOrCreate($wallet, $month);
                $this->createBudgetItems($budget, $categories, $i > 0);
                $this->createMonthTransactions($user, $wallet, $month, $categories);
                $budgetService->computeCarryOver($budget, $month);
            }

            $this->createGoals($user, $wallet, $categories);
            $this->createRecurringTransactions($user, $wallet, $categories);
            $this->createScheduledTransactions($user, $wallet, $categories);
            $this->createBudgetPresets($user);
            $this->createNotes($user);
        }
    }

    private function createWallet(User $user, string $name, float $startBalance, bool $isFavorite = false): Wallet
    {
        $wallet = Wallet::create([
            'user_id' => $user->id,
            'name' => $name,
            'start_balance' => $startBalance,
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
                'budget_id'        => $budget->id,
                'type'             => $item['type'],
                'label'            => $item['label'],
                'planned_amount'   => $item['planned'],
                'category_id'      => $item['cat'] ? $categories[$item['cat']]->id : null,
                'position'         => $position,
                'repeat_next_month' => $repeatFromPrevious,
                'target_type'      => $item['target_type'],
                'target_amount'    => $item['target_amount'],
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
                'user_id'     => $user->id,
                'wallet_id'   => $wallet->id,
                'category_id' => $categories[$cat]->id,
                'type'        => $type,
                'amount'      => round($amount, 2),
                'description' => $desc,
                'date'        => Carbon::create($y, $m, $maxDay)->toDateString(),
                'tags'        => $tags ?: null,
            ]);
        };

        // ── Revenus
        $tx('Salaire', 2800.00, 27, 'Virement salaire', 'income', ['salaire']);
        if ($month->month !== now()->month) {
            $tx('Freelance', fake()->randomFloat(2, 280, 520), 15, 'Facture client', 'income', ['freelance']);
        }

        // ── Épargne
        $tx('Épargne', 200.00, 2, 'Virement épargne vacances', 'expense', ['épargne']);

        // ── Charges fixes
        $tx('Loyer', 850.00, 1, 'Loyer', 'expense', ['logement']);
        $tx('Remboursement prêt', 250.00, 3, 'Prêt auto', 'expense', ['crédit']);
        $tx('Assurance', fake()->randomFloat(2, 58, 62), 8, 'Assurance habitation', 'expense', ['logement']);
        $tx('Électricité', fake()->randomFloat(2, 68, 94), 10, 'Edf', 'expense', ['logement', 'facture']);
        $tx('Internet', 34.99, 5, 'Freebox', 'expense', ['abonnement']);
        $tx('Téléphone', 19.99, 5, 'Forfait mobile', 'expense', ['abonnement']);

        // ── Courses (4-5 passages)
        foreach ([4, 9, 16, 22, 28] as $day) {
            $tx('Alimentation', fake()->randomFloat(2, 55, 100), $day,
                fake()->randomElement(['Lidl', 'Carrefour', 'Leclerc', 'Monoprix', 'Aldi']),
                'expense', ['courses']);
        }

        // ── Transport
        $tx('Transport', fake()->randomFloat(2, 18, 30), 3, 'Navigo / Carburant', 'expense', ['transport']);
        $tx('Transport', fake()->randomFloat(2, 15, 25), 14, 'Trajet', 'expense', ['transport']);
        if (fake()->boolean(60)) {
            $tx('Transport', fake()->randomFloat(2, 10, 20), 22, 'Uber', 'expense', ['transport', 'vtc']);
        }

        // ── Restaurants
        foreach ([7, 13, 19, 25] as $day) {
            if (fake()->boolean(75)) {
                $tx('Restaurants', fake()->randomFloat(2, 18, 48), $day,
                    fake()->randomElement(['Déjeuner', 'Dîner', 'Brasserie', 'Sushi', 'Pizza']),
                    'expense', ['resto']);
            }
        }

        // ── Loisirs
        $tx('Loisirs', fake()->randomFloat(2, 35, 65), 11,
            fake()->randomElement(['Netflix', 'Spotify', 'Cinéma', 'Concert', 'Sport']),
            'expense', ['loisirs', 'abonnement']);
        if (fake()->boolean(50)) {
            $tx('Loisirs', fake()->randomFloat(2, 20, 55), 20,
                fake()->randomElement(['Livre', 'Jeu', 'Exposition', 'Sortie']),
                'expense', ['loisirs']);
        }

        // ── Santé
        if (fake()->boolean(60)) {
            $tx('Santé', fake()->randomFloat(2, 22, 65), fake()->numberBetween(5, 25),
                fake()->randomElement(['Pharmacie', 'Médecin', 'Dentiste', 'Opticien']),
                'expense', ['santé']);
        }

        // ── Vêtements
        if (fake()->boolean(40)) {
            $tx('Vêtements', fake()->randomFloat(2, 30, 90), fake()->numberBetween(8, 25),
                fake()->randomElement(['H&m', 'Zara', 'Asos', 'Decathlon']),
                'expense', ['shopping']);
        }
    }

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

    private function createRecurringTransactions(User $user, Wallet $wallet, array $categories): void
    {
        $recurring = [
            ['description' => 'Netflix',               'amount' => 17.99, 'day_of_month' => 8,  'type' => 'expense', 'active' => true,  'cat' => 'Loisirs'],
            ['description' => 'Spotify',               'amount' => 10.99, 'day_of_month' => 8,  'type' => 'expense', 'active' => true,  'cat' => 'Loisirs'],
            ['description' => 'Salle de sport',        'amount' => 29.90, 'day_of_month' => 1,  'type' => 'expense', 'active' => true,  'cat' => 'Santé'],
            ['description' => 'Salaire',               'amount' => 2800,  'day_of_month' => 27, 'type' => 'income',  'active' => true,  'cat' => 'Salaire'],
            ['description' => 'Ancien abonnement presse', 'amount' => 12.99, 'day_of_month' => 15, 'type' => 'expense', 'active' => false, 'cat' => 'Loisirs'],
        ];

        foreach ($recurring as $data) {
            RecurringTransaction::create([
                'user_id'      => $user->id,
                'wallet_id'    => $wallet->id,
                'category_id'  => $categories[$data['cat']]->id,
                'description'  => $data['description'],
                'amount'       => $data['amount'],
                'day_of_month' => $data['day_of_month'],
                'type'         => $data['type'],
                'active'       => $data['active'],
            ]);
        }
    }

    private function createBudgetPresets(User $user): void
    {
        $presets = [
            ['type' => 'income',   'label' => 'Salaire',           'planned_amount' => 2800.00],
            ['type' => 'income',   'label' => 'Freelance',         'planned_amount' => 400.00],
            ['type' => 'savings',  'label' => 'Épargne vacances',  'planned_amount' => 200.00],
            ['type' => 'bills',    'label' => 'Loyer',             'planned_amount' => 850.00],
            ['type' => 'bills',    'label' => 'Électricité / Gaz', 'planned_amount' => 80.00],
            ['type' => 'bills',    'label' => 'Internet',          'planned_amount' => 35.00],
            ['type' => 'bills',    'label' => 'Téléphone',         'planned_amount' => 20.00],
            ['type' => 'bills',    'label' => 'Assurance',         'planned_amount' => 60.00],
            ['type' => 'expenses', 'label' => 'Courses',           'planned_amount' => 400.00],
            ['type' => 'expenses', 'label' => 'Transport',         'planned_amount' => 80.00],
            ['type' => 'expenses', 'label' => 'Restaurants',       'planned_amount' => 150.00],
            ['type' => 'expenses', 'label' => 'Loisirs',           'planned_amount' => 100.00],
            ['type' => 'expenses', 'label' => 'Santé',             'planned_amount' => 50.00],
            ['type' => 'debt',     'label' => 'Remboursement prêt','planned_amount' => 250.00],
        ];

        foreach ($presets as $position => $preset) {
            BudgetPreset::create([
                'user_id' => $user->id,
                'position' => $position,
                ...$preset,
            ]);
        }
    }

    private function createNotes(User $user): void
    {
        $today = now()->format('d/m/Y');

        // ── Root: Administratif (parent avec enfants) ────────────────────────
        $admin = Note::create([
            'user_id'   => $user->id,
            'parent_id' => null,
            'title'     => 'Administratif',
            'content'   => "# Administratif\n\nInfos importantes et documents administratifs.\n\nVoir aussi [[Finances]] pour le suivi financier.",
            'tags'      => ['admin'],
            'position'  => 0,
        ]);

        Note::create([
            'user_id'   => $user->id,
            'parent_id' => $admin->id,
            'title'     => 'Infos compte bancaire',
            'content'   => "# Infos compte bancaire\n\nIBAN : FR76 3000 4028 3798 7654 3210 943\nBIC : BNPAFRPP\n\n> [!warning] Confidentiel\n> Ne jamais partager ces informations par email.\n\nLié à [[Objectifs financiers]].",
            'tags'      => ['banque', 'confidentiel'],
            'position'  => 0,
        ]);

        // ── Root: Finances (wiki-links, callouts, headings, checkboxes) ──────
        $finances = Note::create([
            'user_id'   => $user->id,
            'parent_id' => null,
            'title'     => 'Finances',
            'content'   => "# Finances personnelles\n\nObjectifs et suivi financier.\n\n## Liens rapides\n\n- [[Objectifs financiers]]\n- [[Budget vacances]]\n- [[Infos compte bancaire]]\n\n## Notes\n\n> [!tip] Astuce\n> Penser à revoir le budget chaque début de mois.\n\n> [!info] Rappel\n> Les virements automatiques sont configurés le 2 de chaque mois.",
            'tags'      => ['finances'],
            'position'  => 1,
        ]);

        Note::create([
            'user_id'   => $user->id,
            'parent_id' => $finances->id,
            'title'     => 'Objectifs financiers',
            'content'   => "# Objectifs financiers\n\n## Court terme\n\n- [x] Ouvrir un Livret A\n- [ ] Atteindre **5 000 €** sur le fonds d'urgence\n- [ ] Rembourser la dette carte de crédit\n\n## Long terme\n\n- [ ] Apport immobilier : objectif **20 000 €** d'ici 3 ans\n- [ ] Investir en ETF monde via le PEA\n- [ ] Préparer la retraite complémentaire\n\n> [!success] Progrès\n> Le fonds d'urgence est à 75% de l'objectif !\n\n> [!quote] Citation\n> \"Ne mets pas tous tes œufs dans le même panier.\"\n\nVoir [[Budget vacances]] pour le prochain gros poste de dépense.\nRetour aux [[Finances]] pour la vue d'ensemble.",
            'tags'      => ['objectifs', 'épargne'],
            'position'  => 0,
        ]);

        $vacances = Note::create([
            'user_id'   => $user->id,
            'parent_id' => $finances->id,
            'title'     => 'Budget vacances',
            'content'   => "# Vacances — Budget prévisionnel\n\n> [!note] Destination\n> Japon — Été prochain, 2 semaines.\n\n## Estimation des coûts\n\n| Poste | Estimation |\n|---|---|\n| Vols A/R | 500 € |\n| Hébergement | 700 € |\n| Nourriture | 400 € |\n| Transport | 200 € |\n| **Total** | **1 800 €** |\n\n## Checklist\n\n- [x] Faire le passeport\n- [ ] Réserver les vols\n- [ ] Réserver les hôtels\n- [ ] Souscrire une assurance voyage\n\nDétails dans [[Check-list départ]].\nObjectifs liés : [[Objectifs financiers#Long terme]].",
            'tags'      => ['vacances', 'budget', 'japon'],
            'position'  => 1,
        ]);

        Note::create([
            'user_id'   => $user->id,
            'parent_id' => $vacances->id,
            'title'     => 'Check-list départ',
            'content'   => "# Check-list départ\n\n## Avant le départ\n\n- [x] Vérifier la validité du passeport\n- [ ] Réserver les vols\n- [ ] Souscrire une assurance voyage\n- [ ] Prévenir la banque\n- [ ] Acheter un adaptateur de prise\n\n## Dans la valise\n\n- [ ] Passeport + copies\n- [ ] Médicaments\n- [ ] Chargeurs\n- [ ] Guide de voyage\n\n> [!danger] Important\n> Vérifier les conditions d'entrée et les vaccins obligatoires !\n\n> [!question] FAQ\n> Faut-il un visa pour le Japon ? Non, séjour touristique < 90 jours.\n\nRetour au [[Budget vacances]].",
            'tags'      => ['checklist', 'vacances'],
            'position'  => 0,
        ]);

        // ── Root: Projets (code blocks, embeds, callouts variés) ─────────────
        $projets = Note::create([
            'user_id'   => $user->id,
            'parent_id' => null,
            'title'     => 'Projets',
            'content'   => "# Projets en cours\n\n## Actifs\n\n- [[Projet Spendly]] — App de gestion financière\n- [[Idées features]] — Brainstorm\n\n## En attente\n\n- Refonte du portfolio personnel\n- Contribution open source\n\n> [!abstract] Résumé\n> Focus sur Spendly pour les 3 prochains mois.",
            'tags'      => ['projets'],
            'position'  => 2,
        ]);

        Note::create([
            'user_id'   => $user->id,
            'parent_id' => $projets->id,
            'title'     => 'Projet Spendly',
            'content'   => "# Projet Spendly\n\n## Stack technique\n\n```php\n// Backend — Laravel\nRoute::get('/notes', [NoteController::class, 'index']);\nRoute::post('/notes', [NoteController::class, 'store']);\n```\n\n```js\n// Frontend — Vue 3 + Inertia\nimport { useNoteTree } from '@/composables/notes/useNoteTree';\n\nconst { tree, selectNote } = useNoteTree(props.notes);\n```\n\n```sql\n-- Requête de stats\nSELECT type, SUM(amount) as total\nFROM transactions\nWHERE user_id = 1\nGROUP BY type;\n```\n\n## Fonctionnalités récentes\n\n- [x] Wiki-links style Obsidian\n- [x] Callouts\n- [x] Syntax highlighting\n- [ ] Mode sombre amélioré\n- [ ] Export PDF\n\n> [!bug] Bug connu\n> Le drag & drop ne fonctionne pas sur mobile Safari.\n\n> [!example] Exemple de callout\n> On peut imbriquer du **markdown** dans les callouts, avec des `backticks` et des [liens](https://example.com).\n\nVoir aussi [[Idées features]] pour la roadmap.",
            'tags'      => ['dev', 'laravel', 'vue'],
            'position'  => 0,
        ]);

        Note::create([
            'user_id'   => $user->id,
            'parent_id' => $projets->id,
            'title'     => 'Idées features',
            'content'   => "# Idées features\n\n## Priorité haute\n\n- [ ] Notifications push pour les transactions récurrentes\n- [ ] Widget dashboard personnalisable\n- [ ] Export CSV / Excel amélioré\n\n## Priorité moyenne\n\n- [ ] Thèmes personnalisés\n- [ ] Partage de budget en famille\n- [ ] Graphiques interactifs\n\n## Plus tard\n\n- [ ] App mobile native\n- [ ] Intégration bancaire open banking\n- [ ] IA pour la catégorisation automatique\n\n> [!todo] À faire cette semaine\n> Implémenter le système de templates pour les notes.\n\n> [!failure] Abandonné\n> L'idée d'un chat intégré a été abandonnée — trop complexe pour le MVP.\n\nRéférence : [[Projet Spendly]] pour le contexte technique.",
            'tags'      => ['features', 'roadmap'],
            'position'  => 1,
        ]);

        // ── Root: Recettes (embed test, headings multiples) ──────────────────
        $recettes = Note::create([
            'user_id'   => $user->id,
            'parent_id' => null,
            'title'     => 'Recettes',
            'content'   => "# Mes recettes\n\nCollection de recettes testées et approuvées.\n\n## Préférées\n\n![[Pâtes carbonara]]\n\n![[Tiramisu]]\n\n## À tester\n\n- Ramen maison\n- Pain au levain\n- Curry thaï",
            'tags'      => ['cuisine', 'recettes'],
            'position'  => 3,
        ]);

        Note::create([
            'user_id'   => $user->id,
            'parent_id' => $recettes->id,
            'title'     => 'Pâtes carbonara',
            'content'   => "# Pâtes carbonara\n\n## Ingrédients\n\n- 400g de spaghetti\n- 200g de guanciale\n- 4 jaunes d'œufs\n- 100g de pecorino romano\n- Poivre noir\n\n## Préparation\n\n1. Cuire les pâtes al dente\n2. Faire revenir le guanciale\n3. Mélanger jaunes + pecorino\n4. Hors du feu, mélanger le tout\n\n> [!tip] Astuce\n> Ne jamais ajouter de crème ! La sauce est faite uniquement avec les œufs et le fromage.\n\n> [!warning] Attention\n> Bien mélanger hors du feu pour ne pas faire des œufs brouillés.",
            'tags'      => ['recette', 'italien', 'pâtes'],
            'position'  => 0,
        ]);

        Note::create([
            'user_id'   => $user->id,
            'parent_id' => $recettes->id,
            'title'     => 'Tiramisu',
            'content'   => "# Tiramisu\n\n## Ingrédients\n\n- 500g de mascarpone\n- 4 œufs\n- 100g de sucre\n- Biscuits cuillère\n- Café espresso fort\n- Cacao amer\n\n## Étapes\n\n- [x] Séparer les blancs des jaunes\n- [x] Fouetter jaunes + sucre\n- [ ] Incorporer le mascarpone\n- [ ] Monter les blancs en neige\n- [ ] Tremper les biscuits dans le café\n- [ ] Alterner couches de crème et biscuits\n- [ ] Réfrigérer 4h minimum\n\n> [!note] Note\n> Meilleur après 24h au frigo.",
            'tags'      => ['recette', 'dessert', 'italien'],
            'position'  => 1,
        ]);

        // ── Root: Daily note (date du jour) ──────────────────────────────────
        Note::create([
            'user_id'   => $user->id,
            'parent_id' => null,
            'title'     => $today,
            'content'   => "# {$today}\n\n## Tâches du jour\n\n- [ ] Revoir le budget mensuel\n- [ ] Tester les wiki-links dans [[Finances]]\n- [ ] Lire la note [[Projet Spendly]]\n- [x] Créer les fixtures de test\n\n## Notes rapides\n\nPenser à regarder les [[Objectifs financiers#Court terme]] ce soir.\n\n> [!info] Rappel\n> Réunion à 14h — préparer la démo.\n\n## Journal\n\nBonne journée productive. Les nouvelles features Obsidian-like fonctionnent bien.\n\n```bash\nphp artisan db:seed\npnpm dev\n```",
            'tags'      => ['daily', 'journal'],
            'position'  => 4,
        ]);

        // ── Root: Note showcase (toutes les features en un seul endroit) ─────
        Note::create([
            'user_id'   => $user->id,
            'parent_id' => null,
            'title'     => 'Showcase features',
            'content'   => "# Showcase — Toutes les features\n\nCette note démontre toutes les fonctionnalités Obsidian-like.\n\n## Wiki-links\n\nLien vers une note : [[Finances]]\nLien vers un heading : [[Objectifs financiers#Court terme]]\nLien interne : [[#Callouts]]\n\n## Embeds\n\nContenu embarqué d'une autre note :\n\n![[Pâtes carbonara]]\n\n## Callouts\n\n> [!note] Note\n> Ceci est une note simple.\n\n> [!tip] Astuce\n> Un conseil utile.\n\n> [!info] Information\n> Pour votre information.\n\n> [!warning] Attention\n> Quelque chose à surveiller.\n\n> [!danger] Danger\n> Action critique !\n\n> [!bug] Bug\n> Un problème identifié.\n\n> [!example] Exemple\n> Voici un exemple concret.\n\n> [!quote] Citation\n> \"La simplicité est la sophistication suprême.\" — Léonard de Vinci\n\n> [!success] Succès\n> Opération réussie !\n\n> [!question] Question\n> Comment ça marche ?\n\n> [!abstract] Résumé\n> En quelques mots...\n\n> [!todo] À faire\n> Ne pas oublier.\n\n> [!failure] Échec\n> Cela n'a pas fonctionné.\n\n## Checkboxes interactifs\n\n- [x] Feature implémentée\n- [x] Tests passés\n- [ ] Review de code\n- [ ] Mise en production\n\n## Code blocks\n\n```js\nconst greeting = 'Hello, Obsidian!';\nconsole.log(greeting);\n```\n\n```php\n<?php\n\$notes = Note::where('user_id', auth()->id())->get();\nforeach (\$notes as \$note) {\n    echo \$note->title;\n}\n```\n\n```python\ndef fibonacci(n):\n    a, b = 0, 1\n    for _ in range(n):\n        a, b = b, a + b\n    return a\n\nprint(fibonacci(10))\n```\n\n```sql\nSELECT n.title, COUNT(b.id) as backlinks\nFROM notes n\nLEFT JOIN notes b ON b.content LIKE CONCAT('%[[', n.title, ']]%')\nGROUP BY n.id\nORDER BY backlinks DESC;\n```\n\n```yaml\napp:\n  name: Spendly\n  version: 2.0\n  features:\n    - wiki-links\n    - callouts\n    - graph-view\n```\n\n```bash\nphp artisan migrate:fresh --seed\npnpm dev\ncurl http://localhost:8000/notes\n```\n\n## Tableau\n\n| Feature | Status | Priorité |\n|---|---|---|\n| Wiki-links | ✅ Done | Haute |\n| Callouts | ✅ Done | Haute |\n| Embeds | ✅ Done | Moyenne |\n| Checkboxes | ✅ Done | Moyenne |\n| Syntax highlight | ✅ Done | Moyenne |\n| Graph view | ✅ Done | Basse |\n\n## Markdown classique\n\n**Gras**, *italique*, ~~barré~~, `code inline`\n\n---\n\n1. Premier élément\n2. Deuxième élément\n3. Troisième élément\n\n- Puce 1\n- Puce 2\n  - Sous-puce\n  - Autre sous-puce\n\n> Blockquote classique sans callout.\n\n[Lien externe](https://example.com)",
            'tags'      => ['showcase', 'test', 'demo'],
            'position'  => 5,
        ]);
    }

    private function createScheduledTransactions(User $user, Wallet $wallet, array $categories): void
    {
        $scheduled = [
            [
                'description'    => 'Billet d\'avion Tokyo',
                'amount'         => 650.00,
                'type'           => 'expense',
                'scheduled_date' => now()->addWeeks(3)->toDateString(),
                'cat'            => 'Loisirs',
            ],
            [
                'description'    => 'Prime annuelle',
                'amount'         => 1500.00,
                'type'           => 'income',
                'scheduled_date' => now()->addMonths(2)->startOfMonth()->addDays(14)->toDateString(),
                'cat'            => 'Salaire',
            ],
            [
                'description'    => 'Contrôle technique',
                'amount'         => 79.00,
                'type'           => 'expense',
                'scheduled_date' => now()->addMonth()->addDays(10)->toDateString(),
                'cat'            => 'Transport',
            ],
        ];

        foreach ($scheduled as $data) {
            ScheduledTransaction::create([
                'user_id'        => $user->id,
                'wallet_id'      => $wallet->id,
                'category_id'    => $categories[$data['cat']]->id,
                'description'    => $data['description'],
                'amount'         => $data['amount'],
                'type'           => $data['type'],
                'scheduled_date' => $data['scheduled_date'],
            ]);
        }
    }
}
