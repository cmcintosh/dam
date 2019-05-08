<?php

namespace Drupal\dam\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\dam\FileCollectionInterface;
use Drupal\user\UserInterface;

/**
 * Defines the FileCollection entity.
 *
 * @ingroup file_collection
 *
 * @ContentEntityType(
 *   id = "file_collection",
 *   label = @Translation("File Collection"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\dam\Controller\FileCollectionListBuilder",
 *     "views_data" = "Drupal\dam\Entity\FileCollectionViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\dam\Form\FileCollectionForm",
 *       "add" = "Drupal\dam\Form\FileCollectionForm",
 *       "edit" = "Drupal\dam\Form\FileCollectionForm",
 *       "delete" = "Drupal\dam\Form\FileCollectionDeleteForm",
 *     },
 *     "access" = "Drupal\dam\FileCollectionAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\dam\FileCollectionHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "file_collection",
 *   revision_table = "file_collection_revision",
 *   admin_permission = "administer file_collection entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "revision" = "revision_id",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/dam/file_collection/{file_collection}",
 *     "add-form" = "/admin/dam/file_collection/file_collection/add",
 *     "edit-form" = "/admin/dam/file_collection/{file_collection}/edit",
 *     "delete-form" = "/admin/dam/file_collection/{file_collection}/delete",
 *     "collection" = "/admin/dam/file_collection",
 *   },
 *   field_ui_base_route = "file_collection.settings"
 * )
 */

class FileCollection extends ContentEntityBase implements FileCollectionInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'uid' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // If no owner has been set explicitly, make the current user the owner.
    if (!$this->getOwner()) {
      $this->setOwnerId(\Drupal::currentUser()->id());
    }
    // If no revision author has been set explicitly, make the node owner the
    // revision author.
    if (!$this->getRevisionAuthor()) {
      $this->setRevisionAuthorId($this->getOwnerId());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preSaveRevision(EntityStorageInterface $storage, \stdClass $record) {
    parent::preSaveRevision($storage, $record);
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
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
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
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
  public function getRevisionAuthor() {
    return $this->get('revision_uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionAuthorId($uid) {
    $this->set('revision_uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionLog() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionLog($revision_log) {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Standard field, used as unique if primary index.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the File Directory entity.'))
      ->setReadOnly(TRUE);

    // Standard field, unique outside of the scope of the current project.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the File Directory entity.'))
      ->setReadOnly(TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the File Directory entity.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['file'] = BaseFieldDefinition::create('file')
      ->setLabel(t('Upload a file'))
      ->setDescription(t('Choose file'))
      ->setRevisionable(TRUE)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setSetting('file_extensions', 'txt pdf doc docx csv xls xlsx png jpg jpeg ')
      ->setDisplayOptions('form', [
        'type' => 'file',
        'weight' => -4,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'file',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['comments'] = BaseFieldDefinition::create('comment')
      ->setLabel(t('Comments'))
      ->setDescription(t('Comments.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'default_mode'=> 1,
        'per_page'=> 50,
        'anonymous'=> 0,
        'form_location'=> 1,
        'preview'=> 1,
        'comment_type'=> 'file_collection_comment',
        'locked'=> false,
      ])
      // ->setDefaultValue([
      //   'status'=> 2,
      //   'cid'=> 0,
      //   'last_comment_timestamp'=> 0,
      //   'last_comment_name'=> null,
      //   'last_comment_uid'=> 0,
      //   'comment_count'=> 0,
      // ])
      // ->setDisplayOptions('form', [
      //   'type' => 'comment_default',
      //   'settings' => [
      //     'form_location' => 1,
      //     'default_mode'=> 1,
      //     'per_page'=> 50,
      //     'anonymous'=> 0,
      //     'preview'=> 1,
      //     'comment_type'=> 'file_collection_comment',
      //     'locked'=> false,
      //   ],
      //   'weight' => -4,
      // ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the File Directory entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Updated'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['revision_uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Revision user ID'))
      ->setDescription(t('The user ID of the author of the current revision.'))
      ->setSetting('target_type', 'user')
      ->setRevisionable(TRUE);

    return $fields;
  }

  /**
   * Checks the physical folder on the server exist.
   *
   * @param string $path
   *   Path to the folder.
   *
   * @return bool
   *   Returns the status of the folder.
   */
  public function updateDirectory($path) {
    if (file_exists($path)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Create Zip of the folder.
   *
   * @param string $path
   *   Path to the folder.
   *
   * @return string
   *   Returns path to the Zip folder.
   */
  public function createZip($path) {
    $dirName = pathinfo($path);
    $dirName = $dirName['basename'];
    // Get real path for our folder.
    $rootPath = \Drupal::service('file_system')->realpath($path);

    if (!class_exists('ZipArchive')) {
      throw new \Exception('Requires the "zip" PHP extension to be installed and enabled in order to export the site as a package.');
      return FALSE;
    }

    // Initialize archive object.
    $zip = new \ZipArchive();
    // Get files directory path.
    $filesPath = \Drupal::service('file_system')->realpath(file_default_scheme() . "://");
    $zip->open($filesPath . '/' . $dirName . '.zip', \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

    // Create recursive directory iterator.
    $files = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($rootPath),
        \RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $name => $file) {
      // Skip directories (they would be added automatically)
      if (!$file->isDir()) {
        // Get real and relative path for current file.
        $filePath = $file->getRealPath();
        $relativePath = substr($filePath, strlen($rootPath) + 1);

        // Add current file to archive.
        $zip->addFile($filePath, $relativePath);
      }
    }

    // Zip archive will be created only after closing object.
    $zip->close();

    $zipPath = $filesPath . '/' . $dirName . '.zip';
    return \Drupal::service('file_system')->realpath($zipPath);
  }

  /**
   * Create Zip of the folder and send it in an email.
   *
   * @param string $path
   *   Path to the folder.
   * @param string $email_id
   *   Email address.
   */
  public function sendEmail($path, $email_id) {
    // Create ZIP.
    $zipPath = $this->createZip($path);
    // Send email.
    $mailManager = \Drupal::service('plugin.manager.mail');
    $module = 'dam';
    $key = 'dam_mail';
    $params['message'] = $this->t('Download ZIP here @var', ['@var' => $zipPath]);
    $params['subject'] = '';
    $langcode = \Drupal::currentUser()->getPreferredLangcode();
    $send = true;

    $result = $mailManager->mail($module, $key, $email_id, $langcode, $params, NULL, $send);
    if ($result['result'] != true) {
      $message = t('There was a problem sending your email notification to @email.', array('@email' => $email_id));
      drupal_set_message($message, 'error');
      \Drupal::logger('dam')->error($message);
      return;
    }

    $message = t('An email notification has been sent to @email ', array('@email' => $email_id));
    drupal_set_message($message);
    \Drupal::logger('dam')->notice($message);
  }
}
