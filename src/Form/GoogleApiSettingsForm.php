<?php

namespace Drupal\custom_webform_handlers\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for Google API settings.
 */
class GoogleApiSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['custom_webform_handlers.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_api_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('custom_webform_handlers.settings');

    $form['google_maps_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google Maps API Key'),
      '#default_value' => $config->get('google_maps_api_key'),
      '#required' => TRUE,
      '#description' => $this->t('Enter your Google Maps API key for Places Autocomplete and Distance Matrix APIs. Obtain it from <a href="https://developers.google.com/maps">Google Cloud Console</a>.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('custom_webform_handlers.settings')
      ->set('google_maps_api_key', $form_state->getValue('google_maps_api_key'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}