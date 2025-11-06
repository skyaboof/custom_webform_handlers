<?php

namespace Drupal\custom_webform_handlers\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;

/**
 * Service for calculating moving quote costs and caching distance results.
 */
class QuoteCalculatorService {

  protected EntityTypeManagerInterface $entityTypeManager;
  protected ClientInterface $httpClient;
  protected ConfigFactoryInterface $configFactory;
  protected LoggerChannelFactoryInterface $loggerFactory;

  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    ClientInterface $httpClient,
    ConfigFactoryInterface $configFactory,
    LoggerChannelFactoryInterface $loggerFactory
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->httpClient = $httpClient;
    $this->configFactory = $configFactory;
    $this->loggerFactory = $loggerFactory;
  }

  /**
   * Returns distance (mi) between origin and destination, using cache if available.
   */
  public function getDistance(string $from, string $to): ?float {
    $storage = $this->entityTypeManager->getStorage('distance_record');
    $existing = $storage->loadByProperties(['from_address' => $from, 'to_address' => $to]);
    if ($existing) {
      $record = reset($existing);
      $distance = preg_replace('/[^0-9.]/', '', $record->get('distance')->value);
      return (float) $distance;
    }

    $api_key = $this->configFactory->get('custom_webform_handlers.settings')->get('google_maps_api_key');
    if (empty($api_key)) {
      $this->loggerFactory->get('custom_webform_handlers')->error('Google API key missing.');
      return NULL;
    }

    $url = sprintf(
      'https://maps.googleapis.com/maps/api/distancematrix/json?origins=%s&destinations=%s&units=imperial&key=%s',
      urlencode($from),
      urlencode($to),
      urlencode($api_key)
    );

    try {
      $response = $this->httpClient->get($url);
      $data = json_decode((string) $response->getBody(), TRUE);
      if ($data['status'] === 'OK' && isset($data['rows'][0]['elements'][0]['distance']['text'])) {
        $distanceText = $data['rows'][0]['elements'][0]['distance']['text'];
        $record = $storage->create([
          'from_address' => $from,
          'to_address' => $to,
          'distance' => $distanceText,
        ]);
        $record->save();
        $distance = preg_replace('/[^0-9.]/', '', $distanceText);
        return (float) $distance;
      }
    }
    catch (\Throwable $e) {
      $this->loggerFactory->get('custom_webform_handlers')->error('Distance API error: @msg', ['@msg' => $e->getMessage()]);
    }

    return NULL;
  }

  /**
   * Calculate moving quote based on distance and form data.
   */
  public function calculateQuote(array $data): array {
    $service = $data['service_type'] ?? '';
    $from = $data['origin_address'] ?? '';
    $to = $data['destination_address'] ?? '';

    $distance = $this->getDistance($from, $to) ?? 0;
    $weight = (float) ($data['shipment_weight'] ?? 4000);
    $total = 0;

    switch ($service) {
      case 'local_residential_move':
        $base = 500;
        $rate = 2.5; // per mile
        $total = $base + ($distance * $rate);
        break;

      case 'long_distance_move':
        $base = 500;
        $rate = 0.00065;
        $total = $base + ($weight * $distance * $rate);
        break;

      case 'on_demand_delivery':
        $base = 75;
        $rate = 1.0;
        $total = $base + ($distance * $rate);
        break;

      default:
        $base = 150;
        $total = $base;
    }

    return [
      'service_type' => $service,
      'distance_mi' => round($distance, 2),
      'estimated_cost' => round($total, 2),
    ];
  }
}
