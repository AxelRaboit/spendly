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

**Prochaines étapes :**
- Créer modèles `Category` et `Transaction`
- Implémenter les CRUDs
