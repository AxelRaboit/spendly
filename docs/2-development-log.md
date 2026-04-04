# Journal de développement Spendly

## 2026-04-04

### Installation de Laravel Breeze

```bash
composer require laravel/breeze --dev
php artisan breeze:install vue --no-interaction
```

**Installé avec Vue.js** (au lieu de Blade). Breeze supporte plusieurs stacks :
- `blade` — templates serveur Blade
- `react` — composants React
- `vue` — composants Vue (choisi pour ce projet)

**Breeze fournit :**
- Routes d'authentification (login, register, logout, password reset, email verification)
- Controllers d'auth (`AuthenticatedSessionController`, `RegisteredUserController`, etc.)
- Templates Blade (`auth/login.blade.php`, `auth/register.blade.php`, etc.)
- Frontend compilé avec Vite (159 packages npm ajoutés)

Aucune nouvelle migration à lancer — Breeze utilise la table `users` existante.

### Création des modèles

```bash
php artisan make:model Category -m
php artisan make:model Transaction -m
```

Créé 2 modèles + leurs migrations.

**Relations prévues :**
- User → hasMany Categories
- User → hasMany Transactions
- Transaction → belongsTo Category

### Définition des migrations et relations

**Migrations :**
- `Category` : `name`, `user_id` (FK), timestamps
- `Transaction` : `user_id` (FK), `category_id` (FK), `amount`, `description`, `date`, timestamps

**Relations Eloquent :**
- `Category` : belongsTo User, hasMany Transactions
- `Transaction` : belongsTo User, belongsTo Category
- `User` : hasMany Categories, hasMany Transactions

Migrations exécutées :
```bash
php artisan migrate
```

Tables créées : `categories`, `transactions`.

**Prochaines étapes :**
- Implémenter les CRUDs (controllers, routes, vues)
