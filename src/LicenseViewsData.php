<?php

namespace Drupal\commerce_license;

use Drupal\views\EntityViewsData;

/**
 * Provides views data for the License entity type.
 */
class LicenseViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $base_table = $this->entityType->getBaseTable() ?: $this->entityType->id();

    // TODO: This needs core patch at https://www.drupal.org/node/2080745 to
    // work properly.
    $data[$base_table]['label'] = [
      'title' => $this->t('Label'),
      'help' => $this->t('The label of the license.'),
      'real field' => 'license_id',
      'field' => [
        'id' => 'entity_label',
      ],
    ];

    // Workaround for core shortcoming.
    // TODO: remove once https://www.drupal.org/node/2337515 is fixed.
    $data[$base_table]['state']['filter']['id'] = 'state_machine_state';

    return $data;
  }

}
