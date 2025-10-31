# Gas Data Source Research Findings

**Date:** 2025-10-30  
**Status:** Research Complete - Awaiting Real-Time Data Availability

## Executive Summary

After comprehensive research with network access to UK gas data sources, we have identified the following:

### Key Finding
**Real-time operational gas demand data by sector (domestic, industrial, commercial) is not currently available through public APIs in the same format as electricity data.**

### Available Data Sources

#### 1. NESO Data Portal (https://api.neso.energy/)
**Status:** ✅ Accessible and Validated

**Available Datasets:**
- FES (Future Energy Scenarios) forecast data
- Annual/scenario-based projections
- Not suitable for real-time tracking

**Specific Datasets Found:**
1. `fes-natural-gas-residential-and-non-domestic-i-c-heat-demand-summary-data-table-ed3`
   - Format: CSV
   - Content: Forecast/scenario data for residential and I&C heat demand
   - Granularity: Annual scenarios (2023, 2024, 2025 editions)
   - **Not operational data**

2. `fes-whole-system-gas-supply-data-table-ws1`
   - Format: CSV
   - Content: Whole system gas supply forecasts
   - **Not operational data**

3. `fes-natural-gas-demand-definitions-ed4`
   - Format: CSV
   - Content: Definitions and metadata
   - **Not operational data**

**API Structure:**
- Base URL: `https://api.neso.energy/`
- CKAN-based API
- Total packages: 122
- Gas-related packages: 4 (all FES forecast data)

**Conclusion:** NESO does not currently publish operational gas demand data via their API in the same format as electricity demand data.

#### 2. National Gas Data Portal (https://data.nationalgas.com/)
**Status:** ✅ Accessible

**Characteristics:**
- Web-based data portal
- Download-based access (not API-driven)
- Likely contains operational data but requires manual download
- Would require screen scraping or manual data import

**Potential Use:** 
- Fallback source for daily/periodic updates
- May have total system demand (not sector breakdown)

#### 3. DESNZ Energy Trends
**Status:** ✅ Accessible (https://www.gov.uk/)

**Characteristics:**
- Government statistical releases
- Monthly/quarterly granularity
- Excel/CSV downloads
- Best sector breakdown available
- Significant lag time (not real-time)

**Potential Use:**
- Source for sector ratio estimates
- Validation of consumption patterns
- Historical analysis

## Comparison with Electricity Data

### Electricity (Current Implementation)
```
Source: NESO "Demand Data Update"
Dataset ID: 7a12172a-939c-404c-b581-a6128b74f588
Resource ID: 177f6fa4-ae49-4182-81ea-0c6b35f26ca6
URL: https://api.neso.energy/dataset/.../download/demanddataupdate.csv
Format: CSV with settlement periods
Update Frequency: Real-time (5-30 minute granularity)
```

### Gas (Current Status)
```
Source: No equivalent real-time API found
Best Available: FES forecast data (annual scenarios)
Gap: Operational demand data not published via API
Alternative: National Gas portal (manual download)
```

## Recommendations

### Short-Term (Immediate)
1. **Document the limitation**: Update the application to clearly indicate gas data is not yet available
2. **Keep infrastructure ready**: Database schema and classes are in place
3. **Monitor for updates**: Watch NESO and National Gas for API developments

### Medium-Term (3-6 months)
1. **Contact NESO directly**: Inquire about plans to publish operational gas demand data
2. **Investigate National Gas API**: Check if they plan to expose operational data via API
3. **Consider manual import**: Implement periodic import from National Gas downloads

### Long-Term (Alternative Approaches)
1. **Estimate from total demand**: 
   - Use total gas demand from National Gas
   - Apply sector ratios from DESNZ statistics
   - Update ratios quarterly

2. **Hybrid approach**:
   - Use FES data for baseline patterns
   - Calibrate with DESNZ monthly statistics
   - Display as "estimated" rather than "actual"

3. **Industry partnership**:
   - Partner with National Gas for data access
   - Request API development for public benefit

## Technical Implementation Notes

### If Real-Time Data Becomes Available

Expected format (similar to electricity):
```csv
SETTLEMENT_DATE,SETTLEMENT_PERIOD,DOMESTIC_GAS,INDUSTRIAL_GAS,COMMERCIAL_GAS
2025-10-30,1,1500,800,300
2025-10-30,2,1450,780,290
```

Implementation would follow the Demand.php pattern:
```php
$rows = Csv::parse(
  'https://api.neso.energy/dataset/{dataset-id}/resource/{resource-id}/download/gasdemand.csv',
  ['SETTLEMENT_DATE', 'SETTLEMENT_PERIOD', 'DOMESTIC_GAS', 'INDUSTRIAL_GAS', 'COMMERCIAL_GAS'],
  [] // ignored columns
);
```

### Current Workaround Options

**Option A: Placeholder with Zero Values** (Current Implementation)
- Infrastructure in place
- Display section but show zeros
- Clear messaging about data availability

**Option B: Estimated Values**
- Fetch total gas demand from available sources
- Apply sector ratios from DESNZ
- Update ratios monthly/quarterly
- Label clearly as "estimated"

**Option C: Remove Until Data Available**
- Hide gas section in UI
- Keep database schema for future use
- Re-enable when data becomes available

## Validation Results

✅ **Network Connectivity**: All sources accessible  
✅ **API Structure**: NESO API operational and documented  
✅ **Data Availability**: Sources confirmed accessible  
❌ **Real-Time Gas Data**: Not available via public API  
✅ **Fallback Options**: Manual download sources identified  
✅ **Infrastructure Ready**: Database and classes prepared  

## Next Actions

1. **Update GasConsumption.php** with findings and recommended approach
2. **Update UI messaging** to reflect data availability status
3. **Monitor data source announcements** for API releases
4. **Consider implementing Option B** (estimated values) if acceptable

## References

- NESO Data Portal: https://www.neso.energy/data-portal
- NESO API: https://api.neso.energy/
- National Gas: https://data.nationalgas.com/
- DESNZ Energy Trends: https://www.gov.uk/government/statistics/gas-section-4-energy-trends

---

**Research Conducted:** 2025-10-30  
**Network Access:** Confirmed with updated allowlist  
**Findings:** Comprehensive search across all available UK gas data sources
