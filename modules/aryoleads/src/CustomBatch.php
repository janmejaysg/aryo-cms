<?php

namespace Drupal\aryoleads;

use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Database\Database;

class CustomBatch {

  public static function batchOperation($value, &$context) {
    $fields = [
      "agentId",
      "agentName",
      "agentMobile",
      "leadId",
      "transactionId",
      "payoutAmount",
      "payoutState",
      "customerName",
      "customerMobile",
      "customerEmail",
      "subId",
      "leadCategory",
      "dateOfSubmission",
      "projectName",
      "status",
      "incStatus",
      "incPayoutAmount",
      "incPayoutState",
      "incTransactionId",
      "remarks",
      "source",
    ];

    // Initialize the context if not already set
    if (!isset($context['results']['data'])) {
      $context['results']['data'] = [];
    }
    if (!isset($context['results']['fields'])) {
      $context['results']['fields'] = $fields;
    }

    // Collect data
    $context['results']['data'][] = $value;
  }

  public static function batchFinished($success, $results, $operations) {
    if ($success) {
      // Create CSV data from all collected values
      $csv_data = self::convertToCsv($results['data'], $results['fields']);
      $timestamp = date('Y-m-d_H-i-s');
      $filename = "Aryo_Leads_{$timestamp}.csv";

      // Set headers for CSV download
      $headers = [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => 'attachment; filename="' . $filename . '"',
      ];

      // Create and send the response
      $response = new Response($csv_data, 200, $headers);
      \Drupal::service('page_cache_kill_switch')->trigger();
      $response->send();

      // Delete the table from the database
      self::deleteDatabaseTable();

      // Exit the process
      exit();
    } else {
      $message = t('The batch process encountered an error.');
      \Drupal::messenger()->addStatus($message);
    }
  }

  private static function convertToCsv($data, $fields) {
    if (empty($data)) {
      return '';
    }

    $output = fopen('php://temp', 'r+');
    fputcsv($output, $fields);

    foreach ($data as $row) {
      $csv_row = [];
      foreach ($fields as $field) {
        $csv_row[] = isset($row[$field]) ? $row[$field] : '';
      }
      fputcsv($output, $csv_row);
    }

    rewind($output);
    $csv = stream_get_contents($output);
    fclose($output);

    return $csv;
  }

  private static function deleteDatabaseTable() {
    // Get the database connection
    $conn = Database::getConnection('default', 'aryodb');

    // Drop the table
    try {
      $conn->schema()->dropTable('batch'); // Replace 'batch' with your table name
      \Drupal::logger('aryoleads')->info('Table "batch" has been deleted.');
    } catch (\Exception $e) {
      \Drupal::logger('aryoleads')->error('Failed to delete table "batch": ' . $e->getMessage());
    }
  }
}
