<?php

namespace Drupal\dam;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a FileDirectory entity.
 * @ingroup file_directory
 */
interface FileDirectoryInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Gets the File Directory name.
   *
   * @return string
   *   Name of the File Directory.
   */
  public function getName();

  /**
   * Sets the File Directory name.
   *
   * @param string $name
   *   The File Directory name.
   *
   * @return \Drupal\dam\FileDirectoryInterface
   *   The called File Directory entity.
   */
  public function setName($name);

  /**
   * Gets the File Directory creation timestamp.
   *
   * @return int
   *   Creation timestamp of the File Directory.
   */
  public function getCreatedTime();

  /**
   * Sets the File Directory creation timestamp.
   *
   * @param int $timestamp
   *   The File Directory creation timestamp.
   *
   * @return \Drupal\dam\FileDirectoryInterface
   *   The called File Directory entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the File Directory published status indicator.
   *
   * Unpublished File Directory are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the File Directory is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a File Directory.
   *
   * @param bool $published
   *   TRUE to set this File Directory to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\dam\FileDirectoryInterface
   *   The called File Directory entity.
   */
  public function setPublished($published);

}
