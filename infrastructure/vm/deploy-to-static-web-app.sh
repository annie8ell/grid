#!/bin/bash
# Deploy public directory to Azure Static Web Apps
# This script should be run on the VM after update.php completes

set -e

echo "====================================="
echo "Deploying to Azure Static Web Apps"
echo "====================================="

# Check if public directory exists
if [ ! -d "/opt/grid/public" ]; then
    echo "ERROR: /opt/grid/public directory not found!"
    exit 1
fi

# Check if required files exist
if [ ! -f "/opt/grid/public/index.html" ]; then
    echo "ERROR: index.html not found!"
    exit 1
fi

# Install SWA CLI if not installed
if ! command -v swa &> /dev/null; then
    echo "Installing Azure Static Web Apps CLI..."
    npm install -g @azure/static-web-apps-cli
fi

# Deploy using SWA CLI
echo "Deploying to Static Web Apps..."
cd /opt/grid
swa deploy ./public \
    --deployment-token "${AZURE_STATIC_WEB_APPS_API_TOKEN}" \
    --env "production"

echo "✅ Deployment complete!"
echo "🌐 Site available at: ${STATIC_WEB_APP_URL}"
