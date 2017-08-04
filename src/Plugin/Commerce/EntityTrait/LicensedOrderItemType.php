<?php

namespace Drupal\commerce_license\Plugin\Commerce\EntityTrait;

use Drupal\commerce\Plugin\Commerce\EntityTrait\EntityTraitBase;
use Drupal\commerce\BundleFieldDefinition;

/**
 * @CommerceEntityTrait(
 *  id = "commerce_license_order_item_type",
 *  label = @Translation("Provides an order item type for use with licenses."),
 *  entity_types = {"commerce_order_item"}
 * )
 */
class LicensedOrderItemType extends EntityTraitBase {

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    // Builds the field definitions.
    $fields = [];
    $fields['license'] = BundleFieldDefinition::create('entity_reference')
      ->setLabel(t('License'))
      ->setDescription(t('The license purchased with this order item.'))
      ->setSetting('target_type', 'commerce_license')
      ->setSetting('handler', 'default')
      ->setCardinality(1)
      // Won't be set when the order item is initially created, so can't be
      // required.
      ->setRequired(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'hidden',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);
    return $fields;
  }

}
