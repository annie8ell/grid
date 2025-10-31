# Azure Infrastructure Deployment

This directory contains Infrastructure as Code (IaC) templates for deploying National Grid: Live to Azure.

## Prerequisites

- Azure CLI installed (`az --version`)
- Azure subscription
- Appropriate permissions to create resources

## Quick Start

### 1. Login to Azure

```bash
az login
az account set --subscription <your-subscription-id>
```

### 2. Create Resource Group

```bash
az group create --name rg-nationalgrid-live --location uksouth
```

### 3. Deploy Infrastructure

```bash
az deployment group create \
  --resource-group rg-nationalgrid-live \
  --template-file main.bicep \
  --parameters \
    mysqlAdminLogin='gridadmin' \
    mysqlAdminPassword='<SecurePassword123!>' \
    environment='prod'
```

### 4. Get Outputs

```bash
az deployment group show \
  --resource-group rg-nationalgrid-live \
  --name main \
  --query properties.outputs
```

## Resources Created

- **App Service Plan** (Linux, PHP 8.3)
- **Web App** (App Service)
- **MySQL Flexible Server** (MariaDB compatible)
- **MySQL Database** (`grid`)

## Configuration

### Parameters

| Parameter | Description | Default |
|-----------|-------------|---------|
| `baseName` | Base name for resources | `nationalgrid` |
| `location` | Azure region | Resource group location |
| `environment` | Environment name | `prod` |
| `appServicePlanSku` | App Service tier | `B1` |
| `mysqlAdminLogin` | MySQL admin username | Required |
| `mysqlAdminPassword` | MySQL admin password | Required |

### Environments

- **dev**: Development (minimal resources)
- **staging**: Pre-production testing
- **prod**: Production (recommended settings)

## Post-Deployment Steps

1. **Import Database Schema**:
   ```bash
   mysql -h <mysql-server>.mysql.database.azure.com \
     -u gridadmin \
     -p \
     grid < ../../grid.sql
   ```

2. **Configure Environment Variables** in Azure Portal or CLI

3. **Deploy Application Code** (see main DEPLOYMENT.md)

4. **Set up WebJob for Updates** (every 5 minutes)

## Cost Optimization

### Development
- Use `B1` App Service Plan (~$13/month)
- Use `Standard_B1ms` MySQL (~$15/month)
- Total: ~$28/month

### Production
- Consider `S1` or `P1v3` for better performance
- Use `Standard_B2s` MySQL with backups
- Enable Application Insights for monitoring

## Monitoring

Access metrics and logs:
```bash
# View application logs
az webapp log tail --name <app-name> --resource-group rg-nationalgrid-live

# View metrics
az monitor metrics list \
  --resource <app-resource-id> \
  --metric "CpuPercentage" \
  --start-time 2023-01-01T00:00:00Z
```

## Troubleshooting

### Connection Issues
Check firewall rules allow your IP:
```bash
az mysql flexible-server firewall-rule create \
  --resource-group rg-nationalgrid-live \
  --name <mysql-server> \
  --rule-name AllowMyIP \
  --start-ip-address <your-ip> \
  --end-ip-address <your-ip>
```

### Deployment Failures
View deployment logs:
```bash
az deployment group show \
  --resource-group rg-nationalgrid-live \
  --name main \
  --query properties.error
```

## Cleanup

To delete all resources:
```bash
az group delete --name rg-nationalgrid-live --yes
```

## Additional Resources

- [Azure App Service Documentation](https://docs.microsoft.com/azure/app-service/)
- [Azure MySQL Flexible Server Documentation](https://docs.microsoft.com/azure/mysql/flexible-server/)
- [Bicep Documentation](https://docs.microsoft.com/azure/azure-resource-manager/bicep/)
