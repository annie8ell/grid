// Azure Static Web App for National Grid: Live Frontend
// Serves the generated static HTML/CSS/JS files

@description('Environment name (dev, staging, prod)')
param environment string = 'dev'

@description('Location for the Static Web App (limited regions)')
@allowed([
  'eastus2'
  'westus2'
  'centralus'
  'eastasia'
  'westeurope'
])
param location string = 'westeurope'

@description('SKU for Static Web App')
@allowed([
  'Free'
  'Standard'
])
param sku string = environment == 'prod' ? 'Standard' : 'Free'

var staticWebAppName = 'swa-grid-${environment}-${uniqueString(resourceGroup().id)}'

resource staticWebApp 'Microsoft.Web/staticSites@2023-01-01' = {
  name: staticWebAppName
  location: location
  sku: {
    name: sku
    tier: sku
  }
  properties: {
    repositoryUrl: '' // Leave empty for manual deployment
    branch: '' // Leave empty for manual deployment
    buildProperties: {
      skipGithubActionWorkflowGeneration: true
    }
    stagingEnvironmentPolicy: 'Enabled'
  }
}

// Output deployment token for CLI deployments
output staticWebAppUrl string = staticWebApp.properties.defaultHostname
output staticWebAppName string = staticWebApp.name
output deploymentToken string = listSecrets(staticWebApp.id, staticWebApp.apiVersion).properties.apiKey
