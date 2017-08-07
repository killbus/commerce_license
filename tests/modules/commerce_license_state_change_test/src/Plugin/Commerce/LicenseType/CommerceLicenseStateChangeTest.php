<?php

namespace Drupal\commerce_license_state_change_test\Plugin\Commerce\LicenseType;

use Drupal\commerce_license\Plugin\Commerce\LicenseType\Base;
use Drupal\commerce_license\Entity\LicenseInterface;

/**
 * @CommerceLicenseType(
 *   id = "commerce_license_state_change_test",
 *   label = @Translation("State change test"),
 * )
 */
class CommerceLicenseStateChangeTest extends Base {

  /**
   * {@inheritdoc}
   */
  public function buildLabel(LicenseInterface $license) {
    return 'test license';
  }

  /**
   * {@inheritdoc}
   */
  public function licenseActivated(LicenseInterface $license) {
    $state = \Drupal::state();
    $state->set('commerce_license_state_change_test', 'licenseActivated');
  }

  /**
   * {@inheritdoc}
   */
  public function licenseDeactivated(LicenseInterface $license) {
    $state = \Drupal::state();
    $state->set('commerce_license_state_change_test', 'licenseDeactivated');
  }

}
