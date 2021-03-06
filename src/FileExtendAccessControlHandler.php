<?php

namespace Drupal\dam;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the File Extend entity.
 *
 * @see \Drupal\dam\Entity\FileExtend.
 */
class FileExtendAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished file entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published file entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit file entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete file entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add file entities');
  }

}
