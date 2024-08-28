<?php

namespace Drupal\aryoleads\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use GuzzleHttp\Exception\RequestException;

class AryoLeadsController extends ControllerBase {
  public function fetchData($fromDate, $toDate, $status, $category, $projectName, $leadId, $agentId, $mobile) {
    $client = \Drupal::httpClient();

    $url = 'http://localhost:8081/getAryoDistLeads';

    try {
      $response = $client->request('GET', $url, [
        'query' => [
          'fromDate' => $fromDate,
          'todate' => $toDate,
          'status' => $status,
          'category' => $category,
          'projectName' => $projectName,
          'leadId' => $leadId,
          'agentId' => $agentId,
          'mobile' => $mobile
        ],
      ]);

      $csvContent = $response->getBody()->getContents();
      $timestamp = date('Y-m-d_H-i-s');
      $filename = "Aryo_Leads_{$timestamp}.csv";

      // Set response headers for CSV download
      $response = new Response($csvContent);
      $response->headers->set('Content-Type', 'text/csv');
      $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

      return $response;
    } catch (RequestException $e) {
      \Drupal::logger('aryoleads')->error($e->getMessage());
      return new JsonResponse(['error' => 'Failed to fetch data from external API.'], 500);
    }
  }

  public function fetchSelfLeads($fromDate, $toDate, $status, $category, $projectName, $leadId, $agentId, $mobile) {
    $client = \Drupal::httpClient();

    $url = 'http://localhost:8081/getAryoSelfLeads';

    try {
      $response = $client->request('GET', $url, [
        'query' => [
          'fromDate' => $fromDate,
          'todate' => $toDate,
          'status' => $status,
          'category' => $category,
          'projectName' => $projectName,
          'leadId' => $leadId,
          'agentId' => $agentId,
          'mobile' => $mobile
        ],
      ]);

      $csvContent = $response->getBody()->getContents();
      $timestamp = date('Y-m-d_H-i-s');
      $filename = "Aryo_Leads_{$timestamp}.csv";

      // Set response headers for CSV download
      $response = new Response($csvContent);
      $response->headers->set('Content-Type', 'text/csv');
      $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

      return $response;
    } catch (RequestException $e) {
      \Drupal::logger('aryoleads')->error($e->getMessage());
      return new JsonResponse(['error' => 'Failed to fetch data from external API.'], 500);
    }
  }

  public function fetchDistributorLeadsCount() {
    $client = \Drupal::httpClient();
    $url = 'http://localhost:8081/getTodaysAryoDistLeads';

    try{

      $response = $client->request('GET', $url);
      // \Drupal::logger('aryoleads')->info('Response data: @response', ['@response' => print_r($data, TRUE)]);

      return $response;
    
    } catch (RequestException $e) {
      \Drupal::logger('aryoleads')->error($e->getMessage());
      return new JsonResponse(['error' => 'Failed to fetch data from external API.'], 500);
    }
  
  }
}
