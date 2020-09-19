<?php

namespace Drupal\Tests\commerce_license\Kernel;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;
use Drupal\Tests\commerce_cart\Traits\CartManagerTestTrait;

/**
 * Tests renewable behavior on the license.
 *
 * @group commerce_license
 */
class CommerceOrderSyncRenewalTest extends CommerceKernelTestBase {

  use CartManagerTestTrait;
  use LicenseOrderCompletionTestTrait;

  /**
   * The order type.
   *
   * @var \Drupal\commerce_order\Entity\OrderType
   */
  protected $orderType;

  /**
   * The product variation type.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariationType
   */
  protected $variationType;

  /**
   * The product variation type for a non renewable license.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariationType
   */
  protected $nonRenewableVariationType;

  /**
   * The variation to test against.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariation
   */
  protected $variation;

  /**
   * The non renewable variation to test against.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariation
   */
  protected $nonRenewableVariation;

  /**
   * The cart manager.
   *
   * @var \Drupal\commerce_cart\CartManagerInterface
   */
  protected $cartManager;

  /**
   * The license storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $licenseStorage;

  /**
   * The customer.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * The license used to test renewable behavior.
   *
   * @var \Drupal\commerce_license\Entity\LicenseInterface
   */
  protected $license;

  /**
   * The license used to test non renewable behavior.
   *
   * @var \Drupal\commerce_license\Entity\LicenseInterface
   */
  protected $nonRenewableLicense;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'entity_reference_revisions',
    'interval',
    'path',
    'profile',
    'state_machine',
    'system',
    'commerce_product',
    'commerce_order',
    'recurring_period',
    'commerce_license',
    'commerce_license_test',
    'commerce_number_pattern',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installEntitySchema('commerce_license');
    $this->installConfig('system');
    $this->installConfig('commerce_order');
    $this->installConfig('commerce_product');
    $this->createUser();

    $this->licenseStorage = $this->container->get('entity_type.manager')->getStorage('commerce_license');

    // Create an order type for licenses which uses the fulfillment workflow.
    $this->orderType = $this->createEntity('commerce_order_type', [
      'id' => 'license_order_type',
      'label' => $this->randomMachineName(),
      'workflow' => 'order_default',
    ]);
    commerce_order_add_order_items_field($this->orderType);

    // Create an order item type that uses that order type.
    $order_item_type = $this->createEntity('commerce_order_item_type', [
      'id' => 'license_order_item_type',
      'label' => $this->randomMachineName(),
      'purchasableEntityType' => 'commerce_product_variation',
      'orderType' => 'license_order_type',
      'traits' => ['commerce_license_order_item_type'],
    ]);
    $this->traitManager = \Drupal::service('plugin.manager.commerce_entity_trait');
    $trait = $this->traitManager->createInstance('commerce_license_order_item_type');
    $this->traitManager->installTrait($trait, 'commerce_order_item', $order_item_type->id());

    // Create a product variation type with the license trait, using our order
    // item type.
    $this->variationType = $this->createEntity('commerce_product_variation_type', [
      'id' => 'license_pv_type',
      'label' => $this->randomMachineName(),
      'orderItemType' => 'license_order_item_type',
      'traits' => ['commerce_license'],
    ]);
    $trait = $this->traitManager->createInstance('commerce_license');
    $this->traitManager->installTrait($trait, 'commerce_product_variation', $this->variationType->id());

    $this->variationType->setThirdPartySetting('commerce_license', 'allow_renewal', TRUE);
    $this->variationType->setThirdPartySetting('commerce_license', 'interval', '1');
    $this->variationType->setThirdPartySetting('commerce_license', 'period', 'month');
    $this->variationType->save();

    // Create a product variation which grants a license.
    $this->variation = $this->createEntity('commerce_product_variation', [
      'type' => 'license_pv_type',
      'sku' => $this->randomMachineName(),
      'price' => [
        'number' => 999,
        'currency_code' => 'USD',
      ],
      'license_type' => [
        'target_plugin_id' => 'renewable',
        'target_plugin_configuration' => [],
      ],
      // Use the rolling interval expiry plugin as it's simple.
      'license_expiration' => [
        'target_plugin_id' => 'rolling_interval',
        'target_plugin_configuration' => [
          'interval' => [
            'interval' => '1',
            'period' => 'year',
          ],
        ],
      ],
    ]);

    // We need a product too otherwise tests complain about the missing
    // backreference.
    $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => $this->randomMachineName(),
      'stores' => [$this->store],
      'variations' => [$this->variation],
    ]);
    $this->reloadEntity($this->variation);
    $this->variation->save();

    // Create a product variation with a non-renewable license.
    $this->nonRenewableVariationType = $this->createEntity('commerce_product_variation_type', [
      'id' => 'license_nrpv_type',
      'label' => $this->randomMachineName(),
      'orderItemType' => 'license_order_item_type',
      'traits' => ['commerce_license'],
    ]);
    $this->traitManager->installTrait($trait, 'commerce_product_variation', $this->nonRenewableVariationType->id());

    $this->nonRenewableVariationType->setThirdPartySetting('commerce_license', 'allow_renewal', FALSE);
    $this->nonRenewableVariationType->save();

    // Create a product variation which grants a license.
    $this->nonRenewableVariation = $this->createEntity('commerce_product_variation', [
      'type' => 'license_nrpv_type',
      'sku' => $this->randomMachineName(),
      'price' => [
        'number' => 999,
        'currency_code' => 'USD',
      ],
      'license_type' => [
        'target_plugin_id' => 'renewable',
        'target_plugin_configuration' => [],
      ],
      // Use the rolling interval expiry plugin as it's simple.
      'license_expiration' => [
        'target_plugin_id' => 'rolling_interval',
        'target_plugin_configuration' => [
          'interval' => [
            'interval' => '1',
            'period' => 'year',
          ],
        ],
      ],
    ]);

    // We need a product too otherwise tests complain about the missing
    // backreference.
    $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => $this->randomMachineName(),
      'stores' => [$this->store],
      'variations' => [$this->variation],
    ]);
    $this->reloadEntity($this->variation);
    $this->variation->save();

    // Create a user to use for orders.
    $this->user = $this->createUser();

    $this->installCommerceCart();
    $this->store = $this->createStore();

    // Create a license in the active state.
    $this->license = $this->createEntity('commerce_license', [
      'type' => 'renewable',
      'state' => 'active',
      'product_variation' => $this->variation->id(),
      'uid' => $this->user->id(),
      // 06/01/2015 @ 1:00pm (UTC).
      'expires' => '1433163600',
      'expiration_type' => [
        'target_plugin_id' => 'rolling_interval',
        'target_plugin_configuration' => [
          'interval' => [
            'interval' => '1',
            'period' => 'year',
          ],
        ],
      ],
    ]);

    // Create a non renewable license in the active state.
    $this->nonRenewableLicense = $this->createEntity('commerce_license', [
      'type' => 'renewable',
      'state' => 'active',
      'product_variation' => $this->nonRenewableVariation->id(),
      'uid' => $this->user->id(),
      // 06/01/2015 @ 1:00pm (UTC).
      'expires' => '1433163600',
      'expiration_type' => [
        'target_plugin_id' => 'rolling_interval',
        'target_plugin_configuration' => [
          'interval' => [
            'interval' => '1',
            'period' => 'year',
          ],
        ],
      ],
    ]);
  }

  /**
   * Tests that a license can't be purchased outside the renewable window.
   */
  public function testRenewOutsideRenewalWindow() {
    // Mock the current time service.
    $expiration_time_outside_window = strtotime('- 2 months', $this->license->getExpiresTime());

    $mock_builder = $this->getMockBuilder('Drupal\Component\Datetime\TimeInterface')
      ->disableOriginalConstructor();

    $datetime_service = $mock_builder->getMock();
    $datetime_service->expects($this->atLeastOnce())
      ->method('getRequestTime')
      ->willReturn($expiration_time_outside_window);
    $this->container->set('datetime.time', $datetime_service);

    // Add a product with license to the cart.
    $cart_order = $this->container->get('commerce_cart.cart_provider')->createCart('license_order_type', $this->store, $this->user);
    $this->cartManager = $this->container->get('commerce_cart.cart_manager');
    $order_item = $this->cartManager->addEntity($cart_order, $this->variation);

    // Assert the order item is NOT in the cart.
    $this->assertFalse($cart_order->hasItem($order_item));
  }

  /**
   * Tests that a license is extended when you repurchased it.
   */
  public function testRenewInRenewalWindow() {
    // Mock the current time service.
    $expiration_time_inside_window = strtotime('- 1 week', $this->license->getExpiresTime());

    $mock_builder = $this->getMockBuilder('Drupal\Component\Datetime\TimeInterface')
      ->disableOriginalConstructor();

    $datetime_service = $mock_builder->getMock();
    $datetime_service->expects($this->atLeastOnce())
      ->method('getRequestTime')
      ->willReturn($expiration_time_inside_window);
    $this->container->set('datetime.time', $datetime_service);

    // Add a product with license to the cart.
    $cart_order = $this->container->get('commerce_cart.cart_provider')->createCart('license_order_type', $this->store, $this->user);
    $this->cartManager = $this->container->get('commerce_cart.cart_manager');
    $order_item = $this->cartManager->addEntity($cart_order, $this->variation);
    $order_item = $this->reloadEntity($order_item);

    // Check that the order item has the previous license.
    $this->assertNotNull($order_item->license->entity, 'The order item has a license set on it.');
    $this->assertEquals($this->license->id(), $order_item->license->entity->id(), 'The order item has a reference to the existing license.');

    // Assert the order item IS IN the cart.
    $this->assertTrue($cart_order->hasItem($order_item), 'The order item IS IN the cart.');

    // Take the order through checkout.
    $this->completeLicenseOrderCheckout($cart_order);

    // Reload the entity because it has been changed.
    $this->license = $this->reloadEntity($this->license);

    $this->assertEquals(date(DATE_ATOM, strtotime('+1 year', 1433163600)), date(DATE_ATOM, $this->license->getExpiresTime()), 'The license has been extended for a year.');
  }

  /**
   * Tests that a license is active after removing renewing product from cart.
   */
  public function testRemovingProductFromCart() {
    $initial_expiration_time = $this->license->getExpiresTime();

    // Mock the current time service.
    $expiration_time_inside_window = strtotime('- 1 week', $initial_expiration_time);

    $mock_builder = $this->getMockBuilder('Drupal\Component\Datetime\TimeInterface')
      ->disableOriginalConstructor();

    $datetime_service = $mock_builder->getMock();
    $datetime_service->expects($this->atLeastOnce())
      ->method('getRequestTime')
      ->willReturn($expiration_time_inside_window);
    $this->container->set('datetime.time', $datetime_service);

    // Add a product with license to the cart.
    $cart_order = $this->container->get('commerce_cart.cart_provider')->createCart('license_order_type', $this->store, $this->user);
    $this->cartManager = $this->container->get('commerce_cart.cart_manager');
    $order_item = $this->cartManager->addEntity($cart_order, $this->variation);
    $order_item = $this->reloadEntity($order_item);

    // Check that the order item has the previous license.
    $this->assertNotNull($order_item->license->entity, 'The order item has a license set on it.');
    $this->assertEquals($this->license->id(), $order_item->license->entity->id(), 'The order item has a reference to the existing license.');

    // Assert the order item IS IN the cart.
    $this->assertTrue($cart_order->hasItem($order_item), 'The order item IS IN the cart.');

    // Test that the license is in renewal_in_progress state.
    $this->assertEquals('renewal_in_progress', $this->license->getState()->value, 'The license is in "renewal in progress" state.');

    // Remove the item from the cart.
    $this->cartManager->removeOrderItem($cart_order, $order_item);

    // Assert the order item is NOT in the cart.
    $this->assertFALSE($cart_order->hasItem($order_item), 'The order item is NOT in the cart.');

    // Reload the entity because it may have been changed.
    $this->license = $this->reloadEntity($this->license);

    // Test that the license is back to active state without the expiration date
    // extended.
    $this->assertEquals('active', $this->license->getState()->value, 'The license is back to the "active" state.');
    $this->assertEquals($initial_expiration_time, $this->license->getExpiresTime(), 'The license has still the same expiration time.');
  }

  /**
   * Tests that a non renewable license can't be purchased if still active.
   */
  public function testNonRenewableLicense() {
    // Add a product with license to the cart.
    $cart_order = $this->container->get('commerce_cart.cart_provider')->createCart('license_order_type', $this->store, $this->user);
    $this->cartManager = $this->container->get('commerce_cart.cart_manager');
    $order_item = $this->cartManager->addEntity($cart_order, $this->nonRenewableVariation);

    // Assert the order item is NOT in the cart.
    $this->assertFalse($cart_order->hasItem($order_item));
  }

  /**
   * Creates and saves a new entity.
   *
   * @param string $entity_type
   *   The entity type to be created.
   * @param array $values
   *   An array of settings.
   *   Example: 'id' => 'foo'.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A new, saved entity.
   */
  protected function createEntity($entity_type, array $values) {
    /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
    $storage = \Drupal::service('entity_type.manager')->getStorage($entity_type);
    $entity = $storage->create($values);
    $status = $entity->save();
    $this->assertEquals(SAVED_NEW, $status, new FormattableMarkup('Created %label entity %type.', [
      '%label' => $entity->getEntityType()->getLabel(),
      '%type' => $entity->id(),
    ]));
    // The newly saved entity isn't identical to a loaded one, and would fail
    // comparisons.
    $entity = $storage->load($entity->id());

    return $entity;
  }

}
