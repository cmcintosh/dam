<?php

namespace Drupal\dam;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a FileLabel entity.
 * @ingroup file_label
 */
interface FileLabelInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
