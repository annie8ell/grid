#!/bin/bash
# VM Setup Script for National Grid: Live Application
# This script sets up a single VM with PHP, MySQL, and scheduled updates

set -e

echo "====================================="
echo "National Grid: Live - VM Setup"
echo "====================================="

# Update system
echo "Updating system packages..."
sudo apt-get update
sudo apt-get upgrade -y

# Install PHP 8.3 and required extensions
echo "Installing PHP 8.3..."
sudo add-apt-repository ppa:ondrej/php -y
sudo apt-get update
sudo apt-get install -y \
    php8.3-cli \
    php8.3-mysql \
    php8.3-curl \
    php8.3-xml \
    php8.3-mbstring \
    php8.3-zip

# Install MariaDB
echo "Installing MariaDB..."
sudo apt-get install -y mariadb-server mariadb-client

# Secure MariaDB installation
echo "Securing MariaDB..."
sudo mysql_secure_installation <<EOF

y
${DATABASE_PASSWORD}
${DATABASE_PASSWORD}
y
y
y
y
EOF

# Create database and user
echo "Creating database and user..."
sudo mysql -e "CREATE DATABASE IF NOT EXISTS ${DATABASE_DATABASE};"
sudo mysql -e "CREATE USER IF NOT EXISTS '${DATABASE_USERNAME}'@'localhost' IDENTIFIED BY '${DATABASE_PASSWORD}';"
sudo mysql -e "GRANT ALL PRIVILEGES ON ${DATABASE_DATABASE}.* TO '${DATABASE_USERNAME}'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"

# Import database schema
echo "Importing database schema..."
if [ -f "/opt/grid/grid.sql" ]; then
    sudo mysql ${DATABASE_DATABASE} < /opt/grid/grid.sql
    echo "Database schema imported successfully"
else
    echo "WARNING: grid.sql not found at /opt/grid/grid.sql"
fi

# Install git for deployment
echo "Installing git..."
sudo apt-get install -y git

# Create application directory
echo "Setting up application directory..."
sudo mkdir -p /opt/grid
sudo chown $(whoami):$(whoami) /opt/grid

# Clone or copy application files
# This assumes files are already in /opt/grid or will be deployed via git

# Create .env file
echo "Creating environment configuration..."
cat > /opt/grid/.env <<ENVEOF
DATABASE_HOSTNAME=localhost
DATABASE_USERNAME=${DATABASE_USERNAME}
DATABASE_PASSWORD=${DATABASE_PASSWORD}
DATABASE_DATABASE=${DATABASE_DATABASE}
ERROR_REPORTING_THRESHOLD=3
ENVEOF

# Set up cron job to run daily at 12:15 PM (after gas data updates)
echo "Configuring cron job..."
(crontab -l 2>/dev/null || true; echo "15 12 * * * cd /opt/grid && /usr/bin/php8.3 update.php >> /var/log/grid-update.log 2>&1") | crontab -

# Create log file with proper permissions
sudo touch /var/log/grid-update.log
sudo chown $(whoami):$(whoami) /var/log/grid-update.log

# Run initial update
echo "Running initial update..."
cd /opt/grid
php8.3 update.php

echo "====================================="
echo "VM Setup Complete!"
echo "====================================="
echo ""
echo "Application directory: /opt/grid"
echo "Log file: /var/log/grid-update.log"
echo "Cron schedule: Daily at 12:15 PM"
echo "Database: MariaDB on localhost"
echo ""
echo "Public files are in: /opt/grid/public/"
echo "These files should be published to Azure Static Web Apps"
echo ""
echo "To manually run an update: cd /opt/grid && php8.3 update.php"
echo "To view logs: tail -f /var/log/grid-update.log"
