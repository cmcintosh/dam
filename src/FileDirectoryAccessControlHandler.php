<?php

namespace Drupal\dam;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the File Directory entity.
 *
 * @see \Drupal\dam\Entity\FileLabel.
 */
class FileDirectoryAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished file directory entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published file directory entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit file directory entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete file directory entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add file directory entities');
  }

}
