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
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('demo:seed {--email= : Demo user email (default: DEMO_USER_EMAIL env or demo@spendly.app)} {--password= : Demo user password (default: DEMO_USER_PASSWORD env or demo)} {--force : Reset if user already exists without confirmation}')]
#[Description('Create (or reset) a demo user with realistic fixture data')]
class SeedDemoUser extends Command
{
    // ── Budget template — applied to every month ──────────────────────────────
    private const array BUDGET_TEMPLATE = [
        // Revenus
        ['type' => 'income',   'label' => 'Salaire',             'planned' => 2900.00, 'cat' => 'Salaire',             'target_type' => null,       'target_amount' => null,    'notes' => null],
        ['type' => 'income',   'label' => 'Freelance',            'planned' => 400.00,  'cat' => 'Freelance',           'target_type' => null,       'target_amount' => null,    'notes' => 'Variable selon les missions'],
        // Épargne
        ['type' => 'savings',  'label' => 'Livret A',             'planned' => 300.00,  'cat' => 'Épargne',             'target_type' => 'saving',   'target_amount' => 300.00,  'notes' => 'Virement automatique le 2'],
        ['type' => 'savings',  'label' => 'Assurance vie',        'planned' => 100.00,  'cat' => 'Épargne',             'target_type' => 'saving',   'target_amount' => 100.00,  'notes' => 'Versement mensuel'],
        ['type' => 'savings',  'label' => 'Épargne vacances',     'planned' => 150.00,  'cat' => 'Épargne vacances',    'target_type' => 'saving',   'target_amount' => 150.00,  'notes' => 'Objectif 1 800€ — Japon'],
        // Charges fixes
        ['type' => 'bills',    'label' => 'Loyer',                'planned' => 900.00,  'cat' => 'Loyer',               'target_type' => 'spending', 'target_amount' => 900.00,  'notes' => null],
        ['type' => 'bills',    'label' => 'Électricité',          'planned' => 75.00,   'cat' => 'Électricité',         'target_type' => 'spending', 'target_amount' => 110.00,  'notes' => 'Plus élevé nov–fév (≈ 95–110€)'],
        ['type' => 'bills',    'label' => 'Internet',             'planned' => 34.99,   'cat' => 'Internet',            'target_type' => 'spending', 'target_amount' => 34.99,   'notes' => null],
        ['type' => 'bills',    'label' => 'Téléphone',            'planned' => 19.99,   'cat' => 'Téléphone',           'target_type' => 'spending', 'target_amount' => 19.99,   'notes' => null],
        ['type' => 'bills',    'label' => 'Assurance habitation', 'planned' => 22.00,   'cat' => 'Assurance habitation', 'target_type' => 'spending', 'target_amount' => 22.00,   'notes' => null],
        ['type' => 'bills',    'label' => 'Assurance auto',       'planned' => 68.00,   'cat' => 'Assurance auto',      'target_type' => 'spending', 'target_amount' => 68.00,   'notes' => 'Mensualisation annuelle'],
        ['type' => 'bills',    'label' => 'Streaming',            'planned' => 37.97,   'cat' => 'Streaming',           'target_type' => 'spending', 'target_amount' => 37.97,   'notes' => 'Netflix 17.99 + Spotify 10.99 + Disney+ 8.99'],
        ['type' => 'bills',    'label' => 'Salle de sport',       'planned' => 29.90,   'cat' => 'Sport',               'target_type' => 'spending', 'target_amount' => 29.90,   'notes' => null],
        ['type' => 'bills',    'label' => 'Mutuelle',             'planned' => 48.00,   'cat' => 'Santé',               'target_type' => 'spending', 'target_amount' => 48.00,   'notes' => null],
        // Dépenses courantes
        ['type' => 'expenses', 'label' => 'Courses',              'planned' => 420.00,  'cat' => 'Alimentation',        'target_type' => 'spending', 'target_amount' => 450.00,  'notes' => null],
        ['type' => 'expenses', 'label' => 'Transport',            'planned' => 85.00,   'cat' => 'Transport',           'target_type' => 'spending', 'target_amount' => 120.00,  'notes' => null],
        ['type' => 'expenses', 'label' => 'Restaurants',          'planned' => 160.00,  'cat' => 'Restaurants',         'target_type' => 'spending', 'target_amount' => 180.00,  'notes' => 'Déjeuners pro inclus'],
        ['type' => 'expenses', 'label' => 'Loisirs',              'planned' => 80.00,   'cat' => 'Loisirs',             'target_type' => 'spending', 'target_amount' => 100.00,  'notes' => null],
        ['type' => 'expenses', 'label' => 'Santé',                'planned' => 30.00,   'cat' => 'Santé',               'target_type' => null,       'target_amount' => null,    'notes' => null],
        ['type' => 'expenses', 'label' => 'Vêtements',            'planned' => 50.00,   'cat' => 'Vêtements',           'target_type' => 'spending', 'target_amount' => 80.00,   'notes' => null],
        ['type' => 'expenses', 'label' => 'Vacances',             'planned' => 150.00,  'cat' => 'Vacances',            'target_type' => null,       'target_amount' => null,    'notes' => 'Provision mensuelle — pic en juillet'],
        ['type' => 'expenses', 'label' => 'Cadeaux',              'planned' => 0.00,    'cat' => 'Cadeaux',             'target_type' => null,       'target_amount' => null,    'notes' => 'Pic en décembre et occasions'],
        // Dette
        ['type' => 'debt',     'label' => 'Prêt auto',            'planned' => 248.00,  'cat' => 'Remboursement prêt',  'target_type' => 'spending', 'target_amount' => 248.00,  'notes' => 'Fin du prêt dans 14 mois'],
    ];

    // ── Per-month variable transactions ───────────────────────────────────────
    // Index 0 = M-11 (oldest), index 11 = M-0 (current month)
    // Format: [category, amount, day, description, type, tags[]]
    private const array MONTHLY_TX = [
        0 => [ // M-11 — mois classique, rdv dentiste
            ['Freelance',          350.00, 15, 'Facture — Agence Bloom',           'income',  ['freelance']],
            ['Remboursement sécu',  38.50, 25, 'Remb. consultation généraliste',   'income',  ['remboursement']],
            ['Électricité',         72.00, 10, 'EDF',                              'expense', ['logement', 'facture']],
            ['Épargne vacances',   150.00,  2, 'Virement épargne vacances',         'expense', ['épargne']],
            ['Alimentation',        68.40,  4, 'Lidl',                             'expense', ['courses']],
            ['Alimentation',        87.30,  9, 'Carrefour',                        'expense', ['courses']],
            ['Alimentation',        74.20, 16, 'Leclerc',                          'expense', ['courses']],
            ['Alimentation',        59.80, 22, 'Lidl',                             'expense', ['courses']],
            ['Alimentation',        91.10, 28, 'Monoprix',                         'expense', ['courses']],
            ['Transport',           26.40,  2, 'Navigo mensuel',                   'expense', ['transport']],
            ['Transport',           18.50, 14, 'Carburant — Total',                'expense', ['transport']],
            ['Restaurants',         28.50,  7, 'Déjeuner — Le Commerce',           'expense', ['resto']],
            ['Restaurants',         42.00, 13, 'Brasserie Flandrin',               'expense', ['resto']],
            ['Restaurants',         19.90, 21, 'Sushi Yaki',                       'expense', ['resto']],
            ['Restaurants',         35.00, 26, 'Déjeuner collègues',               'expense', ['resto']],
            ['Loisirs',             24.00, 11, 'Cinéma (x2)',                      'expense', ['loisirs']],
            ['Loisirs',             18.00, 23, 'Livre & BD',                       'expense', ['loisirs']],
            ['Santé',               55.00, 18, 'Dentiste — détartrage',            'expense', ['santé']],
        ],
        1 => [ // M-10 — préparation vacances d'été
            ['Freelance',          420.00, 15, 'Facture — Studio Digital',         'income',  ['freelance']],
            ['Remboursement sécu',  44.00, 20, 'Remb. dentiste',                   'income',  ['remboursement']],
            ['Électricité',         68.00, 10, 'EDF',                              'expense', ['logement', 'facture']],
            ['Épargne vacances',   150.00,  2, 'Virement épargne vacances',         'expense', ['épargne']],
            ['Alimentation',        72.50,  4, 'Carrefour',                        'expense', ['courses']],
            ['Alimentation',        83.20, 10, 'Leclerc',                          'expense', ['courses']],
            ['Alimentation',        65.40, 17, 'Lidl',                             'expense', ['courses']],
            ['Alimentation',        78.90, 23, 'Monoprix',                         'expense', ['courses']],
            ['Alimentation',        94.30, 29, 'Carrefour',                        'expense', ['courses']],
            ['Transport',           26.40,  2, 'Navigo mensuel',                   'expense', ['transport']],
            ['Transport',           56.80, 20, 'Carburant — plein avant vacances', 'expense', ['transport']],
            ['Restaurants',         32.00,  5, 'Terrasse Le Marché',               'expense', ['resto']],
            ['Restaurants',         48.50, 12, 'Brasserie Lipp',                   'expense', ['resto']],
            ['Restaurants',         27.00, 19, 'Lunch sushi',                      'expense', ['resto']],
            ['Restaurants',         38.00, 27, 'Dîner entre amis',                 'expense', ['resto']],
            ['Loisirs',             26.00,  8, 'Festival — entrée',                'expense', ['loisirs']],
            ['Loisirs',             35.00, 22, 'Concert jazz',                     'expense', ['loisirs']],
            ['Vêtements',          145.00, 14, 'Affaires vacances — Decathlon',    'expense', ['shopping', 'vacances']],
            ['Vacances',            89.00, 25, 'Valise — Samsonite',               'expense', ['vacances']],
            ['Santé',               45.00, 16, 'Pharmacie — vaccins voyage',       'expense', ['santé']],
        ],
        2 => [ // M-9 — VACANCES été (Côte d'Azur)
            // Pas de Navigo, pas de freelance, peu de courses
            ['Électricité',         58.00, 10, 'EDF',                              'expense', ['logement', 'facture']],
            ['Alimentation',        42.00,  3, 'Lidl — avant départ',              'expense', ['courses']],
            ['Alimentation',        95.00,  6, 'Supermarché local',                'expense', ['courses', 'vacances']],
            ['Alimentation',        72.00, 10, 'Marché provençal',                 'expense', ['courses', 'vacances']],
            ['Alimentation',        38.50, 28, 'Monoprix — retour',                'expense', ['courses']],
            ['Vacances',           760.00,  5, "Airbnb — Côte d'Azur (7 nuits)",   'expense', ['vacances']],
            ['Vacances',           180.00,  6, 'Location vélos & activités',       'expense', ['vacances']],
            ['Vacances',            48.00, 11, 'Parc aquatique',                   'expense', ['vacances', 'loisirs']],
            ['Transport',           95.00,  5, 'Carburant — aller/retour',         'expense', ['transport', 'vacances']],
            ['Transport',           18.50, 14, 'Uber — soirée locale',             'expense', ['transport', 'vtc']],
            ['Restaurants',         65.00,  6, 'Resto plage — poissons grillés',   'expense', ['resto', 'vacances']],
            ['Restaurants',         42.00,  7, 'Pizzeria locale',                  'expense', ['resto', 'vacances']],
            ['Restaurants',         88.00,  9, 'Dîner gastronomique',              'expense', ['resto', 'vacances']],
            ['Restaurants',         35.00, 11, 'Déjeuner terrasse',                'expense', ['resto', 'vacances']],
            ['Restaurants',         55.00, 12, 'Soirée crêperie',                  'expense', ['resto', 'vacances']],
            ['Restaurants',         28.00, 14, 'Retour — aire autoroute',          'expense', ['resto']],
            ['Loisirs',             35.00, 17, 'Cinéma — soir',                    'expense', ['loisirs']],
            ['Loisirs',             28.00, 24, 'Livre de plage',                   'expense', ['loisirs']],
            ['Santé',               18.00, 16, 'Pharmacie — crème solaire',        'expense', ['santé']],
        ],
        3 => [ // M-8 — août, retour de vacances
            ['Électricité',         62.00, 10, 'EDF',                              'expense', ['logement', 'facture']],
            ['Épargne vacances',   150.00,  2, 'Virement épargne vacances',         'expense', ['épargne']],
            ['Alimentation',        76.40,  5, 'Carrefour',                        'expense', ['courses']],
            ['Alimentation',        68.20, 12, 'Leclerc',                          'expense', ['courses']],
            ['Alimentation',        82.50, 19, 'Carrefour',                        'expense', ['courses']],
            ['Alimentation',        71.00, 26, 'Monoprix',                         'expense', ['courses']],
            ['Transport',           26.40,  4, 'Navigo mensuel',                   'expense', ['transport']],
            ['Transport',           44.00, 20, 'Carburant',                        'expense', ['transport']],
            ['Restaurants',         38.00,  5, 'Brasserie — retour vacances',      'expense', ['resto']],
            ['Restaurants',         24.50, 14, 'Déjeuner rapide',                  'expense', ['resto']],
            ['Restaurants',         46.00, 22, 'Apéro dînatoire entre amis',       'expense', ['resto']],
            ['Restaurants',         32.00, 29, 'Bistrot du coin',                  'expense', ['resto']],
            ['Loisirs',             22.00, 10, 'Cinéma',                           'expense', ['loisirs']],
            ['Loisirs',             55.00, 18, 'Concert en plein air',             'expense', ['loisirs']],
            ['Vêtements',           89.00, 23, 'Soldes été — Zara',                'expense', ['shopping']],
            ['Santé',               28.00,  8, 'Pharmacie',                        'expense', ['santé']],
        ],
        4 => [ // M-7 — septembre, RENTRÉE
            ['Freelance',          480.00, 15, 'Facture — StartupX',               'income',  ['freelance']],
            ['Électricité',         74.00, 10, 'EDF',                              'expense', ['logement', 'facture']],
            ['Épargne vacances',   150.00,  2, 'Virement épargne vacances',         'expense', ['épargne']],
            ['Streaming',            8.99,  1, 'Disney+ — nouvel abonnement',      'expense', ['abonnement', 'streaming']],
            ['Alimentation',        94.50,  3, 'Carrefour — grand stock rentrée',  'expense', ['courses']],
            ['Alimentation',        76.20, 10, 'Leclerc',                          'expense', ['courses']],
            ['Alimentation',        68.80, 17, 'Lidl',                             'expense', ['courses']],
            ['Alimentation',        82.40, 24, 'Monoprix',                         'expense', ['courses']],
            ['Alimentation',        57.30, 30, 'Aldi',                             'expense', ['courses']],
            ['Transport',           26.40,  2, 'Navigo mensuel',                   'expense', ['transport']],
            ['Transport',           22.00, 15, 'Carburant',                        'expense', ['transport']],
            ['Transport',           11.90, 22, 'Uber — soirée',                    'expense', ['transport', 'vtc']],
            ['Restaurants',         35.00,  5, 'Repas rentrée collègues',          'expense', ['resto']],
            ['Restaurants',         28.50, 12, 'Déjeuner pro',                     'expense', ['resto']],
            ['Restaurants',         42.00, 19, 'Restaurant japonais',              'expense', ['resto']],
            ['Restaurants',         33.00, 26, 'Brasserie',                        'expense', ['resto']],
            ['Loisirs',             22.00,  8, 'Cinéma',                           'expense', ['loisirs']],
            ['Loisirs',             45.00, 20, 'Match — Tribune Auteuil',          'expense', ['loisirs']],
            ['Vêtements',          124.00,  6, 'Zara — vêtements automne',         'expense', ['shopping']],
            ['Vêtements',           79.90, 14, 'ASOS',                             'expense', ['shopping']],
        ],
        5 => [ // M-6 — octobre, mois normal
            ['Remboursement sécu',  23.00, 20, 'Remb. pharmacie',                  'income',  ['remboursement']],
            ['Électricité',         82.00, 10, 'EDF',                              'expense', ['logement', 'facture']],
            ['Épargne vacances',   150.00,  2, 'Virement épargne vacances',         'expense', ['épargne']],
            ['Streaming',            8.99,  1, 'Disney+',                          'expense', ['abonnement', 'streaming']],
            ['Alimentation',        71.30,  4, 'Lidl',                             'expense', ['courses']],
            ['Alimentation',        88.60,  9, 'Carrefour',                        'expense', ['courses']],
            ['Alimentation',        66.40, 16, 'Leclerc',                          'expense', ['courses']],
            ['Alimentation',        79.20, 23, 'Monoprix',                         'expense', ['courses']],
            ['Alimentation',        93.50, 30, 'Carrefour',                        'expense', ['courses']],
            ['Transport',           26.40,  2, 'Navigo mensuel',                   'expense', ['transport']],
            ['Transport',           19.80, 16, 'Carburant',                        'expense', ['transport']],
            ['Transport',           13.50, 25, 'Uber',                             'expense', ['transport', 'vtc']],
            ['Restaurants',         31.00,  7, 'Bistrot du coin',                  'expense', ['resto']],
            ['Restaurants',         44.50, 15, 'Déjeuner pro + collègue',          'expense', ['resto']],
            ['Restaurants',         26.00, 22, 'Lunch sushi',                      'expense', ['resto']],
            ['Restaurants',         39.00, 28, 'Soirée Halloween — bar',           'expense', ['resto']],
            ['Loisirs',             32.00, 12, 'Halloween — déco & bonbons',       'expense', ['loisirs']],
            ['Loisirs',             22.00, 19, 'Cinéma',                           'expense', ['loisirs']],
            ['Loisirs',             38.00, 26, 'Bowling + soirée',                 'expense', ['loisirs']],
            ['Santé',               28.00,  8, 'Pharmacie — rhume',                'expense', ['santé']],
        ],
        6 => [ // M-5 — novembre, BLACK FRIDAY
            ['Remboursement sécu',  30.00, 18, 'Remb. médecin',                    'income',  ['remboursement']],
            ['Électricité',         95.00, 10, 'EDF',                              'expense', ['logement', 'facture']],
            ['Épargne vacances',   150.00,  2, 'Virement épargne vacances',         'expense', ['épargne']],
            ['Streaming',            8.99,  1, 'Disney+',                          'expense', ['abonnement', 'streaming']],
            ['Alimentation',        74.80,  4, 'Carrefour',                        'expense', ['courses']],
            ['Alimentation',        91.20,  9, 'Leclerc',                          'expense', ['courses']],
            ['Alimentation',        68.50, 16, 'Lidl',                             'expense', ['courses']],
            ['Alimentation',        85.30, 22, 'Monoprix',                         'expense', ['courses']],
            ['Alimentation',        77.40, 28, 'Carrefour',                        'expense', ['courses']],
            ['Transport',           26.40,  2, 'Navigo mensuel',                   'expense', ['transport']],
            ['Transport',           21.60, 17, 'Carburant',                        'expense', ['transport']],
            ['Restaurants',         29.00,  6, 'Lunch',                            'expense', ['resto']],
            ['Restaurants',         52.00, 14, "Dîner d'anniversaire",             'expense', ['resto']],
            ['Restaurants',         35.00, 21, 'Brasserie',                        'expense', ['resto']],
            ['Loisirs',             22.00, 10, 'Cinéma',                           'expense', ['loisirs']],
            ['High-tech',          149.00, 25, 'Sony WH-1000XM5 — Black Friday',   'expense', ['high-tech']],
            ['Cadeaux',             85.00, 29, 'Cadeaux Noël — premiers achats',   'expense', ['cadeaux']],
            ['Santé',               42.00,  5, 'Médecin + ordonnance',             'expense', ['santé']],
        ],
        7 => [ // M-4 — décembre, NOËL
            ['Électricité',        108.00, 10, 'EDF — hiver',                      'expense', ['logement', 'facture']],
            ['Épargne vacances',   150.00,  2, 'Virement épargne vacances',         'expense', ['épargne']],
            ['Streaming',            8.99,  1, 'Disney+',                          'expense', ['abonnement', 'streaming']],
            ['Alimentation',        82.40,  4, 'Monoprix',                         'expense', ['courses']],
            ['Alimentation',       104.50,  9, 'Carrefour — fêtes',                'expense', ['courses']],
            ['Alimentation',        76.80, 16, 'Leclerc',                          'expense', ['courses']],
            ['Alimentation',       145.00, 22, 'Grande épicerie fine — réveillon', 'expense', ['courses', 'fêtes']],
            ['Alimentation',        68.20, 28, 'Lidl — après fêtes',               'expense', ['courses']],
            ['Transport',           26.40,  2, 'Navigo mensuel',                   'expense', ['transport']],
            ['Transport',           44.00, 22, 'Carburant — trajet famille',       'expense', ['transport']],
            ['Transport',           18.50, 26, 'Uber — retour réveillon',          'expense', ['transport', 'vtc']],
            ['Restaurants',         35.00,  5, 'Déjeuner équipe',                  'expense', ['resto']],
            ['Restaurants',         48.00, 12, 'Brasserie — dîner',                'expense', ['resto']],
            ['Restaurants',         95.00, 20, 'Repas de Noël entreprise',         'expense', ['resto', 'fêtes']],
            ['Restaurants',        120.00, 31, 'Réveillon du Nouvel An',           'expense', ['resto', 'fêtes']],
            ['Cadeaux',            220.00, 10, 'Cadeaux famille — Amazon',         'expense', ['cadeaux']],
            ['Cadeaux',             95.00, 15, 'Cadeaux amis',                     'expense', ['cadeaux']],
            ['Cadeaux',             45.00, 20, 'Emballages & décorations',         'expense', ['cadeaux']],
            ['Loisirs',             65.00, 13, 'Marché de Noël',                   'expense', ['loisirs', 'fêtes']],
            ['Loisirs',             22.00,  8, 'Cinéma',                           'expense', ['loisirs']],
        ],
        8 => [ // M-3 — janvier, SOLDES
            ['Freelance',          390.00, 15, 'Facture — Agence Bloom',           'income',  ['freelance']],
            ['Remboursement sécu',  46.00, 22, 'Remb. dentiste',                   'income',  ['remboursement']],
            ['Électricité',        104.00, 10, 'EDF — hiver',                      'expense', ['logement', 'facture']],
            ['Épargne vacances',   150.00,  2, 'Virement épargne vacances',         'expense', ['épargne']],
            ['Streaming',            8.99,  1, 'Disney+',                          'expense', ['abonnement', 'streaming']],
            ['Alimentation',        66.40,  4, 'Lidl',                             'expense', ['courses']],
            ['Alimentation',        88.50,  9, 'Carrefour',                        'expense', ['courses']],
            ['Alimentation',        72.20, 16, 'Leclerc',                          'expense', ['courses']],
            ['Alimentation',        79.80, 23, 'Monoprix',                         'expense', ['courses']],
            ['Alimentation',        61.40, 30, 'Aldi',                             'expense', ['courses']],
            ['Transport',           26.40,  2, 'Navigo mensuel',                   'expense', ['transport']],
            ['Transport',           18.00, 18, 'Carburant',                        'expense', ['transport']],
            ['Restaurants',         42.00,  3, 'Bonne année — dîner amis',         'expense', ['resto']],
            ['Restaurants',         28.00, 12, 'Déjeuner rapide',                  'expense', ['resto']],
            ['Restaurants',         35.00, 19, 'Bistrot',                          'expense', ['resto']],
            ['Restaurants',         48.00, 27, 'Galette des rois — resto',         'expense', ['resto']],
            ['Vêtements',          180.00,  8, 'Soldes hiver — Zara, H&M, ASOS',  'expense', ['shopping', 'soldes']],
            ['Loisirs',             22.00, 11, 'Cinéma',                           'expense', ['loisirs']],
            ['Loisirs',             35.00, 25, "Exposition — Musée d'Orsay",       'expense', ['loisirs']],
            ['Santé',               32.00, 14, 'Pharmacie — vitamine D hiver',     'expense', ['santé']],
        ],
        9 => [ // M-2 — février, SAINT-VALENTIN
            ['Remboursement sécu',  54.00, 25, 'Remb. ophtalmo',                   'income',  ['remboursement']],
            ['Électricité',         98.00, 10, 'EDF — fin hiver',                  'expense', ['logement', 'facture']],
            ['Épargne vacances',   150.00,  2, 'Virement épargne vacances',         'expense', ['épargne']],
            ['Streaming',            8.99,  1, 'Disney+',                          'expense', ['abonnement', 'streaming']],
            ['Alimentation',        69.20,  4, 'Lidl',                             'expense', ['courses']],
            ['Alimentation',        84.40, 10, 'Carrefour',                        'expense', ['courses']],
            ['Alimentation',        71.80, 17, 'Leclerc',                          'expense', ['courses']],
            ['Alimentation',        88.50, 24, 'Monoprix',                         'expense', ['courses']],
            ['Transport',           26.40,  2, 'Navigo mensuel',                   'expense', ['transport']],
            ['Transport',           16.50, 20, 'Carburant',                        'expense', ['transport']],
            ['Transport',           14.80, 14, 'Uber — Saint-Valentin',            'expense', ['transport', 'vtc']],
            ['Restaurants',         31.00,  6, 'Déjeuner — brasserie',             'expense', ['resto']],
            ['Restaurants',         95.00, 14, 'Dîner Saint-Valentin',             'expense', ['resto']],
            ['Restaurants',         29.50, 20, 'Lunch sushi',                      'expense', ['resto']],
            ['Loisirs',             22.00,  8, 'Cinéma',                           'expense', ['loisirs']],
            ['Loisirs',             48.00, 15, 'Week-end spa',                     'expense', ['loisirs']],
            ['Santé',               60.00, 11, 'Ophtalmologue',                    'expense', ['santé']],
            ['Cadeaux',             55.00, 13, 'Fleurs + chocolats',               'expense', ['cadeaux']],
        ],
        10 => [ // M-1 — mars, RÉPARATION VOITURE
            ['Freelance',          520.00, 15, 'Facture — StartupX',               'income',  ['freelance']],
            ['Remboursement sécu',  27.00, 28, 'Remb. pharmacie',                  'income',  ['remboursement']],
            ['Électricité',         78.00, 10, 'EDF',                              'expense', ['logement', 'facture']],
            ['Épargne vacances',   150.00,  2, 'Virement épargne vacances',         'expense', ['épargne']],
            ['Streaming',            8.99,  1, 'Disney+',                          'expense', ['abonnement', 'streaming']],
            ['Alimentation',        73.50,  4, 'Carrefour',                        'expense', ['courses']],
            ['Alimentation',        86.20,  9, 'Leclerc',                          'expense', ['courses']],
            ['Alimentation',        69.80, 16, 'Lidl',                             'expense', ['courses']],
            ['Alimentation',        91.40, 22, 'Monoprix',                         'expense', ['courses']],
            ['Alimentation',        64.20, 29, 'Aldi',                             'expense', ['courses']],
            ['Transport',           26.40,  2, 'Navigo mensuel',                   'expense', ['transport']],
            ['Transport',           22.00, 10, 'Carburant',                        'expense', ['transport']],
            ['Transport',          340.00,  8, 'Garage — courroie de distribution', 'expense', ['transport', 'voiture']],
            ['Transport',           79.00,  8, 'Contrôle technique',               'expense', ['transport', 'voiture']],
            ['Restaurants',         33.00,  6, 'Déjeuner pro',                     'expense', ['resto']],
            ['Restaurants',         45.00, 13, 'Brasserie — terrasse printemps',   'expense', ['resto']],
            ['Restaurants',         28.50, 20, 'Lunch sushi',                      'expense', ['resto']],
            ['Restaurants',         52.00, 27, 'Dîner entre amis',                 'expense', ['resto']],
            ['Loisirs',             22.00,  8, 'Cinéma',                           'expense', ['loisirs']],
            ['Loisirs',             55.00, 21, 'Spectacle — théâtre',              'expense', ['loisirs']],
            ['Santé',               32.00, 17, 'Pharmacie',                        'expense', ['santé']],
        ],
        11 => [ // M-0 — avril, mois en cours (partiel)
            ['Électricité',         72.00,  5, 'EDF',                              'expense', ['logement', 'facture']],
            ['Épargne vacances',   150.00,  2, 'Virement épargne vacances',         'expense', ['épargne']],
            ['Streaming',            8.99,  1, 'Disney+',                          'expense', ['abonnement', 'streaming']],
            ['Alimentation',        71.80,  3, 'Lidl',                             'expense', ['courses']],
            ['Alimentation',        89.20,  7, 'Carrefour',                        'expense', ['courses']],
            ['Transport',           26.40,  2, 'Navigo mensuel',                   'expense', ['transport']],
            ['Restaurants',         28.50,  3, 'Déjeuner',                         'expense', ['resto']],
            ['Restaurants',         34.00,  7, 'Brasserie',                        'expense', ['resto']],
        ],
    ];

    public function handle(BudgetService $budgetService, WalletTransferService $transferService): int
    {
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

        $user = User::create([
            'name' => 'Léa Martin',
            'email' => $email,
            'email_verified_at' => now(),
            'password' => bcrypt($password),
            'plan' => PlanType::Pro,
            'is_demo' => true,
            'locale' => 'fr',
            'currency' => 'EUR',
        ]);
        $user->assignRole('ROLE_USER');

        $wallet = $this->createWallet($user, 'Compte courant', 1850.00, true);
        $livretA = $this->createWallet($user, 'Livret A', 4200.00);
        $assuranceVie = $this->createWallet($user, 'Assurance vie', 8500.00);

        $categories = $this->createCategories($user, $wallet);

        // 12 months: index 0 = M-11 (oldest), index 11 = M-0 (current)
        $months = collect(range(11, 0))->map(fn ($n) => now()->subMonths($n)->startOfMonth())->all();

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
                'description' => 'Versement assurance vie',
                'date' => Carbon::create($month->year, $month->month, 2)->toDateString(),
            ]);
        }

        foreach ($months as $i => $month) {
            $budget = $budgetService->getOrCreate($wallet, $month);
            $this->createBudgetItems($budget, $categories, $i > 0);
            $this->createMonthTransactions($user, $wallet, $month, $categories, $i);
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
            ['Email',           $email],
            ['Password',        $password],
            ['Plan',            'Pro'],
            ['Months of data',  '12'],
            ['Wallets',         '3 (Compte courant, Livret A, Assurance vie)'],
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
            // Revenus
            'Salaire', 'Freelance', 'Remboursement sécu',
            // Épargne
            'Épargne', 'Épargne vacances',
            // Charges fixes
            'Loyer', 'Électricité', 'Internet', 'Téléphone',
            'Assurance habitation', 'Assurance auto', 'Streaming', 'Sport',
            // Dépenses courantes
            'Alimentation', 'Transport', 'Restaurants',
            // Loisirs & occasions
            'Loisirs', 'Vacances', 'Cadeaux', 'High-tech',
            // Santé & perso
            'Santé', 'Vêtements',
            // Dette
            'Remboursement prêt',
        ];

        $map = [];
        foreach ($names as $name) {
            $map[$name] = Category::create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'name' => $name,
            ]);
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

    private function createMonthTransactions(
        User $user,
        Wallet $wallet,
        Carbon $month,
        array $categories,
        int $monthIndex,
    ): void {
        $y = $month->year;
        $m = $month->month;

        $createTx = function (string $cat, float $amount, int $day, string $desc, string $type, array $tags) use ($user, $wallet, $categories, $y, $m): void {
            Transaction::create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'category_id' => $categories[$cat]->id,
                'type' => $type,
                'amount' => round($amount, 2),
                'description' => $desc,
                'date' => Carbon::create($y, $m, min($day, Carbon::create($y, $m, 1)->endOfMonth()->day))->toDateString(),
                'tags' => $tags ?: null,
            ]);
        };

        // Fixed transactions — identical every month
        foreach ([
            ['Salaire',            2900.00, 27, 'Virement salaire',      'income',  ['salaire']],
            ['Loyer',               900.00,  1, 'Loyer',                 'expense', ['logement']],
            ['Internet',             34.99,  5, 'Freebox',               'expense', ['abonnement']],
            ['Téléphone',            19.99,  5, 'Forfait mobile',        'expense', ['abonnement']],
            ['Assurance habitation',  22.00,  8, 'Assurance habitation', 'expense', ['logement']],
            ['Assurance auto',        68.00,  8, 'Assurance auto',       'expense', ['assurance']],
            ['Streaming',            17.99,  8, 'Netflix',               'expense', ['abonnement', 'streaming']],
            ['Streaming',            10.99,  8, 'Spotify',               'expense', ['abonnement', 'streaming']],
            ['Sport',                29.90,  1, 'Salle de sport',        'expense', ['sport']],
            ['Santé',                48.00,  5, 'Mutuelle',              'expense', ['santé']],
            ['Remboursement prêt',  248.00,  3, 'Prêt auto',             'expense', ['crédit']],
        ] as [$cat, $amount, $day, $desc, $type, $tags]) {
            $createTx($cat, $amount, $day, $desc, $type, $tags);
        }

        // Variable transactions — per month
        foreach (self::MONTHLY_TX[$monthIndex] ?? [] as [$cat, $amount, $day, $desc, $type, $tags]) {
            $createTx($cat, $amount, $day, $desc, $type, $tags);
        }
    }

    private function createGoals(User $user, Wallet $wallet): void
    {
        $goals = [
            ['name' => 'Voyage au Japon',    'target_amount' => 4500.00,  'saved_amount' => 1800.00, 'deadline' => now()->addMonths(8)->toDateString(),  'color' => '#6366f1'],
            ['name' => "Fonds d'urgence",    'target_amount' => 6000.00,  'saved_amount' => 4200.00, 'deadline' => null,                                 'color' => '#22c55e'],
            ['name' => 'MacBook Pro',        'target_amount' => 2400.00,  'saved_amount' => 960.00,  'deadline' => now()->addMonths(5)->toDateString(),  'color' => '#3b82f6'],
            ['name' => 'Apport immobilier',  'target_amount' => 30000.00, 'saved_amount' => 8500.00, 'deadline' => now()->addMonths(36)->toDateString(), 'color' => '#f59e0b'],
            ['name' => 'Nouveau vélo',       'target_amount' => 900.00,   'saved_amount' => 900.00,  'deadline' => null,                                 'color' => '#10b981'],
            ['name' => 'Permis bateau',      'target_amount' => 1200.00,  'saved_amount' => 350.00,  'deadline' => now()->addMonths(12)->toDateString(), 'color' => '#0ea5e9'],
        ];

        foreach ($goals as $data) {
            Goal::create(['user_id' => $user->id, 'wallet_id' => $wallet->id, 'category_id' => null, ...$data]);
        }
    }

    private function createRecurringTransactions(User $user, Wallet $wallet, array $categories): void
    {
        $recurring = [
            ['description' => 'Netflix',                   'amount' => 17.99,  'day_of_month' => 8, 'type' => 'expense', 'active' => true,  'cat' => 'Streaming'],
            ['description' => 'Spotify',                   'amount' => 10.99,  'day_of_month' => 8, 'type' => 'expense', 'active' => true,  'cat' => 'Streaming'],
            ['description' => 'Disney+',                   'amount' => 8.99,  'day_of_month' => 1, 'type' => 'expense', 'active' => true,  'cat' => 'Streaming'],
            ['description' => 'Salle de sport',            'amount' => 29.90,  'day_of_month' => 1, 'type' => 'expense', 'active' => true,  'cat' => 'Sport'],
            ['description' => 'Mutuelle',                  'amount' => 48.00,  'day_of_month' => 5, 'type' => 'expense', 'active' => true,  'cat' => 'Santé'],
            ['description' => 'Salaire',                   'amount' => 2900.00, 'day_of_month' => 27, 'type' => 'income',  'active' => true,  'cat' => 'Salaire'],
            ['description' => 'Prêt auto',                 'amount' => 248.00, 'day_of_month' => 3, 'type' => 'expense', 'active' => true,  'cat' => 'Remboursement prêt'],
            ['description' => 'Ancien abonnement presse',  'amount' => 12.99, 'day_of_month' => 15, 'type' => 'expense', 'active' => false, 'cat' => 'Loisirs'],
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
            ['description' => "Billet d'avion Tokyo",   'amount' => 780.00,  'type' => 'expense', 'scheduled_date' => now()->addWeeks(3)->toDateString(),                                'cat' => 'Vacances'],
            ['description' => 'Prime annuelle',          'amount' => 1800.00, 'type' => 'income',  'scheduled_date' => now()->addMonths(2)->startOfMonth()->addDays(14)->toDateString(),  'cat' => 'Salaire'],
            ['description' => 'Taxe foncière',           'amount' => 420.00,  'type' => 'expense', 'scheduled_date' => now()->addMonths(3)->addDays(5)->toDateString(),                   'cat' => 'Loyer'],
            ['description' => 'Assurance auto annuelle', 'amount' => 816.00,  'type' => 'expense', 'scheduled_date' => now()->addMonths(5)->startOfMonth()->toDateString(),               'cat' => 'Assurance auto'],
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
            ['type' => 'income',   'label' => 'Salaire',             'planned_amount' => 2900.00],
            ['type' => 'income',   'label' => 'Freelance',            'planned_amount' => 400.00],
            ['type' => 'savings',  'label' => 'Livret A',             'planned_amount' => 300.00],
            ['type' => 'savings',  'label' => 'Assurance vie',        'planned_amount' => 100.00],
            ['type' => 'savings',  'label' => 'Épargne vacances',     'planned_amount' => 150.00],
            ['type' => 'bills',    'label' => 'Loyer',                'planned_amount' => 900.00],
            ['type' => 'bills',    'label' => 'Électricité',          'planned_amount' => 75.00],
            ['type' => 'bills',    'label' => 'Internet',             'planned_amount' => 34.99],
            ['type' => 'bills',    'label' => 'Téléphone',            'planned_amount' => 19.99],
            ['type' => 'bills',    'label' => 'Assurance habitation', 'planned_amount' => 22.00],
            ['type' => 'bills',    'label' => 'Assurance auto',       'planned_amount' => 68.00],
            ['type' => 'bills',    'label' => 'Streaming',            'planned_amount' => 37.97],
            ['type' => 'bills',    'label' => 'Salle de sport',       'planned_amount' => 29.90],
            ['type' => 'bills',    'label' => 'Mutuelle',             'planned_amount' => 48.00],
            ['type' => 'expenses', 'label' => 'Courses',              'planned_amount' => 420.00],
            ['type' => 'expenses', 'label' => 'Transport',            'planned_amount' => 85.00],
            ['type' => 'expenses', 'label' => 'Restaurants',          'planned_amount' => 160.00],
            ['type' => 'expenses', 'label' => 'Loisirs',              'planned_amount' => 80.00],
            ['type' => 'expenses', 'label' => 'Santé',                'planned_amount' => 30.00],
            ['type' => 'expenses', 'label' => 'Vêtements',            'planned_amount' => 50.00],
            ['type' => 'debt',     'label' => 'Prêt auto',            'planned_amount' => 248.00],
        ];

        foreach ($presets as $position => $preset) {
            BudgetPreset::create(['user_id' => $user->id, 'position' => $position, ...$preset]);
        }
    }

    private function createCategorizationRules(User $user, array $categories): void
    {
        $rules = [
            ['pattern' => 'lidl|carrefour|leclerc|monoprix|aldi|intermarché|super u|franprix|casino', 'cat' => 'Alimentation',       'hits' => 28],
            ['pattern' => 'netflix',                                                                   'cat' => 'Streaming',          'hits' => 12],
            ['pattern' => 'spotify',                                                                   'cat' => 'Streaming',          'hits' => 12],
            ['pattern' => 'disney',                                                                    'cat' => 'Streaming',          'hits' => 8],
            ['pattern' => 'freebox|sfr|bouygues|orange',                                               'cat' => 'Internet',           'hits' => 12],
            ['pattern' => 'sncf|ratp|navigo|uber|bolt|blablacar|ouigo',                               'cat' => 'Transport',          'hits' => 20],
            ['pattern' => 'total|bp|shell|esso|carburant',                                             'cat' => 'Transport',          'hits' => 12],
            ['pattern' => 'pharmacie|médecin|docteur|dentiste|opticien|clinique',                     'cat' => 'Santé',              'hits' => 9],
            ['pattern' => 'edf|engie|totalenergies',                                                   'cat' => 'Électricité',        'hits' => 12],
            ['pattern' => 'salaire|virement employeur',                                                'cat' => 'Salaire',            'hits' => 12],
            ['pattern' => 'amazon|fnac|darty|boulanger|cdiscount',                                    'cat' => 'High-tech',          'hits' => 6],
            ['pattern' => 'zara|h&m|asos|decathlon|nike|adidas|uniqlo',                              'cat' => 'Vêtements',          'hits' => 8],
            ['pattern' => 'airbnb|booking|hotels|accor|ibis',                                         'cat' => 'Vacances',           'hits' => 4],
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
