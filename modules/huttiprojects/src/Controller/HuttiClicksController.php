<?php

namespace Drupal\huttiprojects\Controller;

use Drupal\Core\Controller\ControllerBase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class HuttiClicksController extends ControllerBase {
  protected $httpClient;

  const BASE_URL = 'http://192.168.1.26:84';

  public function __construct(Client $client) {
    $this->httpClient = $client;
  }

  public function getHuttiClicks($fromDate, $toDate, $programName, $clickStatus, $cashbackStatus) {
    
    $url = self::BASE_URL . '/getClicksDump';
    \Drupal::logger('huttiprojects')->info('Fetching clicks with parameters: fromDate = @fromDate, toDate = @toDate, programName = @programName, clickStatus = @clickStatus, cashbackStatus = @cashbackStatus', [
      '@fromDate' => $fromDate,
      '@toDate' => $toDate,
      '@programName' => $programName,
      '@clickStatus' => $clickStatus,
      '@cashbackStatus' => $cashbackStatus,
    ]);

    try {
      $response = $this->httpClient->request('GET', $url, [
        'query' => [
          'fromDate' => $fromDate,
          'toDate' => $toDate,
          'programName' => $programName,
          'status' => $clickStatus,
          'cashbackStatus' => $cashbackStatus,
        ]
      ]);

      $responseBody = $response->getBody()->getContents();
      \Drupal::logger('huttiprojects')->info('Clicks data => @responseBody', ['@responseBody' => $responseBody]);
      return json_decode($responseBody, true);

    } catch (RequestException $e) {
      \Drupal::logger('huttiprojects')->error('Failed to fetch clicks data: @message', ['@message' => $e->getMessage()]);
      return [];
    }
  }
}
