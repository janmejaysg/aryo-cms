<?php

namespace Drupal\aryoprojects\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

class AllProjectsForm extends FormBase {

  const BASE_URL = 'http://localhost:8081';
  protected $httpClient;


  public function __construct(ClientInterface $http_client){
    $this->httpClient = $http_client;
  }

  public static function create(ContainerInterface $container){
    return new static(
      // Symfony\Component\DependencyInjection\ContainerInterface::get
      // Finds an entry of the container by its identifier and returns it.
      $container->get('http_client')
    );
  }

  public function getFormId(){
    return 'allprojects_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state){

    $category = \Drupal::routeMatch()->getParameter('categoryName');

    $allprojects = $this->getProjectsByCategory($category);

    $form = [];
    $tableRows = [];

    if($allprojects){
      foreach ($allprojects as $key) {
            $url = Url::fromRoute('aryoprojects.project_details_of_selected_project',[
              'categoryName' => $category,
              'projectName' => $key
            ]);
            $link = Link::fromTextAndUrl($key, $url)->toRenderable();
            $tableRows[] = [
              'project' => ['data' => $link],
            ];

          }
        }
    
    $header = ['Projects'];
    $form['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $tableRows,
    ];

    $form['#attached']['library'][] = 'aryoprojects/aryoprojects_js_css';
    return $form;
  }

  public function getProjectsByCategory($category){

    $url = self::BASE_URL . '/getAllProjectsByCategoryName';

    try{
      $response = $this->httpClient->request('GET', $url,[
        'query' => [
          'category' => $category,
        ]
      ]);
      $responsebody = $response->getBody()->getContents();
      $data = json_decode($responsebody,true);

      return $data;
    }

    catch(RequestException $e){
      \Drupal::logger('aryoprojects')->error($e->getMessage());
      return null;
    }

  }

  public function submitForm(array &$form, FormStateInterface $form_state){

  }


}