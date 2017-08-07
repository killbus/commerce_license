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

  /**
   * Gets the workflow ID this this license type should use.
   *
   * @return string
   *   The ID of the workflow used for this license type.
   */
  public function getWorkflowId();

  /**
   * Copy configuration values to a license entity.
   *
   * This does not save the license.
   *
   * @param \Drupal\commerce_license\Entity\LicenseInterface $license
   */
  public function setConfigurationValuesOnLicense(LicenseInterface $license);

  /**
   * Reacts to the license being activated.
   *
   * The license's privileges should be granted to its user.
   *
   * @param \Drupal\commerce_license\Entity\LicenseInterface $license
   *   The license entity.
   */
  public function licenseActivated(LicenseInterface $license);

  /**
   * Reacts to the license being revoked.
   *
   * The license's privileges should be removed from its user.
   *
   * @param \Drupal\commerce_license\Entity\LicenseInterface $license
   *   The license entity.
   */
  public function licenseRevoked(LicenseInterface $license);

}
