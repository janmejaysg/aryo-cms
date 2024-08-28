<?php

namespace Drupal\huttiprojects\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class HuttiService {
  protected $httpClient;

  const BASE_URL = 'http://192.168.1.26:84';

  public function __construct(Client $client) {
    $this->httpClient = $client;
  }

  public function getProgramNames() {
    $url = self::BASE_URL . '/getProgramNameList';
    $programNames = [];

    try {
      $response = $this->httpClient->request('GET', $url);
      $responseBody = json_decode($response->getBody()->getContents(), TRUE);

      if (isset($responseBody)) {
        foreach ($responseBody as $key => $value) {
          $programNames[$key] = $value;
        }
      }
    } catch (RequestException $e) {
      \Drupal::logger('huttiprojects')->error('Failed to fetch program names: @message', ['@message' => $e->getMessage()]);
    }

    return $programNames;
  }
}
