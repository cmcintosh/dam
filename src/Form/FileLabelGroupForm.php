<?php

namespace Drupal\dam\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * File Label Group add/edit forms.
 *
 * @ingroup file_label_group
 */
class FileLabelGroupForm extends ContentEntityForm {

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

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label File Label Group.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label File Label Group.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.file_label_group.canonical', ['file_label_group' => $entity->id()]);
  }

}
