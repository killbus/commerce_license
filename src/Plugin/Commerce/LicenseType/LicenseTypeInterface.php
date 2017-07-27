<?php

namespace Drupal\commerce_license\Plugin\Commerce\LicenseType;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\commerce\BundlePluginInterface;
use Drupal\commerce_license\Entity\LicenseInterface;

/**
 * Defines the interface for license types.
 */
interface LicenseTypeInterface extends BundlePluginInterface, ConfigurablePluginInterface, PluginFormInterface {

  /**
   * Gets the license type label.
   *
   * @return string
   *   The license type label.
   */
  public function getLabel();

  /**
   * Build a label for the given license type.
   *
   * @param \Drupal\commerce_license\Entity\LicenseInterface $license
   *
   * @return string
   *   The label.
   */
  public function buildLabel(LicenseInterface $license);

}
