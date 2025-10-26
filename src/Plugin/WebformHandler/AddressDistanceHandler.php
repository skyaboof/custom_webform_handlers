<?php

namespace Drupal\custom_webform_handlers\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Webform handler to calculate distance between two addresses.
 *
 * @WebformHandler(
 *   id = "address_distance_handler",
 *   label = @Translation("Address and Distance Calculator"),
 *   category = @Translation("Custom"),
 *   description = @Translation("Calculates distance between two addresses using Google Maps API."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_IGNORED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
class AddressDistanceHandler extends WebformHandlerBase {

  public function defaultConfiguration() {
    return [
      'origin_address_field' => '',
      'destination_address_field' => '',
      'distance_field' => '',
    ];
  }

  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $elements = $this->getWebform()->getElementsInitializedAndFlattened();
    $options = array_filter($elements, function ($e) {
      return isset($e['#type']) && in_array($e['#type'], ['textfield']);
    });
    $field_options = array_map(function ($e, $k) {
      return ($e['#title'] ?? $k) . ' (' . $k . ')';
    }, $options, array_keys($options));

    $form['origin_address_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Origin address field'),
      '#options' => $field_options,
      '#default_value' => $this->configuration['origin_address_field'],
      '#description' => $this->t('Select the origin address field.'),
      '#required' => TRUE,
    ];

    $form['destination_address_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Destination address field'),
      '#options' => $field_options,
      '#default_value' => $this->configuration['destination_address_field'],
      '#description' => $this->t('Select the destination address field.'),
      '#required' => TRUE,
    ];

    $form['distance_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Distance field'),
      '#options' => $field_options,
      '#default_value' => $this->configuration['distance_field'],
      '#description' => $this->t('Select the distance field.'),
      '#required' => TRUE,
    ];

    return $form;
  }

  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['origin_address_field'] = $form_state->getValue('origin_address_field');
    $this->configuration['destination_address_field'] = $form_state->getValue('destination_address_field');
    $this->configuration['distance_field'] = $form_state->getValue('distance_field');
  }

  public function preSave(WebformSubmissionInterface $submission) {
    $api_key = \Drupal::config('custom_webform_handlers.settings')->get('google_maps_api_key');
    if (!$api_key) {
      \Drupal::logger('custom_webform_handlers')->error('No API key available at @time', ['@time' => date('Y-m-d H:i:s')]);
      return;
    }

    $data = $submission->getData();
    $origin = trim($data[$this->configuration['origin_address_field']] ?? '');
    $destination = trim($data[$this->configuration['destination_address_field']] ?? '');

    \Drupal::logger('custom_webform_handlers')->info('preSave triggered at @time: origin=@origin, destination=@dest', [
      '@time' => date('Y-m-d H:i:s'),
      '@origin' => $origin ?: 'empty',
      '@dest' => $destination ?: 'empty',
    ]);

    if (empty($origin) || empty($destination)) {
      \Drupal::logger('custom_webform_handlers')->warning('Empty addresses at @time: origin=@origin, dest=@dest', [
        '@time' => date('Y-m-d H:i:s'),
        '@origin' => $origin ?: 'empty',
        '@dest' => $destination ?: 'empty',
      ]);
      return;
    }

    $url = 'https://maps.googleapis.com/maps/api/distancematrix/json?' . http_build_query([
      'origins' => $origin,
      'destinations' => $destination,
      'mode' => 'driving',
      'units' => 'metric',
      'key' => $api_key,
    ]);

    try {
      $response = \Drupal::httpClient()->get($url);
      $data = json_decode($response->getBody(), TRUE);

      \Drupal::logger('custom_webform_handlers')->info('Distance Matrix API response at @time: status=@status, data=@data', [
        '@time' => date('Y-m-d H:i:s'),
        '@status' => $data['status'] ?? 'unknown',
        '@data' => json_encode($data),
      ]);

      if ($data['status'] === 'OK' && isset($data['rows'][0]['elements'][0]['status']) && $data['rows'][0]['elements'][0]['status'] === 'OK') {
        $distance = $data['rows'][0]['elements'][0]['distance']['text'];
        $submission->setData([$this->configuration['distance_field'] => $distance]);
        \Drupal::logger('custom_webform_handlers')->info('Distance set at @time: @distance', [
          '@time' => date('Y-m-d H:i:s'),
          '@distance' => $distance,
        ]);
      } else {
        \Drupal::logger('custom_webform_handlers')->warning('Distance calculation failed at @time: status=@status, error=@error', [
          '@time' => date('Y-m-d H:i:s'),
          '@status' => $data['status'] ?? 'unknown',
          '@error' => json_encode($data['rows'][0]['elements'][0] ?? 'no data'),
        ]);
      }
    } catch (\Exception $e) {
      \Drupal::logger('custom_webform_handlers')->error('Distance Matrix API request failed at @time: message=@message', [
        '@time' => date('Y-m-d H:i:s'),
        '@message' => $e->getMessage(),
      ]);
    }
  }
}