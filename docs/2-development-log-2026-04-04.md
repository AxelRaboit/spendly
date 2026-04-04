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

### Implémentation du Category CRUD

```bash
php artisan make:controller CategoryController --model=Category
php artisan make:policy CategoryPolicy --model=Category
```

**CategoryController :**
- `index()` — Liste les catégories de l'utilisateur connecté
- `create()` — Affiche le formulaire de création
- `store()` — Crée une catégorie (validation du nom)
- `edit()` — Affiche le formulaire d'édition
- `update()` — Met à jour une catégorie
- `destroy()` — Supprime une catégorie

**CategoryPolicy :**
- Vérifie que l'utilisateur ne peut voir/éditer/supprimer que ses propres catégories
- Basé sur `user_id`

**Routes :**
- Ajout d'une resource route : `Route::resource('categories', CategoryController::class)`
- Routes générées : GET/POST /categories, GET/POST /categories/{id}/edit, PATCH/DELETE /categories/{id}

**Vues Vue.js :**
- `Categories/Index.vue` — Liste avec boutons Edit/Delete
- `Categories/Create.vue` — Formulaire de création
- `Categories/Edit.vue` — Formulaire d'édition

Utilise Inertia.js pour communiquer entre backend et frontend.

### Architecture : Service Classes pour gros projet

Traiter le projet comme un **gros projet** en utilisant des **Service classes** (approche 3 en Laravel, équivalent des Services Symfony).

**Créé 1 Service :**
- `app/Services/CategoryService.php` — contient la logique métier (create, update, delete)

**Fonctionnalités du service :**
- Try/catch pour gestion des erreurs
- Logging de chaque action (info et error)
- Facilement extensible pour ajouter des events, notifications, transactions

**Pattern utilisé :** Dependency injection dans les méthodes du controller
```php
public function store(Request $request, CategoryService $categoryService)
{
    $categoryService->create(auth()->user(), $validated['name']);
}

public function update(Request $request, Category $category, CategoryService $categoryService)
{
    $categoryService->update($category, $validated['name']);
}

public function destroy(Category $category, CategoryService $categoryService)
{
    $categoryService->delete($category);
}
```

**Avantages :**
- Logique métier isolée dans une couche dédiée
- Gestion d'erreurs et logging centralisés
- Testable et réutilisable (API, commands, jobs, etc.)
- Controller léger : juste validation + authorization
- Extensible pour ajouter du logging, events, transactions

### Form Requests (équivalent du FormType Symfony pour la validation)

```bash
php artisan make:request StoreCategoryRequest
php artisan make:request UpdateCategoryRequest
```

**Pourquoi ?** En gros projet, on extrait la validation du controller dans des **Form Request classes** dédiées — équivalent du FormType Symfony (partie validation uniquement).

| Symfony | Laravel |
|---------|---------|
| `FormType` (validation + rendu) | `Form Request` (validation) + Vue component (rendu) |

**`StoreCategoryRequest` :**
- `authorize()` → `true` (l'authorization est gérée par la Policy)
- `rules()` → règles de validation

**Dans le controller, avant :**
```php
public function store(Request $request, CategoryService $categoryService)
{
    $validated = $request->validate(['name' => 'required|string|max:255']);
}
```

**Après :**
```php
public function store(StoreCategoryRequest $request, CategoryService $categoryService)
{
    $categoryService->create(auth()->user(), $request->validated()['name']);
}
```

### Authorization dans les Form Requests

Déplacé l'authorization depuis le controller vers les Form Requests — équivalent de `#[IsGranted]` en Symfony.

Créé `DestroyCategoryRequest` pour gérer l'authorization de la suppression.

```php
// UpdateCategoryRequest.php
public function authorize(): bool
{
    return $this->user()->can('update', $this->route('category'));
}

// DestroyCategoryRequest.php
public function authorize(): bool
{
    return $this->user()->can('delete', $this->route('category'));
}
```

**Résultat dans le controller :** plus de `$this->authorize()` dans `update()` et `destroy()`.

**Note :** Pour les méthodes GET (`show()`, `edit()`), `$this->authorize()` reste dans le controller — pas de Form Request pour les requêtes GET.

| Symfony | Laravel |
|---------|---------|
| `#[IsGranted('EDIT', subject: 'category')]` | `authorize()` dans le Form Request |

**Prochaines étapes :**
- Implémenter le Transaction CRUD avec Service + Form Requests aussi
- Tester l'application

### Implémentation du Transaction CRUD

Même architecture que Category (gros projet) :

```bash
php artisan make:controller TransactionController --model=Transaction
php artisan make:policy TransactionPolicy --model=Transaction
php artisan make:request StoreTransactionRequest
php artisan make:request UpdateTransactionRequest
php artisan make:request DestroyTransactionRequest
```

**TransactionService** (`app/Services/TransactionService.php`) :
- `create()`, `update()`, `delete()`
- Try/catch + logging sur chaque action

**TransactionPolicy** :
- `view()`, `update()`, `delete()` basés sur `user_id`

**Form Requests :**
- `StoreTransactionRequest` — validation + `authorize: true`
- `UpdateTransactionRequest` — validation + authorization via Policy
- `DestroyTransactionRequest` — authorization via Policy uniquement

**Champs validés :**
- `category_id` : required, exists dans categories
- `amount` : required, numeric, min:0.01
- `description` : nullable, string
- `date` : required, date

**Routes :**
- `Route::resource('transactions', TransactionController::class)`

**Vues Vue.js :**
- `Transactions/Index.vue` — liste avec date, description, catégorie, montant
- `Transactions/Create.vue` — formulaire avec select catégorie
- `Transactions/Edit.vue` — formulaire pré-rempli

**Prochaines étapes :**
- Tester l'application en conditions réelles
- Ajouter les seeders pour les données de test

### Traduction en français

**Vues frontend :** Tous les textes des vues Vue.js traduits manuellement en français (login, register, categories, transactions).

**Messages d'erreur Laravel :** Installation du package officiel de traductions :

```bash
composer require laravel-lang/lang --dev
php artisan lang:add fr
```

Fichiers générés dans `lang/fr/` :
- `auth.php` — messages d'authentification
- `validation.php` — messages de validation
- `passwords.php` — messages de mot de passe
- `pagination.php` — messages de pagination

Locale configurée dans `.env` :
```env
APP_LOCALE=fr
APP_FALLBACK_LOCALE=fr
APP_FAKER_LOCALE=fr_FR
```

### Redirection de la page d'accueil

Remplacé la page de bienvenue Laravel par une redirection intelligente :
- Connecté → `/dashboard`
- Non connecté → `/login`

```php
Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});
```

### Amélioration de la page Login

- Ajout du lien **"No account yet? Register"** manquant
- Réorganisation du layout :
  - "Remember me" + "Forgot your password?" sur la même ligne
  - Bouton "Log in" en pleine largeur
  - Séparateur **— or —**
  - Lien Register centré en dessous

### Fix : suppression du middleware dans les constructeurs

En Laravel 11, `$this->middleware('auth')` dans le constructeur d'un controller n'est plus supporté — cela provoque une erreur 500.

**Supprimé le constructeur** dans `CategoryController` et `TransactionController`.

Le middleware `auth` est déjà appliqué au niveau des routes dans `web.php` :
```php
Route::middleware('auth')->group(function () {
    Route::resource('categories', CategoryController::class);
    Route::resource('transactions', TransactionController::class);
});
```

| Symfony | Laravel 11 |
|---------|------------|
| `#[IsGranted('IS_AUTHENTICATED')]` sur le controller | middleware `auth` sur le groupe de routes |

### Dark mode complet

Forcé le dark mode sur l'ensemble de l'application.

**Configuration :**
- `tailwind.config.js` : `darkMode: 'class'`
- `resources/views/app.blade.php` : `<html class="dark">`

**Layouts :**
- `AuthenticatedLayout.vue` : fond `bg-gray-950`, navbar `bg-gray-900`, header `bg-gray-900`
- `GuestLayout.vue` : fond `bg-gray-950`, carte `bg-gray-900`

**Composants Breeze corrigés (avaient des couleurs light-mode hardcodées) :**
- `Dropdown.vue` : fond `bg-white` → `bg-gray-800`
- `DropdownLink.vue` : `text-gray-700 hover:bg-gray-100` → `text-gray-300 hover:bg-gray-700`
- `NavLink.vue` : `text-gray-900` / `text-gray-500` → `text-white` / `text-gray-400`
- `ResponsiveNavLink.vue` : `bg-indigo-50` / `bg-gray-50` → `bg-gray-800`

**Pages :**
- Tous les titres `text-gray-800 dark:text-gray-200` → `text-gray-100`
- Tous les contenus `text-gray-900 dark:text-gray-100` → `text-gray-100`
- Tous les inputs/selects `border-gray-300 dark:bg-gray-700` → `border-gray-700 bg-gray-700 text-gray-100`

### Datepicker : vue3-datepicker

Après plusieurs tentatives infructueuses avec `@vuepic/vue-datepicker` (v9 et v12 — incompatible avec Inertia à cause du système de teleport), choix de **`vue3-datepicker`** + **`date-fns` v3**.

```bash
pnpm add vue3-datepicker date-fns@3
```

**Configuration :**
- Locale française via objet date-fns : `:locale="fr"` (pas une string)
- Format affiché : `dd/MM/yyyy`
- Valeur envoyée au backend : `YYYY-MM-DD` via un `watch` sur le ref date
- Styles dark mode via CSS variables dans `app.css` (`--vdp-bg-color`, `--vdp-selected-bg-color`, etc.)

**Composant `DateInput.vue`** créé pour encapsuler toute la logique.

### Composants réutilisables

Création de composants UI globaux dans `resources/js/Components/` :

| Composant | Rôle |
|---|---|
| `PrimaryButton.vue` | Bouton indigo principal |
| `SecondaryButton.vue` | Bouton gris secondaire |
| `DangerButton.vue` | Bouton rouge suppression |
| `TextInput.vue` | Input texte dark |
| `SelectInput.vue` | Select dark |
| `DateInput.vue` | Datepicker encapsulé |
| `InputLabel.vue` | Label `text-gray-300` |
| `InputError.vue` | Message d'erreur `text-red-400` |

**Enregistrement global** via un plugin dédié `resources/js/plugins/components.js` — les pages n'ont plus besoin d'importer ces composants.

### Composables

Création de `resources/js/composables/` — équivalent des Services côté frontend :

| Composable | Rôle |
|---|---|
| `useConfirmDelete.js` | Suppression avec confirmation + `router.delete(url)` |
| `useCategoryForm.js` | Logique create/edit catégorie |
| `useTransactionForm.js` | Logique create/edit transaction |

Les pages ne contiennent plus que le template + les imports nécessaires.

### Conventions Vue adoptées

- `<script setup>` toujours en haut, `<template>` en dessous
- `v-on:click` / `v-on:submit` à la place des raccourcis `@click` / `@submit`
- Pas de commentaires dans les templates
- Composants UI enregistrés globalement, composables importés localement

### Modal de confirmation de suppression

Remplacement du `confirm()` natif du navigateur par une modal custom.

**`ConfirmModal.vue`** — composant global avec :
- Backdrop semi-transparent avec fermeture au clic
- Icône d'avertissement rouge
- Boutons Annuler / Supprimer
- Transition d'apparition/disparition

**`useConfirmDelete.js`** mis à jour — expose `isOpen`, `message`, `confirmDelete`, `onConfirm`, `onCancel` via des `ref` réactifs au lieu d'un `confirm()` natif.

Utilisé dans `Categories/Index.vue` et `Transactions/Index.vue`.

### Audit complet du projet — corrections

#### Sécurité — Form Requests

- `StoreCategoryRequest` / `StoreTransactionRequest` : `authorize()` retournait `true` sans vérification — corrigé avec `$this->user() !== null`
- `StoreTransactionRequest` / `UpdateTransactionRequest` : la validation de `category_id` ne vérifiait pas l'appartenance à l'utilisateur — ajout de `Rule::exists('categories', 'id')->where('user_id', $this->user()->id)`

#### Modèles — cohérence avec Laravel 13

`Category` et `Transaction` utilisaient `protected $fillable = [...]` alors que `User` utilisait déjà l'attribut PHP 8 `#[Fillable]` (style Laravel 13).

```php
// Avant
protected $fillable = ['name', 'user_id'];

// Après
#[Fillable(['name', 'user_id'])]
class Category extends Model
```

#### Controllers — return types + $request->user()

Ajout des return types PHP sur toutes les méthodes (`Response`, `RedirectResponse`) et remplacement de `auth()->user()` par `$request->user()` (injection explicite, plus testable).

#### Controller de base — AuthorizesRequests

En Laravel 11, le `Controller` de base est vide. Le trait `AuthorizesRequests` (qui fournit `$this->authorize()`) n'est pas inclus par défaut — ajouté manuellement.

```php
abstract class Controller
{
    use AuthorizesRequests;
}
```

#### DashboardController

La logique du dashboard était directement dans une closure dans `web.php` — extrait dans `DashboardController::index()`.

#### Services — création directe via le modèle

`CategoryService::create()` et `TransactionService::create()` utilisaient `$user->categories()->create()` — PHPStan ne pouvait pas inférer le type de retour (`Model` au lieu de `Category`). Remplacé par `Category::create([..., 'user_id' => $user->id])`.

#### Frontend — titres de page

Ajout de `<Head title="..." />` sur toutes les pages Categories et Transactions.

#### useTransactionForm — onSuccess

Le `form.post('/transactions')` ne réinitialisait pas le formulaire après succès — ajout du callback `onSuccess: () => form.reset()`.

### Toolchain qualité de code

Mise en place des mêmes outils que le projet cloud, adaptés à Laravel.

#### Structure

```
tools/
├── phpstan/
│   ├── composer.json   → larastan/larastan
│   └── phpstan.neon
└── rector/
    ├── composer.json   → rector/rector
    └── rector.php
pint.json               → preset Laravel
eslint.config.js        → Prettier (JS) + Vue plugin (Vue)
.prettierrc
Makefile
```

#### PHPStan (Larastan)

- Niveau 5
- Analyse uniquement `app/`
- Exclut `app/Http/Controllers/Auth/` et `app/Http/Requests/Auth/` (code Breeze)
- `bootstrapFiles` pointe sur `vendor/autoload.php` du projet principal pour que Larastan puisse accéder aux classes Laravel

```bash
make stan
```

#### Pint

Laravel Pint (wrapper PHP CS Fixer officiel Laravel) — déjà en dépendance dev. Configuré avec le preset Laravel + règles supplémentaires (`declare_strict_types`, `no_useless_else`, etc.).

Exécuté via des chemins explicites pour ne toucher que notre code (pas les fichiers Breeze, config, migrations) :

```bash
make lint-php   # dry-run
make fix-php    # applique
```

#### Rector

Sets utilisés : `codeQuality`, `codingStyle`, `earlyReturn`, `deadCode` — cible PHP 8.3.

```bash
make rector     # dry-run
make fix-rector # applique
```

#### ESLint + Prettier

- Prettier pour les fichiers `.js` (composables, plugins)
- `eslint-plugin-vue` avec règles strictes pour les fichiers `.vue`
- `vue/v-on-style: longform` — enforce `v-on:` au lieu de `@`
- `vue/require-default-prop: off` — incompatible avec Inertia (props toujours fournies par le serveur)

```bash
make lint-js   # check
make fix-js    # applique
```

#### Makefile

Commandes disponibles :

| Commande | Rôle |
|---|---|
| `make install` | Composer + tools + pnpm |
| `make stan` | PHPStan |
| `make lint-php` / `make fix-php` | Pint |
| `make lint-js` / `make fix-js` | ESLint |
| `make rector` / `make fix-rector` | Rector |
| `make fix` | Tout corriger + PHPStan |

### Structure des composants Vue

Séparation entre composants Breeze et composants custom par convention de casse :

```
resources/js/
├── Components/     ← Breeze (jamais modifié directement)
└── components/     ← Notre code custom
    ├── ConfirmModal.vue
    ├── DateInput.vue
    └── SelectInput.vue
```

ESLint ignore `Components/` (majuscule) et cible `components/` (minuscule) — aucune liste à maintenir.

### Format de la date des transactions

Le cast `'date'` de Laravel sérialisait la date en ISO complet (`2026-04-04T00:00:00.000000Z`). Corrigé via le format dans le cast :

```php
'date' => 'date:d/m/Y',
```

La date est maintenant formatée côté serveur (`04/04/2026`) — aucun formatage nécessaire dans les templates Vue.
