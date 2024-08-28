<?php

namespace Drupal\huttiprojects\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\Exception\RequestException;

class CategoryForm extends FormBase {


  protected $httpClient;
  protected $categoryOptions;

  const BASE_URL = 'http://localhost:8081';

  public function __construct(ClientInterface $http_client){
    $this->httpClient = $http_client;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client')
    );
  }

  public function getFormId() {
    return 'category_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    $conn = Database::getConnection();

    $category_query = $conn->select('hutti_categories', 'c');
    $category_query->fields('c', ['category_id', 'category_name']);
    $category_query->orderBy('category_name', 'ASC');
    $category_records = $category_query->execute()->fetchAllKeyed();
    $category_options = [];
  
    foreach ($category_records as $key => $category_results) {
      $category_options[$key] = $category_results;
    }
  
    $this->categoryOptions = $category_options;
 
    $form['#tree'] = TRUE;

    $form['ID'] = [
      '#type' => 'textfield',
      '#title' => 'ID',
      '#default_value' => '',
      '#required' => TRUE,
    ];

    $Structure = [
     "CATEGORIES" => []
    ];

    foreach ($Structure as $section => $fields) {
      $this->buildSection($form, $section, $fields, $form_state,$category_options);
    }

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Category'),
      '#attributes' => ['class' => ['btn', 'btn-primary']]
    ];

    return $form;
  }

  private function buildSection(array &$form, $section, array $fields, FormStateInterface $form_state, $category_options) {
    $form[$section] = [
      '#type' => 'details',
      '#title' => $this->t($section),
      '#open' => TRUE,
      '#tree' => TRUE
    ];
    $this->buildCategoriesSection($form[$section], $category_options);   
  }

  private function buildCategoriesSection(array &$form, array $category_options) {
    $form['category'] = [
      '#type' => 'select',
      '#options' => $category_options,
      '#default_value' => (isset($record['category_id']) && isset($_GET['id'])) ? ['category_id'] : '', 
    ];
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $section =  str_replace('_submit', '', $triggering_element['#name']);

    $updatedDetails = $this->buildUpdatedDetailsArray($form_state->getValues(),  $section);
    $this->uploadSearchKeywordsToServer($updatedDetails);

  }

  private function buildUpdatedDetailsArray($form_values, $section) {
    $updatedDetails = [];

    if (isset($form_values['ID'])) {
      $updatedDetails['ID'] = $form_values['ID'];
    }

    if (isset($form_values['CATEGORIES']) && isset($form_values['CATEGORIES']['category'])) {
      $category_id = $form_values['CATEGORIES']['category'];
      if (isset($this->categoryOptions[$category_id])) {
          $updatedDetails['CATEGORIES']['category'] = $this->categoryOptions[$category_id];
      }
      if (isset($form_values['CATEGORIES'][$section])) {
          unset($updatedDetails['CATEGORIES'][ $section]);
      }
  }
    return $updatedDetails;
  }

  public function uploadSearchKeywordsToServer($details) {
    try {
      $client = new Client();
      $response = $client->post(self::BASE_URL .'/add_ID_To_Category', [
        'json' => $details,
        'headers' => [
          'Content-Type' => 'application/json',
        ],
      ]);
      $data = json_decode($response->getBody(), TRUE);
      return $data;
    } catch (RequestException $e) {
      $this->messenger->addError($this->t('An error occurred while uploading the file: @message', ['@message' => $e->getMessage()]));
    }
  }
}
