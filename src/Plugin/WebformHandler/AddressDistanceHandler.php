<?php

namespace Drupal\custom_webform_handlers\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
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

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'origin_address_field' => '',
      'destination_address_field' => '',
      'distance_field' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $elements = $this->getWebform()->getElementsInitializedAndFlattened();
    $options = [];
    foreach ($elements as $key => $element) {
      if (isset($element['#type']) && $element['#type'] === 'textfield') {
        $title = $element['#title'] ?? $key;
        $options[$key] = $title . ' (' . $key . ')';
      }
    }

    $form['origin_address_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Origin address field'),
      '#options' => $options,
      '#default_value' => $this->configuration['origin_address_field'],
      '#description' => $this->t('Select the field containing the origin address.'),
      '#required' => TRUE,
    ];

    $form['destination_address_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Destination address field'),
      '#options' => $options,
      '#default_value' => $this->configuration['destination_address_field'],
      '#description' => $this->t('Select the field containing the destination address.'),
      '#required' => TRUE,
    ];

    $form['distance_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Distance field'),
      '#options' => $options,
      '#default_value' => $this->configuration['distance_field'],
      '#description' => $this->t('Select the field to store the calculated distance.'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['origin_address_field'] = $form_state->getValue('origin_address_field');
    $this->configuration['destination_address_field'] = $form_state->getValue('destination_address_field');
    $this->configuration['distance_field'] = $form_state->getValue('distance_field');
  }

  /**
   * {@inheritdoc}
   */
  public function alterForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission = NULL) {
    $api_key = \Drupal::config('custom_webform_handlers.settings')->get('google_maps_api_key');
    if (empty($api_key)) {
      return;
    }

    $form['#attached']['library'][] = 'custom_webform_handlers/distance_calculator';
    $form['#attached']['drupalSettings']['custom_webform_handlers'] = [
      'google_maps_api_key' => $api_key,
      'saveUrl' => Url::fromRoute('custom_webform_handlers.save_distance')->toString(),
      'csrfToken' => \Drupal::csrfToken()->get('custom_webform_handlers.save'),
      'field_names' => [
        'origin' => $this->configuration['origin_address_field'],
        'destination' => $this->configuration['destination_address_field'],
        'distance' => $this->configuration['distance_field'],
      ],
    ];

    // Set autocomplete to 'on' for address fields to ensure Google suggestions display.
    $origin_key = $this->configuration['origin_address_field'];
    if (isset($form['elements'][$origin_key])) {
      $form['elements'][$origin_key]['#attributes']['autocomplete'] = 'on';
    }

    $destination_key = $this->configuration['destination_address_field'];
    if (isset($form['elements'][$destination_key])) {
      $form['elements'][$destination_key]['#attributes']['autocomplete'] = 'on';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(WebformSubmissionInterface $submission) {
    $api_key = \Drupal::config('custom_webform_handlers.settings')->get('google_maps_api_key');
    if (empty($api_key)) {
      \Drupal::logger('custom_webform_handlers')->error('Google Maps API key is missing.');
      return;
    }

    $origin = $submission->getElementData($this->configuration['origin_address_field']);
    $destination = $submission->getElementData($this->configuration['destination_address_field']);

    if ($origin && $destination) {
<<<<<<< HEAD
      $url = 'https://maps.googleapis.com/maps/api/distancematrix/json?origins=' . urlencode($origin) . '&destinations=' . urlencode($destination) . '&key=' . urlencode($api_key);
=======
      $url = 'https://maps.googleapis.com/maps/api/distancematrix/json?origins=' . urlencode($origin) . '&destinations=' . urlencode($destination) . '&units=imperial&key=' . urlencode($api_key);
>>>>>>> 4f21d7e (Refactor Distance Calculator and Update Configuration)

      try {
        $response = \Drupal::httpClient()->get($url);
        $data = json_decode((string) $response->getBody(), TRUE);

        if ($data['status'] === 'OK' && isset($data['rows'][0]['elements'][0]['distance']['value'])) {
<<<<<<< HEAD
          $distance_km = $data['rows'][0]['elements'][0]['distance']['value'] / 1000;
          $submission->setElementData($this->configuration['distance_field'], round($distance_km, 2) . ' km');
=======
          $distance_mi = $data['rows'][0]['elements'][0]['distance']['value'] / 1609.34; // Meters to miles
          $submission->setElementData($this->configuration['distance_field'], round($distance_mi, 2) . ' mi');
>>>>>>> 4f21d7e (Refactor Distance Calculator and Update Configuration)
        }
      }
      catch (\Exception $e) {
        \Drupal::logger('custom_webform_handlers')->error('Distance calculation failed: @message', ['@message' => $e->getMessage()]);
      }
    }
  }

}