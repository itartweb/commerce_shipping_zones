<?php

namespace Drupal\commerce_shipping_zones;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\OrderProcessorInterface;
use Drupal\commerce_shipping\ShippingOrderManagerInterface;

/**
 * Completes the order refresh process for shipments.
 *
 * Saves any previously modified shipments.
 * Transfers the shipment amount and adjustments to the order.
 *
 * Runs after other order processors (promotion, tax, etc).
 *
 * @see \Drupal\commerce_shipping\EarlyOrderProcessor
 */
class LateOrderProcessor implements OrderProcessorInterface {

  /**
   * The shipping order manager.
   *
   * @var \Drupal\commerce_shipping\ShippingOrderManagerInterface
   */
  protected $shippingOrderManager;

  /**
   * Constructs a new LateOrderProcessor object.
   *
   * @param \Drupal\commerce_shipping\ShippingOrderManagerInterface $shipping_order_manager
   *   The shipping order manager.
   */
  public function __construct(ShippingOrderManagerInterface $shipping_order_manager) {
    $this->shippingOrderManager = $shipping_order_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function process(OrderInterface $order) {
    if (!$this->shippingOrderManager->hasShipments($order)) {
      return;
    }
    /** @var \Drupal\commerce_shipping\Entity\ShipmentInterface[] $shipments */
    $shipments = $order->get('shipments')->referencedEntities();
    $single_shipment = count($shipments) === 1;

    foreach ($shipments as $shipment) {
      if ($shipment->hasTranslationChanges()) {
        $shipment->save();
      }

      if ($amount = $shipment->getAmount()) {
        // @todo recalculate shipment price.
        $moduleHandler = \Drupal::service('module_handler');
        if ($moduleHandler->moduleExists('commerce_currency_resolver')) {
          $target_currency = \Drupal::service('commerce_currency_resolver.current_currency')->getCurrency();
          $amount = \Drupal::service('commerce_currency_resolver.calculator')->priceConversion($amount, $target_currency);
        }

        $existed = FALSE;
        foreach ($order->getAdjustments() as $adjustment) {
          if ($adjustment->getType() == 'shipping') {
            $existed = TRUE;
          }
        }

        if (!$existed) {
          // Shipments without an amount are incomplete / unrated.
          $label = $single_shipment ? t('Shipping') : $shipment->getTitle();
          $order->addAdjustment(new Adjustment([
            'type' => 'shipping',
            'label' => $label,
            'amount' => $amount,
            'source_id' => $shipment->id(),
          ]));
        }
        foreach ($shipment->getAdjustments() as $adjustment) {
          if ($adjustment->isLocked()) {
            // Locked shipment adjustments must be transferred unlocked
            // so that they're cleared at the beginning of order refresh.
            $adjustment = new Adjustment([
              'locked' => FALSE,
            ] + $adjustment->toArray());
          }
          $order->addAdjustment($adjustment);
        }
      }
    }
  }

}
