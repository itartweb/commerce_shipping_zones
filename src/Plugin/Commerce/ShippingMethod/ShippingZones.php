<?php

namespace Drupal\commerce_shipping_zones\Plugin\Commerce\ShippingMethod;

use Drupal\commerce_price\Price;
use Drupal\commerce_shipping\Entity\ShipmentInterface;
use Drupal\commerce_shipping\PackageTypeManagerInterface;
use Drupal\commerce_shipping\Plugin\Commerce\ShippingMethod\ShippingMethodBase;
use Drupal\commerce_shipping\ShippingRate;
use Drupal\commerce_shipping\ShippingService;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\commerce_shipping_zones\ShippingZonesServiceInterface;
use Drupal\state_machine\WorkflowManagerInterface;
use Drupal\commerce_price\RounderInterface;

/**
 * Provides the Shipping by Zones shipping method.
 *
 * @CommerceShippingMethod(
 *   id = "shipping_zones",
 *   label = @Translation("Shipping by Zones")
 * )
 */
class ShippingZones extends ShippingMethodBase {

  /**
   * The Service Plugins.
   *
   * @var \Drupal\Core\Plugin\DefaultLazyPluginCollection
   */
  protected $plugins;

  /**
   * Commerce NzPost Logger Channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $watchdog;

  /**
   * @var \Drupal\commerce_shipping\ShippingService;
   */
  protected $services;

  /**
   * @var \Drupal\commerce_shipping_zones\ShippingZonesServiceInterface
   */
  protected $rateService;

  /**
   * @var \Drupal\commerce_price\RounderInterface
   */
  protected $rounder;

  /**
   * @var \Drupal\commerce_price\Entity\CurrencyInterface
   */
  protected $currencyStorage;

  /**
   * NzPost constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_shipping\PackageTypeManagerInterface $package_type_manager
   *   Package Type Manager.
   * @param \Drupal\state_machine\WorkflowManagerInterface $workflow_manager
   *   Workflow Manager.
   * @param \Drupal\commerce_shipping_zones\ShippingZonesServiceInterface $rate_service
   *   RateLookupService.
   * @param \Drupal\commerce_price\RounderInterface $rounder
   *   Rounder.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    PackageTypeManagerInterface $package_type_manager,
    WorkflowManagerInterface $workflow_manager,
    ShippingZonesServiceInterface $rate_service,
    RounderInterface $rounder,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $package_type_manager, $workflow_manager);

    $this->rateService = $rate_service;
    $this->rounder = $rounder;
    $this->currencyStorage = $entity_type_manager->getStorage('commerce_currency');

    $this->services['default'] = new ShippingService('default', $this->configuration['rate_label']);
  }

    /**
     * {@inheritdoc}
     */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.commerce_package_type'),
      $container->get('plugin.manager.workflow'),
      $container->get('commerce_shipping_zones.rate'),
      $container->get('commerce_price.rounder'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $shipping_zones['currency'] = NULL;
    foreach (ShippingZonesServiceInterface::SHIPPING_ZONES_WEIGHTS as $weight) {
      foreach (ShippingZonesServiceInterface::SHIPPING_ZONES_HEADERS as $key => $zone) {
        $shipping_zones['items']["$weight"][$key] = 75.60;
      }
    }

    return [
        'rate_label' => 'Shipping by zones',
        'shipping_zones' => $shipping_zones,
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['rate_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Rate label'),
      '#description' => $this->t('Shown to customers when selecting the rate.'),
      '#default_value' => $this->configuration['rate_label'],
      '#required' => TRUE,
    ];

    $form['shipping_zones'] = [
      '#type' => 'details',
      '#title' => $this->t('Shipping Zones'),
      '#description' => $this->isConfigured() ? $this->t('Update your Shipping Zones') : $this->t('Fill in your Shipping Zones.'),
      '#weight' => $this->isConfigured() ? 10 : -10,
      '#open' => !$this->isConfigured(),
    ];

    $options = [];
    /** @var \Drupal\commerce_price\Entity\CurrencyInterface[] $currencies */
    $currencies = $this->currencyStorage->loadMultiple();
    foreach ($currencies as $currency) {
      $options[$currency->id()] = $currency->label();
    }
    $form['shipping_zones']['currency'] = [
      '#type' => 'select',
      '#title' => $this->t('Currency'),
      '#options' => $options,
      '#default_value' => $this->configuration['shipping_zones']['currency'],
      '#required' => TRUE,
    ];

    $form['shipping_zones']['items'] = [
      '#type' => 'table',
      '#header' => ['Weight (kg)'] + ShippingZonesServiceInterface::SHIPPING_ZONES_HEADERS,
      '#prefix' => '<div id="matrix-field-wrapper">',
      '#suffix' => '</div>',
    ];

    foreach (ShippingZonesServiceInterface::SHIPPING_ZONES_WEIGHTS as $weight) {
      $form['shipping_zones']['items']["$weight"]['weight'] = [
        '#type' => 'markup',
        '#markup' => $weight,
      ];
      foreach (ShippingZonesServiceInterface::SHIPPING_ZONES_HEADERS as $key => $zone) {
        $form['shipping_zones']['items']["$weight"][$key] = [
          '#type' => 'textfield',
          '#size' => 5,
          '#default_value' => $this->configuration['shipping_zones']['items']["$weight"][$key],
          '#required' => TRUE,
        ];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['shipping_zones'] = $values['shipping_zones'];
    }
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateRates(ShipmentInterface $shipment) {
   $availableRates = [];

    if ($shipment->getShippingProfile()->address->isEmpty()) {
      return [];
    }

    if (empty($shipment->getPackageType())) {
      $shipment->setPackageType($this->getDefaultPackageType());
    }

    $rate = $this->rateService->getRates($shipment, $this->configuration);
    $price = new Price((string) $rate, $this->configuration['shipping_zones']['currency']);
    $price = $this->rounder->round($price);

    // @todo recalculate shipment price.
    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('commerce_currency_resolver')) {
      $target_currency = \Drupal::service('commerce_currency_resolver.current_currency')->getCurrency();
      $price = \Drupal::service('commerce_currency_resolver.calculator')->priceConversion($price, $target_currency);
    }

    $availableRates[] = new ShippingRate([
      'shipping_method_id' => $this->parentEntity->id(),
      'service' => $this->services['default'],
      'amount' => $price,
    ]);

    return $availableRates;
  }

  /**
   * Determine if we have the minimum information to connect to Shipping by Zones.
   *
   * @return bool
   *   TRUE if there is enough information to connect, FALSE otherwise.
   */
  protected function isConfigured() {
    if (!empty($this->configuration['shipping_zones'])) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

}
