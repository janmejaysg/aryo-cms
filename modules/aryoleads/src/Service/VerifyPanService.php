<?php

namespace Drupal\aryoleads\Service;

use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Access\AccessResult;

class VerifyPanService {
  protected $current_user;
  protected $logger;

  public function __construct(AccountProxy $currentUser) {
    $this->current_user = $currentUser;

  }

  public function getCurrentUserEmail() {
    $actualUser = $this->current_user->getEmail();
    \Drupal::logger('aryoleads')->info('Response data: @response', ['@response' => $actualUser ]);
    $allowedUser = 'kk@gmail.com';

    if ($actualUser === $allowedUser) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();
  }
}

