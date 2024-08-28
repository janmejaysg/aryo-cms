<?php

namespace Drupal\aryoleads\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\AjaxResponse;

class AryoLeadsForm extends FormBase {

  public function getFormId() {
    return 'aryoleads_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $conn = Database::getConnection();

    $category_query = $conn->select('category', 'c');
    $category_query->fields('c', ['category_id', 'category_name']);
    $category_query->orderBy('category_name', 'ASC');
    $category_records = $category_query->execute()->fetchAllKeyed();
    $category_options = [];

    foreach ($category_records as $key => $category_results) {
      $category_options[$key] = $category_results;
    }

 

    $form['from_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Start Date'),
      '#attributes' => ['class' => ['custom-textfield']]
    ];

    $form['to_date'] = [
      '#type' => 'date',
      '#title' => $this->t('End Date'),
      '#attributes' => ['class' => ['custom-textfield']]
    ];

    $form['lead_status'] = [
      '#type' => 'select',
      '#title' => $this->t('Lead Status'),
      '#options' => [
        '' => $this->t('Select Status'),
        'In process' => $this->t('In process'),
        'Approved' => $this->t('Approved'),
        'Expired' => $this->t('Expired'),
        'Rejected' => $this->t('Rejected'),
      ],
    ];

       $form['category'] = [
        '#type' => 'select',
        '#title' => $this->t('Category'),
        '#options' => ['' => $this->t('All Categories')] + $category_options,
      '#ajax' => [
        'callback' => [$this, 'getStates'],
        'event' => 'change',
        'method' => 'html',
        'wrapper' => 'states-to-update',
        'progress' => ['type' => 'throbber', 'message' => NULL],
      ],
      '#default_value' => (isset($record['category_id']) && isset($_GET['id'])) ? $record['category_id'] : '',
    ];

    $form['project'] = [
      '#type' => 'select',
      '#title' => $this->t('Projects'),
      '#options' => [],
      '#attributes' => ['id' => 'states-to-update'],
      '#validated' => TRUE,
      '#default_value' => (isset($record['project_id']) && isset($_GET['id'])) ? $record['project_id'] : '',
    ];

    
    $form['leadId'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Lead_Id'),
      '#attributes' => ['class' => ['custom-textfield']]
    ];

        
    $form['agentId'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Agent_Id'),
      '#attributes' => ['class' => ['custom-textfield']]
    ];

    $form['agentMobile'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Agent_Mobile'),
      '#attributes' => ['class' => ['custom-textfield']]
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Fetch Leads'),
      '#attributes' => ['class' => ['btn', 'btn-primary', 'custom-submit-button']],
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $from_date = $form_state->getValue('from_date');
    $to_date = $form_state->getValue('to_date');
    $lead_status = $form_state->getValue('lead_status');
    $category_id = $form_state->getValue('category');
    $project_id = $form_state->getValue('project');
    $lead_id = $form_state->getValue('leadId');
    $agent_id = $form_state->getValue('agentId');
    $agent_mobile = $form_state->getValue('agentMobile');

    $from_date = !empty($from_date)
    ? $from_date
    : 'undefined';

    $to_date = !empty($to_date)
    ? $to_date
    : 'undefined';


    $lead_status = !empty($lead_status)
        ? $lead_status
        : 'none';

  // Fetch selected category name directly from the form
  $category_name = !empty($category_id)
  ? $form['category']['#options'][$category_id] // Get from options if selected
  : 'none';                                   // Set to 'none' if not selected

  // Fetch selected project name directly from the form
  $project_name = $this->getProjectName($project_id);

  $lead_id = !empty($lead_id) ? $lead_id : 'none';
  $agent_id = !empty($agent_id) ? $agent_id : 'none';
  $agent_mobile = !empty($agent_mobile) ? $agent_mobile : 'none';



    $form_state->setRedirect('aryoleads.fetch_data', [
      'fromDate' => $from_date,
      'toDate' => $to_date,
      'status' => $lead_status,
      'category' => $category_name,
      'projectName' => $project_name,
      'leadId' => $lead_id,
      'agentId' => $agent_id,
      'mobile' => $agent_mobile
    ]);
  }

  public function getStates(array &$form, FormStateInterface $form_state) {
    $triggeringElement = $form_state->getTriggeringElement();
    $value = $triggeringElement['#value'];
    $projects = $this->getProjectsByCategory($value);
    $wrapper_id = $triggeringElement['#ajax']['wrapper'];
    $renderedField = '<option value="">All Projects</option>';

    foreach ($projects as $key => $value) {
      $renderedField .= "<option value ='$key'>$value</option>";
    }

    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('#'.$wrapper_id, $renderedField));

    return $response;
  }

  

  public function getProjectsByCategory($default_states) {
    $projects_record = [];
    $conn = Database::getConnection();
    $projects_query = $conn->select('projects', 'p');
    $projects_query->fields('p', ['project_id', 'project_name']);
    $projects_query->condition('category_id', $default_states, '=');
    $projects_record = $projects_query->execute()->fetchAllKeyed();
    $project_options = [];

    echo $projects_record;
    foreach ($projects_record as $key => $projects_result) {
      $project_options[$key] = $projects_result;
    }

    return $project_options;
  }

  private function getProjectName($project_id) {
    if (!empty($project_id)) {
      $conn = Database::getConnection();
      $project_query = $conn->select('projects', 'p');
      $project_query->fields('p', ['project_name']);
      $project_query->condition('project_id', $project_id);
      $project_name = $project_query->execute()->fetchField();

      return $project_name ? $project_name : 'none';
    }

    return 'none';
  }

}
