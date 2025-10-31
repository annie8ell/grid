// Azure Infrastructure for National Grid: Live
// Deploy with: az deployment group create --resource-group <rg-name> --template-file main.bicep --parameters @parameters.json

@description('Base name for all resources')
param baseName string = 'nationalgrid'

@description('Location for all resources')
param location string = resourceGroup().location

@description('Environment (dev, staging, prod)')
@allowed([
  'dev'
  'staging'
  'prod'
])
param environment string = 'prod'

@description('App Service Plan SKU')
@allowed([
  'B1'
  'B2'
  'S1'
  'P1v3'
])
param appServicePlanSku string = 'B1'

@description('MySQL Administrator Login')
@secure()
param mysqlAdminLogin string

@description('MySQL Administrator Password')
@secure()
param mysqlAdminPassword string

var appServicePlanName = '${baseName}-plan-${environment}'
var webAppName = '${baseName}-app-${environment}'
var mysqlServerName = '${baseName}-mysql-${environment}'

// App Service Plan
resource appServicePlan 'Microsoft.Web/serverfarms@2022-09-01' = {
  name: appServicePlanName
  location: location
  kind: 'linux'
  sku: {
    name: appServicePlanSku
  }
  properties: {
    reserved: true
  }
}

// Web App
resource webApp 'Microsoft.Web/sites@2022-09-01' = {
  name: webAppName
  location: location
  properties: {
    serverFarmId: appServicePlan.id
    siteConfig: {
      linuxFxVersion: 'PHP|8.3'
      alwaysOn: true
    }
    httpsOnly: true
  }
}

// MySQL Server
resource mysqlServer 'Microsoft.DBforMySQL/flexibleServers@2021-12-01-preview' = {
  name: mysqlServerName
  location: location
  sku: {
    name: 'Standard_B1ms'
    tier: 'Burstable'
  }
  properties: {
    administratorLogin: mysqlAdminLogin
    administratorLoginPassword: mysqlAdminPassword
    version: '8.0.21'
    storage: {
      storageSizeGB: 32
    }
  }
}

output webAppUrl string = 'https://${webApp.properties.defaultHostName}'
output mysqlServerFqdn string = mysqlServer.properties.fullyQualifiedDomainName
