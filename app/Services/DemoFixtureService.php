<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\WalletMode;
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
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class DemoFixtureService
{
    private const array BUDGET_TEMPLATE = [
        ['type' => 'income',   'label' => 'Salaire',             'planned' => 2900.00, 'cat' => 'Salaire',              'target_type' => null,       'target_amount' => null,    'notes' => null],
        ['type' => 'income',   'label' => 'Freelance',            'planned' => 400.00,  'cat' => 'Freelance',            'target_type' => null,       'target_amount' => null,    'notes' => 'Variable selon les missions'],
        ['type' => 'savings',  'label' => 'Livret A',             'planned' => 300.00,  'cat' => 'Épargne',              'target_type' => 'saving',   'target_amount' => 300.00,  'notes' => 'Virement automatique le 2'],
        ['type' => 'savings',  'label' => 'Assurance vie',        'planned' => 100.00,  'cat' => 'Épargne',              'target_type' => 'saving',   'target_amount' => 100.00,  'notes' => 'Versement mensuel'],
        ['type' => 'savings',  'label' => 'Épargne vacances',     'planned' => 150.00,  'cat' => 'Épargne vacances',     'target_type' => 'saving',   'target_amount' => 150.00,  'notes' => 'Objectif 1 800€ — Japon'],
        ['type' => 'bills',    'label' => 'Loyer',                'planned' => 900.00,  'cat' => 'Loyer',                'target_type' => 'spending', 'target_amount' => 900.00,  'notes' => null],
        ['type' => 'bills',    'label' => 'Électricité',          'planned' => 75.00,   'cat' => 'Électricité',          'target_type' => 'spending', 'target_amount' => 110.00,  'notes' => 'Plus élevé nov–fév (≈ 95–110€)'],
        ['type' => 'bills',    'label' => 'Internet',             'planned' => 34.99,   'cat' => 'Internet',             'target_type' => 'spending', 'target_amount' => 34.99,   'notes' => null],
        ['type' => 'bills',    'label' => 'Téléphone',            'planned' => 19.99,   'cat' => 'Téléphone',            'target_type' => 'spending', 'target_amount' => 19.99,   'notes' => null],
        ['type' => 'bills',    'label' => 'Assurance habitation', 'planned' => 22.00,   'cat' => 'Assurance habitation', 'target_type' => 'spending', 'target_amount' => 22.00,   'notes' => null],
        ['type' => 'bills',    'label' => 'Assurance auto',       'planned' => 68.00,   'cat' => 'Assurance auto',       'target_type' => 'spending', 'target_amount' => 68.00,   'notes' => 'Mensualisation annuelle'],
        ['type' => 'bills',    'label' => 'Streaming',            'planned' => 37.97,   'cat' => 'Streaming',            'target_type' => 'spending', 'target_amount' => 37.97,   'notes' => 'Netflix 17.99 + Spotify 10.99 + Disney+ 8.99'],
        ['type' => 'bills',    'label' => 'Salle de sport',       'planned' => 29.90,   'cat' => 'Sport',                'target_type' => 'spending', 'target_amount' => 29.90,   'notes' => null],
        ['type' => 'bills',    'label' => 'Mutuelle',             'planned' => 48.00,   'cat' => 'Santé',                'target_type' => 'spending', 'target_amount' => 48.00,   'notes' => null],
        ['type' => 'expenses', 'label' => 'Courses',              'planned' => 420.00,  'cat' => 'Alimentation',         'target_type' => 'spending', 'target_amount' => 450.00,  'notes' => null],
        ['type' => 'expenses', 'label' => 'Transport',            'planned' => 85.00,   'cat' => 'Transport',            'target_type' => 'spending', 'target_amount' => 120.00,  'notes' => null],
        ['type' => 'expenses', 'label' => 'Restaurants',          'planned' => 160.00,  'cat' => 'Restaurants',          'target_type' => 'spending', 'target_amount' => 180.00,  'notes' => 'Déjeuners pro inclus'],
        ['type' => 'expenses', 'label' => 'Loisirs',              'planned' => 80.00,   'cat' => 'Loisirs',              'target_type' => 'spending', 'target_amount' => 100.00,  'notes' => null],
        ['type' => 'expenses', 'label' => 'Santé',                'planned' => 30.00,   'cat' => 'Santé',                'target_type' => null,       'target_amount' => null,    'notes' => null],
        ['type' => 'expenses', 'label' => 'Vêtements',            'planned' => 50.00,   'cat' => 'Vêtements',            'target_type' => 'spending', 'target_amount' => 80.00,   'notes' => null],
        ['type' => 'expenses', 'label' => 'Vacances',             'planned' => 150.00,  'cat' => 'Vacances',             'target_type' => null,       'target_amount' => null,    'notes' => 'Provision mensuelle — pic en juillet'],
        ['type' => 'expenses', 'label' => 'Cadeaux',              'planned' => 0.00,    'cat' => 'Cadeaux',              'target_type' => null,       'target_amount' => null,    'notes' => 'Pic en décembre et occasions'],
        ['type' => 'debt',     'label' => 'Prêt auto',            'planned' => 248.00,  'cat' => 'Remboursement prêt',   'target_type' => 'spending', 'target_amount' => 248.00,  'notes' => 'Fin du prêt dans 14 mois'],
    ];

    private const array MONTHLY_TX = [
        0 => [
            ['Freelance',          350.00, 15, 'Facture — Agence Bloom',           'income',  ['freelance']],
            ['Remboursement sécu',  38.50, 25, 'Remb. consultation généraliste',   'income',  ['remboursement']],
            ['Électricité',         72.00, 10, 'EDF',                              'expense', ['logement', 'facture']],
            ['Épargne vacances',   150.00,  2, 'Virement épargne vacances',        'expense', ['épargne']],
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
        1 => [
            ['Freelance',          420.00, 15, 'Facture — Studio Digital',         'income',  ['freelance']],
            ['Remboursement sécu',  44.00, 20, 'Remb. dentiste',                   'income',  ['remboursement']],
            ['Électricité',         68.00, 10, 'EDF',                              'expense', ['logement', 'facture']],
            ['Épargne vacances',   150.00,  2, 'Virement épargne vacances',        'expense', ['épargne']],
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
        2 => [
            ['Électricité',         58.00, 10, 'EDF',                              'expense', ['logement', 'facture']],
            ['Alimentation',        42.00,  3, 'Lidl — avant départ',              'expense', ['courses']],
            ['Alimentation',        95.00,  6, 'Supermarché local',                'expense', ['courses', 'vacances']],
            ['Alimentation',        72.00, 10, 'Marché provençal',                 'expense', ['courses', 'vacances']],
            ['Alimentation',        38.50, 28, 'Monoprix — retour',                'expense', ['courses']],
            ['Vacances',           760.00,  5, "Airbnb — Côte d'Azur (7 nuits)",  'expense', ['vacances']],
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
        3 => [
            ['Électricité',         62.00, 10, 'EDF',                              'expense', ['logement', 'facture']],
            ['Épargne vacances',   150.00,  2, 'Virement épargne vacances',        'expense', ['épargne']],
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
        4 => [
            ['Freelance',          480.00, 15, 'Facture — StartupX',               'income',  ['freelance']],
            ['Électricité',         74.00, 10, 'EDF',                              'expense', ['logement', 'facture']],
            ['Épargne vacances',   150.00,  2, 'Virement épargne vacances',        'expense', ['épargne']],
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
        5 => [
            ['Remboursement sécu',  23.00, 20, 'Remb. pharmacie',                  'income',  ['remboursement']],
            ['Électricité',         82.00, 10, 'EDF',                              'expense', ['logement', 'facture']],
            ['Épargne vacances',   150.00,  2, 'Virement épargne vacances',        'expense', ['épargne']],
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
        6 => [
            ['Remboursement sécu',  30.00, 18, 'Remb. médecin',                    'income',  ['remboursement']],
            ['Électricité',         95.00, 10, 'EDF',                              'expense', ['logement', 'facture']],
            ['Épargne vacances',   150.00,  2, 'Virement épargne vacances',        'expense', ['épargne']],
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
        7 => [
            ['Électricité',        108.00, 10, 'EDF — hiver',                      'expense', ['logement', 'facture']],
            ['Épargne vacances',   150.00,  2, 'Virement épargne vacances',        'expense', ['épargne']],
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
        8 => [
            ['Freelance',          390.00, 15, 'Facture — Agence Bloom',           'income',  ['freelance']],
            ['Remboursement sécu',  46.00, 22, 'Remb. dentiste',                   'income',  ['remboursement']],
            ['Électricité',        104.00, 10, 'EDF — hiver',                      'expense', ['logement', 'facture']],
            ['Épargne vacances',   150.00,  2, 'Virement épargne vacances',        'expense', ['épargne']],
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
        9 => [
            ['Remboursement sécu',  54.00, 25, 'Remb. ophtalmo',                   'income',  ['remboursement']],
            ['Électricité',         98.00, 10, 'EDF — fin hiver',                  'expense', ['logement', 'facture']],
            ['Épargne vacances',   150.00,  2, 'Virement épargne vacances',        'expense', ['épargne']],
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
        10 => [
            ['Freelance',          520.00, 15, 'Facture — StartupX',               'income',  ['freelance']],
            ['Remboursement sécu',  27.00, 28, 'Remb. pharmacie',                  'income',  ['remboursement']],
            ['Électricité',         78.00, 10, 'EDF',                              'expense', ['logement', 'facture']],
            ['Épargne vacances',   150.00,  2, 'Virement épargne vacances',        'expense', ['épargne']],
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
        11 => [
            ['Électricité',         72.00,  5, 'EDF',                              'expense', ['logement', 'facture']],
            ['Épargne vacances',   150.00,  2, 'Virement épargne vacances',        'expense', ['épargne']],
            ['Streaming',            8.99,  1, 'Disney+',                          'expense', ['abonnement', 'streaming']],
            ['Alimentation',        71.80,  3, 'Lidl',                             'expense', ['courses']],
            ['Alimentation',        89.20,  7, 'Carrefour',                        'expense', ['courses']],
            ['Transport',           26.40,  2, 'Navigo mensuel',                   'expense', ['transport']],
            ['Restaurants',         28.50,  3, 'Déjeuner',                         'expense', ['resto']],
            ['Restaurants',         34.00,  7, 'Brasserie',                        'expense', ['resto']],
        ],
    ];

    public function __construct(
        private readonly BudgetService $budgetService,
        private readonly WalletTransferService $transferService,
    ) {}

    public function clear(User $user): void
    {
        // FK-safe deletion order:
        // 1. Rules referencing categories (cascadeOnDelete on category_id)
        $user->categorizationRules()->delete();
        // 2. Recurring/scheduled referencing wallets
        $user->recurringTransactions()->delete();
        $user->scheduledTransactions()->delete();
        // 3. Goals (wallet_id is nullOnDelete, user_id cascades)
        $user->goals()->delete();
        // 4. Transactions (wallet_id is nullOnDelete — must delete explicitly)
        $user->transactions()->delete();
        // 5. Wallets cascade to: budgets → budget_items, wallet_members
        $user->wallets()->each(fn (Model $wallet) => $wallet->delete());
        // 6. Categories (transactions already gone)
        $user->categories()->delete();
        // 7. Budget presets (user-only FK)
        $user->budgetPresets()->delete();
    }

    public function seed(User $user): void
    {
        $wallet = $this->createWallet($user, 'Compte courant', 1850.00);
        $livretA = $this->createWallet($user, 'Livret A', 4200.00, WalletMode::Simple);
        $assuranceVie = $this->createWallet($user, 'Assurance vie', 8500.00, WalletMode::Simple);
        $argent = $this->createWallet($user, 'Argent de poche', 120.00, WalletMode::Simple);

        $categories = $this->createCategories($user, $wallet);

        $months = collect(range(11, 0))->map(fn ($offset) => now()->subMonths($offset)->startOfMonth())->all();

        foreach ($months as $month) {
            $this->transferService->create($user, [
                'from_wallet_id' => $wallet->id,
                'to_wallet_id' => $livretA->id,
                'amount' => 300.00,
                'description' => 'Virement Livret A',
                'date' => Carbon::create($month->year, $month->month, 2)->toDateString(),
            ]);
            $this->transferService->create($user, [
                'from_wallet_id' => $wallet->id,
                'to_wallet_id' => $assuranceVie->id,
                'amount' => 100.00,
                'description' => 'Versement assurance vie',
                'date' => Carbon::create($month->year, $month->month, 2)->toDateString(),
            ]);
        }

        foreach ($months as $index => $month) {
            $budget = $this->budgetService->getOrCreate($wallet, $month);
            $this->createBudgetItems($budget, $categories, $index > 0);
            $this->createMonthTransactions($user, $wallet, $month, $categories, $index);
            $this->budgetService->computeCarryOver($budget, $month);
        }

        $this->createLivretATransactions($user, $livretA, $months);
        $this->createAssuranceVieTransactions($user, $assuranceVie, $months);
        $this->createArgentDePocheTransactions($user, $argent, $months);

        $this->createGoals($user, $wallet);
        $this->createRecurringTransactions($user, $wallet, $categories);
        $this->createScheduledTransactions($user, $wallet, $categories);
        $this->createBudgetPresets($user);
        $this->createCategorizationRules($user, $categories);
    }

    private function createWallet(User $user, string $name, float $startBalance, WalletMode $mode = WalletMode::Budget): Wallet
    {
        $wallet = Wallet::create([
            'user_id' => $user->id,
            'name' => $name,
            'start_balance' => $startBalance,
            'mode' => $mode,
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
            'Salaire', 'Freelance', 'Remboursement sécu',
            'Épargne', 'Épargne vacances',
            'Loyer', 'Électricité', 'Internet', 'Téléphone',
            'Assurance habitation', 'Assurance auto', 'Streaming', 'Sport',
            'Alimentation', 'Transport', 'Restaurants',
            'Loisirs', 'Vacances', 'Cadeaux', 'High-tech',
            'Santé', 'Vêtements',
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

    private function createMonthTransactions(User $user, Wallet $wallet, Carbon $month, array $categories, int $monthIndex): void
    {
        $year = $month->year;
        $monthNumber = $month->month;

        $create = function (string $cat, float $amount, int $day, string $desc, string $type, array $tags) use ($user, $wallet, $categories, $year, $monthNumber): void {
            Transaction::create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'category_id' => $categories[$cat]->id,
                'type' => $type,
                'amount' => round($amount, 2),
                'description' => $desc,
                'date' => Carbon::create($year, $monthNumber, min($day, Carbon::create($year, $monthNumber, 1)->endOfMonth()->day))->toDateString(),
                'tags' => $tags ?: null,
            ]);
        };

        foreach ([
            ['Salaire',              2900.00, 27, 'Virement salaire',       'income',  ['salaire']],
            ['Loyer',                 900.00,  1, 'Loyer',                  'expense', ['logement']],
            ['Internet',               34.99,  5, 'Freebox',                'expense', ['abonnement']],
            ['Téléphone',              19.99,  5, 'Forfait mobile',         'expense', ['abonnement']],
            ['Assurance habitation',   22.00,  8, 'Assurance habitation',   'expense', ['logement']],
            ['Assurance auto',         68.00,  8, 'Assurance auto',         'expense', ['assurance']],
            ['Streaming',              17.99,  8, 'Netflix',                'expense', ['abonnement', 'streaming']],
            ['Streaming',              10.99,  8, 'Spotify',                'expense', ['abonnement', 'streaming']],
            ['Sport',                  29.90,  1, 'Salle de sport',         'expense', ['sport']],
            ['Santé',                  48.00,  5, 'Mutuelle',               'expense', ['santé']],
            ['Remboursement prêt',    248.00,  3, 'Prêt auto',              'expense', ['crédit']],
        ] as [$cat, $amount, $day, $desc, $type, $tags]) {
            $create($cat, $amount, $day, $desc, $type, $tags);
        }

        foreach (self::MONTHLY_TX[$monthIndex] ?? [] as [$cat, $amount, $day, $desc, $type, $tags]) {
            $create($cat, $amount, $day, $desc, $type, $tags);
        }
    }

    private function createGoals(User $user, Wallet $wallet): void
    {
        foreach ([
            ['name' => 'Voyage au Japon',   'target_amount' => 4500.00,  'saved_amount' => 1800.00, 'deadline' => now()->addMonths(8)->toDateString(),  'color' => '#6366f1'],
            ['name' => "Fonds d'urgence",   'target_amount' => 6000.00,  'saved_amount' => 4200.00, 'deadline' => null,                                 'color' => '#22c55e'],
            ['name' => 'MacBook Pro',       'target_amount' => 2400.00,  'saved_amount' => 960.00,  'deadline' => now()->addMonths(5)->toDateString(),  'color' => '#3b82f6'],
            ['name' => 'Apport immobilier', 'target_amount' => 30000.00, 'saved_amount' => 8500.00, 'deadline' => now()->addMonths(36)->toDateString(), 'color' => '#f59e0b'],
            ['name' => 'Nouveau vélo',      'target_amount' => 900.00,   'saved_amount' => 900.00,  'deadline' => null,                                 'color' => '#10b981'],
            ['name' => 'Permis bateau',     'target_amount' => 1200.00,  'saved_amount' => 350.00,  'deadline' => now()->addMonths(12)->toDateString(), 'color' => '#0ea5e9'],
        ] as $data) {
            Goal::create(['user_id' => $user->id, 'wallet_id' => $wallet->id, 'category_id' => null, ...$data]);
        }
    }

    private function createRecurringTransactions(User $user, Wallet $wallet, array $categories): void
    {
        foreach ([
            ['description' => 'Netflix',                  'amount' => 17.99,   'day_of_month' => 8,  'type' => 'expense', 'active' => true,  'cat' => 'Streaming'],
            ['description' => 'Spotify',                  'amount' => 10.99,   'day_of_month' => 8,  'type' => 'expense', 'active' => true,  'cat' => 'Streaming'],
            ['description' => 'Disney+',                  'amount' => 8.99,    'day_of_month' => 1,  'type' => 'expense', 'active' => true,  'cat' => 'Streaming'],
            ['description' => 'Salle de sport',           'amount' => 29.90,   'day_of_month' => 1,  'type' => 'expense', 'active' => true,  'cat' => 'Sport'],
            ['description' => 'Mutuelle',                 'amount' => 48.00,   'day_of_month' => 5,  'type' => 'expense', 'active' => true,  'cat' => 'Santé'],
            ['description' => 'Salaire',                  'amount' => 2900.00, 'day_of_month' => 27, 'type' => 'income',  'active' => true,  'cat' => 'Salaire'],
            ['description' => 'Prêt auto',                'amount' => 248.00,  'day_of_month' => 3,  'type' => 'expense', 'active' => true,  'cat' => 'Remboursement prêt'],
            ['description' => 'Ancien abonnement presse', 'amount' => 12.99,   'day_of_month' => 15, 'type' => 'expense', 'active' => false, 'cat' => 'Loisirs'],
        ] as $data) {
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
        foreach ([
            ['description' => "Billet d'avion Tokyo",   'amount' => 780.00,  'type' => 'expense', 'date' => now()->addWeeks(3)->toDateString(),                                'cat' => 'Vacances'],
            ['description' => 'Prime annuelle',          'amount' => 1800.00, 'type' => 'income',  'date' => now()->addMonths(2)->startOfMonth()->addDays(14)->toDateString(), 'cat' => 'Salaire'],
            ['description' => 'Taxe foncière',           'amount' => 420.00,  'type' => 'expense', 'date' => now()->addMonths(3)->addDays(5)->toDateString(),                  'cat' => 'Loyer'],
            ['description' => 'Assurance auto annuelle', 'amount' => 816.00,  'type' => 'expense', 'date' => now()->addMonths(5)->startOfMonth()->toDateString(),              'cat' => 'Assurance auto'],
        ] as $data) {
            ScheduledTransaction::create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'category_id' => $categories[$data['cat']]->id,
                'description' => $data['description'],
                'amount' => $data['amount'],
                'type' => $data['type'],
                'scheduled_date' => $data['date'],
            ]);
        }
    }

    private function createBudgetPresets(User $user): void
    {
        foreach ([
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
        ] as $position => $preset) {
            BudgetPreset::create(['user_id' => $user->id, 'position' => $position, ...$preset]);
        }
    }

    private const array LIVRET_A_MONTHLY = [
        0 => [
            ['income',  10,  1, 'Intérêts mensuels Livret A'],
            ['income', 200, 10, 'Versement — économies mois'],
            ['income',  50,  8, 'Arrondi semaine 1'],
            ['income',  45, 15, 'Arrondi semaine 2'],
            ['income',  60, 22, 'Arrondi semaine 3'],
            ['income',  40, 29, 'Arrondi semaine 4'],
            ['income', 100, 18, 'Versement ponctuel'],
            ['expense', 200, 25, 'Retrait — achat occasion'],
            ['income',  30,  5, 'Micro-épargne automatique'],
            ['income',  25, 12, 'Micro-épargne automatique'],
            ['income',  35, 19, 'Micro-épargne automatique'],
        ],
        1 => [
            ['income',  10,  1, 'Intérêts mensuels Livret A'],
            ['income', 200,  2, 'Versement mensuel'],
            ['income', 150, 14, 'Versement — prime'],
            ['income',  55,  8, 'Arrondi semaine 1'],
            ['income',  45, 15, 'Arrondi semaine 2'],
            ['income',  50, 22, 'Arrondi semaine 3'],
            ['income',  40, 28, 'Arrondi semaine 4'],
            ['income',  20,  5, 'Micro-épargne automatique'],
            ['income',  30, 12, 'Micro-épargne automatique'],
            ['income',  25, 19, 'Micro-épargne automatique'],
            ['income',  20, 26, 'Micro-épargne automatique'],
        ],
        2 => [
            ['income',  11,  1, 'Intérêts mensuels Livret A'],
            ['income', 200,  2, 'Versement mensuel'],
            ['income',  60,  8, 'Arrondi semaine 1'],
            ['income',  50, 15, 'Arrondi semaine 2'],
            ['income',  55, 22, 'Arrondi semaine 3'],
            ['income',  45, 28, 'Arrondi semaine 4'],
            ['income',  30,  5, 'Micro-épargne automatique'],
            ['income',  20, 12, 'Micro-épargne automatique'],
            ['income',  25, 19, 'Micro-épargne automatique'],
            ['income',  35, 26, 'Micro-épargne automatique'],
            ['income', 100, 20, 'Versement ponctuel — économies'],
        ],
        3 => [
            ['income',  11,  1, 'Intérêts mensuels Livret A'],
            ['income', 200,  2, 'Versement mensuel'],
            ['income',  50,  8, 'Arrondi semaine 1'],
            ['income',  45, 15, 'Arrondi semaine 2'],
            ['income',  55, 22, 'Arrondi semaine 3'],
            ['income',  40, 28, 'Arrondi semaine 4'],
            ['income',  20,  5, 'Micro-épargne automatique'],
            ['income',  30, 12, 'Micro-épargne automatique'],
            ['expense', 500, 15, 'Retrait — financement vélo'],
            ['income',  25, 19, 'Micro-épargne automatique'],
            ['income',  20, 26, 'Micro-épargne automatique'],
        ],
        4 => [
            ['income',  10,  1, 'Intérêts mensuels Livret A'],
            ['income', 200,  2, 'Versement mensuel'],
            ['income',  60,  7, 'Arrondi semaine 1'],
            ['income',  50, 14, 'Arrondi semaine 2'],
            ['income',  45, 21, 'Arrondi semaine 3'],
            ['income',  55, 28, 'Arrondi semaine 4'],
            ['income',  25,  5, 'Micro-épargne automatique'],
            ['income',  30, 11, 'Micro-épargne automatique'],
            ['income',  20, 18, 'Micro-épargne automatique'],
            ['income',  35, 25, 'Micro-épargne automatique'],
            ['income', 200, 20, 'Versement — remboursement assurance'],
        ],
        5 => [
            ['income',  11,  1, 'Intérêts mensuels Livret A'],
            ['income', 200,  2, 'Versement mensuel'],
            ['income', 150, 12, 'Versement complémentaire'],
            ['income',  55,  8, 'Arrondi semaine 1'],
            ['income',  45, 15, 'Arrondi semaine 2'],
            ['income',  50, 22, 'Arrondi semaine 3'],
            ['income',  40, 28, 'Arrondi semaine 4'],
            ['income',  30,  5, 'Micro-épargne automatique'],
            ['income',  20, 13, 'Micro-épargne automatique'],
            ['income',  25, 20, 'Micro-épargne automatique'],
            ['income',  35, 27, 'Micro-épargne automatique'],
        ],
        6 => [
            ['income',  12,  1, 'Intérêts mensuels Livret A'],
            ['income', 200,  2, 'Versement mensuel'],
            ['income',  60,  7, 'Arrondi semaine 1'],
            ['income',  50, 14, 'Arrondi semaine 2'],
            ['income',  55, 21, 'Arrondi semaine 3'],
            ['income',  45, 28, 'Arrondi semaine 4'],
            ['income',  20,  5, 'Micro-épargne automatique'],
            ['income',  30, 12, 'Micro-épargne automatique'],
            ['income',  25, 19, 'Micro-épargne automatique'],
            ['income',  20, 26, 'Micro-épargne automatique'],
            ['expense', 300, 25, 'Retrait — dépenses vacances'],
        ],
        7 => [
            ['income',  12,  1, 'Intérêts mensuels Livret A'],
            ['income', 200,  2, 'Versement mensuel'],
            ['income',  50,  8, 'Arrondi semaine 1'],
            ['income',  45, 15, 'Arrondi semaine 2'],
            ['income',  55, 22, 'Arrondi semaine 3'],
            ['income',  40, 29, 'Arrondi semaine 4'],
            ['income',  25,  5, 'Micro-épargne automatique'],
            ['income',  30, 12, 'Micro-épargne automatique'],
            ['expense', 800, 10, 'Retrait — acompte voyage Japon'],
            ['income',  20, 19, 'Micro-épargne automatique'],
            ['income',  35, 26, 'Micro-épargne automatique'],
        ],
        8 => [
            ['income',  11,  1, 'Intérêts mensuels Livret A'],
            ['income', 200,  2, 'Versement mensuel'],
            ['income',  60,  8, 'Arrondi semaine 1'],
            ['income',  50, 15, 'Arrondi semaine 2'],
            ['income',  45, 22, 'Arrondi semaine 3'],
            ['income',  55, 28, 'Arrondi semaine 4'],
            ['income',  30,  5, 'Micro-épargne automatique'],
            ['income',  20, 12, 'Micro-épargne automatique'],
            ['income',  25, 19, 'Micro-épargne automatique'],
            ['income',  35, 26, 'Micro-épargne automatique'],
            ['income', 300,  8, 'Versement — remboursement sécu'],
        ],
        9 => [
            ['income',  11,  1, 'Intérêts mensuels Livret A'],
            ['income', 200,  2, 'Versement mensuel'],
            ['income', 300,  8, 'Versement complémentaire — remboursement'],
            ['income',  55,  8, 'Arrondi semaine 1'],
            ['income',  45, 15, 'Arrondi semaine 2'],
            ['income',  50, 22, 'Arrondi semaine 3'],
            ['income',  40, 28, 'Arrondi semaine 4'],
            ['income',  20,  5, 'Micro-épargne automatique'],
            ['income',  30, 13, 'Micro-épargne automatique'],
            ['income',  25, 20, 'Micro-épargne automatique'],
            ['income',  35, 27, 'Micro-épargne automatique'],
        ],
        10 => [
            ['income',  12,  1, 'Intérêts mensuels Livret A'],
            ['income', 200,  2, 'Versement mensuel'],
            ['income',  60,  7, 'Arrondi semaine 1'],
            ['income',  50, 14, 'Arrondi semaine 2'],
            ['income',  55, 21, 'Arrondi semaine 3'],
            ['income',  45, 28, 'Arrondi semaine 4'],
            ['income',  25,  5, 'Micro-épargne automatique'],
            ['expense', 400, 22, 'Retrait — travaux appartement'],
            ['income',  30, 12, 'Micro-épargne automatique'],
            ['income',  20, 19, 'Micro-épargne automatique'],
            ['income',  35, 26, 'Micro-épargne automatique'],
        ],
        11 => [
            ['income',  12,  1, 'Intérêts mensuels Livret A'],
            ['income', 200,  2, 'Versement mensuel'],
            ['income',  55,  8, 'Arrondi semaine 1'],
            ['income',  45, 15, 'Arrondi semaine 2'],
            ['income',  50, 22, 'Arrondi semaine 3'],
            ['income',  30,  5, 'Micro-épargne automatique'],
            ['income',  20, 12, 'Micro-épargne automatique'],
            ['income',  25, 19, 'Micro-épargne automatique'],
            ['income',  35, 26, 'Micro-épargne automatique'],
            ['income', 200, 18, 'Versement ponctuel — fin d\'année'],
            ['income', 100, 28, 'Versement — prime Noël'],
        ],
    ];

    private const array ASSURANCE_VIE_MONTHLY = [
        0 => [
            ['income',  30,  1, 'Versement mensuel'],
            ['income',  29, 28, 'Valorisation — fonds euros'],
            ['income',  18, 15, 'Dividendes UC — actions monde'],
            ['expense',  3,  5, 'Frais de gestion'],
            ['income', 500, 20, 'Versement exceptionnel'],
            ['income',  12, 10, 'Plus-value arbitrage fonds'],
            ['income',   8, 22, 'Intérêts techniques'],
            ['expense',  2, 28, "Frais d'arbitrage"],
            ['income',  15,  7, 'Participation aux bénéfices'],
            ['income',  10, 18, 'Réévaluation annuelle'],
            ['expense',  1, 30, 'Frais de versement'],
        ],
        1 => [
            ['income',  30,  2, 'Versement mensuel'],
            ['income',  30, 28, 'Valorisation — fonds euros'],
            ['income',  20, 14, 'Dividendes UC — actions monde'],
            ['expense',  3,  5, 'Frais de gestion'],
            ['income',  14, 10, 'Plus-value arbitrage fonds'],
            ['income',   9, 21, 'Intérêts techniques'],
            ['income',  16,  6, 'Participation aux bénéfices'],
            ['expense',  2, 20, "Frais d'arbitrage"],
            ['income',  11, 17, 'Réévaluation unités de compte'],
            ['income',   7, 25, 'Micro-plus-value UC'],
            ['expense',  1, 28, 'Frais de versement'],
        ],
        2 => [
            ['income',  30,  2, 'Versement mensuel'],
            ['income',  31, 28, 'Valorisation — fonds euros'],
            ['income',  22, 14, 'Dividendes UC — actions monde'],
            ['expense',  4,  5, 'Frais de gestion trimestriels'],
            ['income',  15, 10, 'Plus-value arbitrage'],
            ['income',  10, 21, 'Intérêts techniques'],
            ['income',  17,  6, 'Participation aux bénéfices'],
            ['income',  12, 17, 'Réévaluation unités de compte'],
            ['income',   8, 24, 'Micro-plus-value UC'],
            ['expense',  2, 28, "Frais d'arbitrage"],
            ['expense',  1, 30, 'Frais de versement'],
        ],
        3 => [
            ['income',  30,  2, 'Versement mensuel'],
            ['income',  32, 28, 'Valorisation — fonds euros'],
            ['income',  24, 14, 'Dividendes UC — actions monde'],
            ['expense',  3,  5, 'Frais de gestion'],
            ['income',  16, 10, 'Plus-value arbitrage'],
            ['income',  11, 21, 'Intérêts techniques'],
            ['income',  18,  6, 'Participation aux bénéfices'],
            ['income',  13, 17, 'Réévaluation unités de compte'],
            ['income',   9, 24, 'Micro-plus-value UC'],
            ['income',   6, 11, 'Intérêts retardés'],
            ['expense',  1, 30, 'Frais de versement'],
        ],
        4 => [
            ['income',  30,  2, 'Versement mensuel'],
            ['income',  33, 28, 'Valorisation — fonds euros'],
            ['income', 1000, 15, 'Versement exceptionnel — prime annuelle'],
            ['income',  25, 14, 'Dividendes UC — actions monde'],
            ['expense',  4,  5, 'Frais de gestion trimestriels'],
            ['income',  17, 10, 'Plus-value arbitrage'],
            ['income',  12, 21, 'Intérêts techniques'],
            ['income',  19,  6, 'Participation aux bénéfices'],
            ['income',  14, 17, 'Réévaluation unités de compte'],
            ['income',  10, 24, 'Micro-plus-value UC'],
            ['expense',  2, 28, "Frais d'arbitrage"],
        ],
        5 => [
            ['income',  30,  2, 'Versement mensuel'],
            ['income',  35, 28, 'Valorisation — fonds euros'],
            ['income',  26, 14, 'Dividendes UC — actions monde'],
            ['expense',  3,  5, 'Frais de gestion'],
            ['income',  18, 10, 'Plus-value arbitrage'],
            ['income',  13, 21, 'Intérêts techniques'],
            ['income',  20,  6, 'Participation aux bénéfices'],
            ['income',  15, 17, 'Réévaluation unités de compte'],
            ['income',  11, 24, 'Micro-plus-value UC'],
            ['income',   7, 11, 'Intérêts retardés'],
            ['expense',  1, 30, 'Frais de versement'],
        ],
        6 => [
            ['income',  30,  2, 'Versement mensuel'],
            ['income',  36, 28, 'Valorisation — fonds euros'],
            ['income',  28, 14, 'Dividendes UC — actions monde'],
            ['expense',  4,  5, 'Frais de gestion trimestriels'],
            ['expense', 2000, 18, 'Rachat partiel'],
            ['income',  19, 10, 'Plus-value arbitrage'],
            ['income',  14, 21, 'Intérêts techniques'],
            ['income',  21,  6, 'Participation aux bénéfices'],
            ['income',  16, 17, 'Réévaluation unités de compte'],
            ['income',  12, 24, 'Micro-plus-value UC'],
            ['expense',  2, 28, "Frais d'arbitrage"],
        ],
        7 => [
            ['income',  30,  2, 'Versement mensuel'],
            ['income',  34, 28, 'Valorisation — fonds euros'],
            ['income',  22, 14, 'Dividendes UC — actions monde'],
            ['expense',  3,  5, 'Frais de gestion'],
            ['income',  16, 10, 'Plus-value arbitrage'],
            ['income',  11, 21, 'Intérêts techniques'],
            ['income',  18,  6, 'Participation aux bénéfices'],
            ['income',  13, 17, 'Réévaluation unités de compte'],
            ['income',   9, 24, 'Micro-plus-value UC'],
            ['income',   6, 11, 'Intérêts retardés'],
            ['expense',  1, 30, 'Frais de versement'],
        ],
        8 => [
            ['income',  30,  2, 'Versement mensuel'],
            ['income',  35, 28, 'Valorisation — fonds euros'],
            ['income', 300, 10, 'Versement exceptionnel'],
            ['income',  24, 14, 'Dividendes UC — actions monde'],
            ['expense',  4,  5, 'Frais de gestion trimestriels'],
            ['income',  17, 10, 'Plus-value arbitrage'],
            ['income',  12, 21, 'Intérêts techniques'],
            ['income',  19,  6, 'Participation aux bénéfices'],
            ['income',  14, 17, 'Réévaluation unités de compte'],
            ['income',  10, 24, 'Micro-plus-value UC'],
            ['expense',  2, 28, "Frais d'arbitrage"],
        ],
        9 => [
            ['income',  30,  2, 'Versement mensuel'],
            ['income',  36, 28, 'Valorisation — fonds euros'],
            ['income',  25, 14, 'Dividendes UC — actions monde'],
            ['expense',  3,  5, 'Frais de gestion'],
            ['income',  18, 10, 'Plus-value arbitrage'],
            ['income',  13, 21, 'Intérêts techniques'],
            ['income',  20,  6, 'Participation aux bénéfices'],
            ['income',  15, 17, 'Réévaluation unités de compte'],
            ['income',  11, 24, 'Micro-plus-value UC'],
            ['income',   7, 11, 'Intérêts retardés'],
            ['expense',  1, 30, 'Frais de versement'],
        ],
        10 => [
            ['income',  30,  2, 'Versement mensuel'],
            ['income',  37, 28, 'Valorisation — fonds euros'],
            ['income',  26, 14, 'Dividendes UC — actions monde'],
            ['expense',  4,  5, 'Frais de gestion trimestriels'],
            ['income',  19, 10, 'Plus-value arbitrage'],
            ['income',  14, 21, 'Intérêts techniques'],
            ['income',  21,  6, 'Participation aux bénéfices'],
            ['income',  16, 17, 'Réévaluation unités de compte'],
            ['income',  12, 24, 'Micro-plus-value UC'],
            ['income',   8, 11, 'Intérêts retardés'],
            ['expense',  2, 28, "Frais d'arbitrage"],
        ],
        11 => [
            ['income',  30,  2, 'Versement mensuel'],
            ['income',  38, 28, 'Valorisation — fonds euros'],
            ['income',  27, 14, 'Dividendes UC — actions monde'],
            ['expense',  3,  5, 'Frais de gestion'],
            ['income',  20, 10, 'Plus-value arbitrage'],
            ['income',  15, 21, 'Intérêts techniques'],
            ['income',  22,  6, 'Participation aux bénéfices'],
            ['income',  17, 17, 'Réévaluation unités de compte'],
            ['income',  13, 24, 'Micro-plus-value UC'],
            ['income',   9, 11, 'Intérêts retardés'],
            ['expense',  1, 30, 'Frais de versement'],
        ],
    ];

    private const array ARGENT_DE_POCHE_MONTHLY = [
        0 => [
            ['expense', 3.50,  2, 'Café — boulangerie'],
            ['expense', 6.50,  3, 'Sandwich midi'],
            ['expense', 2.80,  5, 'Café'],
            ['expense', 8.40,  7, 'Marché — légumes et fruits'],
            ['expense', 4.20,  9, 'Café + viennoiserie'],
            ['expense', 1.20, 10, 'Baguette'],
            ['expense', 5.00, 12, 'Café entre amis'],
            ['expense', 3.00, 14, 'Pourboire taxi'],
            ['expense', 2.50, 16, 'Journaux'],
            ['expense', 4.50, 18, 'Café — terrasse'],
            ['expense', 1.80, 20, 'Bonbons & snacks'],
            ['expense', 3.20, 22, 'Café réunion'],
            ['expense', 6.80, 24, 'Sandwich + boisson'],
            ['expense', 2.00, 26, 'Eau minérale'],
            ['expense', 1.30, 28, 'Croissant'],
        ],
        1 => [
            ['expense', 4.50,  1, 'Café — terrasse'],
            ['expense', 7.00,  3, 'Sandwich midi'],
            ['expense', 2.80,  5, 'Café'],
            ['expense', 9.50,  7, 'Marché — légumes'],
            ['expense', 3.50,  9, 'Café boulangerie'],
            ['expense', 1.50, 11, 'Baguette + croissant'],
            ['expense', 5.50, 13, 'Café + jus'],
            ['expense', 2.00, 15, 'Pourboire livreur'],
            ['expense', 6.50, 17, 'Sandwich + snack'],
            ['expense', 4.00, 19, 'Café entre collègues'],
            ['expense', 1.80, 21, 'Chewing-gum & bonbons'],
            ['expense', 3.00, 23, 'Café rapide'],
            ['expense', 8.00, 25, 'Marché — fleurs'],
            ['expense', 2.50, 27, 'Eau + boisson'],
            ['expense', 1.30, 28, 'Croissant'],
        ],
        2 => [
            ['expense', 3.50,  2, 'Café'],
            ['expense', 6.00,  4, 'Sandwich midi'],
            ['expense', 2.50,  6, 'Café rapide'],
            ['expense', 10.00,  8, 'Marché — légumes et fromage'],
            ['expense', 4.50, 10, 'Café + croissant'],
            ['expense', 1.20, 12, 'Baguette'],
            ['expense', 5.00, 14, 'Café — terrasse'],
            ['expense', 3.50, 16, 'Snacks bureau'],
            ['expense', 7.50, 18, 'Sandwich + dessert'],
            ['expense', 4.00, 20, 'Café amis'],
            ['expense', 2.00, 22, 'Boisson fraîche'],
            ['expense', 6.50, 24, 'Repas food truck'],
            ['expense', 3.00, 26, 'Café rapide'],
            ['expense', 1.50, 27, 'Croissant'],
        ],
        3 => [
            ['expense', 4.00,  1, 'Café — boulangerie'],
            ['expense', 7.50,  3, 'Sandwich + boisson'],
            ['expense', 2.80,  5, 'Café'],
            ['expense', 9.00,  7, 'Marché — légumes'],
            ['expense', 4.50,  9, 'Café + pain au chocolat'],
            ['expense', 1.20, 11, 'Baguette'],
            ['expense', 5.50, 13, 'Café entre amis'],
            ['expense', 3.00, 15, 'Pourboire'],
            ['expense', 2.50, 17, 'Journaux + magazine'],
            ['expense', 6.00, 19, 'Sandwich déjeuner'],
            ['expense', 4.50, 21, 'Café terrasse'],
            ['expense', 1.80, 23, 'Bonbons'],
            ['expense', 8.50, 25, 'Marché — fruits de saison'],
            ['expense', 2.00, 27, 'Eau minérale'],
            ['expense', 3.50, 29, 'Café rapide'],
        ],
        4 => [
            ['expense', 3.50,  2, 'Café — boulangerie'],
            ['expense', 6.50,  4, 'Sandwich midi'],
            ['expense', 2.80,  6, 'Café'],
            ['expense', 11.00,  8, 'Marché — légumes, fruits, herbes'],
            ['expense', 4.20, 10, 'Café + croissant'],
            ['expense', 1.20, 12, 'Baguette'],
            ['expense', 5.00, 14, 'Café entre collègues'],
            ['expense', 3.50, 16, 'Snacks'],
            ['expense', 7.00, 18, 'Sandwich + jus'],
            ['expense', 4.00, 20, 'Café terrasse'],
            ['expense', 2.50, 22, 'Eau + snack'],
            ['expense', 6.00, 24, 'Food truck déjeuner'],
            ['expense', 3.00, 26, 'Café rapide'],
            ['expense', 1.50, 28, 'Croissant'],
            ['expense', 5.00, 30, 'Café + boisson'],
        ],
        5 => [
            ['expense', 4.50,  1, 'Café — terrasse'],
            ['expense', 7.00,  3, 'Sandwich midi'],
            ['expense', 3.00,  5, 'Café'],
            ['expense', 9.50,  7, 'Marché — légumes et olives'],
            ['expense', 4.00,  9, 'Café boulangerie'],
            ['expense', 1.50, 11, 'Baguette + pain'],
            ['expense', 5.50, 13, 'Café + jus d\'orange'],
            ['expense', 2.00, 15, 'Boisson fraîche'],
            ['expense', 6.50, 17, 'Sandwich + dessert'],
            ['expense', 4.50, 19, 'Café entre amis'],
            ['expense', 2.50, 21, 'Bonbons & snacks'],
            ['expense', 3.50, 23, 'Café rapide'],
            ['expense', 8.00, 25, 'Marché de producteurs'],
            ['expense', 2.00, 27, 'Eau minérale'],
            ['expense', 1.30, 29, 'Croissant'],
        ],
        6 => [
            ['expense', 3.50,  2, 'Café'],
            ['expense', 6.00,  4, 'Sandwich déjeuner'],
            ['expense', 2.80,  6, 'Café rapide'],
            ['expense', 12.00,  8, 'Marché — légumes, fromage, miel'],
            ['expense', 4.50, 10, 'Café + viennoiserie'],
            ['expense', 1.20, 12, 'Baguette'],
            ['expense', 5.00, 14, 'Café terrasse'],
            ['expense', 4.00, 16, 'Glace artisanale'],
            ['expense', 7.50, 18, 'Sandwich + boisson fraîche'],
            ['expense', 3.50, 20, 'Café amis'],
            ['expense', 5.50, 22, 'Jus frais pressé'],
            ['expense', 6.00, 24, 'Food truck'],
            ['expense', 3.00, 26, 'Café rapide'],
            ['expense', 2.00, 28, 'Eau minérale'],
            ['expense', 4.50, 30, 'Glace + café'],
        ],
        7 => [
            ['expense', 4.00,  2, 'Café — boulangerie'],
            ['expense', 7.00,  4, 'Sandwich midi'],
            ['expense', 3.00,  6, 'Café'],
            ['expense', 10.00,  8, 'Marché vacances'],
            ['expense', 4.50, 10, 'Café + croissant'],
            ['expense', 5.00, 12, 'Glace artisanale'],
            ['expense', 3.50, 14, 'Café terrasse'],
            ['expense', 6.50, 16, 'Sandwich + jus'],
            ['expense', 8.00, 18, 'Marché local — spécialités'],
            ['expense', 4.00, 20, 'Café — bar de plage'],
            ['expense', 2.50, 22, 'Eau + boisson'],
            ['expense', 5.00, 24, 'Glace + boisson fraîche'],
            ['expense', 6.50, 26, 'Sandwich local'],
            ['expense', 3.00, 28, 'Café rapide'],
            ['expense', 1.50, 30, 'Baguette'],
        ],
        8 => [
            ['expense', 3.50,  2, 'Café — boulangerie'],
            ['expense', 6.50,  4, 'Sandwich midi'],
            ['expense', 2.80,  6, 'Café'],
            ['expense', 9.00,  8, 'Marché — rentrée légumes'],
            ['expense', 4.20, 10, 'Café + pain au chocolat'],
            ['expense', 1.20, 12, 'Baguette'],
            ['expense', 5.00, 14, 'Café entre amis'],
            ['expense', 3.00, 16, 'Pourboire'],
            ['expense', 7.50, 18, 'Sandwich + dessert'],
            ['expense', 4.50, 20, 'Café terrasse'],
            ['expense', 2.50, 22, 'Snacks bureau'],
            ['expense', 6.00, 24, 'Food truck'],
            ['expense', 3.50, 26, 'Café rapide'],
            ['expense', 1.80, 28, 'Bonbons'],
            ['expense', 2.00, 30, 'Eau minérale'],
        ],
        9 => [
            ['expense', 4.00,  1, 'Café — boulangerie'],
            ['expense', 7.00,  3, 'Sandwich midi'],
            ['expense', 3.00,  5, 'Café'],
            ['expense', 10.50,  7, 'Marché — légumes automne'],
            ['expense', 4.50,  9, 'Café + croissant'],
            ['expense', 1.50, 11, 'Baguette + croissant'],
            ['expense', 5.50, 13, 'Café entre collègues'],
            ['expense', 2.50, 15, 'Snacks'],
            ['expense', 6.50, 17, 'Sandwich déjeuner'],
            ['expense', 4.00, 19, 'Café terrasse'],
            ['expense', 3.00, 21, 'Châtaignes grillées'],
            ['expense', 7.50, 23, 'Food truck déjeuner'],
            ['expense', 3.50, 25, 'Café rapide'],
            ['expense', 2.00, 27, 'Eau minérale'],
            ['expense', 1.30, 29, 'Baguette'],
        ],
        10 => [
            ['expense', 3.50,  2, 'Café'],
            ['expense', 6.00,  4, 'Sandwich midi'],
            ['expense', 2.80,  6, 'Café rapide'],
            ['expense', 9.00,  8, 'Marché — légumes automne'],
            ['expense', 4.20, 10, 'Café + viennoiserie'],
            ['expense', 1.20, 12, 'Baguette'],
            ['expense', 5.00, 14, 'Café entre amis'],
            ['expense', 4.00, 16, 'Chocolat chaud + gâteau'],
            ['expense', 7.00, 18, 'Sandwich + boisson'],
            ['expense', 4.50, 20, 'Café terrasse'],
            ['expense', 3.50, 22, 'Snacks Halloween'],
            ['expense', 6.50, 24, 'Food truck'],
            ['expense', 3.00, 26, 'Café rapide'],
            ['expense', 2.00, 28, 'Eau + snack'],
            ['expense', 1.80, 30, 'Bonbons Halloween'],
        ],
        11 => [
            ['expense', 4.50,  1, 'Café — boulangerie'],
            ['expense', 7.00,  3, 'Sandwich midi'],
            ['expense', 3.00,  5, 'Café'],
            ['expense', 11.00,  7, 'Marché — légumes hiver, épices'],
            ['expense', 4.50,  9, 'Café + croissant'],
            ['expense', 1.50, 11, 'Baguette'],
            ['expense', 5.50, 13, 'Chocolat chaud + gâteau'],
            ['expense', 3.50, 15, 'Pourboire'],
            ['expense', 6.50, 17, 'Sandwich déjeuner'],
            ['expense', 5.00, 19, 'Vin chaud — marché de Noël'],
            ['expense', 8.00, 21, 'Marché de Noël — spécialités'],
            ['expense', 4.00, 23, 'Café rapide'],
            ['expense', 6.00, 25, 'Petits gâteaux de Noël'],
            ['expense', 2.00, 27, 'Eau minérale'],
            ['expense', 3.50, 29, 'Café — veille de fêtes'],
        ],
    ];

    private function createLivretATransactions(User $user, Wallet $wallet, array $months): void
    {
        foreach ($months as $index => $month) {
            foreach (self::LIVRET_A_MONTHLY[$index] ?? [] as [$type, $amount, $day, $desc]) {
                Transaction::create([
                    'user_id' => $user->id,
                    'wallet_id' => $wallet->id,
                    'category_id' => null,
                    'type' => $type,
                    'amount' => (float) $amount,
                    'description' => $desc,
                    'date' => Carbon::create($month->year, $month->month, min($day, $month->copy()->endOfMonth()->day))->toDateString(),
                    'tags' => null,
                ]);
            }
        }
    }

    private function createAssuranceVieTransactions(User $user, Wallet $wallet, array $months): void
    {
        foreach ($months as $index => $month) {
            foreach (self::ASSURANCE_VIE_MONTHLY[$index] ?? [] as [$type, $amount, $day, $desc]) {
                Transaction::create([
                    'user_id' => $user->id,
                    'wallet_id' => $wallet->id,
                    'category_id' => null,
                    'type' => $type,
                    'amount' => (float) $amount,
                    'description' => $desc,
                    'date' => Carbon::create($month->year, $month->month, min($day, $month->copy()->endOfMonth()->day))->toDateString(),
                    'tags' => null,
                ]);
            }
        }
    }

    private function createArgentDePocheTransactions(User $user, Wallet $wallet, array $months): void
    {
        foreach ($months as $index => $month) {
            foreach (self::ARGENT_DE_POCHE_MONTHLY[$index] ?? [] as [$type, $amount, $day, $desc]) {
                Transaction::create([
                    'user_id' => $user->id,
                    'wallet_id' => $wallet->id,
                    'category_id' => null,
                    'type' => $type,
                    'amount' => (float) $amount,
                    'description' => $desc,
                    'date' => Carbon::create($month->year, $month->month, min($day, $month->copy()->endOfMonth()->day))->toDateString(),
                    'tags' => null,
                ]);
            }
        }
    }

    private function createCategorizationRules(User $user, array $categories): void
    {
        foreach ([
            ['pattern' => 'lidl|carrefour|leclerc|monoprix|aldi|intermarché|super u|franprix|casino', 'cat' => 'Alimentation', 'hits' => 28],
            ['pattern' => 'netflix',                                                                   'cat' => 'Streaming',    'hits' => 12],
            ['pattern' => 'spotify',                                                                   'cat' => 'Streaming',    'hits' => 12],
            ['pattern' => 'disney',                                                                    'cat' => 'Streaming',    'hits' => 8],
            ['pattern' => 'freebox|sfr|bouygues|orange',                                               'cat' => 'Internet',     'hits' => 12],
            ['pattern' => 'sncf|ratp|navigo|uber|bolt|blablacar|ouigo',                               'cat' => 'Transport',    'hits' => 20],
            ['pattern' => 'total|bp|shell|esso|carburant',                                             'cat' => 'Transport',    'hits' => 12],
            ['pattern' => 'pharmacie|médecin|docteur|dentiste|opticien|clinique',                     'cat' => 'Santé',        'hits' => 9],
            ['pattern' => 'edf|engie|totalenergies',                                                   'cat' => 'Électricité',  'hits' => 12],
            ['pattern' => 'salaire|virement employeur',                                                'cat' => 'Salaire',      'hits' => 12],
            ['pattern' => 'amazon|fnac|darty|boulanger|cdiscount',                                    'cat' => 'High-tech',    'hits' => 6],
            ['pattern' => 'zara|h&m|asos|decathlon|nike|adidas|uniqlo',                              'cat' => 'Vêtements',    'hits' => 8],
            ['pattern' => 'airbnb|booking|hotels|accor|ibis',                                         'cat' => 'Vacances',     'hits' => 4],
        ] as $rule) {
            CategorizationRule::create([
                'user_id' => $user->id,
                'category_id' => $categories[$rule['cat']]->id,
                'pattern' => $rule['pattern'],
                'hits' => $rule['hits'],
            ]);
        }
    }
}
