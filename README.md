# MEDGREENREV API

## Installation

Clone the repository

```shell
git clone https://github.com/bnza/mgr-api.git
cd mgr-api
```

### Media directory

Create the php's container media dir with occurring permissions in the Docker's host:

First retrieve PHP's service www-data ids with ```id www-data``` which will return something like
```uid=82(www-data) gid=82(www-data) groups=82(www-data),82(www-data)```

Then

```shell
sudo mkdir /path/to/media
sudo chown -R 82:82 /path/to/media
```

Set the ```.env.prod.local``` ```WWW_STATIC_DIR``` key accordingly.

```shell
WWW_STATIC_DIR=/path/to/media
```

### Environment variables

Copy root directory ```.env.dist``` to ```.env``` and fill in the correct information.

Generate the ```APP_SECRET``` (with e.g. [coderstoolbox](https://coderstoolbox.online/toolbox/generate-symfony-secret))
and set it in ```api/.env.prod.local```

```dotenv
APP_SECRET=mysecret
```

### CORS

**File:** `app/config/packages/nelmio_cors.yaml`

```yaml 
nelmio_cors:
    defaults:
        origin_regex: false
        allow_origin: [ 'https://app.example.com', 'https://admin.example.com' ] 
```

OR
set ```CORS_ALLOW_ORIGIN``` in ```api/.env.prod.local```

## Deployment

### Database

Deploy database container

```shell
docker compose up database
```

### PHP

Build and deploy PHP container

```shell
docker compose build php
```

Set environment variable in  ```api/.env.prod.local```

```
JWT_PASSPHRASE=!ChangeMe!
```

Generate JWT key pairs

```shell
docker compose run php bin/console lexik:jwt:generate-keypair
```

### GeoServer

The GeoServer configuration (workspaces, datastores, styles, layer definitions, security config) is tracked in the
repository under `docker/geoserver/data/`. This allows configuration changes made in development to be deployed to
production via `git pull`.

However, certain security-sensitive files **must not** be committed to a public repository:

- `security/masterpw/default/passwd` — encrypted master password
- `security/geoserver.jceks` — Java keystore
- `security/masterpw.digest` — master password digest
- `security/usergroup/default/users.xml` — user accounts with hashed passwords

These files are listed in `docker/geoserver/data/.gitignore` and are therefore **missing** after a fresh
`git clone` on a new environment. Without them, GeoServer's `GeoServerSecurityManager` throws a
`FileNotFoundException` during startup, causing the webapp to fail while Tomcat keeps running (resulting in a 404).

To solve this, a custom `Dockerfile` (`docker/geoserver/Dockerfile`) extends the official GeoServer image with an
init entrypoint script (`docker/geoserver/init-security.sh`). This script runs **before** GeoServer starts and:

1. Checks if the bind-mounted data directory has a `security/` folder (i.e. tracked config exists).
2. If `masterpw/default/passwd` is missing, copies it from the image's bundled defaults.
3. If `usergroup/default/users.xml` is missing, copies it from the image's bundled defaults.
4. GeoServer then auto-generates `geoserver.jceks` and `masterpw.digest` on first startup.
5. Delegates to the original `/opt/startup.sh`, which in turn calls
   `handle_geoserver_admin_credentials.sh` → `update_credentials.sh` to set the admin username and
   password (hashed) from the `GEOSERVER_ADMIN_USER` and `GEOSERVER_ADMIN_PASSWORD` environment variables.

#### Deployment steps

1. In the docker `.env` file, set `USER_UID` and `USER_GID` to your host user's id and group id
   (used by `RUN_WITH_USER_UID`/`RUN_WITH_USER_GID` so GeoServer can write to the bind-mounted data directory):

   ```dotenv
   USER_UID=1000
   USER_GID=1000
   ```

2. Set the desired GeoServer admin credentials in `.env`:

   ```dotenv
   GEOSERVER_ADMIN_USER=admin
   GEOSERVER_ADMIN_PASSWORD=your_secure_password
   ```

3. Build and start the GeoServer container:

   ```shell
   docker compose build geoserver
   docker compose up geoserver
   ```

   On first start the init script will generate the missing security files and the startup chain will
   hash the admin password into `users.xml`. Subsequent restarts reuse the existing files.

### Web Server

Deploy web server container

```shell
docker compose up nginx
```

### Final steps

Once all the containers are set up and running, you can stop them and restart detached:

```shell
docker compose down
docker compose up -d
```

## Documentation

Detailed information on server setup can be found in the [docs/server-setup.md](./docs/server-setup.md) file.

## Development

### Setup Git Hooks

To ensure automated code style checks before commits, please set up the Git hooks locally by running:

Run this on your host machine (not in Docker):

```bash
./deploy/git/setup-git-hooks.sh
```
