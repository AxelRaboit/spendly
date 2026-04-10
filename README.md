<div align="center">

# Spendly

**Application de gestion financiere personnelle**

[![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
[![Vue.js](https://img.shields.io/badge/Vue.js-3-4FC08D?style=flat-square&logo=vue.js&logoColor=white)](https://vuejs.org)
[![Inertia.js](https://img.shields.io/badge/Inertia.js-3-9553E9?style=flat-square)](https://inertiajs.com)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind-4-38BDF8?style=flat-square&logo=tailwind-css&logoColor=white)](https://tailwindcss.com)
[![PHP](https://img.shields.io/badge/PHP-8.4+-777BB4?style=flat-square&logo=php&logoColor=white)](https://php.net)

[**Visiter l'application**](https://spendly.axelraboit.fr) — spendly.axelraboit.fr

</div>

---

## Presentation

Spendly est une application web de gestion financiere personnelle qui vous permet de suivre vos depenses, gerer plusieurs portefeuilles, definir des objectifs d'epargne et analyser vos habitudes financieres mois par mois.

Concu avec une interface sombre moderne, Spendly centralise toutes vos finances en un seul endroit.

---

## Fonctionnalites

- **Tableau de bord** — Vue d'ensemble de vos finances du mois en cours : depenses, remboursements, evolution mensuelle, et derniere transaction enregistree
- **Vue globale** — Synthese multi-portefeuilles avec graphiques de revenus/depenses et repartition par categorie
- **Portefeuilles** — Gestion de plusieurs comptes independants (compte courant, livret, assurance vie…) avec suivi des objectifs budgetaires
- **Transactions** — Liste complete et filtrable de toutes vos transactions, organisees par categorie et par type (depense, revenu, virement)
- **Transactions automatiques** — Gestion des abonnements et transactions recurrentes (streaming, salle de sport, mutuelle…)
- **Objectifs d'epargne** — Suivi de la progression vers vos objectifs financiers avec indicateurs visuels
- **Statistiques** — Analyse graphique de vos habitudes de depenses sur la duree avec projections et evolution multi-mois
- **Categories** — Gestion personnalisee des categories de depenses et de revenus
- **Notes** — Prise de notes enrichie (Markdown) associee a vos finances
- **Export** — Export de vos donnees en Excel
- **Auto-configuration** — Parametrage personnalise des regles de gestion

---

## Apercu

### Tableau de bord

![Tableau de bord](docs/screenshots/tableau-de-bord.png)

> Vue d'ensemble du mois : depenses totales, nombre de remboursements, evolution du budget, derniere transaction et top categories.

---

### Vue globale

![Vue globale](docs/screenshots/vue-globale.png)

> Synthese de tous vos portefeuilles avec graphiques de revenus/depenses par mois et donut de repartition par categorie.

---

### Portefeuille

![Portefeuille](docs/screenshots/portefeuille.png)

> Detail d'un portefeuille : solde, objectifs en cours avec progression, et recapitulatif des transactions du mois.

---

### Transactions

![Transactions](docs/screenshots/transactions.png)

> Detail des transactions d'un portefeuille, par categorie, avec types, montants et statuts.

---

### Liste des transactions

![Liste des transactions](docs/screenshots/transactions-liste.png)

> Vue complete et filtrable de toutes les transactions (categorie, portefeuille, mois).

---

### Transactions automatiques

![Transactions automatiques](docs/screenshots/automatiques.png)

> Gestion des abonnements et paiements recurrents : streaming, sport, mutuelle, salaire…

---

### Objectifs d'epargne

![Objectifs d'epargne](docs/screenshots/objectifs.png)

> Suivi visuel de la progression vers chaque objectif financier (voyage, fonds d'urgence, immobilier…).

---

### Statistiques

![Statistiques](docs/screenshots/statistiques.png)

> Tableaux de bord analytiques : depenses par categorie, evolution sur 6 mois, projections et budget cible.

---

### Categories

![Categories](docs/screenshots/categories.png)

> Gestion des categories personnalisees avec sous-categories et types (depense / revenu).

---

### Notes

![Notes](docs/screenshots/notes.png)

> Editeur de notes Markdown pour accompagner vos objectifs et reflexions financieres.

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

### Prerequis

- PHP >= 8.4
- Composer >= 2
- Node.js >= 20
- PostgreSQL

### Mise en place

```bash
# Cloner le depot
git clone https://github.com/AxelRaboit/spendly.git
cd spendly

# Installer toutes les dependances (composer + tools + pnpm)
make install

# Copier le fichier d'environnement et configurer
cp .env.example .env
php artisan key:generate
php artisan migrate
```

### Demarrage en developpement

**Demarrer le mailer (Mailpit via Docker) :**

```bash
docker compose up -d
```

**Lancer les serveurs de developpement :**

```bash
make dev
```

Lance en parallele : le serveur PHP, la queue, les logs Pail et Vite.

---

## Licence

MIT
