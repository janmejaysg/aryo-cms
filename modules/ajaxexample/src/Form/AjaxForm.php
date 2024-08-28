<?php

namespace Drupal\ajaxexample\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;


class AjaxForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'student_registration_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['update_success']
 = [
      '#type' => 'markup',
      '#markup' => '<div id="success-message" class="success"></div>',
    ];

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enter Name'),
    ];

    $form['actions'] = [
      '#type' => 'button',
      '#value' => $this->t('Submit'),
      '#ajax' => [
        'callback' => '::submitForm',
      ],
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    // Use InvokeCommand to trigger a JavaScript function that updates the success message.
    $response->addCommand(new InvokeCommand('#success-message', 'consent'));
    
    return $response;
  }
}