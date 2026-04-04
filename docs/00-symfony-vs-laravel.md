# Symfony vs Laravel — Paradigmes et Commandes

## Migrations

### Philosophie

**Symfony (Doctrine)** : Auto-génération intelligente avec `doctrine:migrations:diff`. Une fois en production, les migrations sont figées.

**Laravel** : Pas d'auto-génération. Tu écris les migrations à la main. Une fois exécutée, une migration ne doit jamais être modifiée.

### Commandes clés

| Symfony | Laravel | Équivalent |
|---------|---------|-----------|
| `make:entity` | `make:model` | Crée un modèle |
| `make:entity --regenerate` | N/A | Aucun équivalent — écris à la main |
| `doctrine:migrations:diff` | N/A | Aucun équivalent — écris à la main |
| `doctrine:migrations:migrate` | `migrate` | Exécute les migrations |
| `doctrine:fixtures:load` | `db:seed` | Charge les seeders |

### En développement

Pour Laravel en phase active de dev, utilise `php artisan migrate:refresh` sans crainte pour replayer toutes les migrations à zéro. C'est ton environnement de test, modifie les migrations tant que tu veux.

Une fois ta structure stable, gèle les migrations : chaque changement futur = nouvelle migration.

---

## Création d'entités avec relations

### Symfony

```bash
php bin/console make:entity User
# Questions interactives : fields, relations
```

Une seule commande génère l'entité **ET** la migration.

```bash
php bin/console doctrine:migrations:diff
# Génère la migration automatiquement
```

### Laravel

```bash
php artisan make:model Category -m
# Crée le modèle + une migration vide
```

Tu dois écrire **à la main** :
1. Les champs dans la migration
2. Les relations dans le modèle

```php
// app/Models/Category.php
public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}
```

**Symfony = plus automatisé**, **Laravel = plus manuel**.

### Rôle de la migration vs Model

**Symfony :**
- Tu définis l'Entity
- Doctrine génère la migration
- Flux : Entity → Migration

**Laravel :**
- La migration crée la structure brute de la table (colonnes, clés étrangères)
- Le Model définit comment interagir avec la table (relations, logique métier)
- Ils marchent en parallèle — tu dois les garder synchronisés

Exemple : si tu ajoutes une colonne `description` dans la migration, il faut aussi l'ajouter dans `$fillable` du Model pour qu'il soit assignable en masse.

**Analogie Symfony :** C'est similaire à ajouter un champ dans une Entity et le rajouter dans son DTO (synchronisation manuelle). Mais la raison diffère :
- Symfony/DTO : mapping de données (optionnel)
- Laravel/$fillable : **sécurité** contre l'assignement en masse malveillant (obligatoire)

---

## ORM et Modèles

### Doctrine (Symfony)

```php
// config/doctrine/User.orm.yaml
App\Entity\User:
  type: entity
  table: user
  fields:
    name:
      type: string
      length: 255
  oneToMany:
    transactions:
      targetEntity: Transaction
      mappedBy: user
```

### Eloquent (Laravel)

```php
// app/Models/User.php
class User extends Model
{
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
```

**Différence clé** : Eloquent = relations définies directement dans le modèle, pas de fichier YAML séparé.

---

## Structure du projet

| Symfony | Laravel |
|---------|---------|
| `src/Entity/` | `app/Models/` |
| `src/Repository/` | `app/Models/` (pas de repo explicite) |
| `src/Controller/` | `app/Http/Controllers/` |
| `config/` | `config/` |
| `migrations/` | `database/migrations/` |

---

## Seeders

| Symfony | Laravel |
|---------|---------|
| `src/DataFixtures/` (classes PHP) | `database/seeders/` (classes PHP) |
| `doctrine:fixtures:load` | `db:seed` |

---

## Authentification & Scaffolding

### Laravel Breeze

```bash
php artisan breeze:install blade
```

**`blade` est l'option pour le type de template** (pas une installation de Blade). Breeze supporte :
- `blade` — templates serveur Blade
- `react` — composants React
- `vue` — composants Vue

Fournit d'un coup :
- Routes d'auth (login, register, logout, password reset)
- Controllers prêts à l'emploi
- Templates (avec ton choix de stack)
- Frontend compilé (Vite)

### Symfony

**Il n'y a pas d'équivalent direct.**

Ton workflow Symfony :
1. **FOSUserBundle** (deprecated) ou faire à la main
2. **Symfony Security** : juste l'authentification (composant)
3. **Toi-même** : créer les Controllers, FormTypes, Templates

Symfony = composants découplés. Tu dois assembler. Laravel = scaffolding clé en main.

---

## Sessions, Cache, Queue

**Symfony** : Redis, filesystem ou database — c'est optionnel et tu configures.

**Laravel** : Batteries included. Les tables `cache` et `jobs` sont créées par défaut. C'est la philosophie "zero config" de Laravel.

En Symfony c'est une entité que tu crées. En Laravel c'est une table livrée avec le framework.
