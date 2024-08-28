<?php

namespace Drupal\aryoleads\Controller;
use Drupal\Core\Controller\ControllerBase;

class DistributorLeadsController extends ControllerBase {

  public function buildForm() {

    $form = [];
    // $currentIPService = \Drupal::service('cuurent_ip');
    // $currentIP = $currentIPService->getCurrentIP();

    // echo $currentIP;
    $downloadOptions = ["Download Leads", "Download Leads of Agent", "Download Lead By LeadId", "Leads Count"];
    $options = [];

    foreach ($downloadOptions as $option) {
      if($option == 'Download Leads') {
        $options[] = [
          'option' => $this->t("<a href='downlaod-distributor-leads'>$option</a>")
        ];
      }
      else if($option == 'Download Leads of Agent') {
        $options[] = [
          'option' => $this->t("<a href='download-team-leads-of-agent'>$option</a>")
        ];
      }
      else if($option == 'Download Lead By LeadId'){
        $options[] = [
          'option' => $this->t("<a href='download-distributor-lead-by-leadId'>$option</a>")
        ];
      }
      else{
        $options[] = [
          'option' => $this->t("<a href='distributor-leads-count'>$option</a>")
        ];
      }
    }

    $header = ['Distributor Leads'];

    $form['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $options,
    ];

    return $form;
  }


 }