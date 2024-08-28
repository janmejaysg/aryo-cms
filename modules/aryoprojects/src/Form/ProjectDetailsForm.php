<?php

namespace Drupal\aryoprojects\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
// use Drupal\aryoprojects\Service\ProjectDataService;
use Drupal\file\Entity\File;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;

class ProjectDetailsForm extends FormBase {
  protected $httpClient;
  // protected $projectDataService;

  const BASE_URL = 'http://localhost:8081';

  public function __construct(ClientInterface $http_client) {
    $this->httpClient = $http_client;
    // $this->projectDataService = $project_data_service;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client'),
      // $container->get('aryoprojects.project_data_service')
    );
  }

  public function getFormId() {
    return 'project_details_form';
  }
  public function buildForm(array $form, FormStateInterface $form_state) {
    $category = \Drupal::routeMatch()->getParameter('category');
    $projectName = \Drupal::routeMatch()->getParameter('projectName');

    $projectDetails = $this->getProjectDetails($category, $projectName);

    $form['update_success'] = [
      '#type' => 'markup',
      '#markup' => '<div class="success"></div>',
    ];

    $form['update_fail'] = [
      '#type' => 'markup',
      '#markup' => '<div class="failure"></div>',
    ];

    if ($projectDetails) {
      foreach ($projectDetails as $key => $value) {
        if ($key == 'INSTITUTION_LOGO') {
          $form[$key] = [
            '#type' => 'markup',
            '#markup' => '<h6 style="font-weight: 600;">' . $this->t($key) . '</h6>',
          ];
          $form[$key . '_container'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['me-2']],
          ];
          $form[$key . '_container'][$key] = [
            '#title' => $this->t($key),
            '#default_value' => $value,
            '#maxlength' => 5000,
            '#attributes' => ['class' => ['me-2']],
          ];

          if (!empty($value)) {
            $form[$key . '_container'][$key]['institution_logo'] = [
              '#theme' => 'image',
              '#uri' => $value,
              '#alt' => $this->t('institution logo'),
              '#attributes' => ['class' => ['img-thumbnail'], 'style' => 'width: 40px; height: auto']
            ];
          }

          $form['institution_logo'] = [
            '#type' => 'managed_file',
            '#name' => 'institution_logo',
            '#upload_location' => 'public://',
            '#upload_validators' => [
              'file_validate_extensions' => ['jpg jpeg png']
            ],
          ];

          $form['upload_institution_logo'] = [
            '#type' => 'button',
           '#value' => $this->t('Upload Institution Logo'),
            '#ajax' => [
              'callback' => '::updateInstitutionLogo'
            ],
          ];

        } elseif ($key == 'LOGO') {
          $form[$key] = [
            '#type' => 'markup',
            '#markup' => '<h6 style="font-weight: 600;">' . $this->t($key) . '</h6>',
          ];
          $form[$key . '_container'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['me-2',]],
          ];
          
          $form[$key . '_container'][$key] = [
            '#title' => $this->t($key),
            '#default_value' => $value,
            '#maxlength' => 5000,
            '#attributes' => ['class' => ['me-2']],
          ];

          if (!empty($value)) {
            
            $form[$key . '_container'][$key]['logo'] = [
              '#theme' => 'image',
              '#uri' => $value,
              '#alt' => $this->t('logo'),
              '#attributes' => ['class' => ['img-thumbnail'], 'style' => 'width: 140px; height: auto']
            ];
          }

          $form['logo'] = [
            '#type' => 'managed_file',
            '#name' => 'logo',
            '#upload_location' => 'public://',
            '#upload_validators' => [
              'file_validate_extensions' => ['jpg jpeg png']
            ],
          ];

          $form['upload_logo'] = [
            '#type' => 'button',
            '#value' => $this->t('Upload'),
            '#ajax' => [
              'callback' => '::uploadLogo'
            ],
          ];

        } elseif (is_bool($value)) {
          $form[$key] = [
            '#type' => 'select',
            '#title' => $this->t($key),
            '#options' => [
              1 => $this->t('true'),
              0 => $this->t('false'),
            ],
            '#default_value' => $value ? 1 : 0,
            '#ajax' => [
              'callback' => '::updateField',
              'event' => 'change',
              'progress' => ['type' => 'throbber', 'message' => $this->t('Updating...')],
            ],
          ];
        } else {
          $form[$key] = [
            '#type' => 'textfield',
            '#title' => $this->t($key),
            '#default_value' => $value,
            '#maxlength' => 5000,
            '#ajax' => [
              'callback' => '::updateField',
              'event' => 'change',
              'progress' => ['type' => 'throbber', 'message' => $this->t('Updating...')],
            ],
          ];
        }
      }
    } else {
      $form['error'] = [
        '#markup' => $this->t('Unable to fetch project details.'),
      ];
    }

    // Attach custom library for handling enter key events
    $form['#attached']['library'][] = 'aryoprojects/aryoprojects_js_css';

    return $form;
  }

  public function getProjectDetails($category, $projectName){
    $url = self::BASE_URL . '/getProjectDetails';
    \Drupal::logger('aryoprojects')->info('value is @url',['@url' => $url]);
    try{

      \Drupal::logger('aryoprojects')->info('inside try block');
      $reponse = $this->httpClient->request('GET', $url,[
        'query' => [
          'category' => $category,
          'projectName' => $projectName
        ]
      ]);

      $reponseBody = $reponse->getBody()->getContents();
      $data = json_decode($reponseBody, true );

      return $data;
      }
    catch(RequestException $e){
      \Drupal::logger('aryoprojects')->error($e->getMessage());
      return null;
    }
  }

  public function uploadLogo(array &$form, FormStateInterface $form_state) {
    $ajax_response = new AjaxResponse();
    $category = \Drupal::routeMatch()->getParameter('category');
    $projectName = \Drupal::routeMatch()->getParameter('projectName');
    $file_id = $form_state->getValue('logo');
    $logo = $file_id ? File::load($file_id[0]) : null;

    if ($logo) {
        $logo->setPermanent();
        $logo->save();
      
        $url = self::BASE_URL . '/updateProjectLogo';
        $multipart = [
          [
              'name' => 'category',
              'contents' => $category,
          ],
          [
              'name' => 'projectName',
              'contents' => $projectName,
          ],
          [
              'name' => 'key',
              'contents' => 'LOGO',
          ],
          [
              'name' => 'logo',
              'contents' => fopen($logo->getFileUri(), 'r'),
              'filename' => $logo->getFilename(),
          ],
      ];     

        try {
            $response = $this->httpClient->request('POST', $url, [
                'multipart' => $multipart,
            ]);

            $body = json_decode($response->getBody(), true);
            if (isset($body['logo_url'])) {
            
                $form_state->setValue('LOGO', $body['logo_url']);
         
                $ajax_response->addCommand(new ReplaceCommand('#edit-logo-container img','<img src="' . $body['logo_url'] . '" class="img-thumbnail" style="width: 140px; height: auto">'));
                // \Drupal::messenger()->addMessage($this->t('Logo uploaded successfully and URL updated.'));
                return $ajax_response;
            } else {
                // \Drupal::messenger()->addError($this->t('Failed to get logo URL from the response.'));
                $ajax_response->addCommand(new HtmlCommand('.failure', 'Failed to get logo URL from the response.'));
            }
        } catch (RequestException $e) {
            \Drupal::logger('aryoprojects')->error($e->getMessage());
            \Drupal::messenger()->addError($this->t('Failed to upload the logo. Please try again.'));
        }
    } else {
      $ajax_response->addCommand(new HtmlCommand('.failure', 'No file was uploaded.'));
    }
    return new $ajax_response;
}


public function updateInstitutionLogo(array &$form, FormStateInterface $form_state) {
  $ajax_response = new AjaxResponse();
  $category = \Drupal::routeMatch()->getParameter('category');
  $projectName = \Drupal::routeMatch()->getParameter('projectName');
  $file_id = $form_state->getValue('institution_logo');
  $institution_logo = $file_id ? File::load($file_id[0]) : null;

  \Drupal::logger('aryoprojects')->info('id is => @institution_logo', ['@institution_logo' => $institution_logo]);

  if($institution_logo) {
    $institution_logo->setPermanent();
    $institution_logo->save();

    $url = self::BASE_URL . '/updateProjectLogo';

    $multipart = [
      [
        'name' => 'category',
        'contents' => $category,
      ],
      [
        'name' => 'projectName',
        'contents' => $projectName,
      ],
      [
        'name' => 'key',
        'contents' => 'INSTITUTION_LOGO',
      ],
      [
        'name' => 'institution_logo',
        'contents' => fopen($institution_logo->getFileUri(),'r'),
        'filename' => $institution_logo->getFilename(), 
      ],
    ];

    try{
      $response = $this->httpClient->request('POST', $url, [
        'multipart' => $multipart,
      ]);

      $body = json_decode($response->getBody(), true);
      if (isset($body['logo_url'])) {
        $form_state->setValue('INSTITUTION_LOGO', $body['logo_url']);

        // Create an Ajax response to update the logo image
        $ajax_response->addCommand(new ReplaceCommand('#edit-institution-logo-container img', '<img src="' . $body['logo_url'] . '" class="img-thumbnail" style="width: 140px; height: auto">'));
        $ajax_response->addCommand(new HtmlCommand('.success', 'INSTITUTION_LOGO updated successfully'));
        return $ajax_response;
      } else {
        $ajax_response->addCommand(new HtmlCommand('.failure', 'INSTITUTION_LOGO updatation failed'));
      }
    } catch (RequestException $e) {
      \Drupal::logger('aryoprojects')->error($e->getMessage());
      $ajax_response->addCommand(new HtmlCommand('.failure', 'Failed to upload the logo. Please try again.'));
    }
  } else {
    $ajax_response->addCommand(new HtmlCommand('.failure', 'No file was uploaded.'));
  }

  return $ajax_response;
}

public function updateField(array &$form, FormStateInterface $form_state){
  $ajax_response = new AjaxResponse();
  $category = \Drupal::routeMatch()->getParameter('category');
  $projectName = \Drupal::routeMatch()->getParameter('projectName');
  $key = $form_state->getTriggeringElement()['#name'];
  $value = $form_state->getValue($key);

  if ($value === '1') {
    $value = true;
  } elseif ($value === '0') {
    $value = false;
  }

  if($key === 'PAYOUT1' || $key === 'PAYOUT2'){
    $value = doubleval($value);
  }

  $url = self::BASE_URL . '/updateField';

  try{
    $response = $this->httpClient->request('POST', $url, [
      'json' => [
        'category' => $category,
        'projectName' => $projectName,
        'key' => $key,
        'value' => $value,
      ],
    ]);

    $responseBody = $response->getBody()->getContents();
    $ajax_response->addCommand(new HtmlCommand('.success', $responseBody));
  }

  catch (RequestException $e) {
    \Drupal::logger('aryoprojects')->error($e->getMessage());
    $ajax_response->addCommand(new HtmlCommand('.failure', 'An error occurred while updating the field.'));
  }

  return $ajax_response;

}

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Implement the code to update project details here.
  }
}
