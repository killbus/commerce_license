<?php

namespace Drupal\commerce_license;

use Drupal\commerce\CommerceContentEntityStorage;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;

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

    // Take the license owner from the order, for the case when orders are
    // created for another user.
    $uid = $order_item->getOrder()->getCustomerId();

    $license = $this->createFromProductVariation($purchased_entity, $uid);

    return $license;
  }

  /**
   * {@inheritdoc}
   */
  public function createFromProductVariation(ProductVariationInterface $variation, $uid) {
    // TODO: throw an exception if the variation doesn't have this field.
    $license_type_plugin = $variation->get('license_type')->first()->getTargetInstance();

    $license = $this->create([
      'type' => $license_type_plugin->getPluginId(),
      'state' => 'new',
      'product_variation' => $variation->id(),
      'uid' => $uid,
      // Take the expiration type configuration from the product variation
      // expiration field.
      'expiration_type' => $variation->license_expiration,
    ]);

    // Set the license's plugin-specific configuration from the
    // product variation's license_type field plugin instance.
    $license->setValuesFromPlugin($license_type_plugin);

    return $license;
  }

  /**
   * {@inheritdoc}
   */
  public function getExistingLicense(ProductVariationInterface $variation, $uid) {
    $existing_licenses_ids = $this->getQuery()
      ->condition('state', ['active', 'renewal_in_progress'], 'IN')
      ->condition('uid', $uid)
      ->condition('product_variation', $variation->id())
      ->execute();

    if (!empty($existing_licenses_ids)) {
      $existing_license_id = array_shift($existing_licenses_ids);
      return $this->load($existing_license_id);
    }
    else {
      return FALSE;
    }
  }

}
