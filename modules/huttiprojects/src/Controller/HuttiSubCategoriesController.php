<?php

namespace Drupal\huttiprojects\Controller;

use Drupal\Core\Controller\ControllerBase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use Symfony\Component\DependencyInjection\ContainerInterface;

class HuttiSubCategoriesController extends ControllerBase {
    protected $httpClient;

    public function __construct(Client $http_client) {
        $this->httpClient = $http_client;
    }

    public static function create(ContainerInterface $container) {
        return new static(
            $container->get('http_client')
        );
    }

    const BASE_URL = 'http://localhost:8081';

    public function fetchHuttiSubCategories($category) {
        $form = [];
        try {
            $response = $this->httpClient->request('GET', self::BASE_URL . '/getHuttiSubCategories' , [
                'query' => [
                    'category' => $category
                ]
            ]);

            $responseBody = $response->getBody()->getContents();
            $data = json_decode($responseBody, true);

            $subCategoriesName = [];

            foreach ($data as $subcategory) {
                if($category === "METADATA") {
                    $subCategoriesName[] = [
                        'subcategory' => $this->t("<a href='get-hutti-metadata/$category/$subcategory'>$subcategory</a>"),
                    ];
                }
                else {
                    $subCategoriesName[] = [
                        'subcategory' => $this->t("<a href='get-hutti-projects/$category/$subcategory'>$subcategory</a>"),
                    ];
                }
              
            }

            $form['table'] = [
                '#type' => 'table',
                '#header' => ['Sub Category'],
                '#rows' => $subCategoriesName
            ];

        }
        catch (RequestException $e) {
            // $this->handleRequestException($e);
          } catch (ConnectException $e) {
            // $this->handleConnectException($e);
          } catch (\Exception $e) {
            // $this->handleUnexpectedException($e);
          }
      
          return $form;
    }

    // private function handleRequestException(RequestException $e) {
    //     $this->logger('huttiprojects')->error('HTTP request failed: @message', ['@message' => $e->getMessage()]);
    //     // Handle request exception
    //   }
    
    //   private function handleConnectException(ConnectException $e) {
    //     $this->logger('huttiprojects')->error('Connection failed: @message', ['@message' => $e->getMessage()]);
    //     // Handle connection exception
    //   }
    
    //   private function handleUnexpectedException(\Exception $e) {
    //     $this->logger('huttiprojects')->error('An unexpected error occurred: @message', ['@message' => $e->getMessage()]);
    //     // Handle other exceptions
    //   }
}