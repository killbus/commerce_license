<?php

namespace Drupal\Tests\commerce_license\Kernel\System;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests the role license type.
 *
 * @group commerce_license
 */
class LicenseRoleTypeTest extends EntityKernelTestBase {

  /**
   * The modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'user',
    'state_machine',
    'commerce',
    'commerce_price',
    'commerce_product',
    'commerce_license',
  ];

  /**
   * The license storage.
   */
  protected $licenseStorage;

  /**
   * The role storage.
   */
  protected $roleStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_license');

    $this->licenseStorage = $this->container->get('entity_type.manager')->getStorage('commerce_license');
    $this->roleStorage = $this->container->get('entity_type.manager')->getStorage('user_role');
  }

  /**
   * Tests that a role license grants and revokes a role from its owner.
   */
  public function testRoleLicense() {
    $role = $this->roleStorage->create(['id' => 'licensed_role']);
    $role->save();

    $license_owner = $this->createUser();

    // Create a license in the 'new' state, owned by the user.
    $license = $this->licenseStorage->create([
      'type' => 'role',
      'state' => 'new',
      'product' => 1,
      'uid' => $license_owner->id(),
      /*
      // @todo this depends on https://www.drupal.org/node/2879301
      // Use the unlimited expiry plugin as it's simple.
      'license_expiration' => [
        'target_plugin_id' => 'unlimited',
        'target_plugin_configuration' => [],
      ],
      */
      'license_role' => $role,
    ]);

    $license->save();

    // Assert the user does not have the role.
    $license_owner = $this->reloadEntity($license_owner);
    $this->assertFalse($license_owner->hasRole('licensed_role'), "The user does not have the licensed role.");

    // Don't bother pushing the license through state changes, as that is
    // by covered by LicenseStateChangeTest. Just call the plugin direct to
    // grant the license.
    $license->getTypePlugin()->grantLicense($license);

    // The license owner now has the role.
    $license_owner = $this->reloadEntity($license_owner);
    $this->assertTrue($license_owner->hasRole('licensed_role'), "The user has the licensed role.");

    // Revoke the license.
    $license->getTypePlugin()->revokeLicense($license);

    // Assert the user does not have the role.
    $license_owner = $this->reloadEntity($license_owner);
    $this->assertFalse($license_owner->hasRole('licensed_role'), "The user does not have the licensed role.");
  }

}
