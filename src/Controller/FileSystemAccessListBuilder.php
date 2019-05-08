<?php

namespace Drupal\dam\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;

/**
 * Provides a list controller for the FileSystemAccess entity.
 *
 * @ingroup file_system_access
 */
class FileSystemAccessListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   *
   * We override ::render() so that we can add our own content above the table.
   * parent::render() is where EntityListBuilder creates the table using our
   * buildHeader() and buildRow() implementations.
   */
  public function render() {
    $build['description'] = [
      '#markup' => $this->t('File System Access Entity. You can manage the fields on the <a href="@adminlink">File System Access admin page</a>.', array(
        '@adminlink' => \Drupal::urlGenerator()
          ->generateFromRoute('file_system_access.settings'),
      )),
    ];

    $build += parent::render();
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['entity_id'] = $this->t('Entity ID');
    $header['entity_type'] = $this->t('Entity Type');
    $header['can_view'] = $this->t('Can View');
    $header['can_write'] = $this->t('Can Write');
    $header['notify_of_upload'] = $this->t('Upload Notification');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['id'] = $entity->id();
    $row['entity_id'] = $entity->get('entity_id')->value;
    $row['entity_type'] = $entity->get('entity_type')->value;
    $row['can_view'] = ($entity->get('can_view')->value) ? 'Yes':'No';
    $row['can_write'] = ($entity->get('can_write')->value) ? 'Yes':'No';
    $row['notify_of_upload'] = ($entity->get('notify_of_upload')->value) ? 'Yes':'No';
    return $row + parent::buildRow($entity);
  }

}
