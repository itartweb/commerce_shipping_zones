<?php

namespace Drupal\commerce_shipping_zones;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;

/**
 * Modifies the LateOrderProcessor service.
 */
class CommerceShippingZonesServiceProvider extends ServiceProviderBase implements ServiceProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('commerce_shipping.late_order_processor');
    $definition->setClass(LateOrderProcessor::class);
  }

}
