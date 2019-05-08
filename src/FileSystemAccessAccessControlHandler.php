<?php

namespace Drupal\dam;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the File System Access entity.
 *
 * @see \Drupal\dam\Entity\FileSystemAccess.
 */
class FileSystemAccessAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished File System Access entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published File System Access entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit File System Access entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete File System Access entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add File System Access entities');
  }

}
