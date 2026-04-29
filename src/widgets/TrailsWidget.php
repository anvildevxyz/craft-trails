<?php

namespace anvildev\trails\widgets;

use anvildev\trails\Trails;
use Craft;
use craft\base\Widget;

class TrailsWidget extends Widget
{
    public int $days = 7;

    public static function displayName(): string
    {
        return Craft::t('trails', 'Audit Activity');
    }

    public static function icon(): ?string
    {
        return 'shield-check';
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['days'], 'in', 'range' => [7, 14, 30]];
        return $rules;
    }

    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('trails/_widgets/trails-activity-settings', [
            'widget' => $this,
        ]);
    }

    public function getBodyHtml(): ?string
    {
        if (!Craft::$app->getUser()->checkPermission('trails-viewLogs')) {
            return '<p style="padding: 1rem; color: #999;">' . Craft::t('trails', 'Insufficient permissions to view audit data.') . '</p>';
        }

        Craft::$app->getView()->registerAssetBundle(\anvildev\trails\web\assets\cp\CpAsset::class);

        $audit = Trails::getInstance()->audit;

        $recentLogs = $audit->getLogs(['limit' => 5]);

        return Craft::$app->getView()->renderTemplate('trails/_widgets/trails-activity', [
            'summary' => $audit->getActivitySummary($this->days),
            'dailyActivity' => $audit->getDailyActivity($this->days),
            'days' => $this->days,
            'recentLogs' => $recentLogs,
        ]);
    }
}
