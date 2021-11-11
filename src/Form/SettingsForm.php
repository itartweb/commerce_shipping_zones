<?php

namespace Drupal\commerce_shipping_zones\Form;

use Drupal\address\Plugin\Field\FieldType\AvailableCountriesTrait;
use Drupal\commerce_shipping_zones\ShippingZonesServiceInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure commerce_shipping_zones settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  use AvailableCountriesTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_shipping_zones_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['commerce_shipping_zones.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('commerce_shipping_zones.settings');

    $form = [];

    foreach (\Drupal::service('address.country_repository')->getList() as $key => $country) {
      $form[$key] = [
        '#type' => 'select',
        '#title' => $country,
        '#options' => ShippingZonesServiceInterface::SHIPPING_ZONES_HEADERS,
        '#default_value' => $config->get($key) ?? $config->get($key),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('commerce_shipping_zones.settings');
    foreach (\Drupal::service('address.country_repository')->getList() as $key => $country) {
      $config->set($key, $form_state->getValue($key));
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
