<?php

namespace Drupal\aryoleads\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\Messenger\MessengerInterface;

class DistributorLeadsLeadIdForm extends FormBase {
  protected $httpClient;
  protected $messenger;

  public function __construct(ClientInterface $http_client, MessengerInterface $messenger) {
    $this->httpClient = $http_client;
    $this->messenger = $messenger;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client'),
      $container->get('messenger')
    );
  }

  public function getFormId() {
    return 'teamleads_lead_id_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['leadId'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Lead_Id'),
      '#attributes' => ['class' => ['custom-textfield']],
      '#required' => TRUE
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Download Leads'),
      '#attributes' => ['class' => ['btn', 'btn-primary', 'custom-submit-button']],
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) { 
    $leadId = $form_state->getValue('leadId');
    $client = \Drupal::httpClient();
    $url = 'http://localhost:8081/getAryoDistributorLeadByLeadId';

    try {
      $response = $client->request('GET', $url, [
        'query' => [
          'leadId' => $leadId,
        ]
      ]);

      $contentType = $response->getHeaderLine('Content-Type');
      $content = $response->getBody()->getContents();

      if (strpos($contentType, 'application/json') !== false) {
        $data = json_decode($content, true);
        if (isset($data['error'])) {
          $this->messenger->addError($this->t('Failed to fetch data from external API: @error', ['@error' => $data['error']]));
          return;
        }
      } elseif (strpos($contentType, 'text/csv') !== false) {
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "Aryo_Leads_{$timestamp}.csv";

        $headers = [
          'Content-Type' => 'text/csv',
          'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $response = new \Symfony\Component\HttpFoundation\Response($content, 200, $headers);
        \Drupal::service('page_cache_kill_switch')->trigger();
        $response->send();
        exit();
      } else {
        $this->messenger->addError($this->t('Unexpected response from external API.'));
      }
    } catch (RequestException $e) {
      \Drupal::logger('aryoleads')->error($e->getMessage());
      $this->messenger->addError($this->t('Failed to fetch data from external API.'));
    }
  }
}
