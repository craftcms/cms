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
 * @since 3.0.0
 */
class SystemMessagesController extends Controller
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        // Make sure they have access to the System Messages utility
        $this->requirePermission('utility:system-messages');
    }

    /**
     * Returns the HTML for a system email message modal.
     *
     * @return Response
     */
    public function actionGetMessageModal(): Response
    {
        $this->requireAcceptsJson();

        $key = $this->request->getRequiredBodyParam('key');
        $language = $this->request->getBodyParam('language');

        if (!$language) {
            $language = Craft::$app->getSites()->getPrimarySite()->language;
        }

        $message = Craft::$app->getSystemMessages()->getMessage($key, $language);

        return $this->asJson([
            'body' => $this->getView()->renderTemplate('_components/utilities/SystemMessages/message-modal', [
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
        $message->key = $this->request->getRequiredBodyParam('key');
        $message->subject = $this->request->getRequiredBodyParam('subject');
        $message->body = $this->request->getRequiredBodyParam('body');

        if (Craft::$app->getIsMultiSite()) {
            $language = $this->request->getBodyParam('language');
        } else {
            $language = Craft::$app->getSites()->getPrimarySite()->language;
        }

        if (Craft::$app->getSystemMessages()->saveMessage($message, $language)) {
            return $this->asJson(['success' => true]);
        }

        return $this->asErrorJson(Craft::t('app', 'There was a problem saving your message.'));
    }
}
