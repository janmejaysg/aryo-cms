<?php

namespace Drupal\huttiprojects\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\Exception\RequestException;

class SearchForm extends FormBase {
  protected $httpClient;

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
    return 'search_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
 
    $form['#tree'] = TRUE;

    $form['ID'] = [
      '#type' => 'textfield',
      '#title' => 'ID',
      '#default_value' => '',
      '#required' => TRUE,
    ];

    $Structure = [
     "SEARCH" => ["KEYWORDS" => ""]
    ];

    foreach ($Structure as $section => $fields) {
      $this->buildSection($form, $section, $fields, $form_state);
    }

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Search'),
      '#attributes' => ['class' => ['btn', 'btn-primary']]
    ];

    return $form;
  }

  private function buildSection(array &$form, $section, array $fields, FormStateInterface $form_state, $prefix = '') {
    $form[$section] = [
      '#type' => 'details',
      '#title' => $this->t($section),
      '#open' => TRUE,
      '#tree' => TRUE
    ];
    $this->buildSearchSection($form[$section], $section, $fields, $form_state);   
  }

  private function buildSearchSection(array &$form, $section, array $fields, FormStateInterface $form_state) {
    foreach ($fields as $field => $value) {
      $heading = $this->t((string)$field);
      $form[$field] = [
        '#type' => 'textfield',
        '#title' => $heading,
        '#default_value' => $value,
        '#required' => TRUE,
      ];
    }
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

    if (isset($form_values['SEARCH'])) {
      $updatedDetails['SEARCH'] = $form_values['SEARCH'];
      if (isset($form_values['SEARCH'][ $section])) {
          unset($updatedDetails['SEARCH'][ $section]);
      }
  }
    return $updatedDetails;
  }

  public function uploadSearchKeywordsToServer($details) {
    try {
      $client = new Client();
      $response = $client->post(self::BASE_URL .'/addSearchKeywords', [
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
