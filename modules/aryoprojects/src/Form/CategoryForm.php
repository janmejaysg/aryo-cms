<?php

namespace Drupal\aryoprojects\Form;

use Drupal;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CategoryForm extends FormBase {

  protected $httpClient;

  public function __construct(Client $http_client) {
    $this->httpClient = $http_client;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client')
    );
  }
  
  const BASE_URL = 'http://localhost:8081';
  
  public function getFormId() {
    return 'category_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = [];

    $form['update_fail'] = [
      '#type' => 'markup',
      '#markup' => '<div class="failure"></div>',
    ];
  
    $allCategories = $this->getCategoryList();
    foreach ($allCategories as $key => $value) {
  
      $isHide = 0;
  
      $container_name = $key . '_container';
      $form[$container_name] = [
        '#type' => 'container',
        '#attributes' => ['class' => [ 'd-flex', 'align-items-center', 'gap-2']]
      ];
  
      foreach ($value as $subKey => $subValue) {
        if ($subKey == 'ISHIDE') {
          $isHide = $subValue;
        }
  
        if ($subKey != 'ISHIDE') {
          $form[$container_name][$subKey . '_icon'] = [
            '#type' => 'hidden',
            '#value' => $subValue,
          ];
  
          // Add an image element to display the category icon.
          $form[$container_name][$subKey . '_image'] = [
            '#theme' => 'image',
            '#uri' => $subValue,
            '#alt' => $this->t('Icon'),
            '#attributes' => ['class' => ['img-thumbnail'], 'style' => 'width: 40px; height: auto']
          ];
  
          $form[$container_name][$subKey] = [
            '#markup' => $this->t('<a href=":url" class="text-decoration-none">@subKey</a>', [':url' => "projects-by-category/$subKey", '@subKey' => $subKey]),
            '#prefix' => '<div class="me-2 mb-1">',
            '#suffix' => '</div>',
          ];
  
        }
      }
  
      // Add a container to hold the ISHIDE label and select element side by side.
      $form[$key . '_ishide_container'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['custom-container','d-flex', 'align-items-center', 'mb-3']],
      ];
  
      $form[$key . '_ishide_container'][$key . '_ishide_label'] = [
        '#type' => 'label',
        '#title' => $this->t('ISHIDE'),
        '#attributes' => ['class' => ['me-2']],
      ];
  
      $form[$key . '_ishide_container'][$key . '_ishide'] = [
        '#type' => 'select',
        '#options' => [
          0 => $this->t('false'),
          1 => $this->t('true'),
        ],
        '#default_value' => $isHide,
        '#attributes' => ['class' => ['form-select'], 'style' => 'width: auto; height: auto'],
        '#ajax' => [
          'callback' => '::updateField',
          'event' => 'change'
        ]
      ];
    }
  

    $form['#attached']['library'][] = 'aryoprojects/aryoprojects_js_css';
  
    return $form;
  }

  public function updateField(array &$form, FormStateInterface $form_state) {
  $ajax_response = new AjaxResponse();
  $triggering_element = $form_state->getTriggeringElement();

  $key = str_replace('_ishide', '', $triggering_element['#name']);

  $value = $triggering_element['#value'];

  $data = [];
  $data = [
    'key' => $key,
    'value' => [
      'ISHIDE' => (bool)$value
    ]
  ];

  try {
    $response = $this->httpClient->request('POST', self::BASE_URL . '/updateCategories', [
      'json' => $data,
      'timeout' => 60,
    ]);

  } catch (\Exception $e) {
    \Drupal::logger('aryoprojects')->error('Error sending data to server: @message', ['@message' => $e->getMessage()]);
    $ajax_response->addCommand(new HtmlCommand('.failure', 'An error occurred while updating project details.'));
  }

  return $ajax_response;
    
  }
  
  
  public function updateCategories(array &$form, FormStateInterface $form_state) {
    // $ajax_response = new AjaxResponse();
    // $updatedValues = $form_state->getValues();
    
    // // Initialize an empty array to hold the structured categories data.
    // $categories = [];
    // $counter = 1;
    
    // // Iterate through the form values to structure the data correctly.
    // foreach ($updatedValues as $key => $value) {
    //     if (strpos($key, '_icon') !== false) {
    //         // Extract the category name from the key.
    //         $categoryName = str_replace('_icon', '', $key);
            
    //         // Initialize the category array if not already set.
    //         if (!isset($categories[$counter])) {
    //             $categories[$counter] = [];
    //         }

    //         // Add the category icon URL to the categories array.
    //         $categories[$counter][$categoryName] = $value;

    //         // Add the ISHIDE value.
    //         $ishideKey = $counter . '_ishide';
    //         $categories[$counter]['ISHIDE'] = isset($updatedValues[$ishideKey]) ? (bool)$updatedValues[$ishideKey] : false;
    //         $counter++;
    //     }
    // }
    
    // // Send the structured categories data to the server.
    // try {
    //     $response = $this->httpClient->request('POST', self::BASE_URL . '/updateCategories', [
    //         'json' => $categories,
    //         'timeout' => 60, // Increase timeout to 60 seconds
    //     ]);
    //     $responseBody = $response->getBody()->getContents();
    //     // $result = json_decode($responseBody, TRUE);
    //     $ajax_response->addCommand(new HtmlCommand('.success', $responseBody));

    //     // \Drupal::logger('aryoprojects')->info('Server response: @response', ['@response' => print_r($result, TRUE)]);
    // } catch (\Exception $e) {
    //     \Drupal::logger('aryoprojects')->error('Error sending data to server: @message', ['@message' => $e->getMessage()]);
    //     $ajax_response->addCommand(new HtmlCommand('.failure', 'An error occurred while updating project details.'));
    // }

    // return $ajax_response;
  }

  public function getCategoryList() {
    try {
      $response = $this->httpClient->request('GET', self::BASE_URL . '/getChild/?path=ALL_CATEGORY');
      $responseBody = $response->getBody()->getContents();
      $data = json_decode($responseBody, TRUE);

      return $data;
    } catch (\Exception $e) {
      \Drupal::logger('aryoprojects')->error('Error fetching categories: @message', ['@message' => $e->getMessage()]);
      return [];
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
  }




}
