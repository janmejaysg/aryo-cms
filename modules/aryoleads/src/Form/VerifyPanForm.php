<?php

namespace Drupal\aryoleads\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

class VerifyPanForm extends FormBase {

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
    return "verify_pan_form";
  }

  public function buildForm(array $form, FormStateInterface $form_state){

    $form['uid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('User Id'),
      '#required' => TRUE
    ];


    $form['pan-number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Pan Number'),
      '#required' => TRUE,
      '#maxlength' => 10,
    ];
 
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Verify Pan'),
      '#attributes' => ['class' => ['btn', 'btn-primary', 'custom-submit-button']],
    ];

    if ($form_state->has('KYC_Details')) {
      $form['KYC_Details'] = $form_state->get('KYC_Details');
    }



    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $uid = $form_state->getValue('uid');
    $panNumber = $form_state->getValue('pan-number');

    $client = \Drupal::httpClient();
    $url = 'https://aryoconnect.in/verifyPan';

    try {
      $response = $client->post($url, 
        ['json' => ['uid' => $uid , 'pan' => $panNumber]]
      );

      $response_data = $response->getBody()->getContents();
      $decoded_data = json_decode($response_data, TRUE);
      \Drupal::logger('aryoleads')->info('Response data: @response', ['@response' => print_r($decoded_data, TRUE)]);

      $table_rows = $this->buildTableRows($decoded_data);

      $form_state->set('KYC_Details', [
        '#type' => 'table',
        '#header' => ['Key', 'Value'],
        '#rows' => $table_rows,
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
        $rows = array_merge($rows, $this->buildTableRows($value, $prefix . $key . '.'));
      } else {
        $rows[] = [$key, $value];
      }
    }
    return $rows;
  }

}
