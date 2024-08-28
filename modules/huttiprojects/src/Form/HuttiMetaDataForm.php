<?php 
namespace Drupal\huttiprojects\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

class HuttiMetaDataForm extends FormBase {

  protected $httpClient;

  const BASE_URL = 'http://localhost:8081';

  public function __construct(ClientInterface $http_client) {
    $this->httpClient = $http_client;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client')
    );
  }

  public function getFormId() {
    return 'hutti_metadata_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#tree'] = TRUE; // Ensure tree structure

    $category = \Drupal::routeMatch()->getParameter('category');
    $subcategory = \Drupal::routeMatch()->getParameter('subcategory');
  
    $projectDetails = $this->getProjectDetails($category, $subcategory);

    // echo print_r($projectDetails, TRUE);
  
    $form['#prefix'] = '<div id="form-wrapper">';
    $form['#suffix'] = '</div>';
  
    if ($projectDetails) {
      foreach ($projectDetails as $section => $data) {
        // Call buildSection with $form, $section, $data, and $form_state
        if (is_array($data)) {
          $this->buildSection($form, $section, $data, $form_state);
        }
      }
    } else {
      $form['error'] = [
        '#markup' => $this->t('Unable to fetch project details'),
      ];
    }
  
    return $form;
  }
  
  private function buildSection(array &$form, $section, array $data, FormStateInterface $form_state) {
    $form[$section] = [
      '#type' => 'details',
      '#title' => $this->t($section),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
  
    if ($section == 'BRAND') {
      $this->buildFormElementsForBRAND($form[$section], $data, $form_state);
    } else {
      $this->buildFormElements($form[$section], $data);
    }
  
    $form[$section]['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update @section', ['@section' => $section]),
      '#ajax' => [
        'callback' => '::updateElementCallback',
        'wrapper' => 'form-wrapper',
      ],
      '#name' => $section . '_submit',
    ];
  }

  private function buildFormElementsForBRAND(array &$form, array $data, FormStateInterface $form_state, $prefix = '') {
    foreach ($data as $key => $value) {
      $title = $this->t((string) $key);
      $currentKey = $prefix ? $prefix . "[$key]" : $key;

      if (is_array($value)) {
        $form[$currentKey] = [
          '#type' => 'details',
          '#title' => $title,
          '#open' => TRUE,
          '#tree' => TRUE,
        ];

        if ($key == 'CASHBACK') {
          $form[$currentKey]['#prefix'] = '<div id="cashback-wrapper">';
          $form[$currentKey]['#suffix'] = '</div>';
          $form[$currentKey]['#tree'] = TRUE;

          // Initialize the number of cashback items
          $num_cashback = $form_state->get('num_cashback');
          if (is_null($num_cashback)) {
            $num_cashback = count($value);
            $form_state->set('num_cashback', $num_cashback);
          }

          for ($i = 0; $i < $num_cashback; $i++) {
            $form[$currentKey][$i]['CATEGORY'] = [
              '#type' => 'textfield',
              '#title' => $this->t('Category'),
              '#default_value' => isset($value[$i]['CATEGORY']) ? $value[$i]['CATEGORY'] : '',
            ];
            $form[$currentKey][$i]['RATE'] = [
              '#type' => 'textfield',
              '#title' => $this->t('Rate'),
              '#default_value' => isset($value[$i]['RATE']) ? $value[$i]['RATE'] : '',
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
        } else {
          foreach ($value as $subkey => $subvalue) {
            $subTitle = $this->t((string) $subkey);
            $subCurrentKey = $currentKey . "[$subkey]";

            if (is_array($subvalue)) {
              $form[$subCurrentKey] = [
                '#type' => 'details',
                '#title' => $subTitle,
                '#open' => TRUE,
                '#tree' => TRUE,
              ];

              foreach ($subvalue as $subkey2 => $subvalue2) {
                $subTitle2 = $this->t((string) $subkey2);
                $subCurrentKey2 = $subCurrentKey . "[$subkey2]";

                $type = is_bool($subvalue2) ? 'select' : (is_numeric($subvalue2) ? 'number' : 'textfield');

                $form[$subCurrentKey2] = [
                  '#type' => $type,
                  '#title' => $subTitle2,
                  '#default_value' => $subvalue2,
                ];

                if ($type === 'select') {
                  $form[$subCurrentKey2]['#options'] = [
                    1 => $this->t('True'),
                    0 => $this->t('False'),
                  ];
                  $form[$subCurrentKey2]['#default_value'] = $subvalue2 ? 1 : 0;
                } elseif ($type === 'number') {
                  $form[$subCurrentKey2]['#default_value'] = $subvalue2;
                } else {
                  $form[$subCurrentKey2]['#default_value'] = (string) $subvalue2;
                  $form[$subCurrentKey2]['#maxlength'] = 5000;
                }
              }
            } else {
              $type = is_bool($subvalue) ? 'select' : (is_numeric($subvalue) ? 'number' : 'textfield');

              $form[$subCurrentKey] = [
                '#type' => $type,
                '#title' => $subTitle,
                '#default_value' => $subvalue,
              ];

              if ($type === 'select') {
                $form[$subCurrentKey]['#options'] = [
                  1 => $this->t('True'),
                  0 => $this->t('False'),
                ];
                $form[$subCurrentKey]['#default_value'] = $subvalue ? 1 : 0;
              } elseif ($type === 'number') {
                $form[$subCurrentKey]['#default_value'] = $subvalue;
              } else {
                $form[$subCurrentKey]['#default_value'] = (string) $subvalue;
                $form[$subCurrentKey]['#maxlength'] = 5000;
              }
            }
          }
        }
      } else {
        $type = is_bool($value) ? 'select' : (is_numeric($value) ? 'number' : 'textfield');

        $form[$currentKey] = [
          '#type' => $type,
          '#title' => $title,
          '#default_value' => $value,
        ];

        if ($type === 'select') {
          $form[$currentKey]['#options'] = [
            1 => $this->t('True'),
            0 => $this->t('False'),
          ];
          $form[$currentKey]['#default_value'] = $value ? 1 : 0;
        } elseif ($type === 'number') {
          $form[$currentKey]['#default_value'] = $value;
        } else {
          $form[$currentKey]['#default_value'] = (string) $value;
          $form[$currentKey]['#maxlength'] = 5000;
        }
      }
    }
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

  private function buildFormElements(array &$form, array $data, $prefix = '') {
    foreach ($data as $key => $value) {
      $title = $this->t((string) $key);
      $currentKey = $prefix ? $prefix . "[$key]" : $key;

      if (is_array($value)) {
        $form[$currentKey] = [
          '#type' => 'details',
          '#title' => $title,
          '#open' => TRUE,
          '#tree' => TRUE,
        ];
        $this->buildFormElements($form[$currentKey], $value); // Recursive call
      } else {
        $type = is_bool($value) ? 'select' : (is_numeric($value) ? 'number' : 'textfield');

        $form[$currentKey] = [
          '#type' => $type,
          '#title' => $title,
          '#default_value' => $value,
        ];

        if ($type === 'select') {
          $form[$currentKey]['#options'] = [
            1 => $this->t('True'),
            0 => $this->t('False'),
          ];
          $form[$currentKey]['#default_value'] = $value ? 1 : 0;
        } elseif ($type === 'number') {
          $form[$currentKey]['#default_value'] = $value;
        } else {
          $form[$currentKey]['#default_value'] = (string) $value;
          $form[$currentKey]['#maxlength'] = 5000;
        }
      }
    }
  }

  public function updateElementCallback(array &$form, FormStateInterface $form_state) {
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $category = \Drupal::routeMatch()->getParameter('category');
    $subcategory = \Drupal::routeMatch()->getParameter('subcategory');

    // Get the triggered button's name to find which section is being updated
    $triggering_element = $form_state->getTriggeringElement();
    $section = str_replace('_submit', '', $triggering_element['#name']);

    // Get the data for the triggered section
    $section_data = $this->buildUpdatedDetailsArray($form_state->getValue($section));

    $url = self::BASE_URL . '/updateSpecificHuttiProjects';
    try {
      $response = $this->httpClient->request('POST', $url, [
        'json' => [
          'category' => $category,
          'subcategory' => $subcategory,
          'details' => [
            $section => $section_data,
          ],
        ]
      ]);

      if ($response->getStatusCode() == 200) {
        \Drupal::messenger()->addStatus($this->t('Section "@section" has been updated successfully.', ['@section' => $section]));
      } else {
        \Drupal::messenger()->addError($this->t('Failed to update section "@section".', ['@section' => $section]));
      }
    } catch (RequestException $e) {
      \Drupal::logger('huttiprojects')->error('Error updating section "@section": @message', ['@section' => $section, '@message' => $e->getMessage()]);
      \Drupal::messenger()->addError($this->t('An error occurred while updating section "@section".', ['@section' => $section]));
    }
  }

  private function buildUpdatedDetailsArray(array $form_values) {
    $updatedDetails = [];
    foreach ($form_values as $key => $value) {
      if (!in_array($key, ['form_build_id', 'form_token', 'form_id', 'op', 'submit', 'action'])) {
        $this->setNestedArrayValue($updatedDetails, $key, $value);
      }
    }
    return $updatedDetails;
  }

  public function getProjectDetails($category, $subcategory) {
    $url = self::BASE_URL . '/getHuttiProjects';

    try {
      $response = $this->httpClient->request('GET', $url, [
        'query' => [
          'category' => $category,
          'subcategory' => $subcategory
        ],
      ]);

      $responseBody = $response->getBody()->getContents();
      return json_decode($responseBody, TRUE);
    } catch (RequestException $e) {
      return null;
    }
  }

  private function setNestedArrayValue(array &$array, $path, $value) {
    $keys = preg_split('/[\[\]]+/', $path, -1, PREG_SPLIT_NO_EMPTY);
    $current = &$array;

    foreach ($keys as $key) {
      if (!isset($current[$key])) {
        $current[$key] = [];
      }
      $current = &$current[$key];
    }

    $current = $value;
  }
}
