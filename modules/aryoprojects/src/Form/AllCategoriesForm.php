<?php
namespace Drupal\aryoprojects\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use GuzzleHttp\Exception\RequestException;

class AllCategoriesForm extends FormBase {

  const BASE_URL = 'http://localhost:8081';

  protected $httpClient;

  public function __construct(ClientInterface $http_client){
    $this->httpClient = $http_client;
  }

  public static function create(ContainerInterface $container){
    return new static(
      $container->get('http_client')
    );
  }
  public function getFormId() {
    return 'allprojectsCategory_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state){
    $categories = $this->getAllCategories();

    // echo print_r($allDetails, true);

    $form = [];
    $tableRows = [];

    if($categories){
      foreach($categories as $key){
        // Creates a new Url object for a URL that has a Drupal route.
        $url = Url::fromRoute('aryoprojects.all_projects_of_category',[
          'categoryName' => $key
        ]);
        $link = Link::fromTextAndUrl($key, $url)->toRenderable();
        $tableRows[] = [
          'category' => ['data' => $link],
        ];

      }
    }

    $header = ['Categories'];
    $form['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $tableRows,
    ];

    $form['#attached']['library'][] = 'aryoprojects/aryoprojects_js_css';

    return $form;
  }

  public function getAllCategories() {

    $url = self::BASE_URL . '/getAllCategories';

    try{
      $response = $this->httpClient->request('GET', $url);
      $responseBody = $response->getBody()->getContents();
      $data = json_decode($responseBody, true);
      \Drupal::logger('aryoprojects')->info('data => @data', ['@data' => print_r($data, TRUE)]);


      return $data;
    }

    catch(RequestException $e) {
      \Drupal::logger('aryoprojects')->error($e->getMessage());
      return null;
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state){

  }
}