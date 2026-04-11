# spendly-worker — systemd service setup

> **Status**: actif en production.

Ce document décrit la configuration du service systemd `spendly-worker` sur le serveur de production,
ainsi qu'une comparaison avec le setup équivalent sur Nimbus (Symfony Messenger).

---

## Configuration en production

Fichier : `/etc/systemd/system/spendly-worker.service`

```ini
[Unit]
Description=Spendly Worker
After=network.target

[Service]
User=www-data
Group=www-data
WorkingDirectory=/var/www/spendly
ExecStart=/usr/bin/php artisan queue:work --sleep=3 --tries=3 --max-time=3600
Restart=always

[Install]
WantedBy=multi-user.target
```

---

## Comparaison avec Nimbus (Symfony Messenger)

Fichier : `/etc/systemd/system/nimbus-worker.service`

```ini
[Unit]
Description=Nimbus Messenger Worker
After=network.target

[Service]
User=www-data
Group=www-data
WorkingDirectory=/var/www/nimbus
ExecStart=/usr/bin/php bin/console messenger:consume async scheduler_main --time-limit=3600 --memory-limit=512M
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

### Différences clés

| Aspect | Spendly (Laravel Queue) | Nimbus (Symfony Messenger) |
|---|---|---|
| **Commande** | `artisan queue:work` | `bin/console messenger:consume` |
| **Transport** | Queue Laravel (database/redis) | Messenger async + scheduler_main |
| **Scheduler intégré** | Non (via `schedule:run` séparé) | Oui (`scheduler_main`) |
| **Limite mémoire** | Non configurée | `--memory-limit=512M` |
| **RestartSec** | Non configuré | 5 secondes |
| **Tentatives** | `--tries=3` | Géré dans le message handler |
| **Sleep idle** | `--sleep=3` (3s entre les polls) | Event-driven (pas de polling) |

---

## Mise en place du service

```bash
# Créer le fichier de service
sudo nano /etc/systemd/system/spendly-worker.service

# Activer et démarrer
sudo systemctl daemon-reload
sudo systemctl enable spendly-worker
sudo systemctl start spendly-worker
sudo systemctl status spendly-worker
```

---

## Autoriser le restart sans mot de passe (make deploy-prod)

Ajouter dans `/etc/sudoers.d/spendly` (remplacer `axel` par l'utilisateur de déploiement) :

```
axel ALL=(ALL) NOPASSWD: /bin/systemctl start spendly-worker
axel ALL=(ALL) NOPASSWD: /bin/systemctl stop spendly-worker
axel ALL=(ALL) NOPASSWD: /bin/systemctl restart spendly-worker
axel ALL=(ALL) NOPASSWD: /bin/systemctl reload php8.3-fpm
axel ALL=(ALL) NOPASSWD: /var/www/spendly/setup-perms.sh
```

---

## Commandes utiles

```bash
# Via Makefile
make queue-status    # statut du service
make queue-start     # démarrer le worker
make queue-stop      # arrêter le worker
make queue-restart   # redémarrer le worker
make queue-logs      # 50 dernières lignes de logs

# Directement
sudo journalctl -u spendly-worker -f    # suivre les logs en direct
sudo systemctl status spendly-worker
```
