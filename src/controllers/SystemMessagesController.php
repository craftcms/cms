<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\models\SystemMessage;
use craft\web\Controller;
use yii\web\Response;

Craft::$app->requireEdition(Craft::Pro);

/**
 * The SystemMessagesController class is a controller that handles various email message tasks such as saving email
 * messages.
 * Note that all actions in the controller require an authenticated Craft session via [[allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class SystemMessagesController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        // All email message actions require an admin
        $this->requireAdmin();
    }

    /**
     * Returns the HTML for a system email message modal.
     *
     * @return Response
     */
    public function actionGetMessageModal(): Response
    {
        $this->requireAcceptsJson();

        $request = Craft::$app->getRequest();
        $key = $request->getRequiredBodyParam('key');
        $language = $request->getBodyParam('language');
        $message = Craft::$app->getSystemMessages()->getMessage($key, $language);

        return $this->asJson([
            'body' => $this->getView()->renderTemplate('settings/email/_message_modal', [
                'message' => $message,
                'language' => $language,
            ])
        ]);
    }

    /**
     * Saves a system email message.
     *
     * @return Response
     */
    public function actionSaveMessage(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $message = new SystemMessage();
        $message->key = Craft::$app->getRequest()->getRequiredBodyParam('key');
        $message->subject = Craft::$app->getRequest()->getRequiredBodyParam('subject');
        $message->body = Craft::$app->getRequest()->getRequiredBodyParam('body');

        if (Craft::$app->getIsMultiSite()) {
            $language = Craft::$app->getRequest()->getBodyParam('language');
        } else {
            $language = Craft::$app->getSites()->getPrimarySite()->language;
        }

        if (Craft::$app->getSystemMessages()->saveMessage($message, $language)) {
            return $this->asJson(['success' => true]);
        }

        return $this->asErrorJson(Craft::t('app', 'There was a problem saving your message.'));
    }
}
