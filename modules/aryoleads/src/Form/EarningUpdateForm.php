<?php

namespace Drupal\aryoleads\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use League\Csv\Reader;
use GuzzleHttp\Client;
use PhpOffice\PhpSpreadsheet\IOFactory;

class EarningUpdateForm extends FormBase {


  public function getFormId(){
    return 'earning_update_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state){

    $form['heading'] = [
        '#type' => 'markup',
        '#markup' => '<h3>' . $this->t('Update Earnings') . '</h3>',
      ];

    $form ['file_upload'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Upload File'),
      '#upload_location' => 'public://leads_uploads',
      '#upload_validators' => [
          'file_validate_extensions' => ['csv xlsx']
      ],
      '#required' => TRUE,
      ];

      $form['type'] = [
        '#type' => 'select',
        '#title' => $this->t('Select Type'),
        '#options' => [
            'self' => 'Self',
            'team' => 'Team',
        ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update Earning')
    ];

    return $form;

  }

  

  public function submitForm(array &$form, FormStateInterface $form_state){
      // Get the file ID from the form state
      $fid = $form_state->getValue('file_upload')[0];

       // Load the file entity using the file ID
       $file = \Drupal\file\Entity\File::load($fid);

       if($file) {
        $file->setPermanent();

        $file_uri = $file->getFileUri();

        // Convert the file URI to a real file system path
        $file_real_path = \Drupal::service('file_system')->realpath($file_uri);
        // Get the file extension
        $file_extension = pathinfo($file_real_path, PATHINFO_EXTENSION);

        $records = [];
        if ($file_extension === 'csv') {
          // Use League CSV to read the CSV file from the real path
          $csv = Reader::createFromPath($file_real_path, 'r');
          // Set the CSV header offset (first row is header)
          $csv->setHeaderOffset(0);
          // Get the records from the CSV file
          $records = $csv->getRecords();
        }
        elseif ($file_extension === 'xlsx') {
          $spreadsheet = IOFactory::load($file_real_path);
          $worksheet = $spreadsheet->getSheet(0);
          $headers = $worksheet->toArray(null, true, true, true)[1]; // Assuming the first row is the header
          foreach ($worksheet->toArray(null, true, true, true) as $index => $row) {

            \Drupal::logger('aryoleads')->info('Row Data is @row', ['@row' => print_r($row, TRUE)]);

              if ($index === 1) continue; // Skip header row
      
              // Check if the row is empty
              $isEmptyRow = true;
              foreach ($row as $cell) {
                  if (!empty($cell)) {
                      $isEmptyRow = false;
                      break;
                  }
              }

            
      
              // End the loop if the row is empty
              if ($isEmptyRow) break;
      
              $record = [];
              foreach ($headers as $key => $header) {
                  $record[$header] = $row[$key];
              }
              $records[] = $record;
          }
        }

       
        $type = $form_state->getValue('type');

        $updatableData = [];

        foreach ($records as $index => $record) {

        
  
            $updatableData[] = [
                'agentId' => $record['agentId'],
                'transactionAmount' => doubleval($record['transactionAmount']),
                'transactionId' => $record['transactionId'],
                'timeOfTransaction' => $record['timeOfTransaction']
            ];

        }

        $data = [
        'type' => $type,
        'data' => $updatableData,
        ];

        // Create a new HTTP client
        $client = new Client();

        // Define the URL for the API endpoint
        $url = 'http://localhost:8081/updateLeads';

        try{
        // Send a POST request to the API endpoint with the data
        $response = $client->post($url, [
          'json' => $data,
        ]);

        // Get the response body as a plain string
        $response_body = (string) $response->getBody();

        \Drupal::messenger()->addMessage($this->t('Server Response: @message', ['@message' => $response_body]));

    }

    catch (\Exception $e) {
      \Drupal::messenger()->addError($this->t('Failed to update leads. Please try again.'));
    }
    }

  }
}
