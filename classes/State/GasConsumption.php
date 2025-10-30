<?php

namespace KateMorley\Grid\State;

/** Represents gas consumption by sector. */
class GasConsumption extends Map {
  public const DOMESTIC    = 'domestic_gas';
  public const INDUSTRIAL  = 'industrial_gas';
  public const COMMERCIAL  = 'commercial_gas';

  public const KEYS = [
    self::DOMESTIC   => 'Domestic gas',
    self::INDUSTRIAL => 'Industrial gas',
    self::COMMERCIAL => 'Commercial gas'
  ];

  protected const KEY_COMPONENTS = [
    self::DOMESTIC   => ['domestic_gas'],
    self::INDUSTRIAL => ['industrial_gas'],
    self::COMMERCIAL => ['commercial_gas']
  ];
}
