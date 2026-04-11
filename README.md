<div align="center">

# Spendly

**Application de gestion financière personnelle**

[![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
[![Vue.js](https://img.shields.io/badge/Vue.js-3-4FC08D?style=flat-square&logo=vue.js&logoColor=white)](https://vuejs.org)
[![Inertia.js](https://img.shields.io/badge/Inertia.js-3-9553E9?style=flat-square)](https://inertiajs.com)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind-4-38BDF8?style=flat-square&logo=tailwind-css&logoColor=white)](https://tailwindcss.com)
[![PHP](https://img.shields.io/badge/PHP-8.4+-777BB4?style=flat-square&logo=php&logoColor=white)](https://php.net)

[**Visiter l'application**](https://spendly.axelraboit.fr) — spendly.axelraboit.fr

</div>

---

## Présentation

Spendly est une application web de gestion financière personnelle qui vous permet de suivre vos dépenses, gérer plusieurs portefeuilles, définir des objectifs d'épargne et analyser vos habitudes financières mois par mois.

Conçu avec une interface sombre moderne, Spendly centralise toutes vos finances en un seul endroit.

---

## Fonctionnalités

- **Tableau de bord** — Vue d'ensemble de vos finances du mois en cours : dépenses, remboursements, évolution mensuelle, et dernière transaction enregistrée
- **Vue globale** — Synthèse multi-portefeuilles avec graphiques de revenus/dépenses et répartition par catégorie
- **Portefeuilles** — Gestion de plusieurs comptes indépendants (compte courant, livret, assurance vie…) avec suivi des objectifs budgétaires
- **Transactions** — Liste complète et filtrable de toutes vos transactions, organisées par catégorie et par type (dépense, revenu, virement)
- **Transactions automatiques** — Gestion des abonnements et transactions récurrentes (streaming, salle de sport, mutuelle…)
- **Objectifs d'épargne** — Suivi de la progression vers vos objectifs financiers avec indicateurs visuels
- **Statistiques** — Analyse graphique de vos habitudes de dépenses sur la durée avec projections et évolution multi-mois
- **Catégories** — Gestion personnalisée des catégories de dépenses et de revenus
- **Notes** — Prise de notes enrichie (Markdown) associée à vos finances
- **Export** — Export de vos données en Excel
- **Auto-configuration** — Paramétrage personnalisé des règles de gestion

---

## Aperçu

### Tableau de bord

![Tableau de bord](docs/screenshots/tableau-de-bord.png)

> Vue d'ensemble du mois : dépenses totales, nombre de remboursements, évolution du budget, dernière transaction et top catégories.

---

### Vue globale

![Vue globale](docs/screenshots/vue-globale.png)

> Synthèse de tous vos portefeuilles avec graphiques de revenus/dépenses par mois et donut de répartition par catégorie.

---

### Portefeuille

![Portefeuille](docs/screenshots/portefeuille.png)

> Détail d'un portefeuille : solde, objectifs en cours avec progression, et récapitulatif des transactions du mois.

---

### Transactions

![Transactions](docs/screenshots/transactions.png)

> Détail des transactions d'un portefeuille, par catégorie, avec types, montants et statuts.

---

### Liste des transactions

![Liste des transactions](docs/screenshots/transactions-liste.png)

> Vue complète et filtrable de toutes les transactions (catégorie, portefeuille, mois).

---

### Transactions automatiques

![Transactions automatiques](docs/screenshots/automatiques.png)

> Gestion des abonnements et paiements récurrents : streaming, sport, mutuelle, salaire…

---

### Objectifs d'épargne

![Objectifs d'épargne](docs/screenshots/objectifs.png)

> Suivi visuel de la progression vers chaque objectif financier (voyage, fonds d'urgence, immobilier…).

---

### Statistiques

![Statistiques](docs/screenshots/statistiques.png)

> Tableaux de bord analytiques : dépenses par catégorie, évolution sur 6 mois, projections et budget cible.

---

### Catégories

![Catégories](docs/screenshots/categories.png)

> Gestion des catégories personnalisées avec sous-catégories et types (dépense / revenu).

---

### Notes

![Notes](docs/screenshots/notes.png)

> Éditeur de notes Markdown pour accompagner vos objectifs et réflexions financières.

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

**Démarrer le mailer (Mailpit via Docker) :**

```bash
docker compose up -d
```

**Lancer les serveurs de développement :**

```bash
make dev
```

Lance en parallèle : le serveur PHP, la queue, les logs Pail et Vite.

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
