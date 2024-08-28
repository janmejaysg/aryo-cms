<?php
namespace Drupal\huttiprojects\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Provides a simple form for testing.
 */
class HuttiProjectDetailsForm extends FormBase {

  protected $httpClient;

  const BASE_URL = 'http://localhost:8081';

  public function __construct(ClientInterface $http_client) {
    $this->httpClient = $http_client;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client')
    );
  }

  public function getFormId() {
    return 'hutti_projects_details_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $category = \Drupal::routeMatch()->getParameter('category');
    $subcategory = \Drupal::routeMatch()->getParameter('subcategory');
  
    \Drupal::logger('huttiprojects')->info('Category: @category, SubCategory: @subcategory', [
      '@category' => $category,
      '@subcategory' => $subcategory,
    ]);
  
    $projectDetails = $this->getProjectDetails($category, $subcategory);
  
    $form['#tree'] = TRUE;  // Ensure the root form is a tree
    echo $projectDetails;
  
    if ($projectDetails) {

      $this->buildFormElements($form, $projectDetails , $subcategory);
    } else {
      $form['error'] = [
        '#markup' => $this->t('Unable to fetch project details.'),
      ];
    }
  
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update Details'),
      '#attributes' => ['class' => ['btn', 'btn-primary', 'custom-submit-button']],
    ];
  
    return $form;
  }

  private function buildFormElements(&$form, $data,$subcategory, $prefix = '') {
    if(is_array($data)) {
    foreach ($data as $key => $value) {
      $title = $this->t((string) $key);
      $currentKey = $prefix ? $prefix . "[$key]" : $key;
  
      if (is_array($value)) {
        $form[$currentKey] = [
          '#type' => 'details',
          '#title' => $title,
          '#open' => TRUE,
          '#tree' => TRUE,  // Ensure nested array structure
        ];
        $this->buildFormElements($form[$currentKey], $value , $subcategory); // Recursive call
      } else {
        // Always use 'textfield' type
        $form[$currentKey] = [
          '#type' => 'textfield',
          '#title' => $title,
          '#default_value' => (string) $value,
          '#maxlength' => 5000,
        ];
      }
    }
  }
  else {
    $form[$subcategory] = [
      '#type' => 'textfield',
      '#title' => $subcategory,
      '#default_value' => (string) $data,
      '#maxlength' => 5000
    ];
  }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Log form values at the start of submitForm
    \Drupal::logger('huttiprojects')->info('Form Values on submit: @values', ['@values' => json_encode($form_state->getValues())]);
  
    $category = \Drupal::routeMatch()->getParameter('category');
    $subcategory = \Drupal::routeMatch()->getParameter('subcategory');
  
    // Capture and log updated details array
    $updatedDetails = $this->buildUpdatedDetailsArray($form_state->getValues());
    \Drupal::logger('huttiprojects')->info('Processed Updated Details Array: @details', ['@details' => json_encode($updatedDetails)]);
  
    // Prepare HTTP request
    $url = self::BASE_URL . '/updateHuttiProjects';
    try {
      $response = $this->httpClient->request('POST', $url, [
        'json' => [
          'category' => $category,
          'subcategory' => $subcategory,
          'details' => $updatedDetails,
        ],
      ]);
  
      // Handle response status
      if ($response->getStatusCode() == 200) {
        \Drupal::messenger()->addStatus($this->t('Project details have been updated successfully.'));
      } else {
        \Drupal::messenger()->addError($this->t('Failed to update project details.'));
      }
    } catch (RequestException $e) {
      // Log error
      \Drupal::logger('huttiprojects')->error('Error updating project details: @message', ['@message' => $e->getMessage()]);
      \Drupal::messenger()->addError($this->t('An error occurred while updating project details.'));
    }
  
    // Set rebuild to refresh the form
    $form_state->setRebuild(TRUE);
  }

  private function buildUpdatedDetailsArray($form_values) {
    \Drupal::logger('huttiprojects')->info('Form values in buildUpdatedDetailsArray: @values', ['@values' => json_encode($form_values)]);
  
    $updatedDetails = [];
    foreach ($form_values as $key => $value) {
      if (!in_array($key, ['form_build_id', 'form_token', 'form_id', 'op', 'submit','actions'])) {
        \Drupal::logger('huttiprojects')->info('Setting nested array value: @key => @value', ['@key' => $key, '@value' => json_encode($value)]);
        $this->setNestedArrayValue($updatedDetails, $key, $value);
      }
    }
    return $updatedDetails;
  }
  
  private function setNestedArrayValue(&$array, $path, $value) {
    $keys = preg_split('/[\[\]]+/', $path, -1, PREG_SPLIT_NO_EMPTY);
    $current = &$array;
  
    foreach ($keys as $key) {
      if (!isset($current[$key])) {
        $current[$key] = [];
      }
      $current = &$current[$key];
    }
  
    // Update the final value
    $current = $value;
  
    // Debugging: Log the current path and value
    \Drupal::logger('huttiprojects')->info('Setting value at path: @path to @value', ['@path' => $path, '@value' => json_encode($value)]);
  }

  public function getProjectDetails($category, $subcategory) {
    $url = self::BASE_URL . '/getHuttiProjects';

    try {
      $response = $this->httpClient->request('GET', $url, [
        'query' => [
          'category' => $category,
          'subcategory' => $subcategory,
        ],
      ]);

      $responseBody = $response->getBody()->getContents();
      $decodedResponse = json_decode($responseBody, true);
      if (json_last_error() === JSON_ERROR_NONE) {
          return $decodedResponse;
      } else {
          return $responseBody;
      }
      
      // return json_decode($responseBody, true);
    } catch (RequestException $e) {
      \Drupal::logger('huttiprojects')->error('Error fetching project details: @message', ['@message' => $e->getMessage()]);
      return null;
    }
  }
}
