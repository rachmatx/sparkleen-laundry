# Sparkleen Laundry - Deployment Guide

## Overview
Your application is containerized with Docker and ready for deployment. This guide covers deploying to a VPS with SSL via Let's Encrypt.

## Prerequisites
- VPS with Docker and Docker Compose installed
- Domain: `raachmatx.me` (already configured DNS)
- Port 80 and 443 accessible from the internet

## Step 1: Prepare Your Server

```bash
# SSH into your VPS
ssh root@your_vps_ip

# Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose
```

## Step 2: Clone Your Repository

```bash
cd /opt
git clone <your-repo-url> sparkleen
cd sparkleen
```

## Step 3: Configure Environment

```bash
# Copy production environment file
cp .env.production .env

# Edit with your values
nano .env
```

Update these critical variables:
```env
CI_ENVIRONMENT=production
app.baseURL=https://raachmatx.me/
database.default.password=YOUR_STRONG_PASSWORD
MYSQL_ROOT_PASSWORD=YOUR_STRONG_PASSWORD
DB_PASSWORD=YOUR_STRONG_PASSWORD
whatsapp.apiToken=YOUR_TOKEN_HERE
```

## Step 4: Update Nginx Configuration for SSL

Create `nginx-ssl.conf`:

```nginx
server {
    listen 80;
    server_name rachmatx.me;
    
    location /.well-known/acme-challenge/ {
        root /var/www/certbot;
    }
    
    location / {
        return 301 https://$host$request_uri;
    }
}

server {
    listen 443 ssl http2;
    server_name rachmatx.me;
    root /app/public;
    index index.php;

    ssl_certificate /etc/letsencrypt/live/raachmatx.me/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/raachmatx.me/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    client_max_body_size 20M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 60s;
    }

    location ~ /\. {
        deny all;
    }

    location ~ /\.env {
        deny all;
    }
}
```

Replace `./nginx.conf` with this SSL version in docker-compose.yml volumes.

## Step 5: Initialize Let's Encrypt Certificate

```bash
# Create directories
mkdir -p www certbot_certs

# First, bring up without SSL
docker compose up -d

# Get initial certificate (certbot already in compose)
docker compose exec certbot certbot certonly --webroot -w /var/www/certbot \
  -d rachmatx.me \
  --email your-email@example.com \
  --agree-tos \
  --non-interactive

# Stop and update nginx config
docker compose down
# Update nginx.conf to nginx-ssl.conf in compose
docker compose up -d
```

## Step 6: Auto-Renewal

Add a cron job for auto-renewal:

```bash
# Edit crontab
crontab -e

# Add this line (runs renewal check daily at 2 AM)
0 2 * * * cd /opt/sparkleen && docker compose exec -T certbot certbot renew --quiet
```

## Step 7: Database Setup

```bash
# Run migrations
docker compose exec app php spark migrate

# Seed initial data (if seeder exists)
docker compose exec app php spark db:seed SampleSeeder
```

## Step 8: Verify Deployment

```bash
# Check container status
docker compose ps

# View PHP logs
docker compose logs app

# Test endpoint
curl -I https://rachmatx.me
```

## DNS Configuration

Ensure your domain registrar DNS points to your VPS IP:
```
A Record: rachmatx.me → YOUR_VPS_IP
```

## Monitoring & Maintenance

```bash
# View logs
docker compose logs -f app

# Restart services
docker compose restart

# Backup database
docker compose exec mysql mysqldump -u sparkleen -p sparkleen > backup.sql

# Update images
docker compose pull
docker compose up -d
```

## Troubleshooting

**SSL certificate issues:**
```bash
docker compose logs certbot
docker compose exec certbot certbot certificates
```

**Database connection errors:**
```bash
docker compose logs mysql
docker compose exec mysql mysql -u root -p -e "SHOW DATABASES;"
```

**PHP errors:**
```bash
docker compose logs app
docker compose exec app cat /var/log/php_errors.log
```

## Support

For CodeIgniter questions: https://forum.codeigniter.com
For Docker issues: https://docs.docker.com
