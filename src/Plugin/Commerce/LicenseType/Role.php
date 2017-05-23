<?php

namespace Drupal\commerce_license\Plugin\Commerce\LicenseType;

use Drupal\commerce\BundleFieldDefinition;
use Drupal\commerce_license\Entity\LicenseInterface;

/**
 * Provides a license type which grants one or more roles.
 *
 * @CommerceLicenseType(
 *   id = "role",
 *   label = @Translation("Role"),
 * )
 */
class Role extends Base {

  /**
   * {@inheritdoc}
   */
  public function buildLabel(LicenseInterface $license) {
    $args = [
      '@id' => $license->license_id,
    ];
    return $this->t('File license (@id)', $args);
  }

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = parent::buildFieldDefinitions();

    // @TODO: Role reference field? Get this from the product?

    return $fields;
  }

}
