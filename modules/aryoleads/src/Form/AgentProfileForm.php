<?php

namespace Drupal\aryoleads\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

class AgentProfileForm extends FormBase {

  protected $httpClient;

  public function __construct(ClientInterface $http_client) {
    $this->httpClient = $http_client;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get("http_client")
    );
  }

  public function getFormId() { 
    return "agent_profile_form";
  }

  public function buildForm(array $form, FormStateInterface $form_state){

    $option = ['AgentId' => 'Agent Id', 'AgentMobile' => 'Agent Mobile'];

    $form['agent'] = [
      '#type' => 'select',
      '#title' => 'Get Agent Profile By',
      '#options' => $option,
      '#default_value' => 'AgentId',
      '#ajax' => [
        'callback' => '::updateForm',
        'event' => 'change',
        'wrapper' => 'form-wrapper',
      ]
    ];

    $form['form_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'form-wrapper']
    ];

    $selected_user = $form_state->getValue('agent', 'AgentId');

    if ($selected_user === 'AgentId') {
      $form['form_wrapper']['agentId'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Agent ID'),
        '#required' => TRUE,
      ];
    } elseif ($selected_user === 'AgentMobile') {
      $form['form_wrapper']['agentMobile'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Agent Mobile'),
        '#required' => TRUE,
      ];
    }

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Get Profile'),
      '#attributes' => ['class' => ['btn', 'btn-primary', 'custom-submit-button']],
    ];

    if ($form_state->has('Agent_Profile')) {
      $form['Agent_Profile'] = $form_state->get('Agent_Profile');
    }

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $agentId = $form_state->getValue('agentId');
    $agentMobile = $form_state->getValue('agentMobile');

    $client = \Drupal::httpClient();
    $url = 'http://localhost:8081/getAryoAgentProfile';

    try {
      $response = $client->request('GET', $url, [
        'query' => [
          'uid' => $agentId,
          'mobile' => $agentMobile,
        ],
      ]);

      $response_data = $response->getBody()->getContents();
      $decoded_data = json_decode($response_data, TRUE);
      \Drupal::logger('aryoleads')->info('Response data: @response', ['@response' => print_r($decoded_data, TRUE)]);

      $table_rows = $this->buildTableRows($decoded_data);

      $form_state->set('Agent_Profile', [
        '#type' => 'table',
        '#header' => ['Key', 'Value'],
        '#rows' => $table_rows,
        '#empty' => $this->t('No leads found.'),
      ]);

    } catch (RequestException $e) {
      \Drupal::logger('aryoleads')->error($e->getMessage());
      \Drupal::messenger()->addError($this->t('Failed to fetch data from external API.'));
    }
    $form_state->setRebuild(TRUE);
  }

  private function buildTableRows(array $data, $prefix = '') {
    $rows = [];
    foreach ($data as $key => $value) {
      if (is_array($value)) {
        // Handle the specific case for 'interestedCategory'
        
        if ($key === 'interestedCategory') {
          $rows[] = [$prefix . $key, implode(', ', $value)];
        } else {
          $rows = array_merge($rows, $this->buildTableRows($value, $prefix . $key . '.'));
        }
      } else {
        // Check if the value is a boolean and convert it to true/false
        if (is_bool($value)) {
          $value = $value ? 'true' : 'false';
        }
        $rows[] = [$key, $value];
      }
    }
    return $rows;
  }

  public function updateForm(array &$form, FormStateInterface $form_state) {
    return $form['form_wrapper'];
  }
}