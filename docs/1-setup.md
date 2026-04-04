# Setup du projet Spendly

## Stack

- PHP 8.4
- Laravel 13
- PostgreSQL (via pgAdmin)

## Étapes réalisées

### 1. Création du projet

```bash
cd /home/axel/Documents/dev/personal
composer create-project laravel/laravel spendly
```

### 2. Configuration PostgreSQL

Modification du `.env` :

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=spendly_dev
DB_USERNAME=axel
DB_PASSWORD=
```

### 3. Ajout au .pgpass

Ajout des lignes suivantes dans `~/.pgpass` pour éviter la saisie du mot de passe :

```
127.0.0.1:5432:spendly_dev:axel:XXXXXX
localhost:5432:spendly_dev:axel:XXXXXX
```

### 4. Création de la base de données

Base de données `spendly_dev` créée via pgAdmin, owner `axel`.

### 5. Migrations initiales

```bash
php artisan migrate
```

Tables créées par Laravel par défaut : `users`, `cache`, `jobs`.

Résultat :
```
Creating migration table .............. 20.39ms DONE
create_users_table .................... 21.77ms DONE
create_cache_table .................... 11.42ms DONE
create_jobs_table ..................... 17.89ms DONE
```

