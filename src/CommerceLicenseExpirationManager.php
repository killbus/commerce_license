<?php

namespace Drupal\commerce_license;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Commerce license expiration plugin plugin manager.
 *
 * @see \Drupal\commerce_license\Annotation\CommerceLicenseType
 * @see plugin_api
 */
class CommerceLicenseExpirationManager extends DefaultPluginManager {

  /**
   * Constructs a new CommerceLicenseExpirationManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/Commerce/LicenseExpiration',
      $namespaces,
      $module_handler,
      'Drupal\commerce_license\Plugin\Commerce\LicenseExpiration\LicenseExpirationInterface',
      'Drupal\commerce_license\Annotation\CommerceLicenseExpiration');

    $this->alterInfo('commerce_license_commerce_license_expiration_info');
    $this->setCacheBackend($cache_backend, 'commerce_license_commerce_license_expiration_plugins');
  }

}
