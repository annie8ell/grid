# Architecture Comparison

This document compares the two deployment architectures for National Grid: Live.

## Architecture Overview

### Option A: VM + Static Web App (Cost-Optimized) 🌟 RECOMMENDED

```
Frontend (Static Web App)  ←─ rsync/GitHub Actions ─→  Backend VM
     Free/$9/month                                        $8-36/month
     Azure Static Web Apps                                Linux + PHP + MariaDB
     Global CDN                                           Cron (daily updates)
```

### Option B: App Service + MySQL (Full Platform)

```
Frontend + Backend (App Service)  ←──→  MySQL Flexible Server
     $55/month                            $28/month
     Managed PHP runtime                  Managed database
     Auto-scaling                         Auto-backups
```

## Cost Comparison

| Component | Option A (VM + SWA) | Option B (App Service) |
|-----------|---------------------|------------------------|
| **Dev Environment** |
| Compute | VM B1s: $8/month | App Service B1: $13/month |
| Database | Included (on VM) | MySQL B1: $12/month |
| Storage | 30GB Disk: $2/month | Included |
| Frontend | Static Web App Free: $0 | Included |
| **Dev Total** | **~$10/month** | **~$25/month** |
| **Prod Environment** |
| Compute | VM B2s: $36/month | App Service B2: $55/month |
| Database | Included (on VM) | MySQL B2: $28/month |
| Storage | 30GB Premium: $5/month | Included |
| Frontend | Static Web App Free: $0 | Included |
| **Prod Total** | **~$41/month** | **~$83/month** |
| **Savings** | **Base** | **+102% cost** |

## Feature Comparison

| Feature | VM + Static Web App | App Service + MySQL |
|---------|--------------------|--------------------|
| **Cost** | ✅ $10-41/month | ❌ $25-83/month |
| **Managed Updates** | ❌ Manual OS updates | ✅ Automatic |
| **Scaling** | ⚠️ Manual VM resize | ✅ Auto-scaling |
| **Redundancy** | ❌ Single VM | ✅ Built-in HA |
| **Control** | ✅ Full root access | ⚠️ Limited |
| **Setup Complexity** | ⚠️ More steps | ✅ Simpler |
| **Update Frequency** | Daily (12:15 PM) | Every 5 minutes |
| **Database Access** | ✅ Localhost (fast) | ⚠️ Network (slower) |
| **Frontend CDN** | ✅ Global CDN | ❌ Single region |
| **Cold Start** | ✅ None | ⚠️ Possible |
| **Monitoring** | ⚠️ Manual setup | ✅ Built-in |
| **Backup** | ⚠️ Manual | ✅ Automatic |

## When to Use Each

### Use VM + Static Web App If:

✅ **Budget is a primary concern** - Save 50%+ on costs  
✅ **Daily updates are sufficient** - Gas data updates D+1  
✅ **You're comfortable with Linux** - Can manage a VM  
✅ **Simple architecture preferred** - Single VM to manage  
✅ **Global CDN important** - Static Web Apps has worldwide edge  
✅ **Low traffic application** - <100k visitors/month  

### Use App Service + MySQL If:

✅ **Managed services required** - No VM management  
✅ **High availability critical** - Built-in redundancy  
✅ **Auto-scaling needed** - Traffic varies significantly  
✅ **Compliance requirements** - Need managed database backups  
✅ **5-minute updates required** - Real-time tracking (though gas data doesn't support this)  
✅ **Team lacks Linux expertise** - PaaS abstracts infrastructure  

## Update Frequency Consideration

**Key Insight**: Since National Gas SISR04 data only updates D+1 (next day at 12:00 PM), there's no benefit to updating every 5 minutes for gas consumption tracking.

| Data Source | Update Frequency | Current Implementation |
|-------------|------------------|------------------------|
| **NESO Electricity** | 5-30 minutes | Can be real-time |
| **National Gas SISR04** | D+1 (daily) | Limiting factor |
| **Elexon** | 5-30 minutes | Can be real-time |
| **Carbon Intensity** | 30 minutes | Can be real-time |

**Conclusion**: Daily updates at 12:15 PM (after gas data) are **optimal** for the complete energy tracking application.

## Migration Path

### From App Service → VM + Static Web App

1. **Export database** from MySQL Flexible Server
2. **Deploy VM** using bicep template
3. **Import database** to MariaDB on VM
4. **Deploy code** to /opt/grid
5. **Create Static Web App** 
6. **Configure deployment** (GitHub Actions or direct)
7. **Test thoroughly**
8. **Update DNS** (if using custom domain)
9. **Decommission** App Service + MySQL

**Downtime**: ~15 minutes (during DNS cutover)

### From VM + Static Web App → App Service

1. **Deploy App Service** + MySQL infrastructure
2. **Export database** from VM MariaDB
3. **Import database** to MySQL Flexible Server
4. **Update connection strings**
5. **Deploy code** to App Service
6. **Test thoroughly**
7. **Update DNS**
8. **Decommission** VM + Static Web App

**Downtime**: ~15 minutes (during DNS cutover)

## Recommendations by Use Case

### Personal/Hobby Project
→ **VM + Static Web App (Free tier)** - $10/month

### Small Business/Startup
→ **VM + Static Web App (Standard tier)** - $20-50/month

### Enterprise/Critical Infrastructure
→ **App Service + MySQL** - $83+/month with auto-scaling

### High-Traffic Public Service
→ **App Service + MySQL** with Azure Front Door - $150+/month

## Performance Comparison

### Option A: VM + Static Web App
- **Static site load time**: <100ms (CDN)
- **Data update time**: 5-30 seconds (localhost DB)
- **Database query**: <5ms (localhost)
- **API calls**: Normal (public IPs)

### Option B: App Service + MySQL
- **Static site load time**: 200-500ms (single region)
- **Data update time**: 10-60 seconds (network DB)
- **Database query**: 10-50ms (network latency)
- **API calls**: Normal (public IPs)

**Winner**: Option A for frontend performance (CDN), Option B for uptime guarantees

## Conclusion

For the National Grid: Live application with daily gas data updates:

**🌟 RECOMMENDED: VM + Static Web App**

**Rationale**:
1. **50% cost savings** without sacrificing essential features
2. **Daily updates sufficient** given data source limitations
3. **Better global performance** via CDN for static files
4. **Simpler architecture** - one VM vs. three services
5. **Faster local database** access for update processing
6. **More control** over PHP/database configuration

The App Service option remains valid for teams requiring managed services and willing to pay premium for PaaS conveniences, but for most use cases, the cost savings and performance benefits of the VM + Static Web App architecture make it the superior choice.
