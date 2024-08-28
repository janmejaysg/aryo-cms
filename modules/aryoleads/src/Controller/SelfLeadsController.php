<?php

namespace Drupal\aryoleads\Controller;
use Drupal\Core\Controller\ControllerBase;

class SelfLeadsController extends ControllerBase {

  public function buildForm() {
    $form = [];
    $downloadOptions = ["Download Leads", "Download Leads of User", "Download Lead By LeadId", "Leads Count"];
    $options = [];

    foreach ($downloadOptions as $option) {
      if($option == 'Download Leads') {
        $options[] = [
          'option' => $this->t("<a href='downlaod-self-leads'>$option</a>")
        ];
      }
      else if($option == 'Download Leads of User') {
        $options[] = [
          'option' => $this->t("<a href='download-self-leads-of-user'>$option</a>")
        ];
      }
      else if($option == 'Download Lead By LeadId'){
        $options[] = [
          'option' => $this->t("<a href='download-self-lead-by-leadId'>$option</a>")
        ];
      }
      else{
        $options[] = [
          'option' => $this->t("<a href='self-leads-count'>$option</a>")
        ];
      }
    }

    $header = ['Self Leads'];

    $form['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $options,
    ];

    return $form;
  }


 }