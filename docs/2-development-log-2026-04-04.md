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
