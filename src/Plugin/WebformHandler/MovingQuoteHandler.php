<?php

/**
 * @file
 * Moving Quote calculation handler.
 * Created by: skyaboof
 * Created on: 2025-11-06 02:13:12 UTC
 */

namespace Drupal\custom_webform_handlers\Plugin\WebformHandler;

use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Moving Quote calculation handler.
 *
 * @WebformHandler(
 *   id = "moving_quote_handler",
 *   label = @Translation("Moving Quote Calculator"),
 *   category = @Translation("Custom"),
 *   description = @Translation("Calculates moving quote prices."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 * )
 */
class MovingQuoteHandler extends WebformHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    $values = $form_state->getValues();
    if (!empty($values['calculated_distance'])) {
      $distance = str_replace(' mi', '', $values['calculated_distance']);
      if (!is_numeric($distance)) {
        $form_state->setError($form['calculated_distance'], $this->t('Invalid distance calculation.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(WebformSubmissionInterface $webform_submission) {
    $data = $webform_submission->getData();
    $total = $this->calculateTotal($data);
    $data['calculated_total'] = $total;
    $webform_submission->setData($data);
  }

  /**
   * Calculate total price based on all factors.
   */
  protected function calculateTotal(array $data) {
    $total = 0;
    $base_prices = $this->getBasePrices();

    if ($data['service_type'] === 'local_residential_move' && isset($data['move_size_residential'])) {
      $total += $base_prices[$data['move_size_residential']] ?? 0;
    }

    if (!empty($data['calculated_distance'])) {
      $distance = (float) str_replace(' mi', '', $data['calculated_distance']);
      $total += $this->calculateDistanceFee($distance, $data['service_type']);
    }

    if (!empty($data['origin_stairs_flights'])) {
      $total += $this->calculateStairsFee($data['origin_stairs_flights']);
    }
    if (!empty($data['destination_stairs_flights'])) {
      $total += $this->calculateStairsFee($data['destination_stairs_flights']);
    }

    return $total;
  }

  /**
   * Get base prices for different move sizes.
   */
  protected function getBasePrices() {
    return [
      'studio' => 415,
      '1_br' => 569,
      '2_br' => 909,
      '3_br' => 2073,
      '4_br_plus' => 2073,
    ];
  }

  /**
   * Calculate distance fee.
   */
  protected function calculateDistanceFee($distance, $service_type) {
    if ($service_type === 'local_residential_move') {
      return $distance * 2.5;
    }
    if ($service_type === 'long_distance_move') {
      return $distance * 0.65;
    }
    return 0;
  }

  /**
   * Calculate stairs fee.
   */
  protected function calculateStairsFee($flights) {
    return $flights * 50;
  }
}