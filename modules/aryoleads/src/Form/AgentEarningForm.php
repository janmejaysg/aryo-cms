<?php

namespace Drupal\aryoleads\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\HttpFoundation\JsonResponse;

class AgentEarningForm extends FormBase {

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
    return 'agent_earning_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    $option = ['AgentId' => 'Agent ID', 'AgentMobile' => 'Agent Mobile'];

    $form['agent'] = [
      '#type' => 'select',
      '#title' => 'Download Earning by',
      '#options' => $option,
      '#default_value' => 'AgentId',
      '#ajax' => [
        'callback' => '::updateForm',
        'event' => 'change',
        'wrapper' => 'form-wrapper'
      ]
    ];

    $form['form_wrapper'] =[
      '#type' => 'container',
      '#attributes' => ['id' => 'form-wrapper'],
    ];

    $selected_user = $form_state->getValue('agent', 'AgentId');

    if($selected_user === 'AgentId'){
      $form['form_wrapper']['agent_id'] =[
        '#type' => 'textfield',
        '#title' => $this->t('Agent ID'),
        '#required' => TRUE,
      ];
    }
    else if($selected_user === 'AgentMobile'){
      $form['form_wrapper']['agent_mobile'] =[
        '#type' => 'textfield',
        '#title' => $this->t('Agent Mobile'),
        '#required' => TRUE,
      ];
    }

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Download Earnings'),
      '#attributes' => ['class' => ['btn', 'btn-primary', 'custom-submit-button']],
    ];

    return $form;
  }

  public function updateForm(array &$form, FormStateInterface $form_state) {
    return $form['form_wrapper'];
  }


  public function submitForm(array &$form, FormStateInterface $form_state) {
    $agent_id = $form_state->getValue('agent_id');
    $agent_mobile = $form_state->getValue('agent_mobile');

    // Make the HTTP request to the external API
    $client = \Drupal::httpClient();
    $url = 'http://localhost:8081/getAryoAgentEarning';

    try {
      $response = $client->request('GET', $url, [
        'query' => [
          'agentId' => $agent_id,
          'agentMobile' => $agent_mobile,
        ],
        'timeout' => 600, // Set the timeout to 600 seconds (10 minutes)
      ]);
      
      $csvContent = $response->getBody()->getContents();
      $timestamp = date('Y-m-d_H-i-s');
      $filename = "Agent_Earning_{$timestamp}.csv";

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

  
  }

  



