### MEDGREENREV API

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

Copy root directory ```.env.dist``` to ```.env``` and fill in the correct information.

Generate the ```APP_SECRET``` (with e.g. [coderstoolbox](https://coderstoolbox.online/toolbox/generate-symfony-secret))
and set it in ```api/.env.prod.local```

```shell
APP_SECRET=mysecret
```

Deploy database container

```shell
docker-compose up database
```

Build and deploy php container

```shell
docker-compose build php
```

Set environment variable in  ```api/.env.prod.local```

```
JWT_PASSPHRASE=!ChangeMe!
```

Generate JWT key pairs

```shell
docker-compose run php bin/console lexik:jwt:generate-keypair
```

Deploy web server container

```shell
docker-compose up nginx
```

## Setup Git Hooks

Run this on your host machine (not in Docker):

```bash
./scripts/setup-git-hooks.sh
