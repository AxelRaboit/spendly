# Tests — Spendly

## Lancer les tests

```bash
make test          # tous les tests
make test-feature  # tests feature uniquement
make test-unit     # tests unit uniquement
```

## Configuration

- Base de données : `spendly_test` (PostgreSQL)
- Driver de session : `array`
- Cache : `array`
- Credentials dans `.env.testing` (ignoré par git)
- `RefreshDatabase` — base remise à zéro entre chaque test

---

## Suite Feature

### CategoryTest — 11 tests

#### Authorization
| Test | Description |
|---|---|
| `guest_cannot_access_categories` | Un visiteur non connecté est redirigé vers `/login` |
| `user_cannot_view_another_users_category` | Un user ne peut pas accéder à l'édition d'une catégorie qui ne lui appartient pas |
| `user_cannot_update_another_users_category` | Un user ne peut pas modifier la catégorie d'un autre |
| `user_cannot_delete_another_users_category` | Un user ne peut pas supprimer la catégorie d'un autre |

#### Index
| Test | Description |
|---|---|
| `user_sees_only_their_categories` | La liste ne contient que les catégories du user connecté |

#### CRUD
| Test | Description |
|---|---|
| `user_can_create_a_category` | Création d'une catégorie + vérification en base |
| `category_name_is_required` | Le champ `name` est obligatoire |
| `user_can_update_their_category` | Mise à jour du nom + vérification en base |
| `user_can_delete_their_category` | Suppression + vérification absence en base |

#### Filtres
| Test | Description |
|---|---|
| `search_filter_returns_matching_categories` | La recherche filtre correctement par nom |
| `search_filter_is_accent_insensitive` | `sante` trouve `Santé` (PostgreSQL `unaccent`) |

---

### TransactionTest — 13 tests

#### Authorization
| Test | Description |
|---|---|
| `guest_cannot_access_transactions` | Un visiteur non connecté est redirigé vers `/login` |
| `user_cannot_edit_another_users_transaction` | Un user ne peut pas accéder à l'édition d'une transaction qui ne lui appartient pas |
| `user_cannot_update_another_users_transaction` | Un user ne peut pas modifier la transaction d'un autre |
| `user_cannot_delete_another_users_transaction` | Un user ne peut pas supprimer la transaction d'un autre |
| `user_cannot_use_another_users_category` | Un user ne peut pas créer une transaction avec la catégorie d'un autre |

#### Index
| Test | Description |
|---|---|
| `user_sees_only_their_transactions` | La liste ne contient que les transactions du user connecté |

#### CRUD
| Test | Description |
|---|---|
| `user_can_create_a_transaction` | Création d'une transaction + vérification en base |
| `transaction_requires_amount_and_date` | Les champs `amount` et `date` sont obligatoires |
| `transaction_amount_must_be_positive` | `amount` doit être > 0 |
| `user_can_update_their_transaction` | Mise à jour du montant + vérification en base |
| `user_can_delete_their_transaction` | Suppression + vérification absence en base |

#### Filtres
| Test | Description |
|---|---|
| `search_filter_returns_matching_transactions` | La recherche filtre correctement par description |
| `category_filter_returns_matching_transactions` | Le filtre par catégorie retourne les bonnes transactions |

---

### Auth (Breeze) — 19 tests

Tests fournis par Laravel Breeze, non modifiés.

| Fichier | Tests |
|---|---|
| `AuthenticationTest` | Login, mauvais mot de passe, logout |
| `EmailVerificationTest` | Affichage, vérification valide/invalide |
| `PasswordConfirmationTest` | Confirmation mot de passe |
| `PasswordResetTest` | Demande de reset, écran reset, reset avec token valide |
| `PasswordUpdateTest` | Mise à jour mot de passe, mot de passe incorrect |
| `RegistrationTest` | Affichage formulaire, inscription |

### ProfileTest (Breeze) — 4 tests

Tests fournis par Laravel Breeze, non modifiés.

| Test | Description |
|---|---|
| `profile_page_is_displayed` | Page profil accessible |
| `profile_information_can_be_updated` | Mise à jour nom/email |
| `email_verification_status_is_unchanged_when_email_unchanged` | Vérification email non réinitialisée si email inchangé |
| `user_can_delete_their_account` | Suppression de compte |

---

## Résumé

| Suite | Tests | Assertions |
|---|---|---|
| CategoryTest | 11 | ~40 |
| TransactionTest | 13 | ~50 |
| Auth + Profile (Breeze) | 24 | ~70 |
| Unit | 1 | 1 |
| **Total** | **49** | **166** |

## Ce qui n'est pas encore testé

- `DashboardController` — données agrégées
- `StatisticsController` — requêtes SQL complexes
- `QueryFilter` — logique de filtrage en isolation (tests unitaires)
- `CategoryService` / `TransactionService` — logique métier
