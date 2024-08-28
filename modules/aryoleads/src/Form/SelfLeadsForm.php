<?php

namespace Drupal\aryoleads\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

class SelfLeadsForm extends FormBase {

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
    return 'selfleads_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['heading'] = [
      '#type' => 'markup',
      '#markup' => '<h5>' . $this->t('Download Self Leads') . '</h5>',
    ];


    $form['from_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Start Date'),
      // '#required' => TRUE,
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


    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Download Leads'),
      '#attributes' => ['class' => ['btn', 'btn-primary', 'custom-submit-button']],
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) { 
    $user_id = $form_state->getValue('user_id');
    $user_mobile = $form_state->getValue('user_mobile');
    $from_date = $form_state->getValue('from_date');
    $to_date = $form_state->getValue('to_date');
    $lead_status = $form_state->getValue('lead_status');

    $from_date = !empty($from_date) ? $from_date : 'undefined';
    $to_date = !empty($to_date) ? $to_date :'undefined';
    $lead_status = !empty($lead_status) ? [$lead_status] :['In process', 'Approved', 'Rejected', 'Expired'];

    $client = \Drupal::httpClient();
    $url = 'http://localhost:8081/getAryoSelfLeads';

    try {
      $response = $client->request('GET', $url, [
        'query' => [
          'user_id' => $user_id,
          'user_mobile' => $user_mobile,
          'fromDate' => $from_date,
          'todate' => $to_date,
          'status' => $lead_status,
        ]
      ]);

      $csvContent = $response->getBody()->getContents();
      $timestamp = date('Y-m-d_H-i-s');
      $filename = "Aryo_Leads_{$timestamp}.csv";

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
      $this->messenger()->addError($this->t('Failed to fetch data from external API.'));
    }
  }

}
