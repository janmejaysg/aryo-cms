<?php

namespace Drupal\aryoleads\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use League\Csv\Reader;
use GuzzleHttp\Client;
use PhpOffice\PhpSpreadsheet\IOFactory;


class SelfLeadsUpdateForm extends FormBase {

    public function getFormId() {
        return 'self_lead_update_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state) {
        
        $form['file_upload'] = [
            '#type' => 'managed_file',
            '#title' => $this->t('Upload File'),
            '#upload_location' => 'public://lead_uploads',
            '#upload_validators' => [
                'file_validate_extensions' => ['csv xlsx'],
            ],
            '#required' => TRUE
        ];

        $form['update_field'] = [
            '#type' => 'select',
            '#title' => $this->t('Update Field'),
            '#options' => [
                'payoutAmount' => 'Payout Amount',
                'payoutState' => 'Payout State',
                'remarks' => 'Remarks',
                'status' => 'Status',
                'incStatus' => 'Incentive Status',
                'transactionId' => 'Transaction Id',
                'incTransactionId' => 'Incentive Transaction Id',
                'incPayoutAmount' => 'Incentive Payout Amount'
            ],
        ];

        $form['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Update Leads'),
        ];

        if ($form_state->has('invalid_leads_table')) {
            $form['invalid_leads_heading'] = [
                '#type' => 'markup',
                '#markup' => '<h4 class="text-danger mt-2 mb-1">' . $this->t('Leads with invalid data') . '</h4>',
            ];
            $form['invalid_leads_table'] = $form_state->get('invalid_leads_table');
        }

           $form['#attached']['library'][] = 'aryoleads/aryoleads_js_css';



        return $form;
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {

            $statusOptions = ['In process', 'Approved', 'Expired', 'Rejected'];

            // Get the file ID from the form state
            $fid = $form_state->getValue('file_upload')[0];
    
            // Load the file entity using the file ID
            $file = \Drupal\file\Entity\File::load($fid);
    
            if ($file) {
                $file->setPermanent();
    
                // Save the file entity
                $file->save();
    
                // Get the file URI (path) from the file entity
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
                // Handle XLSX files
                elseif ($file_extension === 'xlsx') {
                    $spreadsheet = IOFactory::load($file_real_path);
                    $worksheet = $spreadsheet->getActiveSheet();
                    $headers = $worksheet->toArray(null, true, true, true)[1]; // Assuming the first row is the header
                    foreach ($worksheet->toArray(null, true, true, true) as $index => $row) {
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
                
                $updatableField = $form_state->getValue('update_field');
    
                // Initialize an array to store the leads data
                $updatableData = [];
                $invalidLeadsStatus = [];
                $emptyTransactionId = [];
                $invalidPayoutAmount = [];
        
                // Loop through each record in the CSV or XLSX
                foreach ($records as $index => $record) {
                    if (($updatableField === 'status' && !in_array($record['status'], $statusOptions)) ||
                        ($updatableField === 'incStatus' && !in_array($record['incStatus'], $statusOptions))) {
                            if($file_extension === 'xlsx'){
                        // Store the invalid index and leadId
                            $emptyTransactionId[] = [
                                'index' => $index + 2,
                                'leadId' => $record['leadId'],
                            ];
                            }
                            else{
                            // Store the invalid index and leadId
                            $emptyTransactionId[] = [
                                'index' => $index + 1,
                                'leadId' => $record['leadId'],
                            ];
                            }
                    }
        
                    if (($updatableField === 'transactionId' && empty($record['transactionId'])) ||
                        ($updatableField === 'incTransactionId' && empty($record['incTransactionId']))) {
                        if($file_extension === 'xlsx'){
                    // Store the invalid index and leadId
                        $emptyTransactionId[] = [
                            'index' => $index + 2,
                            'leadId' => $record['leadId'],
                        ];
                        }
                        else{
                        // Store the invalid index and leadId
                        $emptyTransactionId[] = [
                            'index' => $index + 1,
                            'leadId' => $record['leadId'],
                        ];
                        }
                       
                    }
    
                    if(($updatableField === 'payoutAmount' && !is_numeric($record['payoutAmount'])) || ($updatableField === 'incPayoutAmount' && !is_numeric($record['incPayoutAmount']))){
                        if($file_extension === 'xlsx') {
                            $invalidPayoutAmount[] = [
                                'index' => $index + 2,
                                'leadId' => $record['leadId']
                            ];
                        }
                        else{
                            $invalidPayoutAmount[] = [
                                'index' => $index + 1,
                                'leadId' => $record['leadId']
                            ];
                        }
                      
                    }
                    if ($updatableField === 'payoutAmount'|| $updatableField === 'incPayoutAmount') {
                        $updatableData[] = [
                            'leadId' => $record['leadId'],
                            $updatableField => doubleval($record[$updatableField])
                        ];
                    } else {
                        $updatableData[] = [
                            'leadId' => $record['leadId'],
                            $updatableField => $record[$updatableField]
                        ];
                    }
                }
        
                if (!empty($invalidLeadsStatus)) {
                    $table_header = [
                        $this->t('Index'),
                        $this->t('LeadId'),
                    ];
                    $table_rows = [];
                    foreach ($invalidLeadsStatus as $value) {
                        $table_rows[] = [
                            'index' => $value['index'],
                            'leadId' => $value['leadId'],
                        ];
                    }
                    $form_state->set('invalid_leads_table', [
                        '#type' => 'table',
                        '#header' => $table_header,
                        '#rows' => $table_rows,
                        '#empty' => $this->t('No leads found.'),
                    ]);
                    $form_state->setRebuild(TRUE);
                    
                    return;
                }
        
                if (!empty($emptyTransactionId)) {
                    $table_header = [
                        $this->t('Index'),
                        $this->t('LeadId'),
                    ];
                    $table_rows = [];
                    foreach ($emptyTransactionId as $value) {
                        $table_rows[] = [
                            'index' => $value['index'],
                            'leadId' => $value['leadId'],
                        ];
                    }
                    $form_state->set('invalid_leads_table', [
                        '#type' => 'table',
                        '#header' => $table_header,
                        '#rows' => $table_rows,
                        '#empty' => $this->t('No leads found.'),
                    ]);
                    $form_state->setRebuild(TRUE);
                    
                   return;
                }
    
                if (!empty($invalidPayoutAmount)) {
                    $table_header = [
                        $this->t('Index'),
                        $this->t('LeadId'),
                    ];
                    $table_rows = [];
                    foreach ($invalidPayoutAmount as $value) {
                        $table_rows[] = [
                            'index' => $value['index'],
                            'leadId' => $value['leadId'],
                        ];
                    }
                    $form_state->set('invalid_leads_table', [
                        '#type' => 'table',
                        '#header' => $table_header,
                        '#rows' => $table_rows,
                        '#empty' => $this->t('No leads found.'),
                    ]);
                    $form_state->setRebuild(TRUE);
                    
                    return;
                }
        
                // Prepare the data to be sent in the API request
                $data = [
                    'updatableField' => $updatableField,
                    'updatableData' => $updatableData,
                ];
        
                // Create a new HTTP client
                $client = new Client();
                // Define the URL for the API endpoint
                $url = 'http://localhost:8081/updateLeads';
        
                try {
                    // Send a POST request to the API endpoint with the data
                    $response = $client->post($url, [
                        'json' => $data,
                    ]);
        
                    // Get the response body as a plain string
                    $response_body = (string) $response->getBody();
        
                    \Drupal::messenger()->addMessage($this->t('Server Response: @message', ['@message' => $response_body]));
                } catch (\Exception $e) {
                    \Drupal::messenger()->addError($this->t('Failed to update leads. Please try again.'));
                }
            }
    
            // Clear the invalid leads table state if everything is fine
            $form_state->set('invalid_leads_table', NULL);
      
    }

    
}
