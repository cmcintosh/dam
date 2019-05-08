<?php

namespace Drupal\dam\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\dam\FileSystemAccessInterface;
use Drupal\user\UserInterface;

/**
 * Defines the FileSystemAccess entity.
 *
 * @ingroup file_system_access
 *
 * @ContentEntityType(
 *   id = "file_system_access",
 *   label = @Translation("File System Access"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\dam\Controller\FileSystemAccessListBuilder",
 *     "views_data" = "Drupal\dam\Entity\FileSystemAccessViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\dam\Form\FileSystemAccessForm",
 *       "add" = "Drupal\dam\Form\FileSystemAccessForm",
 *       "edit" = "Drupal\dam\Form\FileSystemAccessForm",
 *       "delete" = "Drupal\dam\Form\FileSystemAccessDeleteForm",
 *     },
 *     "access" = "Drupal\dam\FileSystemAccessAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\dam\FileSystemAccessHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "file_system_access",
 *   admin_permission = "administer file_system_access entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/dam/file_system_access/{file_system_access}",
 *     "add-form" = "/admin/dam/file_system_access/add",
 *     "edit-form" = "/admin/dam/file_system_access/{file_system_access}/edit",
 *     "delete-form" = "/admin/dam/file_system_access/{file_system_access}/delete",
 *     "collection" = "/admin/dam/file_system_access",
 *   },
 *   field_ui_base_route = "file_system_access.settings"
 * )
 */

class FileSystemAccess extends ContentEntityBase implements FileSystemAccessInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   *
   * When a new entity instance is added, set the user_id entity reference to
   * the current user as the creator of the instance.
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
  * {*inheritdoc}
  */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? TRUE : FALSE);
    return $this;
  }

/**
 * {@inheritdoc}
 */
  public function setAccess($field, $value) {
    $this->set($field, $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    // Standard field, used as unique if primary index.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the file system access entity.'))
      ->setReadOnly(TRUE);

    // Standard field, unique outside of the scope of the current project.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the file system access entity.'))
      ->setReadOnly(TRUE);

    $fields['agent_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Agent Type'))
      ->setDescription(t('Entity Type.'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['agent_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Agent Type'))
      ->setDescription(t('Entity Type.'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['entity_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Entity ID'))
      ->setDescription(t('Entity ID.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity Type'))
      ->setDescription(t('Entity Type.'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['can_view'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Can View'))
      ->setDescription(t('Allows view access to files and folders.'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(TRUE)
      ->setSettings(['on_label' => 'Yes', 'off_label' => 'No'])
      ->setDisplayOptions('view', [
        'label' => 'visible',
        'type' => 'boolean',
        'weight' => 4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['can_write'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Can Write'))
      ->setDescription(t('Allows write access to files and folders.'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(TRUE)
      ->setSettings(['on_label' => 'Yes', 'off_label' => 'No'])
      ->setDisplayOptions('view', [
        'label' => 'visible',
        'type' => 'boolean',
        'weight' => 4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['notify_of_upload'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Notify of Upload'))
      ->setDescription(t('Set notification of upload for files and folders.'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(TRUE)
      ->setSettings(['on_label' => 'Yes', 'off_label' => 'No'])
      ->setDisplayOptions('view', [
        'label' => 'visible',
        'type' => 'boolean',
        'weight' => 4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
