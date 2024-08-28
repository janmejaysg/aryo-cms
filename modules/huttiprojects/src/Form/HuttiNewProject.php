<?php

namespace Drupal\huttiprojects\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\ClientInterface;
use Drupal\Core\Database\Database;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Drupal\file\Entity\File;
use Drupal\Core\Messenger\MessengerInterface;

class HuttiNewProject extends FormBase {
  protected $httpClient;
  protected $messenger;
  protected $categoryOptions;

  const BASE_URL = 'http://localhost:8081';

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
    return 'hutti_new_project_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $conn = Database::getConnection();

    $category_query = $conn->select('hutti_categories', 'c');
    $category_query->fields('c', ['category_id', 'category_name']);
    $category_query->orderBy('category_name', 'ASC');
    $category_records = $category_query->execute()->fetchAllKeyed();
    $category_options = [];

    foreach ($category_records as $key => $category_results) {
      $category_options[$key] = $category_results;
    }

    $this->categoryOptions = $category_options;

    $form['#tree'] = TRUE;

    $Structure = [
      "METADATA" => ["CASHBACK" => "", "LOGO" => "", "NAME" => "", "ONHOLD" => "", "REGEXP" => "", "SAMPLE_ORDERID" => ""],
      "BRAND" => ["ABOUT" => [[""]], "AGENCY" => "", "BANNER" => "", "CASHBACK" => [["CATEGORY" => "", "RATE" => ""], ["CATEGORY" => "", "RATE" => ""]], "CATEGORIES" => "", "CONFIRMATION_TIME" => "", "HEADING" => "", "ICON" => "", "NAME" => "", "OFFER_TERMS" => [["Important Information" => ""], ["General Information" => ""]], "TRACKING_TIME" => "", "URL" => ""],
      "SEARCH" => ["KEYWORDS" => ""],
      "CATEGORIES" => []
    ];

    $form['ID'] = [
      '#type' => 'textfield',
      '#title' => 'ID',
      '#default_value' => '',
      '#required' => TRUE,
    ];

    foreach ($Structure as $section => $fields) {
      $this->buildSection($form, $section, $fields, $form_state, $category_options);
    }

    // $form['actions']['submit'] = [
    //   '#type' => 'submit',
    //   '#value' => $this->t('Add Project'),
    //   '#attributes' => ['class' => ['btn', 'btn-primary']]
    // ];

    return $form;
  }

  private function buildSection(array &$form, $section, array $fields, FormStateInterface $form_state, array $category_options, $prefix = '') {
    $form[$section] = [
      '#type' => 'details',
      '#title' => $this->t($section),
      '#open' => FALSE,
      '#tree' => TRUE
    ];

    switch ($section) {
      case 'METADATA':
        $this->buildMetaDataSection($form[$section], $section, $fields, $form_state);
        break;
      case 'BRAND':
        $this->buildBrandSection($form[$section], $section, $fields, $form_state);
        break;
      case 'SEARCH':
        $this->buildSearchSection($form[$section], $section, $fields, $form_state);
        break;
      case 'CATEGORIES':
        $this->buildCategoriesSection($form[$section], $section, $fields, $form_state, $category_options);
        break;
    }
  }

  private function buildMetaDataSection(array &$form, $section , array $fields, FormStateInterface $form_state, $prefix = '') {
    foreach ($fields as $field => $value) {
      $heading = $this->t((string)$field);
      $currentKey = $prefix ? $prefix . "[$field]" : $field;

      if ($heading == 'LOGO') {
        $form[$currentKey] = [
          '#type' => 'managed_file',
          '#title' => $heading,
          '#name' => 'project_logo',
          '#upload_location' => 'public://',
          '#upload_validators' => [
            'file_validate_extensions' => ['jpg jpeg png gif'],
          ],
          '#required' => TRUE,
        ];
      } else {
        $form[$currentKey] = [
          '#type' => 'textfield',
          '#title' => $heading,
          '#default_value' => $value,
          '#required' => TRUE,
        ];
      }
    }

    $form[$section]['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('ADD @section', ['@section' => $section]),
      '#ajax' => [
        'callback' => '::addElementCallback',
        'wrapper' => 'form-wrapper'
      ],
      '#name' => $section . '_submit',
    ];

  }

  public function addElementCallback (array &$form, FormStateInterface $form_state) {
    return $form;
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
            '#title' => $this->t('@index', ['@index' => $i]),
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
            ],
          ];
          $form[$currentKey]['actions']['remove_about'] = [
            '#type' => 'submit',
            '#value' => $this->t('Remove About'),
            '#submit' => ['::removeAboutSubmit'],
            '#ajax' => [
              'callback' => '::aboutCallback',
              'wrapper' => 'about-wrapper',
            ],
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
          ],
        ];
        $form[$currentKey]['actions']['remove_cashback'] = [
          '#type' => 'submit',
          '#value' => $this->t('Remove Category'),
          '#submit' => ['::removeCashbackSubmit'],
          '#ajax' => [
            'callback' => '::cashbackCallback',
            'wrapper' => 'cashback-wrapper',
          ],
        ];
      } else if ($heading == "BANNER" || $heading == "ICON") {
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
    $form[$section]['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('ADD @section', ['@section' => $section]),
      '#ajax' => [
        'callback' => '::addElementCallback',
        'wrapper' => 'form-wrapper'
      ],
      '#name' => $section . '_submit',
    ];
  }

  private function buildSearchSection(array &$form, $section, array $fields, FormStateInterface $form_state, $prefix = '') {
    foreach ($fields as $field => $value) {
      $heading = $this->t((string)$field);
      $currentKey = $prefix ? $prefix . "[$field]" : $field;
      $form[$currentKey] = [
        '#type' => 'textfield',
        '#title' => $heading,
        '#default_value' => $value,
        '#required' => TRUE,
      ];
    }

    $form[$section]['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('ADD @section', ['@section' => $section]),
      '#ajax' => [
        'callback' => '::addElementCallback',
        'wrapper' => 'form-wrapper'
      ],
      '#name' => $section . '_submit',
    ];
  }

  private function buildCategoriesSection(array &$form, $section, array $fields, FormStateInterface $form_state, array $category_options, $prefix = '') {
    $form['category'] = [
      '#type' => 'select',
      '#options' => $category_options,
      '#default_value' => (isset($record['category_id']) && isset($_GET['id'])) ? ['category_id'] : '', 
    ];

    $form[$section]['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('ADD @section', ['@section' => $section]),
      '#ajax' => [
        'callback' => '::addElementCallback',
        'wrapper' => 'form-wrapper'
      ],
      '#name' => $section . '_submit',
    ];
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

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $section =  str_replace('_submit', '', $triggering_element['#name']);
    \Drupal::logger('huttiprojects')->info('Processing section: @section', ['@section' => $section]);

    $updatedDetails = $this->buildUpdatedDetailsArray($form_state->getValues(), $section);
     \Drupal::logger('huttiprojects')->info('Processing section: @updatedDetails', ['@updatedDetails' => print_r($updatedDetails, TRUE)]);
    $this->messenger->addMessage($this->t('Project details updated successfully.'));

    if ($section == 'METADATA') {
        $file_id_1 = $form_state->getValue(['METADATA', 'LOGO']);
        if ($file_id_1) {
            $metadata_logo = File::load($file_id_1[0]);
            if ($metadata_logo) {
                $metadata_logo->setPermanent();
                $metadata_logo->save();
                $server_response = $this->uploadMetaDataToServer($metadata_logo, $updatedDetails);
            }
        }
    }
    if ($section == 'BRAND') {
        $file_id_2 = $form_state->getValue(['BRAND', 'BANNER']);
        $file_id_3 = $form_state->getValue(['BRAND', 'ICON']);
        if ($file_id_2 && $file_id_3) {
            $brand_banner = File::load($file_id_2[0]);
            $brand_icon = File::load($file_id_3[0]);
            if ($brand_banner && $brand_icon) {
                $brand_banner->setPermanent();
                $brand_banner->save();
                $brand_icon->setPermanent();
                $brand_icon->save();
                $server_response = $this->uploadBrandToServer($brand_banner, $brand_icon, $updatedDetails);
                if ($server_response) {
                    $this->messenger->addMessage($this->t('Logo uploaded and project details updated successfully.'));
                }
            }
        }
    }
    if ($section == 'SEARCH' || $section == 'CATEGORIES') {
        $server_response = $this->uploadDataToServer($updatedDetails);
        if ($server_response) {
            $this->messenger->addMessage($this->t('Project details updated successfully.'));
        }
    }
}


private function buildUpdatedDetailsArray($form_values, $section) {
  \Drupal::logger('huttiprojects')->info('Processing section: @section', ['@section' => $section]);

  $updatedDetails = [];

  if (isset($form_values['ID'])) {
      $updatedDetails['ID'] = $form_values['ID'];
  }
  \Drupal::logger('huttiprojects')->info('Processing ID: @form_values', ['@form_values' => print_r($form_values, TRUE)]);

  if ($section == 'METADATA') {
      if (isset($form_values['METADATA'])) {
          $updatedDetails['METADATA'] = $form_values['METADATA'];
          if (isset($form_values['METADATA'][ $section])) {
              unset($updatedDetails['METADATA'][ $section]);
          }
          if (isset($form_values['METADATA']['LOGO'])) {
              $updatedDetails['METADATA']['LOGO'] = reset($form_values['METADATA']['LOGO']);
          }
      }
  }

  if ($section == 'BRAND') {
      if (isset($form_values['BRAND'])) {
          $updatedDetails['BRAND'] = $form_values['BRAND'];
          if (isset($form_values['BRAND'][ $section])) {
              unset($updatedDetails['BRAND'][ $section]);
          }
          if (isset($form_values['BRAND']['BANNER'])) {
              $updatedDetails['BRAND']['BANNER'] = reset($form_values['BRAND']['BANNER']);
          }
          if (isset($form_values['BRAND']['ICON'])) {
              $updatedDetails['BRAND']['ICON'] = reset($form_values['BRAND']['ICON']);
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
  }

  if ($section == 'SEARCH') {
      if (isset($form_values['SEARCH'])) {
          $updatedDetails['SEARCH'] = $form_values['SEARCH'];
          if (isset($form_values['SEARCH'][ $section])) {
              unset($updatedDetails['SEARCH'][ $section]);
          }
      }
  }

  if ($section == 'CATEGORIES') {
      if (isset($form_values['CATEGORIES']) && isset($form_values['CATEGORIES']['category'])) {
          $category_id = $form_values['CATEGORIES']['category'];
          if (isset($this->categoryOptions[$category_id])) {
              $updatedDetails['CATEGORIES']['category'] = $this->categoryOptions[$category_id];
          }
          if (isset($form_values['CATEGORIES'][$section])) {
              unset($updatedDetails['CATEGORIES'][ $section]);
          }
      }
  }

  return $updatedDetails;
}


  public function uploadMetaDataToServer($metadata_logo, $details) {
    try {
        $client = new Client();
        $response = $client->post(self::BASE_URL .'/addMetadata', [
            'multipart' => [
                [
                    'name'=> 'metadata_logo',
                    'contents' => fopen($metadata_logo->getFileUri(),'r'),
                    'filename' => $metadata_logo->getFilename(),
                ],
                [
                    'name' => 'details',
                    'contents' => json_encode($details),
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                ],
            ]
        ]);
        $data = json_decode($response->getBody(), TRUE);
        return $data;
    } catch (RequestException $e) {
        $this->messenger->addError($this->t('An error occurred while uploading the file: @message', ['@message' => $e->getMessage()]));
    }
}


  private function uploadBrandToServer($brand_banner, $brand_icon, $details) {
    try {
      $client = new Client();
      $response = $client->post(self::BASE_URL . '/addNewBrand', [
        'multipart' => [
          [
            'name' => 'brand_banner',
            'contents' => fopen($brand_banner->getFileUri(), 'r'),
            'filename' => $brand_banner->getFilename(),
          ],
          [
            'name' => 'brand_icon',
            'contents' => fopen($brand_icon->getFileUri(), 'r'),
            'filename' => $brand_icon->getFilename(),
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

    public function uploadDataToServer($details) {
    try {
      $client = new Client();
      $response = $client->post(self::BASE_URL .'/addNewProject', [
         'multipart' => [
         [
         'name' => 'details',
         'contents' => json_encode($details),
        ]
        ],
      ]);
      $data = json_decode($response->getBody(), TRUE);
      return $data;
    }
    catch (RequestException $e) {
       $this->messenger->addError($this->t('An error occurred while uploading the file: @message', ['@message' => $e->getMessage()]));
    }
  }
}
