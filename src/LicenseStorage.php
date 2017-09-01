<?php

namespace Drupal\commerce_license;

use Drupal\commerce\CommerceContentEntityStorage;
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
class LicenseStorage extends CommerceContentEntityStorage implements LicenseStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function createFromOrderItem(OrderItemInterface $order_item) {
    $purchased_entity = $order_item->getPurchasedEntity();

    // TODO: throw an exception if the purchased entity doesn't have this field.
    $license_type_plugin = $purchased_entity->get('license_type')->first()->getTargetInstance();

    $license = $this->create([
      'type' => $license_type_plugin->getPluginId(),
      'state' => 'new',
      'product' => $purchased_entity->id(),
      // Take the license owner from the order, for the case when orders are
      // created for another user.
      'uid' => $order_item->getOrder()->uid,
      // Take the expiration type configuration from the purchased entity's
      // expiration field.
      'expiration_type' => $purchased_entity->license_expiration,
    ]);

    // Set the license's plugin-specific configuration from the
    // product variation's license_type field plugin instance.
    $license->setValuesFromPlugin($license_type_plugin);

    return $license;
  }

}
