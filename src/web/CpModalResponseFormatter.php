<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web;

use Craft;
use craft\helpers\Html;
use craft\helpers\StringHelper;
use craft\web\assets\htmx\HtmxAsset;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\JsonResponseFormatter;
use yii\web\Response as YiiResponse;
use yii\web\ResponseFormatterInterface;

/**
 * Control panel modal response formatter.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
class CpModalResponseFormatter extends Component implements ResponseFormatterInterface
{
    public const FORMAT = 'cp-modal';

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function format($response)
    {
        /** @var CpModalResponseBehavior|null $behavior */
        $behavior = $response->getBehavior(CpModalResponseBehavior::NAME);

        if (!$behavior) {
            throw new InvalidConfigException('CpModalResponseFormatter can only be used on responses with a CpModalResponseBehavior.');
        }

        $request = Craft::$app->getRequest();
        $view = Craft::$app->getView();
        $view->registerAssetBundle(HtmxAsset::class);

        $this->_formatJson($request, $response, $behavior);
    }

    private function _formatJson(\yii\web\Request $request, YiiResponse $response, CpModalResponseBehavior $behavior): void
    {
        $response->format = Response::FORMAT_JSON;

        $namespace = StringHelper::randomString(10);
        $view = Craft::$app->getView();

        if ($behavior->prepareModal) {
            $containerId = $request->getHeaders()->get('X-Craft-Container-Id');
            if (!$containerId) {
                throw new BadRequestHttpException('Request missing the X-Craft-Container-Id header.');
            }
            $view->setNamespace($namespace);
            call_user_func($behavior->prepareModal, $response, $containerId);
            $view->setNamespace(null);
        }

        $content = $view->namespaceInputs(function() use ($behavior) {
            $components = [];
            if ($behavior->contentHtml) {
                $components[] = is_callable($behavior->contentHtml) ? call_user_func($behavior->contentHtml) : $behavior->contentHtml;
            }
            if ($behavior->action) {
                $components[] = Html::actionInput($behavior->action, [
                    'class' => 'action-input',
                ]);
            }
            return implode("\n", $components);
        }, $namespace);

        $errorSummary = $behavior->errorSummary ? $view->namespaceInputs($behavior->errorSummary, $namespace) : null;

        $response->data = [
            'namespace' => $namespace,
            'formAttributes' => $behavior->formAttributes,
            'action' => $behavior->action,
            'submitButtonLabel' => $behavior->submitButtonLabel,
            'content' => $content,
            'errorSummary' => $errorSummary,
            'headHtml' => $view->getHeadHtml(),
            'bodyHtml' => $view->getBodyHtml(),
            'deltaNames' => $view->getDeltaNames(),
            'initialDeltaValues' => $view->getInitialDeltaValues(),
            'data' => $response->data,
        ];

        (new JsonResponseFormatter())->format($response);
    }
}
