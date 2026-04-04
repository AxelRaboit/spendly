# Roadmap du projet Spendly

## Étapes à faire

- [ ] Tests (PHPUnit)
- [ ] Pagination sur les listes
- [ ] Filtres / recherche sur les transactions
- [ ] Graphiques / statistiques

## En cours

(aucun)

## Fait

- [x] Création du projet Laravel 13
- [x] Configuration PostgreSQL
- [x] Setup du `.pgpass`
- [x] Création de la base de données `spendly_dev`
- [x] Exécution des migrations initiales
- [x] Authentification avec Laravel Breeze (stack Vue)
- [x] Modèles `Category` et `Transaction` avec migrations
- [x] CRUD Categories (Controller, Policy, Service, Form Requests, Vues Vue)
- [x] CRUD Transactions (Controller, Policy, Service, Form Requests, Vues Vue)
- [x] Dark mode forcé
- [x] Traduction française (laravel-lang)
- [x] Datepicker (vue3-datepicker + date-fns v3)
- [x] Composants UI globaux (boutons, inputs, select, datepicker)
- [x] Composables (useConfirmDelete, useCategoryForm, useTransactionForm)
- [x] Modal de confirmation de suppression
- [x] Dashboard avec statistiques et dernières dépenses (DashboardController)
- [x] Audit complet du projet (sécurité, return types, conventions Laravel 13)
- [x] Toolchain qualité (PHPStan + Larastan, Pint, Rector, ESLint, Makefile)
- [x] Séparation composants Breeze / composants custom (`Components/` vs `components/`)
- [x] Composants EditButton et DeleteButton
