<?php

namespace anvildev\trails\listeners;

use anvildev\trails\models\Settings;
use anvildev\trails\services\AuditService;
use Craft;
use craft\elements\User;
use craft\events\UserGroupsAssignEvent;
use craft\services\ProjectConfig;
use craft\services\UserGroups;
use craft\services\UserPermissions;
use craft\services\Users;
use yii\base\Event;

class SystemEventListener
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly Settings $settings,
    ) {
    }

    public function register(): void
    {
        if ($this->settings->logPermissionChanges) {
            Event::on(Users::class, Users::EVENT_AFTER_ASSIGN_USER_TO_GROUPS, function(UserGroupsAssignEvent $event) {
                $user = Craft::$app->getUsers()->getUserById($event->userId);
                $groupNames = array_values(array_filter(array_map(
                    fn($id) => Craft::$app->getUserGroups()->getGroupById($id)?->name,
                    $event->groupIds
                )));
                $this->audit->log('user.groups.assigned', User::class, $event->userId, [
                    'username' => $user?->username,
                    'groupIds' => $event->groupIds,
                    'groupNames' => $groupNames,
                ]);
            });

            Event::on(UserPermissions::class, UserPermissions::EVENT_AFTER_SAVE_USER_PERMISSIONS, function(Event $event) {
                $this->audit->log('user.permissions.changed', User::class, $event->userId, [
                    'username' => Craft::$app->getUsers()->getUserById($event->userId)?->username,
                    'permissionCount' => count($event->permissions ?? []),
                ]);
            });

            Event::on(UserGroups::class, UserGroups::EVENT_AFTER_SAVE_USER_GROUP, function(Event $event) {
                $group = $event->userGroup;
                $this->audit->log('usergroup.updated', \craft\models\UserGroup::class, $group->id, [
                    'name' => $group->name,
                    'handle' => $group->handle,
                ]);
            });
        }

        if ($this->settings->logConfigChanges) {
            Event::on(ProjectConfig::class, ProjectConfig::EVENT_AFTER_APPLY_CHANGES, function() {
                $this->audit->log('config.changed', ProjectConfig::class, null, [
                    'description' => 'Project config changes applied',
                ]);
            });
        }
    }
}
