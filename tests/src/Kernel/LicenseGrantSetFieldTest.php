<?php

namespace Drupal\Tests\commerce_license\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests the a field can be set on the license when granted and revoked.
 *
 * @group commerce_license
 */
class LicenseGrantSetFieldTest extends EntityKernelTestBase {

  /**
   * The modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'user',
    'entity',
    'state_machine',
    'commerce',
    'commerce_price',
    'commerce_product',
    'recurring_period',
    'commerce_license',
    'commerce_license_test',
  ];

  /**
   * The license storage.
   */
  protected $licenseStorage;

  /**
   * The license type plugin manager.
   */
  protected $licenseTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_license');
    $this->installConfig('user');

    // Install the bundle plugins for the license entity type which the
    // commerce_license_test module provides. This takes care of creating the
    // fields which the bundle plugins define.
    $this->container->get('entity.bundle_plugin_installer')->installBundles(
      $this->container->get('entity_type.manager')->getDefinition('commerce_license'),
      ['commerce_license_test']
    );

    $this->licenseTypeManager = $this->container->get('plugin.manager.commerce_license_type');
    $this->licenseStorage = $this->container->get('entity_type.manager')->getStorage('commerce_license');
  }

  /**
   * Tests that the license type plugin can set field values on the license.
   */
  public function testLicensePluginSetField() {
    $license_owner = $this->createUser();

    // Create a license in the 'new' state, owned by the user.
    $license = $this->licenseStorage->create([
      'type' => 'with_field',
      'state' => 'new',
      'product_variation' => 1,
      'uid' => $license_owner->id(),
      // Use the unlimited expiry plugin as it's simple.
      'expiration_type' => [
        'target_plugin_id' => 'unlimited',
        'target_plugin_configuration' => [],
      ],
    ]);

    $license->save();
    $license = $this->reloadEntity($license);

    $this->assertEqual('', $license->test_field->value, 'The plugin-controlled field is not set.');

    // Change the state to 'active' and save the license. This should cause the
    // plugin to react.
    $license->state = 'active';
    $license->save();
    $license = $this->reloadEntity($license);

    $this->assertEqual('granted', $license->test_field->value, 'The plugin-controlled field has been set by grantLicense().');

    // Change the state to 'expired' and save the license. This should cause the
    // plugin to react.
    $license->state = 'expired';
    $license->save();
    $license = $this->reloadEntity($license);

    $this->assertEqual('revoked', $license->test_field->value, 'The plugin-controlled field has been set by revokeLicense().');
  }

}
