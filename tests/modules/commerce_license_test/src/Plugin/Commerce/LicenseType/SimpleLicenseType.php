<?php

namespace Drupal\commerce_license_test\Plugin\Commerce\LicenseType;

/**
 * This license type plugin is for use in tests that don't need to do anything
 * in particular with the license type, but need to give a type for license
 * entities.
 *
 * @CommerceLicenseType(
 *   id = "simple",
 *   label = @Translation("Simple license"),
 * )
 */
class SimpleLicenseType extends TestLicenseBase {

}
