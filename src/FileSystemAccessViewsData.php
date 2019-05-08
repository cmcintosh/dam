<?php

namespace Drupal\dam;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 * Defines a class to build a listing of FileSystemAccess entities.
 *
 * @ingroup file_system_access
 */
class FileSystemAccessViewsData extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    $build_list = $this->viewMultiple(array($entity), $view_mode, $langcode);

    // The default ::buildMultiple() #pre_render callback won't run, because we
    // extract a child element of the default renderable array. Thus we must
    // assign an alternative #pre_render callback that applies the necessary
    // transformations and then still calls ::buildMultiple().
    $build = $build_list[0];
    $build['#pre_render'][] = array($this, 'build');

    $form = \Drupal::formBuilder()->getForm('Drupal\dam\Form\FileSystemAccessForm', $entity );
    $build['preview'] = $form;

    return $build;
  }

}
