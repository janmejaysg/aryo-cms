<?php

namespace Drupal\huttiprojects\Controller;

use Drupal\Core\Controller\ControllerBase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;

class HuttiCategoriesController extends ControllerBase {
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

    public function buildForm() {
        $form = [];
        try {
            $response = $this->httpClient->get(self::BASE_URL . '/getAllHuttiCategories');
            $responseBody = $response->getBody()->getContents();
            $data = json_decode($responseBody, true);

            \Drupal::logger('huttiprojects')->info('Data received: @data', ['@data' => print_r($data, true)]);

            $categoriesName = [];

            foreach ($data as $category) {
                $categoriesName[] = [
                    'category' => $this->t("<a href='hutti-sub-categories/$category'>$category</a>")
                ];
            }

            $header = ['All Categories'];

            $form['table'] = [
                '#type' => 'table',
                '#header' => $header,
                '#rows' => $categoriesName
            ];

        }
        catch (RequestException $e) {
            // $this->handleRequestException($e);
        }
        catch (ConnectException $e) {
            // $this->handleConnectExcepion($e);
        }
        catch (\Exception $e) {
            // $this->handleUnexpectedException($e);
        }

        return $form;
    }

}