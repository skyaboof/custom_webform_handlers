<?php

/**
 * @file
 * Service for managing moving price calculations.
 * Created by: skyaboof
 * Created on: 2025-11-06 02:15:07 UTC
 */

namespace Drupal\custom_webform_handlers\Service;

class MovingPriceService {
  
  /**
   * Get base prices for all service types.
   */
  public function getBasePrices() {
    return [
      'local_residential' => [
        'studio' => 415,
        '1_br' => 569,
        '2_br' => 909,
        '3_br' => 2073,
        '4_br_plus' => 2073,
      ],
      'long_distance' => [
        'base_fee' => 500,
        'per_mile' => 0.65,
      ],
      'specialty_items' => [
        'piano' => 425,
        'gun_safe' => 275,
        'fine_art' => 350,
        'gym_equipment' => 225,
      ],
    ];
  }

  /**
   * Get surcharge rates.
   */
  public function getSurchargeRates() {
    return [
      'stairs_per_flight' => 50,
      'elevator' => 35,
      'long_carry' => 75,
      'additional_mover' => 45,
    ];
  }

  /**
   * Calculate total price for a move.
   */
  public function calculateTotal($serviceType, $details) {
    $total = 0;
    $prices = $this->getBasePrices();
    
    switch ($serviceType) {
      case 'local_residential_move':
        $total += $this->calculateLocalMove($details, $prices['local_residential']);
        break;
      case 'long_distance_move':
        $total += $this->calculateLongDistanceMove($details, $prices['long_distance']);
        break;
    }
    
    return $total;
  }

  /**
   * Calculate local move cost.
   */
  protected function calculateLocalMove($details, $prices) {
    $total = $prices[$details['size']] ?? 0;
    
    if (!empty($details['distance'])) {
      $total += $details['distance'] * 2.5;
    }
    
    return $total;
  }
}