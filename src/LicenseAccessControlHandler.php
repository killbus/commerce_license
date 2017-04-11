<?php

namespace Drupal\commerce_license;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

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
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished license entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published license entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit license entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete license entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add license entities');
  }

}
