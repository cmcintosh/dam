<?php

namespace Drupal\dam\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class FTPAccountTypeForm.
 */
class FTPAccountTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $ftp_account_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $ftp_account_type->label(),
      '#description' => $this->t("Label for the FTP Account type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $ftp_account_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\dam\Entity\FTPAccountType::load',
      ],
      '#disabled' => !$ftp_account_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $ftp_account_type = $this->entity;
    $status = $ftp_account_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label FTP Account type.', [
          '%label' => $ftp_account_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label FTP Account type.', [
          '%label' => $ftp_account_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($ftp_account_type->toUrl('collection'));
  }

}
