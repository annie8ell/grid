# VM + Static Web App Deployment (Cost-Optimized)

This deployment architecture separates the backend processing from the frontend hosting to minimize costs while maintaining functionality.

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│                    Azure Static Web App                     │
│              (Serves static HTML/CSS/JS)                    │
│                    ~$0-10/month (Free tier available)       │
└─────────────────────────────────────────────────────────────┘
                              ▲
                              │ rsync/GitHub Actions
                              │ (Once daily after cron)
┌─────────────────────────────────────────────────────────────┐
│                     Azure Linux VM                          │
│              (Backend + Database)                           │
│                                                              │
│  ┌────────────────┐      ┌────────────────┐               │
│  │   PHP 8.3      │──────│   MariaDB      │               │
│  │   update.php   │      │   Database     │               │
│  │   (Daily cron) │      │                │               │
│  └────────────────┘      └────────────────┘               │
│         │                                                   │
│         └──> Generates /opt/grid/public/index.html        │
│                                                              │
│  Cost: ~$8/month (B1s dev) to ~$36/month (B2s prod)       │
└─────────────────────────────────────────────────────────────┘
                              │
                              │ HTTPS API calls
                              ▼
                    External Data Sources
              (NESO, National Gas, Elexon, etc.)
```

## Cost Breakdown

### Development Environment
- **VM (B1s)**: ~$8/month
- **Disk (30GB Standard)**: ~$2/month
- **Static Web App (Free tier)**: $0/month
- **Data transfer**: ~$1/month
- **Total**: ~$11/month

### Production Environment
- **VM (B2s)**: ~$36/month
- **Disk (30GB Premium)**: ~$5/month
- **Static Web App (Free tier)**: $0/month (or $9/month for custom domain/SLA)
- **Data transfer**: ~$2/month
- **Total**: ~$43/month (or ~$52/month with Standard Static Web App)

### Comparison with Previous Architecture
- **Previous**: App Service (~$55/month) + MySQL Flexible Server (~$28/month) = **~$83/month**
- **New**: VM + Static Web App = **~$43/month**
- **Savings**: **~48% cost reduction**

## Deployment Steps

### 1. Deploy Azure VM

```bash
# Set variables
RESOURCE_GROUP="rg-grid-prod"
LOCATION="eastus"
ENVIRONMENT="prod"

# Create resource group
az group create --name $RESOURCE_GROUP --location $LOCATION

# Deploy VM infrastructure
az deployment group create \
  --resource-group $RESOURCE_GROUP \
  --template-file vm-deploy.bicep \
  --parameters environment=$ENVIRONMENT \
               adminUsername="gridadmin" \
               adminSshPublicKey="$(cat ~/.ssh/id_rsa.pub)" \
               databaseUsername="grid_user" \
               databasePassword="YourSecurePassword123!" \
               databaseName="grid"

# Get VM connection info
VM_FQDN=$(az deployment group show \
  --resource-group $RESOURCE_GROUP \
  --name vm-deploy \
  --query properties.outputs.publicIp.value -o tsv)

echo "VM FQDN: $VM_FQDN"
```

### 2. Deploy Application Code to VM

```bash
# SSH to VM
ssh gridadmin@$VM_FQDN

# Clone repository
cd /opt/grid
git clone https://github.com/annie8ell/grid.git .

# Create .env file (if not already created by cloud-init)
cat > .env <<EOF
DATABASE_HOSTNAME=localhost
DATABASE_USERNAME=grid_user
DATABASE_PASSWORD=YourSecurePassword123!
DATABASE_DATABASE=grid
ERROR_REPORTING_THRESHOLD=3
EOF

# Import database schema
mysql grid < grid.sql

# Run initial update
php8.3 update.php

# Verify public files were generated
ls -la public/
cat public/index.html | head -20
```

### 3. Deploy Azure Static Web App

```bash
# Deploy Static Web App infrastructure
az deployment group create \
  --resource-group $RESOURCE_GROUP \
  --template-file ../static-web-app/deploy.bicep \
  --parameters environment=$ENVIRONMENT

# Get deployment token
DEPLOYMENT_TOKEN=$(az staticwebapp secrets list \
  --name "swa-grid-$ENVIRONMENT" \
  --resource-group $RESOURCE_GROUP \
  --query properties.apiKey -o tsv)

echo "Deployment Token: $DEPLOYMENT_TOKEN"

# Get Static Web App URL
SWA_URL=$(az staticwebapp show \
  --name "swa-grid-$ENVIRONMENT" \
  --resource-group $RESOURCE_GROUP \
  --query defaultHostname -o tsv)

echo "Static Web App URL: https://$SWA_URL"
```

### 4. Configure Automated Deployment

#### Option A: GitHub Actions (Recommended)

1. Add secrets to GitHub repository:
   - `VM_HOST`: VM FQDN from step 1
   - `VM_USER`: gridadmin
   - `VM_SSH_KEY`: Private SSH key
   - `AZURE_STATIC_WEB_APPS_API_TOKEN`: Deployment token from step 3
   - `AZURE_STATIC_WEB_APP_NAME`: Static Web App name

2. Trigger deployment workflow manually or set up webhook

#### Option B: Direct from VM (Alternative)

```bash
# On the VM, install SWA CLI
npm install -g @azure/static-web-apps-cli

# Create deployment script
cat > /opt/grid/deploy-to-swa.sh <<'EOF'
#!/bin/bash
cd /opt/grid
swa deploy ./public \
    --deployment-token "$AZURE_STATIC_WEB_APPS_API_TOKEN" \
    --env "production"
EOF

chmod +x /opt/grid/deploy-to-swa.sh

# Add environment variable
echo 'export AZURE_STATIC_WEB_APPS_API_TOKEN="your-token-here"' >> ~/.bashrc
source ~/.bashrc

# Update cron to deploy after update
crontab -e
# Change line to:
# 15 12 * * * cd /opt/grid && /usr/bin/php8.3 update.php && /opt/grid/deploy-to-swa.sh >> /var/log/grid-update.log 2>&1
```

## Update Schedule

The application updates **once daily at 12:15 PM** (instead of every 5 minutes). This schedule is chosen because:

1. **National Gas SISR04 data** updates at D+1 around 12:00 PM
2. **Gas consumption is the limiting factor** for update frequency
3. **Electricity data** from NESO/Elexon is still available but updated once daily
4. **Significant cost savings** from reduced compute time
5. **Still provides daily insights** which is sufficient for historical tracking

To change the schedule, edit the crontab:
```bash
crontab -e
# Format: minute hour day month weekday command
# Example for twice daily (12:15 PM and 11:59 PM):
# 15 12 * * * cd /opt/grid && /usr/bin/php8.3 update.php && /opt/grid/deploy-to-swa.sh >> /var/log/grid-update.log 2>&1
# 59 23 * * * cd /opt/grid && /usr/bin/php8.3 update.php && /opt/grid/deploy-to-swa.sh >> /var/log/grid-update.log 2>&1
```

## Monitoring

### Check Update Status
```bash
# SSH to VM
ssh gridadmin@$VM_FQDN

# View recent logs
tail -100 /var/log/grid-update.log

# Check if cron is running
crontab -l
systemctl status cron

# Manual update
cd /opt/grid && php8.3 update.php
```

### Check Database
```bash
mysql -u grid_user -p grid

# Check latest data
SELECT * FROM past_five_minutes ORDER BY timestamp DESC LIMIT 5;
SELECT * FROM past_days ORDER BY timestamp DESC LIMIT 5;
```

### Check Static Web App
```bash
# Visit the URL
curl -I https://$SWA_URL

# Check deployment status
az staticwebapp show \
  --name "swa-grid-$ENVIRONMENT" \
  --resource-group $RESOURCE_GROUP
```

## Maintenance

### Update Application Code
```bash
ssh gridadmin@$VM_FQDN
cd /opt/grid
git pull
php8.3 update.php
/opt/grid/deploy-to-swa.sh  # If using direct deployment
```

### Database Backup
```bash
# On the VM
mysqldump -u grid_user -p grid > /tmp/grid-backup-$(date +%Y%m%d).sql

# Download backup
scp gridadmin@$VM_FQDN:/tmp/grid-backup-*.sql ./backups/
```

### Scale Up VM (if needed)
```bash
# Stop VM
az vm deallocate --resource-group $RESOURCE_GROUP --name "vm-grid-$ENVIRONMENT"

# Resize
az vm resize \
  --resource-group $RESOURCE_GROUP \
  --name "vm-grid-$ENVIRONMENT" \
  --size Standard_B2s

# Start VM
az vm start --resource-group $RESOURCE_GROUP --name "vm-grid-$ENVIRONMENT"
```

## Troubleshooting

### Update Not Running
```bash
# Check cron service
sudo systemctl status cron

# Check cron logs
sudo grep CRON /var/log/syslog | tail -20

# Test manual update
cd /opt/grid && php8.3 update.php
```

### Database Connection Issues
```bash
# Check MariaDB status
sudo systemctl status mariadb

# Test connection
mysql -u grid_user -p -h localhost grid
```

### Static Web App Not Updating
```bash
# Check if public files were generated
ls -la /opt/grid/public/
stat /opt/grid/public/index.html

# Test SWA CLI
swa --version

# Check deployment logs
swa deploy ./public --deployment-token "$TOKEN" --verbose
```

## Security Considerations

1. **SSH Access**: Use SSH keys only, disable password authentication
2. **Firewall**: Only SSH (port 22) is open; no web server ports needed
3. **Database**: Only accessible from localhost
4. **Secrets**: Store deployment tokens in GitHub Secrets or Azure Key Vault
5. **Updates**: Run `apt-get update && apt-get upgrade` monthly
6. **Backups**: Schedule regular database backups

## Advantages of This Architecture

✅ **48% cost reduction** compared to App Service + MySQL  
✅ **Simpler infrastructure** - single VM instead of multiple services  
✅ **No connection pooling issues** - direct localhost MySQL access  
✅ **Full control** over PHP and database versions  
✅ **Free static hosting** with Azure Static Web Apps  
✅ **Global CDN** for static files (via Static Web Apps)  
✅ **Suitable for daily updates** (gas data limitation)  
✅ **Easy to backup and restore** - single VM  
✅ **Scalable** - can upgrade VM size as needed  

## Disadvantages to Consider

⚠️ **Manual VM management** - OS updates, security patches  
⚠️ **Single point of failure** - no built-in redundancy  
⚠️ **Requires SSH access** for troubleshooting  
⚠️ **Daily updates only** - not suitable for real-time needs  
⚠️ **Two-step deployment** - VM generates, then push to Static Web App  

For most use cases with daily gas data updates, these trade-offs are acceptable given the significant cost savings.
