<?php

namespace Drupal\commerce_license_set_expiry_test\Plugin\Commerce\LicenseExpiration;

use Drupal\commerce_license\Plugin\Commerce\LicenseExpiration\LicenseExpirationBase;

/**
 * @CommerceLicenseExpiration(
 *   id = "commerce_license_set_expiry_test",
 *   label = @Translation("Set expiry test"),
 *   description = @Translation("Set expiry test"),
 * )
 */
class CommerceLicenseSetExpiryTest extends LicenseExpirationBase {

  /**
   * {@inheritdoc}
   */
  public function calculateDate($start) {
    // Return a fixed timestamp that we can test.
    return 12345;
  }

}
