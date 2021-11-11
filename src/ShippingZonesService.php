<?php

namespace Drupal\commerce_shipping_zones;

use Drupal\commerce_shipping\Entity\ShipmentInterface;

/**
 *
 * Class ShippingZonesService.
 */
class ShippingZonesService implements ShippingZonesServiceInterface {

  /**
   * Entity\Order definition.
   *
   * @var \Drupal\commerce_order\Entity\Order
   */
  protected $order;

  /**
   * @var \Drupal\commerce_shipping\Entity\ShipmentInterface
   */
  protected $commerce_shipment;

  /**
   * Set the shipment for rate requests.
   *
   * @param \Drupal\commerce_shipping\Entity\ShipmentInterface $commerce_shipment
   *   A Drupal Commerce shipment entity.
   */
  public function setShipment(ShipmentInterface $commerce_shipment) {
    $this->commerce_shipment = $commerce_shipment;
  }

  /**
   * @inheritdoc
   */
  public function getRates(ShipmentInterface $shipment, array $config) {
    if ($shipment->getShippingProfile()->get('address')->isEmpty()) {
      return NULL;
    }

    $weight = round((float) $shipment->getWeight()->getNumber(), 1);

    $key_weight = NULL;
    $min_weight = 0;
    foreach (ShippingZonesServiceInterface::SHIPPING_ZONES_WEIGHTS as $key => $w) {
      if (!empty($key_weight)) {
        break;
      }

      if (!isset(ShippingZonesServiceInterface::SHIPPING_ZONES_WEIGHTS[$key + 1])) {
        $key_weight = $w;
      }
      else {
        if ($weight == $min_weight) {
          $key_weight = $w;
        }
        $max_weight = ShippingZonesServiceInterface::SHIPPING_ZONES_WEIGHTS[$key + 1];
        if ($weight > $min_weight && $weight < $max_weight) {
          $key_weight = $max_weight;
        }
      }

      $min_weight = $w;
    }

    $country_code = $shipment->getShippingProfile()->get('address')->first()->getCountryCode();
    $config_zones = \Drupal::service('config.factory')->get('commerce_shipping_zones.settings');

    if (!empty($config['shipping_zones']['items'][$key_weight][$config_zones->get($country_code)])) {
      return $config['shipping_zones']['items'][$key_weight][$config_zones->get($country_code)];
    }

    return NULL;
  }

}
