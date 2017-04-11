<?php

namespace Drupal\commerce_license;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\commerce_license\Entity\LicenseInterface;

/**
 * Defines the storage handler class for License entities.
 *
 * This extends the base storage class, adding required special handling for
 * License entities.
 *
 * @ingroup commerce_license
 */
interface LicenseStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of License revision IDs for a specific License.
   *
   * @param \Drupal\commerce_license\Entity\LicenseInterface $entity
   *   The License entity.
   *
   * @return int[]
   *   License revision IDs (in ascending order).
   */
  public function revisionIds(LicenseInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as License author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   License revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\commerce_license\Entity\LicenseInterface $entity
   *   The License entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(LicenseInterface $entity);

  /**
   * Unsets the language for all License with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
