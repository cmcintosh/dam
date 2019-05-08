<?php

namespace Drupal\dam;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a FileCollection entity.
 * @ingroup file_collection
 */
interface FileCollectionInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Gets the File Collection name.
   *
   * @return string
   *   Name of the File Collection.
   */
  public function getName();

  /**
   * Sets the File Collection name.
   *
   * @param string $name
   *   The File Collection name.
   *
   * @return \Drupal\dam\FileCollectionInterface
   *   The called File Collection entity.
   */
  public function setName($name);

  /**
   * Gets the File Collection creation timestamp.
   *
   * @return int
   *   Creation timestamp of the File Collection.
   */
  public function getCreatedTime();

  /**
   * Sets the File Collection creation timestamp.
   *
   * @param int $timestamp
   *   The File Collection creation timestamp.
   *
   * @return \Drupal\dam\FileCollectionInterface
   *   The called File Collection entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the File Collection published status indicator.
   *
   * Unpublished File Collection are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the File Collection is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a File Collection.
   *
   * @param bool $published
   *   TRUE to set this File Collection to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\dam\FileCollectionInterface
   *   The called File Collection entity.
   */
  public function setPublished($published);

}
