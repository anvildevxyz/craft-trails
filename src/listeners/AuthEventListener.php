<?php

namespace anvildev\trails\listeners;

use anvildev\trails\jobs\AnonymizeUserLogsJob;
use anvildev\trails\models\Settings;
use anvildev\trails\services\AuditService;
use Craft;
use craft\elements\User;
use craft\events\ElementEvent;
use craft\services\Elements;
use yii\base\Event;
use yii\web\UserEvent;

class AuthEventListener
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly Settings $settings,
    ) {
    }

    public function register(): void
    {
        if ($this->settings->logAuthentication) {
            Event::on(\craft\web\User::class, \craft\web\User::EVENT_AFTER_LOGIN, function(UserEvent $event) {
                /** @var User $user */
                $user = $event->identity;
                $this->audit->log('user.login', User::class, $user->id, [
                    'username' => $user->username,
                    'email' => $user->email,
                ]);
            });

            Event::on(\craft\web\User::class, \craft\web\User::EVENT_AFTER_LOGOUT, function(UserEvent $event) {
                /** @var User|null $user */
                $user = $event->identity;
                if ($user) {
                    $this->audit->log('user.logout', User::class, $user->id, [
                        'username' => $user->username,
                    ]);
                }
            });
        }

        if ($this->settings->logFailedLogins) {
            Event::on(\craft\controllers\UsersController::class, \craft\web\Controller::EVENT_AFTER_ACTION, function(\yii\base\ActionEvent $event) {
                if ($event->action->id !== 'login' || !Craft::$app->getSession()->hasFlash('error')) {
                    return;
                }
                $request = Craft::$app->getRequest();
                $loginName = $request->getBodyParam('loginName') ?? $request->getBodyParam('username');
                if ($loginName) {
                    $this->audit->log('user.login.failed', User::class, null, [
                        'loginName' => $loginName,
                        'reason' => 'authentication_failed',
                    ]);
                }
            });
        }

        // GDPR: Anonymize user data on deletion (always active).
        // Runs async — a user with many logs would otherwise hang the delete request.
        Event::on(Elements::class, Elements::EVENT_AFTER_DELETE_ELEMENT, function(ElementEvent $event) {
            $element = $event->element;
            if (!($element instanceof User)) {
                return;
            }

            Craft::$app->getQueue()->push(new AnonymizeUserLogsJob([
                'userId' => (int) $element->id,
            ]));

            $this->audit->log('user.gdpr.anonymized', User::class, $element->id, [
                'description' => 'User PII anonymization queued for audit logs due to account deletion',
            ]);
        });
    }
}
