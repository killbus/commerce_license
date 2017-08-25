<?php

namespace Drupal\commerce_license;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_license\Entity\LicenseInterface;

/**
 * Defines the storage handler class for License entities.
 *
 * This extends the base storage class, adding required special handling for
 * License entities.
 *
 * @ingroup commerce_license
 */
interface LicenseStorageInterface extends ContentEntityStorageInterface {

  /**
   * Creates a new license from an order item.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item.
   *
   * @return \Drupal\commerce_license\Entity\LicenseInterface
   *   A new, unsaved license entity.
   */
  public function createFromOrderItem(OrderItemInterface $order_item);

}
