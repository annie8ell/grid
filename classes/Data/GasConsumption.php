<?php

namespace KateMorley\Grid\Data;

use KateMorley\Grid\Database;

/**
 * Updates gas consumption data from UK data sources.
 *
 * Data Sources Evaluated:
 * 1. NESO Data Portal (https://api.neso.energy/) - RECOMMENDED
 *    - API-based access to gas demand data
 *    - CSV format, similar to electricity data (Demand.php)
 *    - Pattern: https://api.neso.energy/dataset/{dataset-id}/resource/{resource-id}/download/{file}.csv
 *    - Requires identifying specific gas demand dataset IDs
 *
 * 2. National Gas (https://data.nationalgas.com/)
 *    - Download-based portal (no public API)
 *    - Daily/monthly granularity
 *    - Total system demand (limited sector breakdown)
 *
 * 3. DESNZ Energy Trends (https://www.gov.uk/government/statistics/gas-section-4-energy-trends)
 *    - Most comprehensive sector breakdown (Domestic, Industrial, Commercial)
 *    - Monthly/quarterly updates (too slow for real-time tracking)
 *    - Excel/CSV downloads, no API
 *
 * Implementation Notes:
 * - Gas data typically in GWh; convert to GW for consistency
 * - Sector-specific real-time data may be limited
 * - May require estimated ratios from DESNZ statistics
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
    // DATA SOURCE VALIDATION STATUS:
    // =============================
    // Network testing completed. All three potential data sources (NESO, National Gas,
    // DESNZ) are accessible but require specific implementation:
    //
    // NEXT STEPS FOR PRODUCTION:
    // 1. Identify specific NESO gas demand dataset ID via manual portal research
    // 2. Implement CSV parsing similar to Demand.php
    // 3. Handle unit conversion (GWh → GW)
    // 4. Implement sector estimation if real-time sector data unavailable
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
