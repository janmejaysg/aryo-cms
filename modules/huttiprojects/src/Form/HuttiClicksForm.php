<?php

namespace Drupal\huttiprojects\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\huttiprojects\Service\HuttiService;
use Drupal\huttiprojects\Controller\HuttiClicksController;
use Symfony\Component\DependencyInjection\ContainerInterface;

class HuttiClicksForm extends FormBase {

  protected $programNames;
  protected $clicksController;

  public function __construct(HuttiService $huttiProgramNames, HuttiClicksController $clicksController) {
    $this->programNames = $huttiProgramNames;
    $this->clicksController = $clicksController;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('huttiprojects.hutti_service'),
      $container->get('huttiprojects.hutti_clicks_controller')
    );
  }

  public function getFormId() {
    return 'hutti_clicks_form_id';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $allProgramNames = $this->programNames->getProgramNames();
    \Drupal::logger('huttiprojects')->info('data is @data',['@data' => print_r($allProgramNames,TRUE)]);
    $cashbackStatus = ['approved', 'rejected', 'confirmed', 'requested', 'paid', 'cancelled'];
    $status = ['in_process', 'pending', 'tracked', 'missing', 'rejected', 'approved'];

    $form['start_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Start Date'),
    ];

    $form['to_date'] = [
      '#type' => 'date',
      '#title' => $this->t('End Date'),
    ];

    $form['program_name'] = [
      '#type' => 'select',
      '#title' => $this->t('Program Name'),
      '#options' => ['' => $this->t('All Programs')] + $allProgramNames,
    ];

    $form['click_status'] = [
      '#type' => 'select',
      '#title' => $this->t('Click Status'),
      '#options' => ['' => $this->t('All')] + $status,
    ];

    $form['cashback_status'] = [
      '#type' => 'select',
      '#title' => $this->t('Cashback Status'),
      '#options' => ['' => $this->t('All')] + $cashbackStatus,
    ];

    $form['actions'] = [
      '#type' => 'button',
      '#value' => $this->t('Download Clicks'),
      '#ajax' => [
        'callback' => '::downloadClicks',
        'event' => 'click',
      ],
    ];

    $form['#attached']['library'][] = 'huttiprojects/huttiprojects_js_css';

    return $form;
  }

  public function downloadClicks(array &$form, FormStateInterface $form_state) {
    $ajax_response = new AjaxResponse();

    $fromDate = $form_state->getValue('start_date');
    $toDate = $form_state->getValue('to_date');
    $programNameId = $form_state->getValue('program_name');
    $statusId = $form_state->getValue('click_status');
    $cashbackStatusId = $form_state->getValue('cashback_status');

    $programName = $form['program_name']['#options'][$programNameId];
    $status = $form['click_status']['#options'][$statusId];
    $cashbackStatus = $form['cashback_status']['#options'][$cashbackStatusId];

    $clicksData = $this->clicksController->getHuttiClicks($fromDate, $toDate, $programName, $status, $cashbackStatus);

    return $ajax_response;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

  }
}
