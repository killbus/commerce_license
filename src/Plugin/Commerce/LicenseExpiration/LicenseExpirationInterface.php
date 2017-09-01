<?php

namespace Drupal\commerce_license\Plugin\Commerce\LicenseExpiration;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines an interface for Commerce license expiration plugins.
 */
interface LicenseExpirationInterface extends ConfigurablePluginInterface, PluginFormInterface {

  /**
   * Gets the plugin label.
   *
   * @return string
   *   The plugin label.
   */
  public function getLabel();

  /**
   * Gets the plugin description.
   *
   * @return string
   *   The plugin description.
   */
  public function getDescription();

  /**
   * Calculate the end timestamp for this expiration.
   *
   * @param int $start
   *   The timestamp to begin the period from.
   *
   * @return int
   *   The expiry timestamp.
   */
  public function calculateDate($start);

}
