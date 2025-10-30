# Deployment Guide

This guide covers deploying the National Grid: Live application to production, with specific guidance for Azure infrastructure.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Quick Deployment Checklist](#quick-deployment-checklist)
3. [Azure Deployment](#azure-deployment)
4. [Manual Deployment](#manual-deployment)
5. [Database Migration](#database-migration)
6. [Post-Deployment Verification](#post-deployment-verification)
7. [Common Issues and Solutions](#common-issues-and-solutions)

---

## Prerequisites

### Required Software
- PHP 8.3 or later
- MariaDB 10.6+ or MySQL 8.0+
- Web server (nginx recommended, Apache works)
- Cron daemon for scheduled updates

### Required Permissions
- Database: `SELECT`, `INSERT`, `UPDATE`, `DELETE` privileges
- File system: Write access to `public/favicon.svg` and `public/index.html`
- Cron: Ability to create cron jobs

### Optional
- Cloudflare account (for analytics integration)
- Domain with DNS access

---

## Quick Deployment Checklist

- [ ] Clone repository or upload files
- [ ] Configure `.env` file with database credentials
- [ ] Create database and import `grid.sql`
- [ ] Apply database migration for gas columns (if upgrading)
- [ ] Configure web server to serve `public/` directory
- [ ] Set up cron job for `update.php` (every 5 minutes)
- [ ] Verify write permissions on `public/` files
- [ ] Test initial data update manually
- [ ] Monitor error logs for 24 hours
- [ ] Configure Cloudflare analytics (optional)

---

## Azure Deployment

### Architecture Overview

**Recommended Azure Services:**
- **Azure App Service** (Linux, PHP 8.3) - for web and update script
- **Azure Database for MySQL/MariaDB** (Flexible Server) - for data storage
- **Azure Static Web Apps** (alternative for static content only)
- **Azure Storage Account** (optional, for backups)

### Option 1: Azure App Service (Recommended)

#### 1. Create Azure Resources

```bash
# Set variables
RESOURCE_GROUP="rg-nationalgrid-live"
LOCATION="uksouth"  # or "ukwest"
APP_SERVICE_PLAN="plan-nationalgrid"
APP_NAME="nationalgrid-live"
DB_SERVER="mysql-nationalgrid-live"
DB_NAME="grid"

# Create resource group
az group create --name $RESOURCE_GROUP --location $LOCATION

# Create App Service Plan (Linux, PHP 8.3)
az appservice plan create \
  --name $APP_SERVICE_PLAN \
  --resource-group $RESOURCE_GROUP \
  --location $LOCATION \
  --is-linux \
  --sku B1

# Create Web App
az webapp create \
  --name $APP_NAME \
  --resource-group $RESOURCE_GROUP \
  --plan $APP_SERVICE_PLAN \
  --runtime "PHP:8.3"

# Create MySQL Flexible Server
az mysql flexible-server create \
  --resource-group $RESOURCE_GROUP \
  --name $DB_SERVER \
  --location $LOCATION \
  --admin-user gridadmin \
  --admin-password '<YourSecurePassword>' \
  --sku-name Standard_B1ms \
  --tier Burstable \
  --version 8.0.21 \
  --storage-size 32 \
  --public-access 0.0.0.0-255.255.255.255

# Create database
az mysql flexible-server db create \
  --resource-group $RESOURCE_GROUP \
  --server-name $DB_SERVER \
  --database-name $DB_NAME
```

#### 2. Configure Application Settings

```bash
# Set environment variables
az webapp config appsettings set \
  --name $APP_NAME \
  --resource-group $RESOURCE_GROUP \
  --settings \
    DATABASE_HOSTNAME="${DB_SERVER}.mysql.database.azure.com" \
    DATABASE_USERNAME="gridadmin" \
    DATABASE_PASSWORD="<YourSecurePassword>" \
    DATABASE_DATABASE="grid" \
    ERROR_REPORTING_THRESHOLD="3" \
    CLOUDFLARE_API_TOKEN="" \
    CLOUDFLARE_ZONE_ID=""
```

#### 3. Deploy Application

```bash
# Option A: Deploy from local repository
cd /path/to/grid
az webapp up \
  --name $APP_NAME \
  --resource-group $RESOURCE_GROUP \
  --runtime "PHP:8.3"

# Option B: Configure Git deployment
az webapp deployment source config-local-git \
  --name $APP_NAME \
  --resource-group $RESOURCE_GROUP

# Get Git URL and push
git remote add azure <git-url-from-above>
git push azure main
```

#### 4. Import Database Schema

```bash
# Get MySQL connection string
az mysql flexible-server show-connection-string \
  --server-name $DB_SERVER \
  --database-name $DB_NAME

# Connect and import
mysql -h ${DB_SERVER}.mysql.database.azure.com \
  -u gridadmin \
  -p \
  $DB_NAME < grid.sql
```

#### 5. Configure Scheduled Updates (WebJob)

Create a WebJob to run `update.php` every 5 minutes:

**webjob/run.sh:**
```bash
#!/bin/bash
cd /home/site/wwwroot
php update.php
```

```bash
# Make executable
chmod +x webjob/run.sh

# Create WebJob schedule (settings.job)
echo '{
  "schedule": "0 */5 * * * *"
}' > webjob/settings.job

# Deploy WebJob
az webapp deployment source config-zip \
  --name $APP_NAME \
  --resource-group $RESOURCE_GROUP \
  --src webjob.zip
```

### Option 2: Azure Static Web Apps + Azure Functions

For a more cost-effective serverless approach:

1. **Static Content**: Deploy `public/` to Azure Static Web Apps
2. **Data Updates**: Deploy `update.php` as Azure Function (PHP runtime)
3. **Database**: Azure Database for MySQL (same as above)
4. **Scheduling**: Use Timer Trigger for Azure Function

---

## Manual Deployment

For traditional VPS/VM deployments:

### 1. Server Setup

```bash
# Ubuntu/Debian
sudo apt update
sudo apt install -y php8.3-cli php8.3-fpm php8.3-mysql nginx mariadb-server

# CentOS/RHEL
sudo dnf install -y php83 php83-cli php83-fpm php83-mysqlnd nginx mariadb-server
```

### 2. Upload Files

```bash
# Via rsync
rsync -avz --exclude='.git' \
  .env update.php classes/ public/ \
  user@server:/var/www/grid/

# Via scp
scp -r .env update.php classes public \
  user@server:/var/www/grid/
```

### 3. Configure Nginx

**`/etc/nginx/sites-available/grid`:**
```nginx
server {
    listen 80;
    server_name grid.yourdomain.com;
    root /var/www/grid/public;
    index index.html;
    charset utf-8;

    location / {
        try_files $uri $uri/ =404;
    }

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;

    # Cache static assets
    location ~* \.(css|js|svg|png|jpg|jpeg|gif|ico|woff|woff2)$ {
        expires 1d;
        add_header Cache-Control "public, immutable";
    }

    # Deny access to sensitive files
    location ~ /\. {
        deny all;
    }
}
```

```bash
# Enable site
sudo ln -s /etc/nginx/sites-available/grid /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 4. Configure Cron Job

```bash
# Edit crontab
crontab -e

# Add this line (runs every 5 minutes)
*/5 * * * * /usr/bin/php /var/www/grid/update.php >> /var/log/grid-update.log 2>&1
```

---

## Database Migration

### For Existing Installations (Upgrading to Gas Support)

If you're upgrading an existing installation, run these migrations:

```sql
-- Connect to your database
mysql -u gridadmin -p grid

-- Add gas columns to all time-series tables
ALTER TABLE past_five_minutes 
  ADD COLUMN domestic_gas decimal(4,2) UNSIGNED NOT NULL DEFAULT 0.00,
  ADD COLUMN industrial_gas decimal(4,2) UNSIGNED NOT NULL DEFAULT 0.00,
  ADD COLUMN commercial_gas decimal(4,2) UNSIGNED NOT NULL DEFAULT 0.00;

ALTER TABLE past_half_hours 
  ADD COLUMN domestic_gas decimal(4,2) UNSIGNED NOT NULL DEFAULT 0.00,
  ADD COLUMN industrial_gas decimal(4,2) UNSIGNED NOT NULL DEFAULT 0.00,
  ADD COLUMN commercial_gas decimal(4,2) UNSIGNED NOT NULL DEFAULT 0.00;

ALTER TABLE past_days 
  ADD COLUMN domestic_gas decimal(4,2) UNSIGNED NOT NULL DEFAULT 0.00,
  ADD COLUMN industrial_gas decimal(4,2) UNSIGNED NOT NULL DEFAULT 0.00,
  ADD COLUMN commercial_gas decimal(4,2) UNSIGNED NOT NULL DEFAULT 0.00;

ALTER TABLE past_weeks 
  ADD COLUMN domestic_gas decimal(4,2) UNSIGNED NOT NULL DEFAULT 0.00,
  ADD COLUMN industrial_gas decimal(4,2) UNSIGNED NOT NULL DEFAULT 0.00,
  ADD COLUMN commercial_gas decimal(4,2) UNSIGNED NOT NULL DEFAULT 0.00;

ALTER TABLE past_years 
  ADD COLUMN domestic_gas decimal(4,2) UNSIGNED NOT NULL DEFAULT 0.00,
  ADD COLUMN industrial_gas decimal(4,2) UNSIGNED NOT NULL DEFAULT 0.00,
  ADD COLUMN commercial_gas decimal(4,2) UNSIGNED NOT NULL DEFAULT 0.00;

-- Verify migrations
SHOW COLUMNS FROM past_half_hours LIKE '%gas%';
```

**Migration script** (`migrate_gas_columns.sql` - already created):
```bash
mysql -u gridadmin -p grid < migrations/add_gas_columns.sql
```

---

## Post-Deployment Verification

### 1. Test Database Connection

```bash
php -r "
\$db = new mysqli(
  getenv('DATABASE_HOSTNAME') ?: 'localhost',
  getenv('DATABASE_USERNAME') ?: 'gridadmin',
  getenv('DATABASE_PASSWORD'),
  getenv('DATABASE_DATABASE') ?: 'grid'
);
if (\$db->connect_error) {
  die('Connection failed: ' . \$db->connect_error);
}
echo 'Database connection successful\n';
"
```

### 2. Test Data Update Manually

```bash
cd /var/www/grid  # or your deployment path
php update.php

# Expected output:
# Updating generation… OK (X.XXX seconds)
# Updating emissions…  OK (X.XXX seconds)
# Updating pricing…    OK (X.XXX seconds)
# Updating demand…     OK (X.XXX seconds)
# Updating gas…        OK (X.XXX seconds)
# Updating visits…     OK (X.XXX seconds)
# Finishing update…    OK (X.XXX seconds)
# Outputting files…    OK (X.XXX seconds)
```

### 3. Verify File Permissions

```bash
# Check write permissions
ls -la public/index.html public/favicon.svg

# Should show write permissions for the cron user
# If not, fix:
sudo chown www-data:www-data public/index.html public/favicon.svg
sudo chmod 644 public/index.html public/favicon.svg
```

### 4. Monitor Logs

```bash
# Cron output
tail -f /var/log/grid-update.log

# Web server logs
tail -f /var/log/nginx/access.log
tail -f /var/log/nginx/error.log

# Azure App Service logs
az webapp log tail --name $APP_NAME --resource-group $RESOURCE_GROUP
```

### 5. Validate Gas Data Sources

```bash
# Run validation script
php validate_gas_sources.php

# Review output for data source accessibility
```

---

## Common Issues and Solutions

### Issue 1: Database Connection Fails

**Symptoms:** "Connection refused" or "Access denied"

**Solutions:**
```bash
# Check MySQL is running
systemctl status mariadb  # or mysql

# Verify credentials
mysql -u gridadmin -p -e "SELECT 1;"

# Check firewall (Azure)
az mysql flexible-server firewall-rule create \
  --resource-group $RESOURCE_GROUP \
  --name $DB_SERVER \
  --rule-name AllowAppService \
  --start-ip-address <app-service-outbound-ip> \
  --end-ip-address <app-service-outbound-ip>
```

### Issue 2: Files Not Updating

**Symptoms:** `index.html` and `favicon.svg` don't change

**Solutions:**
```bash
# Check cron is running
systemctl status cron

# Check cron logs
grep CRON /var/log/syslog

# Test manually
php update.php

# Check file permissions
namei -l /var/www/grid/public/index.html
```

### Issue 3: PHP mysqli Extension Missing

**Symptoms:** "Call to undefined function mysqli_connect()"

**Solutions:**
```bash
# Install extension
sudo apt install php8.3-mysql  # Debian/Ubuntu
sudo dnf install php83-mysqlnd  # CentOS/RHEL

# Verify
php -m | grep mysqli

# Azure: Add to App Settings
az webapp config appsettings set \
  --name $APP_NAME \
  --resource-group $RESOURCE_GROUP \
  --settings PHP_EXTENSIONS="mysqli"
```

### Issue 4: Memory Limit Exceeded

**Symptoms:** "Allowed memory size exhausted"

**Solutions:**
```bash
# Increase PHP memory limit
# Azure App Service
az webapp config set \
  --name $APP_NAME \
  --resource-group $RESOURCE_GROUP \
  --php-memory-limit 256M

# Manual server: Edit php.ini
memory_limit = 256M
```

### Issue 5: SSL/HTTPS Configuration

**Azure:** SSL certificates are automatic with App Service managed domains.

**Manual server with Let's Encrypt:**
```bash
# Install certbot
sudo apt install certbot python3-certbot-nginx

# Obtain certificate
sudo certbot --nginx -d grid.yourdomain.com

# Auto-renewal is configured automatically
```

---

## Infrastructure Gotchas and Best Practices

### Azure-Specific Considerations

1. **Connection Pooling:** Azure MySQL has connection limits. Use persistent connections in PHP or connection pooling.

2. **Outbound IP:** Azure App Service outbound IPs can change. Use VNet integration or add all possible IPs to MySQL firewall.

3. **Storage:** Azure App Service has ephemeral storage. Don't rely on file system for persistent data beyond `public/` files.

4. **Timezone:** Azure App Service defaults to UTC. Ensure your application handles timezones correctly.

5. **Cold Starts:** B1 tier may have cold starts. Consider higher tier for production.

### Performance Optimization

1. **Database Indexes:** Ensure indexes on `time` columns (already in schema).

2. **Caching:** Consider Azure CDN or Cloudflare for static content caching.

3. **Query Optimization:** Monitor slow queries:
   ```sql
   SET GLOBAL slow_query_log = 'ON';
   SET GLOBAL long_query_time = 2;
   ```

4. **Connection Persistence:** Use `mysqli_pconnect()` or persistent PDO connections.

### Security Best Practices

1. **Environment Variables:** Never commit `.env` file. Use Azure Key Vault for sensitive data.

2. **Database Security:** 
   - Use strong passwords
   - Limit IP access to Azure services only
   - Enable SSL connections

3. **HTTPS Only:** Always use HTTPS in production (automatic with Azure App Service).

4. **Regular Updates:** Keep PHP, MySQL, and dependencies updated.

### Monitoring and Alerts

```bash
# Azure: Set up Application Insights
az monitor app-insights component create \
  --app $APP_NAME \
  --location $LOCATION \
  --resource-group $RESOURCE_GROUP

# Set up alerts
az monitor metrics alert create \
  --name "High-CPU-Alert" \
  --resource-group $RESOURCE_GROUP \
  --scopes $(az webapp show --name $APP_NAME --resource-group $RESOURCE_GROUP --query id -o tsv) \
  --condition "avg Percentage CPU > 80" \
  --description "Alert when CPU exceeds 80%"
```

---

## Support and Troubleshooting

For issues specific to this application:
1. Check `validate_gas_sources.php` output
2. Review cron logs: `/var/log/grid-update.log`
3. Monitor database size: `SELECT table_name, ROUND(((data_length + index_length) / 1024 / 1024), 2) AS "Size (MB)" FROM information_schema.TABLES WHERE table_schema = "grid";`

For Azure-specific issues:
- Use `az webapp log tail` for real-time logs
- Check Azure Portal for metrics and diagnostics
- Review Azure MySQL slow query log

---

## Next Steps After Deployment

1. **Configure Gas Data Source:** Research NESO portal for gas demand dataset IDs (see README.md)
2. **Set up Monitoring:** Configure alerts for failures and performance issues
3. **Configure Backups:** Set up automated database backups
4. **Performance Tuning:** Monitor and optimize based on actual load
5. **CDN Configuration:** Set up Cloudflare or Azure CDN for global performance
