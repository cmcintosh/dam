<?php

namespace Drupal\dam;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a FileSystemAccess entity.
 * @ingroup file_system_access
 */
interface FileSystemAccessInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
