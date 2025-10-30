#!/usr/bin/env php
<?php

/**
 * Gas Data Source Validation Script
 * 
 * This script validates the accessibility and suitability of UK gas consumption
 * data sources for integration into the National Grid: Live application.
 * 
 * Usage: php validate_gas_sources.php
 */

require_once __DIR__ . '/classes/Data/GasConsumption.php';

use KateMorley\Grid\Data\GasConsumption;

echo "================================================================\n";
echo "UK Gas Consumption Data Sources Validation\n";
echo "================================================================\n\n";

echo "Evaluating potential data sources for UK gas consumption tracking...\n\n";

// Run validation
$results = GasConsumption::validateSources();

// Display results
foreach ($results as $key => $result) {
    $status = $result['accessible'] ? '✓ ACCESSIBLE' : '✗ NOT ACCESSIBLE';
    $api = $result['api_available'] ? '✓ API Available' : '✗ No API';
    $recommended = $result['recommended'] ? '★ RECOMMENDED' : '';
    
    echo "-------------------------------------------------------------------\n";
    echo strtoupper($key) . ": " . $result['name'] . " $recommended\n";
    echo "-------------------------------------------------------------------\n";
    echo "URL:        " . $result['url'] . "\n";
    echo "Status:     $status\n";
    echo "API:        $api\n";
    echo "Notes:      " . $result['notes'] . "\n";
    echo "\n";
}

echo "================================================================\n";
echo "RECOMMENDATIONS\n";
echo "================================================================\n\n";

echo "PRIMARY SOURCE: NESO Data Portal\n";
echo "- Most compatible with existing infrastructure (already used for electricity)\n";
echo "- API-based access available\n";
echo "- Action Required: Research NESO portal to identify gas demand dataset IDs\n";
echo "- Reference: classes/Data/Demand.php for implementation pattern\n\n";

echo "FALLBACK SOURCE: National Gas Portal\n";
echo "- Manual download-based access\n";
echo "- Daily data suitable for periodic updates\n";
echo "- Limited sector breakdown\n\n";

echo "VALIDATION SOURCE: DESNZ Energy Trends\n";
echo "- Use for sector ratio estimates and validation\n";
echo "- Most detailed sector breakdown\n";
echo "- Monthly/quarterly updates (not real-time)\n\n";

echo "================================================================\n";
echo "IMPLEMENTATION CHECKLIST\n";
echo "================================================================\n\n";

$checklist = [
    '[ ] Research NESO data portal for gas demand datasets',
    '[ ] Identify dataset ID and resource ID for gas data',
    '[ ] Determine CSV column structure',
    '[ ] Verify data granularity (5-min, 30-min, or daily)',
    '[ ] Confirm unit format (GWh, TWh, or other)',
    '[ ] Check if sector breakdown is available in real-time',
    '[ ] If no sector breakdown: download DESNZ statistics for ratio estimation',
    '[ ] Implement Csv::parse() call in GasConsumption::update()',
    '[ ] Implement getDatum() method with proper unit conversion',
    '[ ] Test with sample data',
    '[ ] Validate against DESNZ monthly statistics',
    '[ ] Update UI help text to indicate data source and update frequency',
    '[ ] Document any limitations or estimation methods used'
];

foreach ($checklist as $item) {
    echo "$item\n";
}

echo "\n================================================================\n";
echo "DATA FORMAT EXPECTATIONS\n";
echo "================================================================\n\n";

echo "Expected CSV Format (NESO pattern):\n";
echo "-----------------------------------\n";
echo "SETTLEMENT_DATE,SETTLEMENT_PERIOD,GAS_DEMAND_DOMESTIC,GAS_DEMAND_INDUSTRIAL,GAS_DEMAND_COMMERCIAL\n";
echo "2025-10-30,1,1500,800,300\n";
echo "2025-10-30,2,1450,780,290\n";
echo "...\n\n";

echo "Unit Conversion:\n";
echo "----------------\n";
echo "Source:      GWh (Gigawatt-hours) - typical for gas data\n";
echo "Target:      GW (Gigawatts) - for consistency with electricity\n";
echo "Conversion:  Depends on time period\n";
echo "             - For half-hourly: GWh / 0.5 = GW\n";
echo "             - For daily: GWh / 24 = GW average\n\n";

echo "Sector Exclusions:\n";
echo "------------------\n";
echo "EXCLUDE: Gas used for electricity generation (CCGT/OCGT)\n";
echo "         - Already tracked in Generation.php\n";
echo "         - Including would cause double-counting\n\n";

echo "================================================================\n";
echo "For more information, see:\n";
echo "- classes/Data/GasConsumption.php (implementation)\n";
echo "- README.md (architecture documentation)\n";
echo "================================================================\n";
