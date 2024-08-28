<?php

namespace Drupal\aryoleads\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\Database\Database;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\AjaxResponse;
use Symfony\Component\HttpFoundation\JsonResponse;

class DistributorLeadsForm extends FormBase {

  protected $httpClient;

  public function __construct(ClientInterface $http_client) {
    $this->httpClient = $http_client;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client')
    );
  }

  public function getFormId() {
    return 'distleads_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $conn = Database::getConnection();

    // $currentIPService = \Drupal::service('current_ip');
    // $currentIP = $currentIPService->getCurrentIP();

    // echo $currentIP;

    $category_query = $conn->select('category', 'c')
      ->fields('c', ['category_id', 'category_name'])
      ->orderBy('category_name', 'ASC');
    $category_records = $category_query->execute()->fetchAllKeyed();
    $category_options = [];

    foreach ($category_records as $key => $category_results) {
      $category_options[$key] = $category_results;
    }

    $form['from_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Start Date'),
      '#required' => TRUE,
    ];

    $form['to_date'] = [
      '#type' => 'date',
      '#title' => $this->t('End Date'),
      '#required' => TRUE,
    ];

    $form['lead_status'] = [
      '#type' => 'select',
      '#title' => $this->t('Lead Status'),
      '#options' => [
        '' => $this->t('All'),
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
        'progress' => ['type' => 'throbber', 'message' => 'Fetching Projects'],
      ],
      '#default_value' => $this->getDefaultCategory($form_state),
    ];

    $form['project'] = [
      '#type' => 'select',
      '#title' => $this->t('Projects'),
      '#options' => [],
      '#attributes' => ['id' => 'states-to-update'],
      '#validated' => TRUE,
      '#default_value' => $this->getDefaultProject($form_state),
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Download Leads'),
      '#attributes' => ['class' => ['btn', 'btn-primary', 'custom-submit-button']],
    ];

    return $form;
  }

  private function getDefaultCategory(FormStateInterface $form_state) {
    // Fetch default category from the record if available
    return (isset($record['category_id']) && isset($_GET['id'])) ? $record['category_id'] : '';
  }

  private function getDefaultProject(FormStateInterface $form_state) {
    // Fetch default project from the record if available
    return (isset($record['project_id']) && isset($_GET['id'])) ? $record['project_id'] : '';
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $from_date = $form_state->getValue('from_date');
    $to_date = $form_state->getValue('to_date');
    $lead_status = $form_state->getValue('lead_status');
    $category_id = $form_state->getValue('category');
    $project_id = $form_state->getValue('project');

    $from_date = !empty($from_date) ? $from_date : 'undefined';
    $to_date = !empty($to_date) ? $to_date : 'undefined';
    $lead_status = !empty($lead_status) ? [$lead_status] : ['In process', 'Approved', 'Expired', 'Rejected'];

    // Fetch selected category name directly from the form
    $category_name = !empty($category_id) ? $form['category']['#options'][$category_id] : [];

    // Fetch selected project name
    $project_name = $this->getProjectName($project_id);

    // Make the HTTP request to the external API
    $client = \Drupal::httpClient();
    $url = 'http://localhost:8081/getAryoDistributorLeads';

    try {
      $response = $client->request('GET', $url, [
        'query' => [
          'fromDate' => $from_date,
          'todate' => $to_date,
          'status' => $lead_status,
          'category' => $category_name,
          'projectName' => $project_name,
        ],
        'timeout' => 300,
      ]);

      $csvContent = $response->getBody()->getContents();
      $timestamp = date('Y-m-d_H-i-s');
      $filename = "Aryo_Dist_Leads_{$timestamp}.csv";

      $headers = [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => 'attachment; filename="' . $filename . '"',
      ];

      $response = new \Symfony\Component\HttpFoundation\Response($csvContent, 200, $headers);
      \Drupal::service('page_cache_kill_switch')->trigger();
      $response->send();
      exit(); // Ensure no further processing happens

    } catch (RequestException $e) {
      \Drupal::logger('aryoleads')->error($e->getMessage());
      return new JsonResponse(['error' => 'Failed to fetch data from external API.'], 500);
    }
  }

  public function getStates(array &$form, FormStateInterface $form_state) {
    $triggeringElement = $form_state->getTriggeringElement();
    // \Drupal::logger('aryoleads')->info('message => @triggeringElement', ['message'=> $triggeringElement]);

    $value = $triggeringElement['#value'];
    $projects = $this->getProjectsByCategory($value);
    $wrapper_id = $triggeringElement['#ajax']['wrapper'];
    $renderedField = "<option value=''>All Projects</option>";

    foreach ($projects as $key => $project_value) {
      $renderedField .= "<option value ='$key'>$project_value</option>";
    }

    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('#' . $wrapper_id, $renderedField));

    return $response;
  }

  public function getProjectsByCategory($category_id) {
    $conn = Database::getConnection();
    $projects_query = $conn->select('projects', 'p')
      ->fields('p', ['project_id', 'project_name'])
      ->condition('category_id', $category_id, "=");
    $project_records = $projects_query->execute()->fetchAllKeyed();
    $project_options = [];

    foreach ($project_records as $key => $project_result) {
      $project_options[$key] = $project_result;
    }

    return $project_options;
  }

  private function getProjectName($project_id) {
    if (!empty($project_id)) {
      $conn = Database::getConnection();
      $project_query = $conn->select('projects', 'p')
        ->fields('p', ['project_name'])
        ->condition('project_id', $project_id);
      $project_name = $project_query->execute()->fetchField();

      return $project_name ? $project_name : [];
    }

    return [];
  }
}
