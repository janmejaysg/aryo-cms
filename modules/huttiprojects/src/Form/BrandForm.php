<?php

namespace Drupal\huttiprojects\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\Exception\RequestException;
use Drupal\file\Entity\File;

class BrandForm extends FormBase {
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
    return 'brand_form';
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
      "BRAND" => ["ABOUT" => [[""]], "AGENCY" => "", "BANNER" => "", "CASHBACK" => [["CATEGORY" => "", "RATE" => ""], ["CATEGORY" => "", "RATE" => ""]], "CONFIRMATION_TIME" => "", "HEADING" => "", "OFFER_TERMS" => [["Important Information" => ""], ["General Information" => ""]], "TRACKING_TIME" => "", "URL" => ""]
    ];

    foreach ($Structure as $section => $fields) {
      $this->buildSection($form, $section, $fields, $form_state);
    }

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Brand'),
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
    $this->buildBrandSection($form[$section], $section, $fields, $form_state);   
  }

  public function addElementCallback (array &$form, FormStateInterface $form_state) {
    return $form;
  }

  public function addCashbackSubmit(array &$form, FormStateInterface $form_state) {
    $num_cashback = $form_state->get('num_cashback');
    $num_cashback++;
    $form_state->set('num_cashback', $num_cashback);
    $form_state->setRebuild(TRUE);
  }

  public function removeCashbackSubmit(array &$form, FormStateInterface $form_state) {
    $num_cashback = $form_state->get('num_cashback');
    if ($num_cashback > 0) {
      $num_cashback--;
      $form_state->set('num_cashback', $num_cashback);
    }
    $form_state->setRebuild(TRUE);
  }

  public function cashbackCallback(array &$form, FormStateInterface $form_state) {
    return $form['BRAND']['CASHBACK'];
  }

  public function addAboutSubmit(array &$form, FormStateInterface $form_state) {
    $num_about = $form_state->get('num_about');
    $num_about++;
    $form_state->set('num_about', $num_about);
    $form_state->setRebuild(TRUE);
  }

  public function removeAboutSubmit(array &$form, FormStateInterface $form_state) {
    $num_about = $form_state->get('num_about');
    if ($num_about > 0) {
      $num_about--;
      $form_state->set('num_about', $num_about);
    }
    $form_state->setRebuild(TRUE);
  }

  public function aboutCallback(array &$form, FormStateInterface $form_state) {
    return $form['BRAND']['ABOUT'];
  }

  private function buildBrandSection(array &$form, $section, array $fields, FormStateInterface $form_state, $prefix = '') {
    foreach ($fields as $field => $value) {
      $heading = $this->t((string)$field);
      $currentKey = $prefix ? $prefix . "[$field]" : $field;

      if ($heading == "ABOUT") {
        $form[$currentKey] = [
          '#type' => 'details',
          '#title' => $heading,
          '#open' => true,
          '#tree' => TRUE,
          '#prefix' => '<div id="about-wrapper">',
          '#suffix' => '</div>',
        ];

        $num_about = $form_state->get('num_about');
        if (is_null($num_about)) {
          $num_about = count($value);
          $form_state->set('num_about', $num_about);
        }

        for ($i = 0; $i < $num_about; $i++) {
          $form[$currentKey][$i] = [
            '#type' => 'details',
            '#title' => $this->t('@index', ['@index' => $i]),
            '#open' => true,
            '#tree' => TRUE,
          ];

          $form[$currentKey][$i]['textfield'] = [
            '#type' => 'textfield',
            // '#title' => $this->t('@index', ['@index' => $i]),
            '#default_value' => '',
            '#required' => TRUE,
          ];

          $form[$currentKey]['actions'] = [
            '#type' => 'actions',
          ];
          $form[$currentKey]['actions']['add_about'] = [
            '#type' => 'submit',
            '#value' => $this->t('Add About'),
            '#submit' => ['::addAboutSubmit'],
            '#ajax' => [
              'callback' => '::aboutCallback',
              'wrapper' => 'about-wrapper',
              'effect' => 'fade',
            ],
            '#limit_validation_errors' => [],
          ];
          $form[$currentKey]['actions']['remove_about'] = [
            '#type' => 'submit',
            '#value' => $this->t('Remove About'),
            '#submit' => ['::removeAboutSubmit'],
            '#ajax' => [
              'callback' => '::aboutCallback',
              'wrapper' => 'about-wrapper',
              'effect' => 'fade',
            ],
            '#limit_validation_errors' => [],
          ];
        }
      } else if ($heading == 'CASHBACK') {
        $form[$currentKey] = [
          '#type' => 'details',
          '#title' => $heading,
          '#open' => true,
          '#tree' => TRUE,
        ];

        $form[$currentKey]['#prefix'] = '<div id="cashback-wrapper">';
        $form[$currentKey]['#suffix'] = '</div>';
        $form[$currentKey]['#tree'] = TRUE;

        $num_cashback = $form_state->get('num_cashback');
        if (is_null($num_cashback)) {
          $num_cashback = count($value);
          $form_state->set('num_cashback', $num_cashback);
        }

        for ($i = 0; $i < $num_cashback; $i++) {
          $form[$currentKey][$i] = [
            '#type' => 'details',
            '#title' => $this->t('@index', ['@index' => $i]),
            '#open' => true,
            '#tree' => TRUE,
          ];

          $form[$currentKey][$i]['CATEGORY'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Category'),
            '#default_value' => isset($value[$i]['CATEGORY']) ? $value[$i]['CATEGORY'] : '',
            '#required' => TRUE,
          ];
          $form[$currentKey][$i]['RATE'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Rate'),
            '#default_value' => isset($value[$i]['RATE']) ? $value[$i]['RATE'] : '',
            '#required' => TRUE,
          ];
        }

        $form[$currentKey]['actions'] = [
          '#type' => 'actions',
        ];
        $form[$currentKey]['actions']['add_cashback'] = [
          '#type' => 'submit',
          '#value' => $this->t('Add Category'),
          '#submit' => ['::addCashbackSubmit'],
          '#ajax' => [
            'callback' => '::cashbackCallback',
            'wrapper' => 'cashback-wrapper',
            'effect' => 'fade',
          ],
          '#limit_validation_errors' => [],
        ];
        $form[$currentKey]['actions']['remove_cashback'] = [
          '#type' => 'submit',
          '#value' => $this->t('Remove Category'),
          '#submit' => ['::removeCashbackSubmit'],
          '#ajax' => [
            'callback' => '::cashbackCallback',
            'wrapper' => 'cashback-wrapper',
            'effect' => 'fade',
          ],
          '#limit_validation_errors' => [],
        ];
      } else if ($heading == "BANNER") {
        $form[$currentKey] = [
          '#type' => 'managed_file',
          '#title' => $heading,
          '#name' => 'project_banner',
          '#upload_location' => 'public://',
          '#upload_validators' => [
            'file_validate_extensions' => ['jpg jpeg png gif'],
          ],
          '#required' => TRUE,
        ];
      } else if ($heading == "OFFER_TERMS") {
        $form[$currentKey] = [
          '#type' => 'details',
          '#title' => $heading,
          '#open' => true,
          '#tree' => TRUE,
        ];

        $num_offer_terms = $form_state->get('num_offer_terms');
        if (is_null($num_offer_terms)) {
          $num_offer_terms = count($value);
          $form_state->set('num_offer_terms', $num_offer_terms);
        }

        for ($i = 0; $i < $num_offer_terms; $i++) {
          $form[$currentKey][$i] = [
            '#type' => 'details',
            '#title' => $this->t('@index', ['@index' => $i + 1]),
            '#open' => true,
            '#tree' => TRUE,
          ];

          if ($i == 0) {
            $form[$currentKey][$i]['Important Information'] = [
              '#type' => 'textfield',
              '#title' => $this->t('Important Information'),
              '#default_value' => isset($value[$i + 1]['Important Information']) ? $value[$i + 1]['Important Information'] : '',
              '#required' => TRUE,
            ];
          } else {
            $form[$currentKey][$i]['General Information'] = [
              '#type' => 'textfield',
              '#title' => $this->t('General Information'),
              '#default_value' => isset($value[$i + 1]['General Information']) ? $value[$i + 1]['General Information'] : '',
              '#required' => TRUE,
            ];
          }
        }
      } else if (is_array($value)) {
        $form[$currentKey] = [
          '#type' => 'details',
          '#title' => $heading,
          '#open' => true,
          '#tree' => TRUE,
        ];
        foreach ($value as $subfield => $subvalue) {
          $subheading = $this->t((string)$subfield);
          $subKey = $currentKey . "[$subfield]";
          if (is_array($subvalue)) {
            $form[$currentKey][$subKey] = [
              '#type' => 'details',
              '#title' => $subheading,
              '#open' => true,
            ];
            foreach ($subvalue as $subsubfield => $subsubvalue) {
              $subsubheading = $this->t((string)$subsubfield);
              $subsubKey = $subKey . "[$subsubfield]";
              $form[$currentKey][$subKey][$subsubKey] = [
                '#type' => 'textfield',
                '#title' => $subsubheading,
                '#default_value' => $subsubvalue,
                '#required' => TRUE,
              ];
            }
          } else {
            $form[$currentKey][$subKey] = [
              '#type' => 'textfield',
              '#title' => $subheading,
              '#default_value' => $subvalue,
              '#required' => TRUE,
            ];
          }
        }
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

    $file_id_2 = $form_state->getValue(['BRAND', 'BANNER']);
    if ($file_id_2) {
        $brand_banner = File::load($file_id_2[0]);
        if ($brand_banner) {
            $brand_banner->setPermanent();
            $brand_banner->save();
            $server_response = $this->uploadBrandToServer($brand_banner, $updatedDetails);
            if ($server_response) {
                $this->messenger->addMessage($this->t('Logo uploaded and project details updated successfully.'));
            }
        }
    }
  }

  private function buildUpdatedDetailsArray($form_values, $section) {
    $updatedDetails = [];

    if (isset($form_values['ID'])) {
      $updatedDetails['ID'] = $form_values['ID'];
    }

    if (isset($form_values['BRAND'])) {
      $updatedDetails['BRAND'] = $form_values['BRAND'];
      if (isset($form_values['BRAND'][ $section])) {
          unset($updatedDetails['BRAND'][ $section]);
      }
      if (isset($form_values['BRAND']['BANNER'])) {
          $updatedDetails['BRAND']['BANNER'] = reset($form_values['BRAND']['BANNER']);
      }
  }

  if (isset($form_values['BRAND']['ABOUT'])) {
      $about = [];
      foreach ($form_values['BRAND']['ABOUT'] as $key => $value) {
          if (is_array($value) && !in_array($key, ['actions'])) {
              $about[$key] = $value['textfield'];
          }
      }
      $updatedDetails['BRAND']['ABOUT'] = $about;
  }

  if (isset($form_values['BRAND']['CASHBACK'])) {
      $cashback = [];
      foreach ($form_values['BRAND']['CASHBACK'] as $key => $value) {
          if (is_array($value) && isset($value['CATEGORY']) && isset($value['RATE'])) {
              $cashback[$key] = [
                  'CATEGORY' => $value['CATEGORY'],
                  'RATE' => $value['RATE'],
              ];
          }
      }
      $updatedDetails['BRAND']['CASHBACK'] = $cashback;
  }

  if (isset($form_values['BRAND']['OFFER_TERMS'])) {
      $offer_terms = [];
      foreach ($form_values['BRAND']['OFFER_TERMS'] as $key => $value) {
          if (is_array($value)) {
              if (isset($value['Important Information'])) {
                  $offer_terms[$key]['Important Information'] = $value['Important Information'];
              }
              if (isset($value['General Information'])) {
                  $offer_terms[$key]['General Information'] = $value['General Information'];
              }
          }
      }
      $updatedDetails['BRAND']['OFFER_TERMS'] = $offer_terms;
  }
    return $updatedDetails;
  }

  private function uploadBrandToServer($brand_banner, $details) {
    try {
      $client = new Client();
      $response = $client->post(self::BASE_URL . '/addBrand', [
        'multipart' => [
          [
            'name' => 'brand_banner',
            'contents' => fopen($brand_banner->getFileUri(), 'r'),
            'filename' => $brand_banner->getFilename(),
          ],
          [
            'name' => 'details',
            'contents' => json_encode($details),
          ],
        ],
      ]);
      $data = json_decode($response->getBody(), TRUE);
      return $data;
    } catch (RequestException $e) {
      $this->messenger->addError($this->t('An error occurred while uploading the file: @message', ['@message' => $e->getMessage()]));
      return FALSE;
    }
}
}
