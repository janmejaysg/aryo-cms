<?php

namespace Drupal\aryoprojects\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
// use Drupal\aryoprojects\Service\ProjectDataService;


class ProjectsByCategoryForm extends FormBase {

  // protected $projectDataService;

  // public function __construct(ProjectDataService $project_data_service) {
  //   $this->projectDataService = $project_data_service;
  // }

  const BASE_URL = 'http://localhost:8081';
  protected $httpClient;


  public function __construct(ClientInterface $http_client){
    $this->httpClient = $http_client;
  }

  // public static function create(ContainerInterface $container) {
  //   return new static(
  //     $container->get('aryoprojects.project_data_service')
  //   );
  // }

  public static function create(ContainerInterface $container){
    return new static(
      // Symfony\Component\DependencyInjection\ContainerInterface::get
      // Finds an entry of the container by its identifier and returns it.
      $container->get('http_client')
    );
  }

  public function getFormId() {
    return "projectsByCategory_form";
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $category = \Drupal::routeMatch()->getParameter('category');

    $allProjects = $this->getProjectsByCategory($category);
    $form = [];
    $tableRows = [];

    if ($allProjects) {
      foreach ($allProjects as $key) {
        $url = Url::fromRoute('aryoprojects.project_details', [
          'category' => $category,
          'projectName' => $key,
        ]);
        $link = Link::fromTextAndUrl($key, $url)->toRenderable();
        $tableRows[] = [
          'project' => ['data' => $link],
        ];
      }
    }

    $header = ['Project Name']; // Adjust the header according to your data

    $form['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $tableRows,
    ];

    $form['#attached']['library'][] = 'aryoprojects/aryoprojects_js_css';

    return $form;
  }

  public function getProjectsByCategory($category){

    $url = self::BASE_URL . '/getProjectsByCategory';

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

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Form submission logic goes here
  }

}
