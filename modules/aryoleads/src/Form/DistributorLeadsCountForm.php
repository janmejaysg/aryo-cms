<?php

namespace Drupal\aryoleads\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

class DistributorLeadsCountForm extends FormBase {
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
    return 'distleads_count_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['from_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Start Date'),
      '#required' => TRUE
     ];

    $form['to_date'] = [
      '#type' => 'date',
      '#title' => $this->t('End Date'),
      '#required' => TRUE
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Leads Count'),
      '#attributes' => ['class' => ['btn', 'btn-primary', 'custom-submit-button']],
    ];

    if ($form_state->has('leads_table')) {
      $form['leads_table'] = $form_state->get('leads_table');
    }

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $from_date = $form_state->getValue('from_date');
    $to_date = $form_state->getValue('to_date');

    $client = \Drupal::httpClient();
    $url = 'http://localhost:8081/getAryoDistributorLeadsCount';

    try {
      $response = $client->request('GET', $url, [
        'query' => [
          'fromDate' => $from_date,
          'toDate' => $to_date,
        ],
      ]);

      $response_data = $response->getBody()->getContents();
      $decoded_response_data = json_decode($response_data, TRUE);
      \Drupal::logger('aryoleads')->info('Response data: @response', ['@response' => print_r($decoded_response_data, TRUE)]);

      if (!empty($decoded_response_data)) {
        $table_header = [
          $this->t('Category'),
          $this->t('Project Name'),
          $this->t('Leads Count'),
        ];

        $table_rows = [];
        foreach ($decoded_response_data as $item) {
          if (isset($item['category'])) {
            foreach ($item['projects'] as $project) {
              $table_rows[] = [
                'category' => $item['category'],
                'project_name' => $project['projectName'],
                'leads_count' => $project['leadsCount'],
              ];
            }
          } else if (isset($item['LeadsCount'])) {
            $table_rows[] = [
              'category' => 'Total Leads',
              'project_name' => 'All Projects',
              'leads_count' => $item['LeadsCount'],
            ];
          }
        }

        $form_state->set('leads_table', [
          '#type' => 'table',
          '#header' => $table_header,
          '#rows' => $table_rows,
          '#empty' => $this->t('No leads found.'),
        ]);
      } else {
        \Drupal::messenger()->addWarning($this->t('The API response does not contain the expected data.'));
      }
    } catch (RequestException $e) {
      \Drupal::logger('aryoleads')->error($e->getMessage());
      \Drupal::messenger()->addError($this->t('Failed to fetch data from external API.'));
    }

    // Rebuild the form to display the table.
    $form_state->setRebuild(TRUE);
  }
}
