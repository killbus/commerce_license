<?php

namespace Drupal\commerce_license\Plugin\Commerce\LicenseType;

use Drupal\commerce\BundleFieldDefinition;
use Drupal\commerce_license\Entity\LicenseInterface;


/**
 * Provides a license type which grants access to files.
 *
 * @CommerceLicenseType(
 *   id = "file",
 *   label = @Translation("File"),
 * )
 */
class File extends Base {

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
  public function licenseActivated(LicenseInterface $license) {
    // TODO.
  }

  /**
   * {@inheritdoc}
   */
  public function licenseRevoked(LicenseInterface $license) {
    // TODO
  }

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = parent::buildFieldDefinitions();
    // @TODO: File entity reference field here, multi-value.

    return $fields;
  }

}
