<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\debug;

use Craft;
use yii\debug\Panel;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;

/**
 * Debugger panel that collects and displays deprecation warnings.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class DeprecatedPanel extends Panel
{
    /** @inheritdoc */
    public function getName()
    {
        return 'Deprecated';
    }

    /** @inheritdoc */
    public function getSummary()
    {
        return Craft::$app->getView()->render('@app/views/debug/deprecated/summary', [
            'panel' => $this
        ]);
    }

    /**
     * @inheritdoc
     * @throws NotFoundHttpException if a `trace` parameter is in the query string, but its value isn’t a valid deprecation warning’s ID
     */
    public function getDetail()
    {
        $request = Craft::$app->getRequest();

        if ($request->getQueryParam('clear')) {
            Craft::$app->getDeprecator()->deleteAllLogs();
        }

        $logId = $request->getQueryParam('trace');

        if ($logId) {
            $log = Craft::$app->getDeprecator()->getLogById($logId);

            if ($log === null) {
                throw new NotFoundHttpException('The requested deprecation warning could not be found.');
            }

            return Craft::$app->getView()->render('@app/views/debug/deprecated/traces', [
                'panel' => $this,
                'log' => $log
            ]);
        }

        return Craft::$app->getView()->render('@app/views/debug/deprecated/detail', [
            'panel' => $this
        ]);
    }

    /**
     *
     */
    public function save()
    {
        return ArrayHelper::toArray(Craft::$app->getDeprecator()->getRequestLogs());
    }
}
