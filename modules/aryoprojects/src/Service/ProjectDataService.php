<?php
namespace Drupal\aryoprojects\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\Cache\CacheBackendInterface;

class ProjectDataService {

  protected $httpClient;
  protected $cacheBackend;

  // const BASE_URL = 'http://34.31.137.130';
  const BASE_URL = 'http://localhost:8081';

  public function __construct(Client $http_client, CacheBackendInterface $cache_backend) {
    $this->httpClient = $http_client;
    $this->cacheBackend = $cache_backend;
  }

  public function getProjectsByCategory($category) {
    $cacheId = 'projects_by_category_' . $category;
    if ($cache = $this->cacheBackend->get($cacheId)) {
      return $cache->data;
    }

    $url = self::BASE_URL . '/getChild';

    try {
      $response = $this->httpClient->request('GET', $url, [
        'query' => [
          'path' => $category,
        ],
      ]);
      $responseBody = $response->getBody()->getContents();
      $data = json_decode($responseBody, true);
      \Drupal::logger('aryoprojects')->info('data => @data', ['@data' => print_r($data, TRUE)]);

      // Cache the data for 1 hour (3600 seconds).
      $this->cacheBackend->set($cacheId, $data, time() + 3600);

      return $data;
    } catch (RequestException $e) {
      \Drupal::logger('aryoprojects')->error($e->getMessage());
      return null;
    }
  }

  public function getAryoProjectInnerDetails() {
    $cacheId = 'projects_inner_details';
    if ($cache = $this->cacheBackend->get($cacheId)){
      return $cache->data;
    }

    $url = self::BASE_URL . '/getAllCategories';

    try{
      $response = $this->httpClient->request('GET', $url);
      $responseBody = $response->getBody()->getContents();
      $data = json_decode($responseBody, true);
      \Drupal::logger('aryoprojects')->info('data => @data', ['@data' => print_r($data, TRUE)]);

      $this->cacheBackend->set($cacheId, $data, time() + 3600);

      return $data;
    }

    catch(RequestException $e) {
      \Drupal::logger('aryoprojects')->error($e->getMessage());
      return null;
    }
  }

}
