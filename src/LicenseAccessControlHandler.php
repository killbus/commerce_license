<?php

namespace Drupal\commerce_license;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the License entity.
 *
 * @see \Drupal\commerce_license\Entity\License.
 */
class LicenseAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\commerce_license\Entity\LicenseInterface $entity */
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view all licenses');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'administer licenses');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'administer licenses');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    $access = AccessResult::allowedIfHasPermission($account, 'create licenses')
      ->orIf(AccessResult::allowedIfHasPermission($account, 'administer licenses'));
    return $access;
  }

}
