<?php

namespace Drupal\huttiprojects\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\Exception\RequestException;
use Drupal\file\Entity\File;

class MetadataForm extends FormBase {
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
    return 'metadata_form';
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
      "METADATA" => ["CASHBACK" => "", "LOGO" => "", "NAME" => "", "ONHOLD" => "", "REGEXP" => "", "SAMPLE_ORDERID" => ""]
    ];

    foreach ($Structure as $section => $fields) {
      $this->buildSection($form, $section, $fields, $form_state);
    }

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Project'),
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
    $this->buildMetaDataSection($form[$section], $fields, $form_state);   
  }

  private function buildIDSection(array &$form, $section, array $fields, FormStateInterface $form_state) {
    // Not implemented in the provided code
  }

  private function buildMetaDataSection(array &$form, array $fields, FormStateInterface $form_state, $prefix = '') {
    foreach ($fields as $field => $value) {
      $heading = $this->t((string)$field);
      $currentKey = $prefix ? $prefix . "[$field]" : $field;

      if ($heading == 'LOGO') {
        $form[$currentKey] = [
          '#type' => 'managed_file',
          '#title' => $heading,
          '#name' => 'project_logo',
          '#upload_location' => 'public://',
          '#upload_validators' => [
            'file_validate_extensions' => ['jpg jpeg png gif'],
          ],
          '#required' => TRUE,
        ];
      } elseif ($heading == 'ONHOLD') {
        $form[$currentKey] = [
          '#type' => 'select',
          '#title' => $heading,
          '#options' => [
            'FALSE' => $this->t('False'),
            'TRUE' => $this->t('True'),
          ],
          '#default_value' => $value ? 'TRUE' : 'FALSE',
        ];
      } else {
        $form[$currentKey] = [
          '#type' => 'textfield',
          '#title' => $heading,
          '#default_value' => $value,
          '#required' => TRUE,
        ];
      }
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $section =  str_replace('_submit', '', $triggering_element['#name']);

    $updatedDetails = $this->buildUpdatedDetailsArray($form_state->getValues(),  $section);

    $file_id_1 = $form_state->getValue(['METADATA', 'LOGO']);
    if ($file_id_1) {
        $metadata_logo = File::load($file_id_1[0]);
        if ($metadata_logo) {
            $metadata_logo->setPermanent();
            $metadata_logo->save();
            $server_response = $this->uploadMetaDataToServer($metadata_logo, $updatedDetails);
        }
    }
  }

  private function buildUpdatedDetailsArray($form_values, $section) {
    $updatedDetails = [];

    if (isset($form_values['ID'])) {
      $updatedDetails['ID'] = $form_values['ID'];
    }

    if (isset($form_values['METADATA'])) {
      $updatedDetails['METADATA'] = $form_values['METADATA'];
      if (isset($form_values['METADATA'][$section])) {
        unset($updatedDetails['METADATA'][$section]);
      }
      if (isset($form_values['METADATA']['LOGO'])) {
        $updatedDetails['METADATA']['LOGO'] = reset($form_values['METADATA']['LOGO']);
      }
      if (isset($form_values['METADATA']['ONHOLD'])) {
        $updatedDetails['METADATA']['ONHOLD'] = $form_values['METADATA']['ONHOLD'] === 'TRUE';
      }
    }
    return $updatedDetails;
  }

  public function uploadMetaDataToServer($metadata_logo, $details) {
    try {
      $client = new Client();
      $response = $client->post(self::BASE_URL .'/addMetadata', [
        'multipart' => [
          [
            'name'=> 'metadata_logo',
            'contents' => fopen($metadata_logo->getFileUri(),'r'),
            'filename' => $metadata_logo->getFilename(),
          ],
          [
            'name' => 'details',
            'contents' => json_encode($details),
            'headers' => [
              'Content-Type' => 'application/json',
            ],
          ],
        ]
      ]);
      $data = json_decode($response->getBody(), TRUE);
      return $data;
    } catch (RequestException $e) {
      $this->messenger->addError($this->t('An error occurred while uploading the file: @message', ['@message' => $e->getMessage()]));
    }
  }
}
