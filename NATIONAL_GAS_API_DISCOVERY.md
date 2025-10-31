# National Gas API Discovery

**Date:** 2025-10-30  
**Status:** NEW API DISCOVERED - Requires Further Investigation

## Critical Update

User identified that National Gas DOES have REST APIs available at:
- https://apideveloper.nationalgas.com/
- https://data.nationalgas.com/apis/rest-apis

## API Endpoints Discovered

### Base URL
`https://data.nationalgas.com/api/`

### Available Endpoints

1. **Latest Gas Flows**
   - Endpoint: `/api/latest-gas-flows-download`
   - Format: CSV
   - Data: Supply from entry points with timestamps
   - Update Frequency: Real-time (2-minute granularity observed)
   - ✅ **WORKING** - Returns live data

2. **Gas Data Folders**
   - Endpoint: `/api/find-gas-data-folders`
   - Format: JSON
   - Returns: List of available data categories
   - Categories Found:
     - **Demand** ✓
     - Balancing
     - Calorific Value
     - Entry/Exit Capacity
     - Interruption
     - Linepack
     - LNG
     - And 8 more...

3. **Demand Reports**
   - Endpoint: `/api/gas-data-reports-folders?folder=Demand`
   - Format: JSON
   - Key Reports Available:
     - **Actual Demands (SISR04)** - Shows actual commercial demands for LDZs
     - **Actual Composite Weather Variables (SISR02)**
     - **Actual Offtake Flows (AOF)** - Physical flows from NTS by exit point

4. **Other Endpoints**
   - `/api/gas-quality-latest-report` - Working, returns JSON
   - `/api/daily-summary-report-download?Date=YYYY-MM-DD`
   - `/api/reports-download?`
   - `/api/customisable-downloads-download?`
   - `/api/entry-exit-capacity-download`
   - `/api/gas-quality-data-historical-locations`

## Key Finding: Actual Demands Report (SISR04)

**Description:**
"This report shows the Actual Commercial Demands for each of the LDZs (Local Distribution Zones) for a single Gas Day, the sum of all LDZ demand, and the actual throughput for the NTS (National Transmission System)."

**Characteristics:**
- Includes: All LDZs, Storage Injections, Interconnectors, NTS direct feed
- Excludes: Linepack change and shrinkage  
- Update Schedule:
  - Initial: D+1 at approximately 12:00
  - Final: D+6 (closed out data)
- Data Type: Actual commercial demands (not forecast)

## Comparison with Previous Assessment

### Previous Conclusion (INCORRECT)
"National Gas Portal - No public API for automated access"

### Corrected Finding
National Gas DOES have a REST API with:
- ✅ Public access (no authentication required for basic endpoints)
- ✅ Programmatic access via HTTPS
- ✅ CSV and JSON formats
- ✅ Real-time and near-real-time data
- ✅ Demand data available

## Next Steps for Investigation

### Immediate Actions Required

1. **Test SISR04 Report Access**
   ```
   Determine the correct endpoint to fetch SISR04 data
   Example: /api/reports-download?report=SISR04&date=2025-10-30
   ```

2. **Understand LDZ Demand Structure**
   - Determine if LDZ demands can be mapped to sectors (Domestic, Industrial, Commercial)
   - Or if total demand needs to be split using DESNZ ratios

3. **Check Data Granularity**
   - Confirm update frequency (D+1 may be too slow for real-time)
   - Investigate if within-day estimates are available

4. **API Documentation**
   - Access https://apideveloper.nationalgas.com/ for full API documentation
   - Check authentication requirements
   - Review rate limits and terms of use

5. **Test Data Download**
   - Fetch sample SISR04 report
   - Parse CSV/JSON structure
   - Validate data quality and completeness

## Implementation Implications

### If Sector Breakdown Available
- Can directly populate domestic_gas, industrial_gas, commercial_gas
- Implementation similar to Demand.php
- Daily update cycle (D+1) means 1-day lag

### If Only Total Demand Available
- Fetch total NTS demand
- Apply sector ratios from DESNZ statistics
- Update ratios quarterly
- Label as "estimated by sector"

### Data Frequency Considerations
- D+1 update schedule means this is **NOT real-time**
- Still significantly better than monthly DESNZ data
- More suitable for daily/historical tracking than live monitoring
- May need to adjust UI expectations

## Recommended Next Steps

1. **Immediate**: Test the SISR04 report endpoint and examine data structure
2. **Short-term**: Implement data fetching if structure is suitable
3. **Documentation**: Update GasConsumption.php with National Gas API details
4. **Testing**: Validate data accuracy against known benchmarks

## API Accessibility

✅ **Confirmed Working:**
- `/api/latest-gas-flows-download` - Returns CSV data
- `/api/find-gas-data-folders` - Returns JSON
- `/api/gas-data-reports-folders?folder=Demand` - Returns demand reports metadata
- `/api/gas-quality-latest-report` - Returns JSON

⏳ **Requires Testing:**
- SISR04 report download endpoint
- Historical data access
- Customizable download parameters

## Correction to GAS_DATA_RESEARCH.md

The statement "National Gas Data Portal - No public API for automated access" was **INCORRECT**.

National Gas DOES provide REST APIs for programmatic access to gas demand data, including the SISR04 "Actual Demands" report which contains the data needed for this application.

Further investigation is required to determine the exact format, granularity, and sector breakdown of the demand data.

---

**Research Status:** API discovered, endpoints identified, testing in progress  
**Action Required:** Deep dive into SISR04 report structure and implementation
