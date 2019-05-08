<?php

namespace Drupal\dam;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a FileLabelGroup entity.
 * @ingroup file_label_group
 */
interface FileLabelGroupInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
