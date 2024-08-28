<?php

namespace Drupal\aryoleads\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\Ajax\HtmlCommand;
use Symfony\Component\HttpFoundation\JsonResponse;

class ProfilesForm extends FormBase {

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
    return 'profiles_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

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

    $form['source'] = [
      '#type' => 'select',
      '#title' => $this->t('Source'),
      '#options' => [
        '' => $this->t('All'),
        'DSA' => $this->t('DSA')
      ]
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Downlads Profiles'),
      '#attributes' => ['class' => ['btn', 'btn-primary', 'custom-submit-button']],
    ];

    return $form;
  }


  public function submitForm(array &$form, FormStateInterface $form_state) {
    $from_date = $form_state->getValue('from_date');
    $to_date = $form_state->getValue('to_date');
    $source_val = $form_state->getValue('source');
    $source = $source_val === '' ? NULL : $source_val;

    // Make the HTTP request to the external API
    $client = \Drupal::httpClient();
    $url = 'http://localhost:8081/getAryoAgentInfo';

    try {
      $response = $client->request('GET', $url, [
        'query' => [
          'fromDate' => $from_date,
          'toDate' => $to_date,
          'source' => $source
        ],
        'timeout' => 600, // Set the timeout to 600 seconds (10 minutes)
      ]);
      
      $csvContent = $response->getBody()->getContents();
      $timestamp = date('Y-m-d_H-i-s');
      $filename = "Agents_Profiles_{$timestamp}.csv";

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
    }
  }
}

