<?php

namespace Drupal\commerce_license\Plugin\Commerce\LicenseType;

use Drupal\commerce\BundleFieldDefinition;
use Drupal\commerce_license\Entity\LicenseInterface;

/**
 * Provides the credit card payment method type.
 *
 * @CommerceLicenseType(
 *   id = "role",
 *   label = @Translation("Role license"),
 * )
 */
class Role extends Base {

  /**
   * {@inheritdoc}
   */
  public function buildLabel(LicenseInterface $license) {

  }

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = parent::buildFieldDefinitions();

    return $fields;
  }

}
