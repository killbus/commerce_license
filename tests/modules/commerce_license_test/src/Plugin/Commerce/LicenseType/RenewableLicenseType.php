<?php

namespace Drupal\commerce_license_test\Plugin\Commerce\LicenseType;

use Drupal\commerce_license\ExistingRights\ExistingRightsResult;
use Drupal\commerce_license\Plugin\Commerce\LicenseType\ExistingRightsFromConfigurationCheckingInterface;
use Drupal\user\UserInterface;

/**
 * This license type plugin used for renewable case.
 *
 * @CommerceLicenseType(
 *   id = "renewable",
 *   label = @Translation("Renewable license"),
 * )
 */
class RenewableLicenseType extends TestLicenseBase implements ExistingRightsFromConfigurationCheckingInterface {

  /**
   * {@inheritdoc}
   */
  public function checkUserHasExistingRights(UserInterface $user) {
    return ExistingRightsResult::rightsExistIf(
      TRUE,
      $this->t("You already have the rights."),
      $this->t("The user already has the rights.")
    );
  }

}
