<?php

/**
 * Trails plugin for Craft CMS 5.x
 *
 * Enterprise-grade audit trail and compliance logging
 *
 * @link      https://anvildev.xyz
 * @copyright Copyright (c) 2026 anvildev
 */

namespace anvildev\trails;

use anvildev\trails\listeners\AuthEventListener;
use anvildev\trails\listeners\BookedEventListener;
use anvildev\trails\listeners\CommerceEventListener;
use anvildev\trails\listeners\ElementEventListener;
use anvildev\trails\listeners\SystemEventListener;
use anvildev\trails\models\Settings;
use anvildev\trails\services\AuditService;
use anvildev\trails\services\EventBridgeService;
use anvildev\trails\services\ExportService;
use anvildev\trails\services\RetentionService;
use anvildev\trails\variables\TrailsVariable;
use Craft;
use craft\base\Plugin;
use craft\events\RegisterTemplateRootsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\services\UserPermissions;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use craft\web\View;
use yii\base\Event;

/**
 * @method static Trails getInstance()
 * @method Settings getSettings()
 * @property-read AuditService $audit
 * @property-read EventBridgeService $eventBridge
 * @property-read ExportService $export
 * @property-read RetentionService $retention
 * @property-read \anvildev\trails\services\TableRotationService $tableRotation
 * @property-read \anvildev\trails\services\MerkleService $merkle
 * @property-read \anvildev\trails\services\AnchorService $anchor
 * @property-read \anvildev\trails\services\CertificateService $certificate
 * @property-read \anvildev\trails\services\RealtimeService $realtime
 * @property-read \anvildev\trails\services\ApiAuthService $apiAuth
 */
class Trails extends Plugin
{
    public string $schemaVersion = '1.0.0';
    public bool $hasCpSection = true;
    public bool $hasCpSettings = false;

    public static function config(): array
    {
        return [
            'components' => [
                'audit' => AuditService::class,
                'eventBridge' => EventBridgeService::class,
                'export' => ExportService::class,
                'retention' => RetentionService::class,
                'tableRotation' => \anvildev\trails\services\TableRotationService::class,
                'merkle' => \anvildev\trails\services\MerkleService::class,
                'anchor' => \anvildev\trails\services\AnchorService::class,
                'certificate' => \anvildev\trails\services\CertificateService::class,
                'realtime' => \anvildev\trails\services\RealtimeService::class,
                'apiAuth' => \anvildev\trails\services\ApiAuthService::class,
            ],
        ];
    }

    public function init(): void
    {
        parent::init();

        Craft::setAlias('@trails', $this->getBasePath());

        $this->registerEventListeners();
        $this->registerBuiltInBridges();
        $this->eventBridge->bindAll();
        $this->registerMcpTools();

        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules += [
                'trails' => 'trails/cp/logs/index',
                'trails/logs' => 'trails/cp/logs/index',
                'trails/logs/<logId:\d+>' => 'trails/cp/logs/view',
                'trails/export' => 'trails/cp/export/index',
                'trails/export/download' => 'trails/cp/export/download',
                'trails/export/status' => 'trails/cp/export/status',
                'trails/export/file' => 'trails/cp/export/file',
                'trails/settings' => 'trails/cp/settings/general',
                'trails/settings/general' => 'trails/cp/settings/general',
                'trails/settings/security' => 'trails/cp/settings/security',
                'trails/settings/integrations' => 'trails/cp/settings/integrations',
                'trails/settings/logging' => 'trails/cp/settings/logging',
                'trails/settings/capture' => 'trails/cp/settings/capture',
                'trails/settings/alerts' => 'trails/cp/settings/alerts',
                'trails/settings/external' => 'trails/cp/settings/external',
                'trails/settings/save' => 'trails/cp/settings/save',
                'trails/settings/cleanup' => 'trails/cp/settings/cleanup',
                'trails/integrity' => 'trails/cp/integrity/index',
                'trails/integrity/verify' => 'trails/cp/integrity/verify',
                'trails/integrity/certificate' => 'trails/cp/integrity/certificate',
                'trails/stream/table' => 'trails/cp/stream/table',
                'trails/stream/sse' => 'trails/cp/stream/sse',
                'trails/timeline/element/<elementType:.+>/<elementId:\d+>' => 'trails/cp/timeline/element',
                'trails/timeline/user/<userId:\d+>' => 'trails/cp/timeline/user',
            ];
        });

        Event::on(View::class, View::EVENT_REGISTER_CP_TEMPLATE_ROOTS, function(RegisterTemplateRootsEvent $event) {
            $event->roots['trails'] = $this->getBasePath() . DIRECTORY_SEPARATOR . 'templates';
        });

        Event::on(UserPermissions::class, UserPermissions::EVENT_REGISTER_PERMISSIONS, function(RegisterUserPermissionsEvent $event) {
            $event->permissions[] = [
                'heading' => Craft::t('trails', 'Trails'),
                'permissions' => [
                    'trails-viewLogs' => ['label' => Craft::t('trails', 'View audit logs')],
                    'trails-exportLogs' => ['label' => Craft::t('trails', 'Export audit logs')],
                    'trails-manageSettings' => ['label' => Craft::t('trails', 'Manage settings')],
                ],
            ];
        });

        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function(Event $event) {
            $event->sender->set('trails', TrailsVariable::class);
        });

        Event::on(\craft\services\Dashboard::class, \craft\services\Dashboard::EVENT_REGISTER_WIDGET_TYPES, function(\craft\events\RegisterComponentTypesEvent $event) {
            $event->types[] = \anvildev\trails\widgets\TrailsWidget::class;
        });

        // GraphQL
        if ($this->getSettings()->enableGraphql) {
            Event::on(
                \craft\services\Gql::class,
                \craft\services\Gql::EVENT_REGISTER_GQL_TYPES,
                function(\craft\events\RegisterGqlTypesEvent $event) {
                    $event->types[] = \anvildev\trails\gql\types\TrailsLogType::class;
                    $event->types[] = \anvildev\trails\gql\types\TrailsSummaryType::class;
                    $event->types[] = \anvildev\trails\gql\types\TrailsIntegrityType::class;
                    $event->types[] = \anvildev\trails\gql\types\TrailsMerkleProofType::class;
                }
            );

            Event::on(
                \craft\services\Gql::class,
                \craft\services\Gql::EVENT_REGISTER_GQL_QUERIES,
                function(\craft\events\RegisterGqlQueriesEvent $event) {
                    $event->queries = array_merge(
                        $event->queries,
                        \anvildev\trails\gql\queries\TrailsQuery::getQueries()
                    );
                }
            );

            Event::on(
                \craft\services\Gql::class,
                \craft\services\Gql::EVENT_REGISTER_GQL_SCHEMA_COMPONENTS,
                function(\craft\events\RegisterGqlSchemaComponentsEvent $event) {
                    $event->queries['Trails'] = [
                        'trails.logs:read' => ['label' => Craft::t('trails', 'View audit logs via GraphQL')],
                    ];
                }
            );
        }

        Event::on(\craft\services\Gc::class, \craft\services\Gc::EVENT_RUN, function() {
            $settings = $this->getSettings();
            if ($settings->enabled && $settings->retentionDays > 0) {
                $this->retention->cleanupOldLogs();
            }
            if ($settings->externalLoggingEnabled && $settings->externalEndpoint) {
                $this->audit->flushShippingBuffer();
            }
            // Clean up expired exports
            $this->export->cleanupExpiredExports();
            // Monthly table rotation
            $currentMonth = date('Y-m');
            $cache = Craft::$app->getCache();
            $lastRotation = $cache->get('trails:lastRotation');
            if ($lastRotation !== $currentMonth) {
                $cache->set('trails:lastRotation', $currentMonth, 86400 * 35);
                Craft::$app->getQueue()->push(new \anvildev\trails\jobs\RotateLogTableJob());
            }
        });

        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            $this->controllerNamespace = 'anvildev\\trails\\console\\controllers';
        }

        Craft::info('Trails plugin loaded', __METHOD__);
    }

    private function registerBuiltInBridges(): void
    {
        CommerceEventListener::register($this->eventBridge);
        BookedEventListener::register($this->eventBridge);
    }

    /**
     * Register Trails' read-only tools with the craft-mcp plugin, when it is
     * installed. Soft dependency (class_exists-guarded), so Trails runs unchanged
     * when craft-mcp is absent.
     */
    private function registerMcpTools(): void
    {
        if (!class_exists(\stimmt\craft\Mcp\Mcp::class)) {
            return;
        }

        Event::on(
            \stimmt\craft\Mcp\Mcp::class,
            \stimmt\craft\Mcp\Mcp::EVENT_REGISTER_TOOLS,
            static function(\stimmt\craft\Mcp\events\RegisterToolsEvent $event): void {
                $event->addTool(\anvildev\trails\mcp\AuditLogTools::class, 'trails');
                $event->addTool(\anvildev\trails\mcp\ActivityTools::class, 'trails');
                $event->addTool(\anvildev\trails\mcp\IntegrityTools::class, 'trails');
            }
        );
    }

    private function registerEventListeners(): void
    {
        $settings = $this->getSettings();
        if (!$settings->enabled) {
            return;
        }

        (new ElementEventListener($this->audit, $settings))->register();
        (new AuthEventListener($this->audit, $settings))->register();
        (new SystemEventListener($this->audit, $settings))->register();
    }

    public function getCpNavItem(): ?array
    {
        $item = parent::getCpNavItem();
        $item['label'] = Craft::t('trails', 'Trails');
        $user = Craft::$app->getUser()->getIdentity();
        $isAdmin = $user && $user->admin;
        $canView = $isAdmin || ($user && $user->can('trails-viewLogs'));
        $canExport = $isAdmin || ($user && $user->can('trails-exportLogs'));
        $canManage = $isAdmin || ($user && $user->can('trails-manageSettings'));

        $item['subnav'] = [];
        if ($canView) {
            $item['subnav']['logs'] = ['label' => Craft::t('trails', 'Activity Logs'), 'url' => 'trails/logs'];
        }
        if ($canExport) {
            $item['subnav']['export'] = ['label' => Craft::t('trails', 'Export'), 'url' => 'trails/export'];
        }
        if ($canView) {
            $item['subnav']['integrity'] = ['label' => Craft::t('trails', 'Integrity'), 'url' => 'trails/integrity'];
        }
        if ($canManage) {
            $item['subnav']['settings'] = ['label' => Craft::t('trails', 'Settings'), 'url' => 'trails/settings'];
        }
        return $item;
    }

    protected function createSettingsModel(): ?Settings
    {
        return new Settings();
    }
}
