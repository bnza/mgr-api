# MEDGREENREV API

## Installation

Clone the repository

```shell
git clone https://github.com/bnza/erc-api.git
cd erc-api
```

Clone the ```doctrine-postgis``` repository

```shell
mkdir api/packages/bnza
git clone https://github.com/bnza/doctrine-postgis.git api/packages/doctrine-postgis
```

### Media directory

Create the php's container media dir with occurring permissions in the Docker's host:

First retrieve PHP's service www-data ids with ```id username``` which will return something like
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

### Geoserver
Deploy Geoserver container

In docker `.env` set `USER_UID` and `USER_GID` to your user id and group id respectively.

Uncomment the `GEOSERVER_ADMIN_USER` and `GEOSERVER_ADMIN_PASSWORD` properties in `docker compose.yml`.
```yaml
services:
  geoserver:
      environment:
          #      - GEOSERVER_ADMIN_USER=${GEOSERVER_ADMIN_USER:-geoserver}
          #      - GEOSERVER_ADMIN_PASSWORD=${GEOSERVER_ADMIN_PASSWORD:-geoserver}
          - INSTALL_EXTENSIONS=true
          - STABLE_EXTENSIONS=wps
          - SKIP_DEMO_DATA=true
```
Then run the container once in order to generate `docker/geoserver/data/security/usergroup/default/users.xml`:
```shell
docker compose up geoserver
```
Comment again the properties and restart the container
```shell
docker compose restart geoserver
```
 
### Web Server
Deploy web server container

```shell
docker compose up nginx
```

### Final steps
Once all the containers are set up and running, you can stop them and restart detached:
```shell
docke compose dows
docker compose up -d
```

## Development

### Setup Git Hooks

To ensure automated code style checks before commits, please set up the Git hooks locally by running:

Run this on your host machine (not in Docker):

```bash
./deploy/git/setup-git-hooks.sh
```
