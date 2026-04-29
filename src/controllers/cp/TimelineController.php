<?php

namespace anvildev\trails\controllers\cp;

use anvildev\trails\Trails;
use Craft;
use yii\web\Response;

class TimelineController extends BaseCpController
{
    protected function requiredPermission(): string
    {
        return 'trails-viewLogs';
    }

    public function actionElement(string $elementType, int $elementId): Response
    {
        $decodedType = urldecode($elementType);

        $logs = Trails::getInstance()->audit->query()
            ->element($decodedType, $elementId)
            ->limit(100)
            ->all();

        $elementTitle = null;
        if (class_exists($decodedType)) {
            $element = Craft::$app->getElements()->getElementById($elementId, $decodedType);
            $elementTitle = $element?->title;
        }

        return $this->renderTemplate('trails/timeline/element', [
            'logs' => $logs,
            'elementType' => $decodedType,
            'elementId' => $elementId,
            'elementTitle' => $elementTitle,
        ]);
    }

    public function actionUser(int $userId): Response
    {
        $logs = Trails::getInstance()->audit->query()
            ->user($userId)
            ->limit(100)
            ->all();

        $user = Craft::$app->getUsers()->getUserById($userId);
        $userName = $user?->username;

        return $this->renderTemplate('trails/timeline/user', [
            'logs' => $logs,
            'userId' => $userId,
            'userName' => $userName,
        ]);
    }
}
