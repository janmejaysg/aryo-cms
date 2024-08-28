<?php

namespace Drupal\huttiprojects\Controller;

use Drupal\Core\Controller\ControllerBase;

class HuttiNewProjectController extends ControllerBase {
  public function buildForm() {
    $form = [];
    $addOptions = ['Metatdata', 'Brand', 'Search', 'Categories'];
    $categories = [];

    foreach ($addOptions as $option) {
      if ($option == 'Metatdata') {
        $categories[] = [
          'category' => $this->t("<a href='add-metadata'>$option</a>")
        ];
      } else if($option == 'Brand') {
        $categories[] = [
          'category' => $this->t("<a href='add-brand'>$option</a>")
        ];
      }
      else if($option == 'Search') {
        $categories[] = [
          'category' => $this->t("<a href='add-search'>$option</a>")
        ];
      }
      else {
        $categories[] = [
          'category' => $this->t("<a href='add-category'>$option</a>")
        ];
      }
    }

    $header = ['Add New Category'];

    $form['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $categories,
    ];

    return $form;
  }
}




