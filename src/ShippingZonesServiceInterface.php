<?php

namespace Drupal\commerce_shipping_zones;

use Drupal\commerce_shipping\Entity\ShipmentInterface;

/**
 * Interface ShippingZonesServiceInterface.
 */
interface ShippingZonesServiceInterface {

  /**
   * {@inheritdoc}
   */
  const SHIPPING_ZONES_WEIGHTS = [
    '0.5',
    '1.0',
    '1.5',
    '2.0',
    '2.5',
    '3.0',
    '3.5',
    '4.0',
    '4.5',
    '5.0',
    '5.5',
    '6.0',
    '6.5',
    '7.0',
    '7.5',
    '8.0',
    '8.5',
    '9.0',
    '9.5',
    '10.0',
    '10.5',
    '11.0',
    '11.5',
    '12.0',
    '12.5',
    '13.0',
    '13.5',
    '14.0',
    '14.5',
    '15.0',
    '15.5',
    '16.0',
    '16.5',
    '17.0',
    '17.5',
    '18.0',
    '18.5',
    '19.0',
    '19.5',
    '20.0',
    '20.5',
    '21.0',
    '21.5',
    '22.0',
    '22.5',
    '23.0',
    '23.5',
    '24.0',
    '24.5',
    '25.0',
    '25.5',
    '26.0',
    '26.5',
    '27.0',
    '27.5',
    '28.0',
    '28.5',
    '29.0',
    '29.5',
    '30.0',
    '31.0',
    '32.0',
    '33.0',
    '34.0',
    '35.0',
    '36.0',
    '37.0',
    '38.0',
    '39.0',
    '40.0',
    '41.0',
    '42.0',
    '43.0',
    '44.0',
    '45.0',
    '46.0',
    '47.0',
    '48.0',
    '49.0',
    '50.0',
  ];

  /**
   * {@inheritdoc}
   */
  const SHIPPING_ZONES_HEADERS = [
    'zone_1' => 'Zone 1',
    'zone_2' => 'Zone 2',
    'zone_3' => 'Zone 3',
    'zone_4' => 'Zone 4',
    'zone_5' => 'Zone 5',
    'zone_6' => 'Zone 6',
    'zone_7' => 'Zone 7',
    'zone_8' => 'Zone 8',
  ];

  /**
   * Gets a new rate request.
   *
   * @param ShipmentInterface $shipment
   *
   * @return array
   *   The available rates as an array.
   */
  function getRates(ShipmentInterface $shipment, array $config);

}
