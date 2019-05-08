<?php

namespace Drupal\dam\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * File Extend entity add/edit forms.
 *
 * @ingroup file_extend
 */
class FileExtendForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $entity = $this->entity;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = &$this->entity;

    // Set new Revision.
    $entity->setNewRevision();

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label File.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label File.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.file_extend.canonical', ['file_extend' => $entity->id()]);
  }

}