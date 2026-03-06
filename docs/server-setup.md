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
