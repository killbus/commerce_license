<?php

namespace Drupal\commerce_license\Plugin\Commerce\LicenseType;

use Drupal\Core\Plugin\PluginBase;

/**
 * Provides the base payment method type class.
 */
abstract class Base extends PluginBase implements LicenseTypeInterface {

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    return [];
  }

}
