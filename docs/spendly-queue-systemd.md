# spendly-queue — systemd service setup

> **Status**: not yet in use. To be set up when queue processing is needed in production.

This document describes how to create and enable the `spendly-queue` systemd service
on the production server. The `make deploy-prod` target expects this service to exist
in order to restart the queue worker after each deployment.

---

## 1. Create the service file

Create `/etc/systemd/system/spendly-queue.service`:

```ini
[Unit]
Description=Spendly Laravel Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
WorkingDirectory=/path/to/spendly
ExecStart=/usr/bin/php artisan queue:work --sleep=3 --tries=3 --max-time=3600
Restart=on-failure
RestartSec=5s
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
```

> Replace `/path/to/spendly` with the actual path to the application on the server.

---

## 2. Enable and start the service

```bash
sudo systemctl daemon-reload
sudo systemctl enable spendly-queue
sudo systemctl start spendly-queue
sudo systemctl status spendly-queue
```

---

## 3. Allow sudo without password (required for make deploy-prod)

Add the following to `/etc/sudoers.d/spendly` (replace `axel` with the deploy user):

```
axel ALL=(ALL) NOPASSWD: /bin/systemctl start spendly-queue
axel ALL=(ALL) NOPASSWD: /bin/systemctl stop spendly-queue
axel ALL=(ALL) NOPASSWD: /bin/systemctl restart spendly-queue
axel ALL=(ALL) NOPASSWD: /bin/systemctl reload php8.3-fpm
axel ALL=(ALL) NOPASSWD: /path/to/spendly/setup-perms.sh
```

---

## 4. Useful commands

```bash
# Via Makefile
make queue-status    # check service status
make queue-start     # start worker
make queue-stop      # stop worker
make queue-restart   # restart worker
make queue-logs      # show last 50 lines of logs

# Directly
sudo journalctl -u spendly-queue -f    # follow logs
sudo systemctl status spendly-queue
```
