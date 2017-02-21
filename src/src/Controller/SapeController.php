<?php

namespace Drupal\Sape\Controller;

use Drupal\Core\Controller\ControllerBase;

class SapeController extends ControllerBase {

    public function content() {
        return array(
            '#type' => 'markup',
            '#markup' => $this->t('Hello, World!'),
        );
    }

}