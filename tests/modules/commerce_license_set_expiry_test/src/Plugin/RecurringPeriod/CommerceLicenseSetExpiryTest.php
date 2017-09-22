<?php

namespace Drupal\commerce_license_set_expiry_test\Plugin\RecurringPeriod;

use Drupal\recurring_period\Plugin\RecurringPeriod\RecurringPeriodBase;

/**
 * @RecurringPeriod(
 *   id = "commerce_license_set_expiry_test",
 *   label = @Translation("Set expiry test"),
 *   description = @Translation("Set expiry test"),
 * )
 */
class CommerceLicenseSetExpiryTest extends RecurringPeriodBase {

  /**
   * {@inheritdoc}
   */
  public function calculateDate($start) {
    // Return a fixed timestamp that we can test.
    return 12345;
  }

}
