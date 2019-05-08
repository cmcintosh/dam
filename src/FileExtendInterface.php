<?php

namespace Drupal\dam;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a FileExtend entity.
 * @ingroup file_extend
 */
interface FileExtendInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Gets the File Extend name.
   *
   * @return string
   *   Name of the File Extend.
   */
  public function getName();

  /**
   * Sets the File Extend name.
   *
   * @param string $name
   *   The File Extend name.
   *
   * @return \Drupal\dam\FileExtendInterface
   *   The called File Extend entity.
   */
  public function setName($name);

  /**
   * Gets the File Extend creation timestamp.
   *
   * @return int
   *   Creation timestamp of the File Extend.
   */
  public function getCreatedTime();

  /**
   * Sets the File Extend creation timestamp.
   *
   * @param int $timestamp
   *   The File Extend creation timestamp.
   *
   * @return \Drupal\dam\FileExtendInterface
   *   The called File Extend entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the File Extend published status indicator.
   *
   * Unpublished File Extend are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the File Extend is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a File Extend.
   *
   * @param bool $published
   *   TRUE to set this File Extend to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\dam\FileExtendInterface
   *   The called File Extend entity.
   */
  public function setPublished($published);

}
