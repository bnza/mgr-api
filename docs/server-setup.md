## Server setup

### Create a new admin user

```shell
sudo useradd -m -s /bin/bash -c "Full Name" username
sudo passwd username
sudo usermod -aG sudo username
```

### Public key authentication

On the client side, generate a key pair and add the public key to the server.
Replace `server_name` with the name of the server.
Replace `user` with the name of the user.

```shell
 ssh-keygen -t ed25519 -f ~/.ssh/server_name.pem -C "username@server_name"
 ssh-copy-id -i ~/.ssh/server_name.pem.pub username@server_name
```

Then remove password authentication for the new user.

```shell
sudo vi /etc/ssh/sshd_config
```

```text
# /etc/ssh/sshd_config
# For specific user 'username', require ONLY key authentication
Match User username
    PasswordAuthentication no
    AuthenticationMethods publickey
```

Keeping the old SSH connection open execute:

```shell
# Check syntax
sudo sshd -t

# If syntax is OK, restart SSH
sudo systemctl restart sshd
```

Then from the client test the connection:

```shell
ssh -i ~/.ssh/server_name.pem username@server_name
```

### Sudo without password

```shell
sudo visudo
```

```text
# /etc/sudoers
username ALL=(ALL) NOPASSWD:ALL
```

Then login as `username` and execute:

```shell
sudo whoami 
```

it should return `root`.

### Install Docker

Use the [convenience script](https://docs.docker.com/engine/install/ubuntu/#install-using-the-convenience-script):

```shell
curl -fsSL https://get.docker.com -o get-docker.sh
```

Then check the steps and run it:

```shell
sudo sh ./get-docker.sh --dry-run
sudo sh ./get-docker.sh
```

Add the user to the docker group:

```shell
sudo usermod -aG docker $USER
```

### Docker and containerd data directory

By default Docker and containerd store data in `/var/lib/docker` and `/var/lib/containerd`.
If the `/var` partition is too small for container images and build cache, move the data directories
to a larger mount point (e.g. `/mnt/data`, see [Media block device mount](#media-block-device-mount-and-file-system)).

Stop the services:

```shell
sudo systemctl stop docker
sudo systemctl stop containerd
```

Configure Docker by creating or editing `/etc/docker/daemon.json`:

```json
{
  "data-root": "/mnt/data/docker"
}
```

Configure containerd by editing `/etc/containerd/config.toml` and setting the `root` key:

```toml
root = "/mnt/data/containerd"
```

If the file doesn't exist or is minimal, check the current value with:

```shell
sudo containerd config dump | grep "root ="
```

Start the services:

```shell
sudo systemctl start containerd
sudo systemctl start docker
```

Verify the new paths:

```shell
docker info | grep "Docker Root Dir"
sudo containerd config dump | grep "root ="
```

### Firewall configuration

Open http and https ports:

```shell
sudo ufw allow http
sudo ufw allow https
```

## Media block device mount and file system

Check the block devices:

```shell
lsblk
```

Create a new file system:

```shell
sudo mkfs.ext4 /dev/sdX
```

Create the mount directory:

```shell
sudo mkdir /mnt/data
```

Find the UUID of the block device:

```shell
sudo blkid /dev/sdX
```

Make a backup of the fstab file:

```shell
sudo cp /etc/fstab /etc/fstab.backup-$(date +%Y%m%d)
```

and add the following line to the file:

```text
/dev/disk/by-uuid/yuor-uuid-here /mnt/data ext4 defaults 0 2
```

test the fstab file:

```shell
mount -a
```

if everything is ok, then:

```shell
sudo systemctl daemon-reload
mount -a
```

### Directory setup

```shell
sudo mkdir -p /mnt/data/{sw,volumes/static}
```

set the permissions:

```shell
sudo chown -R :$USER /mnt/data/sw
sudo chmod g+w /mnt/data/sw
```

## SSL Certificate Setup (Certbot)

### Prerequisites

Make sure `NGINX_HOST` and `CERTBOT_EMAIL` are set in your `.env` file.
The certbot scripts run inside the certbot container via `docker compose run`.

### First-time initialization

Generate a temporary self-signed certificate so that nginx can start with the SSL
configuration:

```shell
docker compose run --rm certbot /opt/certbot-scripts/init-certs.sh
```

Then start the production stack:

```shell
docker compose up -d
```

Once nginx is running, obtain a real Let's Encrypt certificate and reload nginx:

```shell
docker compose run --rm certbot /opt/certbot-scripts/renew-certs.sh
docker compose exec nginx nginx -s reload
```

### Certificate renewal

The same renewal command can be used to renew certificates manually:

```shell
docker compose run --rm certbot /opt/certbot-scripts/renew-certs.sh
docker compose exec nginx nginx -s reload
```

You can automate this with a cron job:

```shell
# Add to crontab (runs once every two months, on the 1st at 03:00)
0 3 1 */2 * cd /path/to/mgr-api && docker compose run --rm certbot /opt/certbot-scripts/renew-certs.sh && docker compose exec nginx nginx -s reload
```
