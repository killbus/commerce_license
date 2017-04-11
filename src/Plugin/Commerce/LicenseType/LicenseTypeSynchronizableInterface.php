<?php

namespace Drupal\commerce_license\Plugin\Commerce\LicenseType;

use Drupal\commerce\BundlePluginInterface;
use Drupal\commerce_license\Entity\LicenseInterface;

/**
 * Defines the interface for payment method types.
 */
interface LicenseTypeSynchronizableInterface extends LicenseTypeInterface {

  /**
   * Gets the license type label.
   *
   * @return string
   *   The license type label.
   */
  public function getLabel();

  /**
   * Builds a label for the given license.
   *
   * @param \Drupal\commerce_license\Entity\LicenseInterface $license
   *   The payment method.
   *
   * @return string
   *   The label.
   */
  public function buildLabel(LicenseInterface $license);

}
