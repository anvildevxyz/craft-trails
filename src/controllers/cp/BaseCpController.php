<?php

declare(strict_types=1);

namespace anvildev\trails\controllers\cp;

use craft\web\Controller;

abstract class BaseCpController extends Controller
{
    abstract protected function requiredPermission(): string;

    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        $this->requireCpRequest();
        $this->requirePermission($this->requiredPermission());

        return true;
    }
}
