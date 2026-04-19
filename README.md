<div align="center">

# Spendly

**Application de gestion financière personnelle**

[![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
[![Vue.js](https://img.shields.io/badge/Vue.js-3-4FC08D?style=flat-square&logo=vue.js&logoColor=white)](https://vuejs.org)
[![Inertia.js](https://img.shields.io/badge/Inertia.js-3-9553E9?style=flat-square)](https://inertiajs.com)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind-4-38BDF8?style=flat-square&logo=tailwind-css&logoColor=white)](https://tailwindcss.com)
[![PHP](https://img.shields.io/badge/PHP-8.4+-777BB4?style=flat-square&logo=php&logoColor=white)](https://php.net)
[![Vite](https://img.shields.io/badge/Vite-8-646CFF?style=flat-square&logo=vite&logoColor=white)](https://vitejs.dev)

[**Visiter l'application**](https://spendly.axelraboit.fr) — spendly.axelraboit.fr

</div>

---

## Présentation

Spendly est une application web de gestion financière personnelle qui vous permet de suivre vos dépenses, gérer plusieurs portefeuilles, définir des objectifs d'épargne et analyser vos habitudes financières mois par mois.

Disponible en thème sombre et clair, entièrement responsive, Spendly centralise toutes vos finances en un seul endroit.

---

## Fonctionnalités

- **Tableau de bord** — Vue d'ensemble du mois en cours : dépenses, remboursements, évolution mensuelle, objectifs en cours et dernières transactions
- **Vue globale** — Synthèse multi-portefeuilles avec graphiques de revenus/dépenses et répartition par catégorie
- **Portefeuilles** — Gestion de plusieurs comptes indépendants en mode **Budget** (catégories, lignes budgétaires, suivi détaillé) ou **Simplifié** (transactions simples sans catégories, idéal pour l'argent de poche), avec virements entre comptes
- **Transactions** — Liste complète et filtrable de toutes vos transactions
- **Transactions planifiées** — Suivi des dépenses annuelles, primes et échéances futures
- **Transactions automatiques** — Gestion des abonnements et transactions récurrentes (streaming, sport, mutuelle…)
- **Objectifs d'épargne** — Suivi visuel de la progression vers vos objectifs financiers
- **Statistiques** — Analyse graphique sur la durée avec projections, taux d'épargne et tendances par catégorie
- **Catégories** — Gestion personnalisée des catégories avec auto-catégorisation intelligente
- **Import** — Import de transactions depuis un fichier Excel (modèle fourni)
- **Export** — Export des données budgétaires en Excel
- **Formules & Tarifs** — Offres Free et Pro avec gestion de l'abonnement
- **Profil** — Gestion du profil : langue, informations personnelles, devise, lignes budgétaires par défaut et mot de passe
- **Administration** — Dashboard admin avec statistiques globales, gestion des utilisateurs et invitations

---

## Aperçu

### Connexion

![Connexion](docs/readme/screenshots/login.jpg)

> Page de connexion avec accès démo intégré pour tester l'application sans créer de compte.

---

### Inscription

![Inscription](docs/readme/screenshots/registration.png)

> Formulaire d'inscription : nom, e-mail, mot de passe et confirmation.

---

### Tableau de bord

![Tableau de bord](docs/readme/screenshots/tableau-de-bord.png)

> Vue d'ensemble du mois : dépenses totales, remboursements, évolution du budget, objectifs en cours, dernières transactions et top catégories.

---

### Vue globale

![Vue globale](docs/readme/screenshots/vue-globale.jpg)

> Synthèse de tous vos portefeuilles avec graphiques de revenus/dépenses par mois et donut de répartition par catégorie.

---

### Portefeuilles

![Portefeuilles](docs/readme/screenshots/portefeuilles.jpg)

> Liste de vos portefeuilles avec soldes actuels et accès rapide au budget de chacun.

---

### Créer un portefeuille

![Créer un portefeuille](docs/readme/screenshots/portefeuille-creation.jpg)

> Choix du type lors de la création : **Budget** pour un suivi détaillé avec catégories et lignes budgétaires, ou **Simplifié** pour des transactions sans catégories, idéal pour l'argent de poche.

---

### Portefeuille simplifié

![Portefeuille simplifié](docs/readme/screenshots/portefeuille-simplifie.jpg)

> Vue d'un portefeuille simplifié : solde actuel, total entrées/dépenses avec moyenne journalière, et liste chronologique des transactions filtrables (Tout / Entrées / Dépenses).

---

### Portefeuille budget

![Portefeuille budget - Vue générale](docs/readme/screenshots/portefeuille-budget-1.png)

![Portefeuille budget - Lignes budgétaires (1)](docs/readme/screenshots/portefeuille-budget-2.png)

![Portefeuille budget - Lignes budgétaires (2)](docs/readme/screenshots/portefeuille-budget-3.png)

> Vue d'un portefeuille budget : synthèse mensuelle (prévu / dépensé / total), répartition par catégorie, progression du budget, objectifs d'épargne associés et détail complet des lignes budgétaires (charges fixes, épargne, dépenses, non budgétés).

---

### Virement entre portefeuilles

![Virement](docs/readme/screenshots/virement.jpg)

> Modale de virement : transfert d'un montant entre deux portefeuilles en quelques clics.

---

### Transactions d'une ligne budgétaire

![Budget portefeuille](docs/readme/screenshots/budget-portefeuille.png)

> Détail des transactions d'une ligne budgétaire : liste des dépenses associées à une catégorie en cliquant sur une ligne du budget.

---

### Nouvelle transaction

![Nouvelle transaction](docs/readme/screenshots/nouvelle-transaction.png)

> Formulaire de saisie rapide : type (dépense/revenu/virement), montant, catégorie, date, description, tags et pièces jointes.

---

### Transactions

![Transactions](docs/readme/screenshots/transactions.png)

> Liste complète et filtrable de toutes les transactions (catégorie, portefeuille, mois, type).

---

### Transactions planifiées

![Transactions planifiées](docs/readme/screenshots/transactions-planifiees.jpg)

> Suivi des transactions planifiées : dépenses annuelles, primes et échéances futures.

---

### Nouvelle transaction planifiée

![Nouvelle transaction planifiée](docs/readme/screenshots/nouvelle-transactions-planifiees.jpg)

> Formulaire de création d'une transaction planifiée : montant, catégorie, date d'échéance et description.

---

### Transactions automatiques

![Transactions automatiques](docs/readme/screenshots/transactions-auto.jpg)

> Gestion des abonnements et paiements récurrents : streaming, sport, mutuelle, salaire…

---

### Nouvelle transaction automatique

![Nouvelle transaction automatique](docs/readme/screenshots/nouvelle-transactions-auto.jpg)

> Formulaire de création d'une transaction automatique : montant, fréquence, catégorie et date de début.

---

### Objectifs d'épargne

![Objectifs d'épargne](docs/readme/screenshots/objectifs.jpg)

> Suivi visuel de la progression vers chaque objectif financier (voyage, fonds d'urgence, immobilier…).

---

### Nouvel objectif

![Nouvel objectif](docs/readme/screenshots/nouvel-objectif.jpg)

> Formulaire de création d'un objectif d'épargne : nom, montant cible, montant actuel et date limite.

---

### Statistiques

![Statistiques](docs/readme/screenshots/statistiques.png)

> Tableaux de bord analytiques : dépenses par catégorie, taux d'épargne, évolution sur 6 mois, projections et tendances par catégorie.

---

### Catégories

![Catégories](docs/readme/screenshots/categories.jpg)

> Gestion des catégories personnalisées avec sous-catégories et types (dépense / revenu).

---

### Auto-catégorisation

![Auto-catégorisation](docs/readme/screenshots/auto-categorisation.jpg)

> Règles apprises automatiquement à partir de vos habitudes pour catégoriser vos transactions sans effort.

---

### Importer des transactions

![Import](docs/readme/screenshots/import.jpg)

> Import de transactions depuis un fichier Excel en suivant le modèle téléchargeable.

---

### Formules & Tarifs

![Plan](docs/readme/screenshots/plan.jpg)

> Offres Free et Pro avec comparatif des fonctionnalités et gestion de l'abonnement via Stripe.

---

### Profil

![Profil 1](docs/readme/screenshots/profil-1.png)

![Profil 2](docs/readme/screenshots/profil-2.png)

> Gestion du profil : langue, informations personnelles, devise, lignes budgétaires par défaut et mot de passe.

---

### Administration

![Administration](docs/readme/screenshots/admin.png)

> Dashboard admin : inscriptions, transactions, croissance cumulée, langues utilisées, répartition des formules et statistiques en temps réel.

---

## Architecture — SPA sans API REST

Spendly est une SPA (Single Page Application) mais sans API REST exposée. C'est le choix technique central du projet.

### Inertia.js : le pont Laravel ↔ Vue

Sans Inertia, construire une SPA nécessite soit une API REST dédiée (et donc dupliquer la logique métier), soit du rendu serveur classique (et perdre la fluidité du frontend). Inertia résout ce dilemme en servant de pont entre les deux mondes.

```
[Browser]                           [Laravel server]
     │                                      │
     │  Initial request (HTML)              │  Render layout + page component
     │ ◄──────────────────────────────────  │  + JSON data injected
     │                                      │
     │  Navigation (Inertia visit)          │
     │  ──────────────────────────────────► │  Controller → Inertia::render('Page', $data)
     │ ◄──────────────────────────────────  │  JSON response {component, props, url}
     │                                      │
     │  Vue swaps the component             │
     │  (no full page reload)               │
```

- Le contrôleur retourne `Inertia::render('Wallets/Index', ['wallets' => $wallets])` — pas de serialisation manuelle, pas de route API
- Vue reçoit les données comme des props directement typées
- La navigation est fluide (SPA) sans écrire une seule ligne de fetch/axios pour les données de page
- Les règles d'autorisation, la validation, les redirections restent dans Laravel — la seule source de vérité

### Transactions automatiques et queue Laravel

Les transactions récurrentes (abonnements, salaire…) sont générées automatiquement via la queue Laravel. Plutôt qu'un cron qui bloque et rejoue tout en cas d'échec, chaque génération est un job indépendant : si l'un échoue, les autres continuent, et Laravel gère les retries automatiquement.

---

## Stack technique

| Couche | Technologie |
|--------|-------------|
| Backend | Laravel 13, PHP 8.4+ |
| Frontend | Vue.js 3, Inertia.js 3 |
| Style | Tailwind CSS 4 |
| Graphiques | Chart.js 4 + vue-chartjs |
| Auth & permissions | Laravel Sanctum, Spatie Permissions |
| Export | PhpSpreadsheet, xlsx-js-style |
| Emails | Resend |
| Build | Vite 8 |

---

## Installation

### Prérequis

- PHP >= 8.4
- Composer >= 2
- Node.js >= 20
- PostgreSQL

### Mise en place

```bash
# Cloner le dépôt
git clone https://github.com/AxelRaboit/spendly.git
cd spendly

# Installer toutes les dépendances (composer + tools + pnpm)
make install

# Copier le fichier d'environnement et configurer
cp .env.example .env
php artisan key:generate
php artisan migrate
```

### Démarrage en développement

```bash
make start
```

Lance en parallèle : le mailer (Mailpit via Docker), le serveur PHP, la queue, les logs Pail et Vite.

---

## Commandes utiles

```bash
# Tests
make test              # suite complète
make test-unit         # tests unitaires uniquement
make test-feature      # tests de fonctionnalité uniquement

# Qualité du code
make fix               # auto-correction (Pint, Rector, ESLint) + PHPStan
make stan              # PHPStan seul

# Base de données
make migrate           # exécuter les migrations
make migrate-fresh     # repartir de zéro (drop + migrate)
make fixtures          # migrate-fresh + seeders

# Utilisateurs
make demo-seed         # créer/réinitialiser l'utilisateur démo (EMAIL= optionnel)
make role-dev EMAIL=user@example.com   # passer un utilisateur en ROLE_DEV
```

---

## Licence

MIT
