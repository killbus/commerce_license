<?php

namespace Drupal\commerce_license\Entity;

use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Url;
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

}
