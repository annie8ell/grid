# Azure Deployment Summary - October 31, 2025

## Infrastructure Deployed

### Resource Group
- **Name**: `rg-grid-prod`
- **Location**: `westus2`
- **Status**: Active

### Virtual Machine (Backend)
- **Name**: `vm-grid-prod`
- **Size**: Standard_B1s (~$8/month)
- **OS**: Ubuntu 22.04 LTS
- **Public DNS**: `grid-prod-tunh5gpwovvqg.westus2.cloudapp.azure.com`
- **SSH Access**: `ssh gridadmin@grid-prod-tunh5gpwovvqg.westus2.cloudapp.azure.com`
- **Status**: Currently deallocated (no compute charges)

#### VM Configuration
- PHP 8.3 with extensions (mysqli, curl, xml, mbstring, zip)
- MariaDB 10.6+
- Node.js 20.x + Azure Static Web Apps CLI
- Application directory: `/opt/grid`
- Database: `grid` (local MariaDB)
- Cron job: Daily updates at 12:15 PM UTC

### Azure Static Web App (Frontend)
- **Name**: `swa-grid-prod-tunh5gpwovvqg`
- **Current SKU**: Standard (~$9/month) - **Recommended: Downgrade to Free**
- **URL**: https://witty-stone-09122241e.3.azurestaticapps.net
- **Deployment Token**: Rotated on Oct 31, 2025 (retrieve with az CLI)

## Deployment Fixes Applied

### Issues Resolved
1. **Cloud-init username mismatch**: Fixed hardcoded `ubuntu` user to use `__ADMIN_USERNAME__` parameter
2. **Manual setup required**: Automated git clone, database import, and initial update in cloud-init
3. **Missing dependencies**: Added Node.js and SWA CLI installation to cloud-init
4. **Public IP quota**: Changed from Basic to Standard SKU public IP

### Files Modified
- `infrastructure/vm/cloud-init.yaml`: Complete automation of VM bootstrap
- `infrastructure/vm/vm-deploy.bicep`: Added `__ADMIN_USERNAME__` parameter substitution
- `infrastructure/static-web-app/staticwebapp.json`: Updated routing configuration
- `.gitignore`: Added `DEPLOYMENT_INFO.md` to prevent committing secrets

## Current State

### ✅ Working
- VM deployed and configured
- Database schema imported
- Initial data update completed
- Public files generated (`index.html`, `favicon.svg`)
- Static Web App deployed and serving content
- Cron job scheduled for daily updates
- Deployment token rotated for security

### ⚠️ Pending
- **Password protection**: Azure SWA doesn't support basic HTTP password auth with custom passwords. Options:
  - Use invitation-based access (built-in SWA feature)
  - Use Azure AD authentication
  - Use GitHub OAuth
  - Deploy behind Azure Front Door with custom authentication
- **SKU downgrade**: SWA currently on Standard tier; should downgrade to Free to save ~$9/month

## Commands for Future Deployments

### Deploy New Infrastructure
```bash
# Create resource group
az group create --name rg-grid-prod --location westus2

# Deploy VM
az deployment group create \
  --resource-group rg-grid-prod \
  --template-file infrastructure/vm/vm-deploy.bicep \
  --parameters environment=prod \
               vmSize=Standard_B1s \
               adminUsername=gridadmin \
               adminSshPublicKey="$(cat ~/.ssh/id_rsa.pub)" \
               databaseUsername=grid_user \
               databasePassword='<SECURE_PASSWORD>' \
               databaseName=grid

# Deploy Static Web App
az deployment group create \
  --resource-group rg-grid-prod \
  --template-file infrastructure/static-web-app/deploy.bicep \
  --parameters environment=prod sku=Free
```

### Teardown Infrastructure
```bash
# Delete resource group (removes all resources)
az group delete --name rg-grid-prod --yes --no-wait
```

### Access Deployed Resources
```bash
# SSH to VM
ssh gridadmin@grid-prod-tunh5gpwovvqg.westus2.cloudapp.azure.com

# Get SWA deployment token
az staticwebapp secrets list \
  --resource-group rg-grid-prod \
  --name swa-grid-prod-tunh5gpwovvqg \
  --query properties.apiKey -o tsv

# Deploy to SWA from VM
cd /opt/grid
swa deploy ./public --deployment-token <TOKEN> --env production
```

### VM Management
```bash
# Start VM (for updates or maintenance)
az vm start --resource-group rg-grid-prod --name vm-grid-prod

# Stop VM (to avoid charges)
az vm deallocate --resource-group rg-grid-prod --name vm-grid-prod

# Check VM status
az vm get-instance-view \
  --resource-group rg-grid-prod \
  --name vm-grid-prod \
  --query "instanceView.statuses[?starts_with(code,'PowerState/')].displayStatus" -o tsv
```

## Cost Estimate

### Current Configuration
- VM (Standard_B1s): ~$8/month (when running)
- Disk (30GB Standard): ~$2/month
- Public IP (Standard): ~$3/month
- Static Web App (Standard): ~$9/month
- **Total**: ~$22/month

### Recommended Configuration
- VM (Standard_B1s): ~$8/month
- Disk (30GB Standard): ~$2/month
- Public IP (Standard): ~$3/month
- Static Web App (Free): $0/month
- **Total**: ~$13/month (41% savings)

### Cost Optimization Tips
1. Keep VM deallocated when not actively updating (saves compute costs)
2. Downgrade SWA to Free tier (saves $9/month)
3. Consider B1ls VM size if B1s is too powerful (~$4/month vs $8/month)

## Security Notes

### Secured Items
- Database accessible only from localhost on VM
- SSH key-based authentication only (no passwords)
- SWA deployment token rotated
- Sensitive files excluded from git via `.gitignore`

### Known Limitations
- No built-in password protection on Static Web App
- Database password stored in VM cloud-init (replaced during deployment)
- Public IP required for SSH access

## Next Steps

1. **Before teardown**: Backup any important data from the VM
2. **Test deployment**: Verify cloud-init automation works end-to-end on fresh deployment
3. **Consider auth**: Decide on authentication strategy for production (invitation-based, Azure AD, etc.)
4. **Optimize costs**: Downgrade SWA to Free tier

## Backup Commands

```bash
# Backup database
ssh gridadmin@grid-prod-tunh5gpwovvqg.westus2.cloudapp.azure.com \
  "mysqldump -u grid_user -pGridSecure2025! grid" > grid-backup-$(date +%Y%m%d).sql

# Download public files
scp -r gridadmin@grid-prod-tunh5gpwovvqg.westus2.cloudapp.azure.com:/opt/grid/public ./backup-public/
```

---

**Documentation Date**: October 31, 2025  
**Deployment Status**: Successful with manual fixes applied  
**Cloud-init Status**: Fixed and ready for automated deployments  
**Security Status**: Tokens rotated, sensitive files removed from repo
