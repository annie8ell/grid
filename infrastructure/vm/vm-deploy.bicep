// Azure VM Infrastructure for National Grid: Live
// Cost-optimized single VM deployment with PHP 8.3 and MariaDB

@description('Environment name (dev, staging, prod)')
param environment string = 'dev'

@description('Location for all resources')
param location string = resourceGroup().location

@description('VM Admin Username')
@secure()
param adminUsername string

@description('VM Admin SSH Public Key')
@secure()
param adminSshPublicKey string

@description('Database Username')
@secure()
param databaseUsername string

@description('Database Password')
@secure()
param databasePassword string

@description('Database Name')
param databaseName string = 'grid'

@description('VM Size - B1s for dev (~$8/month), B2s for prod (~$36/month)')
param vmSize string = environment == 'prod' ? 'Standard_B2s' : 'Standard_B1s'

var vmName = 'vm-grid-${environment}'
var vnetName = 'vnet-grid-${environment}'
var nicName = 'nic-grid-${environment}'
var nsgName = 'nsg-grid-${environment}'
var publicIpName = 'pip-grid-${environment}'
var diskName = 'disk-grid-${environment}'

// Virtual Network
resource vnet 'Microsoft.Network/virtualNetworks@2023-05-01' = {
  name: vnetName
  location: location
  properties: {
    addressSpace: {
      addressPrefixes: [
        '10.0.0.0/16'
      ]
    }
    subnets: [
      {
        name: 'default'
        properties: {
          addressPrefix: '10.0.0.0/24'
          networkSecurityGroup: {
            id: nsg.id
          }
        }
      }
    ]
  }
}

// Network Security Group - No inbound ports needed (outbound only for API calls)
resource nsg 'Microsoft.Network/networkSecurityGroups@2023-05-01' = {
  name: nsgName
  location: location
  properties: {
    securityRules: [
      {
        name: 'SSH'
        properties: {
          priority: 1000
          protocol: 'Tcp'
          access: 'Allow'
          direction: 'Inbound'
          sourceAddressPrefix: '*'
          sourcePortRange: '*'
          destinationAddressPrefix: '*'
          destinationPortRange: '22'
        }
      }
      {
        name: 'AllowOutbound'
        properties: {
          priority: 100
          protocol: '*'
          access: 'Allow'
          direction: 'Outbound'
          sourceAddressPrefix: '*'
          sourcePortRange: '*'
          destinationAddressPrefix: '*'
          destinationPortRange: '*'
        }
      }
    ]
  }
}

// Public IP (for SSH access)
resource publicIp 'Microsoft.Network/publicIPAddresses@2023-05-01' = {
  name: publicIpName
  location: location
  sku: {
    name: 'Standard'
  }
  properties: {
    publicIPAllocationMethod: 'Static'
    dnsSettings: {
      domainNameLabel: 'grid-${environment}-${uniqueString(resourceGroup().id)}'
    }
  }
}

// Network Interface
resource nic 'Microsoft.Network/networkInterfaces@2023-05-01' = {
  name: nicName
  location: location
  properties: {
    ipConfigurations: [
      {
        name: 'ipconfig1'
        properties: {
          subnet: {
            id: vnet.properties.subnets[0].id
          }
          privateIPAllocationMethod: 'Dynamic'
          publicIPAddress: {
            id: publicIp.id
          }
        }
      }
    ]
    networkSecurityGroup: {
      id: nsg.id
    }
  }
}

// Virtual Machine
resource vm 'Microsoft.Compute/virtualMachines@2023-07-01' = {
  name: vmName
  location: location
  properties: {
    hardwareProfile: {
      vmSize: vmSize
    }
    osProfile: {
      computerName: vmName
      adminUsername: adminUsername
      linuxConfiguration: {
        disablePasswordAuthentication: true
        ssh: {
          publicKeys: [
            {
              path: '/home/${adminUsername}/.ssh/authorized_keys'
              keyData: adminSshPublicKey
            }
          ]
        }
      }
      customData: base64(replace(replace(replace(replace(loadTextContent('cloud-init.yaml'), '__ADMIN_USERNAME__', adminUsername), '__DATABASE_USERNAME__', databaseUsername), '__DATABASE_PASSWORD__', databasePassword), '__DATABASE_NAME__', databaseName))
    }
    storageProfile: {
      imageReference: {
        publisher: 'Canonical'
        offer: '0001-com-ubuntu-server-jammy'
        sku: '22_04-lts-gen2'
        version: 'latest'
      }
      osDisk: {
        name: diskName
        createOption: 'FromImage'
        managedDisk: {
          storageAccountType: 'Standard_LRS'
        }
        diskSizeGB: 30
      }
    }
    networkProfile: {
      networkInterfaces: [
        {
          id: nic.id
        }
      ]
    }
  }
}

output vmName string = vm.name
output publicIp string = publicIp.properties.dnsSettings.fqdn
output sshCommand string = 'ssh ${adminUsername}@${publicIp.properties.dnsSettings.fqdn}'
