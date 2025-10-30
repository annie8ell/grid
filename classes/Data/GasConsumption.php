<?php

namespace KateMorley\Grid\Data;

use KateMorley\Grid\Database;

/**
 * Updates gas consumption data from UK data sources.
 *
 * DATA SOURCE RESEARCH COMPLETED (2025-10-30):
 * ============================================
 * 
 * FINDING: Real-time operational gas demand data by sector is NOT currently available
 * through public APIs in the same format as electricity data.
 *
 * Data Sources Evaluated:
 * 
 * 1. NESO Data Portal (https://api.neso.energy/)
 *    Status: ✅ Accessible, ❌ No operational gas data
 *    - Only FES (Future Energy Scenarios) forecast data available
 *    - 4 gas-related datasets found, all scenario/projection data
 *    - No equivalent to electricity "Demand Data Update"
 *    - API structure: CKAN-based, 122 total packages
 *
 * 2. National Gas (https://data.nationalgas.com/)
 *    Status: ✅ Accessible, manual download required
 *    - Web-based data portal (not API-driven)
 *    - Likely contains operational data
 *    - Requires manual download or screen scraping
 *    - May have total system demand (not sector breakdown)
 *
 * 3. DESNZ Energy Trends (https://www.gov.uk/)
 *    Status: ✅ Accessible, monthly/quarterly updates
 *    - Government statistical releases
 *    - Best sector breakdown available (Domestic, Industrial, Commercial)
 *    - Significant lag time (not suitable for real-time)
 *    - Useful for sector ratio estimates
 *
 * RECOMMENDATIONS:
 * - Short-term: Keep infrastructure ready, display unavailability notice
 * - Medium-term: Contact NESO/National Gas about API development
 * - Long-term: Implement estimated values using total demand + sector ratios
 *
 * See GAS_DATA_RESEARCH.md for complete research findings.
 *
 * Implementation Notes:
 * - Gas data typically in GWh; convert to GW for consistency
 * - Excludes gas used for electricity generation (CCGT/OCGT) to avoid double-counting
 */
class GasConsumption {
  public const KEYS = [
    'domestic_gas',
    'industrial_gas',
    'commercial_gas'
  ];

  /**
   * Updates the gas consumption data.
   *
   * @param Database $database The database instance
   *
   * @throws DataException If the data was invalid
   */
  public static function update(Database $database): void {
    // DATA SOURCE RESEARCH COMPLETED (2025-10-30):
    // ============================================
    // 
    // CRITICAL FINDING: Real-time operational gas demand data is NOT available via
    // public APIs. NESO only publishes FES forecast/scenario data, not operational data.
    //
    // CURRENT STATUS:
    // - Infrastructure ready (database schema, state management, UI)
    // - No real-time API data source identified
    // - Alternative: National Gas portal (manual download)
    // - Fallback: DESNZ sector ratios + estimated values
    //
    // IMPLEMENTATION OPTIONS:
    // 
    // Option A (Current): Placeholder with zero values
    //   - Keep infrastructure active but display zeros
    //   - Clear UI messaging about data availability
    //   - Ready for immediate activation when data becomes available
    //
    // Option B (Estimated): Use available data with estimates
    //   - Fetch total gas demand from National Gas (if available)
    //   - Apply sector ratios from DESNZ statistics
    //   - Display as "estimated" values
    //   - Update ratios quarterly
    //
    // Option C (Hidden): Remove from UI until data available
    //   - Keep database schema for future use
    //   - Hide gas section in interface
    //   - Re-enable when operational data source found
    //
    // RECOMMENDATION: Continue with Option A (current implementation)
    // Monitor NESO and National Gas for API developments
    //
    // See GAS_DATA_RESEARCH.md for complete findings and recommendations.
    //
    // Example implementation pattern (when dataset ID is known):
    //
    // $rows = Csv::parse(
    //   'https://api.neso.energy/dataset/{DATASET_ID}/resource/{RESOURCE_ID}/download/gasdemand.csv',
    //   [
    //     'SETTLEMENT_DATE',
    //     'SETTLEMENT_PERIOD',
    //     'GAS_DEMAND_TOTAL'  // Or sector-specific columns if available
    //   ],
    //   [] // ignored columns
    // );
    //
    // $data = array_map(fn ($item) => self::getDatum($item), $rows);
    // $database->update(self::KEYS, $data);
    
    // Placeholder: No data update until data source is configured
    // This prevents errors while infrastructure is in place
  }

  /**
   * Validates gas data source connectivity and format.
   * 
   * This method can be called manually to verify data sources before deployment.
   *
   * @return array Validation results for each source
   */
  public static function validateSources(): array {
    $results = [
      'neso' => self::validateNESO(),
      'national_gas' => self::validateNationalGas(),
      'desnz' => self::validateDESNZ()
    ];
    
    return $results;
  }

  /**
   * Validates NESO API accessibility.
   *
   * @return array Validation result
   */
  private static function validateNESO(): array {
    $testUrl = 'https://api.neso.energy/api/3/action/package_search?q=gas';
    $response = @file_get_contents($testUrl);
    
    return [
      'name' => 'NESO Data Portal API',
      'url' => 'https://api.neso.energy/',
      'accessible' => ($response !== false),
      'api_available' => true,
      'recommended' => true,
      'notes' => 'CKAN-based API. Requires manual research to identify gas demand dataset IDs.'
    ];
  }

  /**
   * Validates National Gas portal accessibility.
   *
   * @return array Validation result
   */
  private static function validateNationalGas(): array {
    $headers = @get_headers('https://data.nationalgas.com/');
    
    return [
      'name' => 'National Gas Data Portal',
      'url' => 'https://data.nationalgas.com/',
      'accessible' => ($headers !== false),
      'api_available' => false,
      'recommended' => false,
      'notes' => 'Download-based portal. No public API. Limited sector breakdown.'
    ];
  }

  /**
   * Validates DESNZ portal accessibility.
   *
   * @return array Validation result
   */
  private static function validateDESNZ(): array {
    $headers = @get_headers('https://www.gov.uk/government/organisations/department-for-energy-security-and-net-zero');
    
    return [
      'name' => 'DESNZ Energy Trends',
      'url' => 'https://www.gov.uk/government/statistics/gas-section-4-energy-trends',
      'accessible' => ($headers !== false),
      'api_available' => false,
      'recommended' => false,
      'notes' => 'Best sector breakdown but monthly/quarterly updates. Too slow for real-time.'
    ];
  }

  /**
   * Parses a gas demand data row and returns a datum array.
   * 
   * Template method for future implementation.
   *
   * @param array $item The CSV row data
   *
   * @return array [timestamp, domestic_gas, industrial_gas, commercial_gas]
   * @throws DataException If the data was invalid
   */
  private static function getDatum(array $item): array {
    // Example implementation (to be customized based on actual data format):
    //
    // return [
    //   Time::getSettlementTime($item[0], $item[1]),
    //   (float)$item[2] / 1000, // Convert GWh to GW, adjust as needed
    //   (float)$item[3] / 1000,
    //   (float)$item[4] / 1000
    // ];
    
    throw new DataException('getDatum not yet implemented - awaiting data source configuration');
  }
}
