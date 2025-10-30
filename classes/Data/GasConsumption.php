<?php

namespace KateMorley\Grid\Data;

use KateMorley\Grid\Database;

/** Updates gas consumption data. */
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
    // Placeholder implementation - to be enhanced with real data sources
    // Data sources to be integrated:
    // - National Gas (https://www.nationalgas.com/ or https://data.nationalgas.com/)
    // - NESO Data Portal (https://www.neso.energy/data-portal)
    // - DESNZ Energy Trends (https://www.gov.uk/government/organisations/department-for-energy-security-and-net-zero)
    
    // For now, this is a stub that sets default values
    // Real implementation would:
    // 1. Fetch gas consumption data from API(s)
    // 2. Parse data, convert units (GWh/TWh to GW)
    // 3. Store in database with appropriate timestamp
    // 4. Handle errors with DataException
    
    // Until real data sources are integrated, we'll use zero values
    // to avoid errors in the database
    $data = [];
    
    // No actual data to update yet, but the structure is in place
    // When real data is available, use:
    // $database->update(self::KEYS, $data);
  }
}
