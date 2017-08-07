<?php

namespace Drupal\commerce_license\Entity;

use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining License entities.
 *
 * @ingroup commerce_license
 */
interface LicenseInterface extends EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the License creation timestamp.
   *
   * @return int
   *   Creation timestamp of the License.
   */
  public function getCreatedTime();

  /**
   * Sets the License creation timestamp.
   *
   * @param int $timestamp
   *   The License creation timestamp.
   *
   * @return \Drupal\commerce_license\Entity\LicenseInterface
   *   The called License entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Get an unconfigured instance of the associated license type plugin.
   *
   * @return \Drupal\commerce_license\Plugin\Commerce\LicenseType\LicenseTypeInterface
   */
  public function getTypePlugin();

  /**
   * Gets the license state.
   *
   * @return \Drupal\state_machine\Plugin\Field\FieldType\StateItemInterface
   *   The shipment state.
   */
  public function getState();

  /**
   * Implements the workflow_callback for the state field.
   *
   * @param \Drupal\commerce_license\Entity\LicenseInterface $license
   *   The license.
   *
   * @return string
   *   The workflow ID.
   *
   * @see \Drupal\state_machine\Plugin\Field\FieldType\StateItem
   */
  public static function getWorkflowId(LicenseInterface $license);

}
