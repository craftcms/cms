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
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\web\JsonResponseFormatter;
use yii\web\Response as YiiResponse;
use yii\web\ResponseFormatterInterface;

/**
 * Control panel screen response formatter.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class CpScreenResponseFormatter extends Component implements ResponseFormatterInterface
{
    const FORMAT = 'cp-screen';

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function format($response)
    {
        /** @var CpScreenResponseBehavior $behavior */
        $behavior = $response->getBehavior(CpScreenResponseBehavior::NAME);

        if (!$behavior) {
            throw new InvalidConfigException('CpScreenResponseFormatter can only be used on responses with a CpScreenResponseBehavior.');
        }

        if (Craft::$app->getRequest()->getAcceptsJson()) {
            $this->_formatJson($response, $behavior);
        } else {
            $this->_formatTemplate($response, $behavior);
        }
    }

    private function _formatJson(YiiResponse $response, CpScreenResponseBehavior $behavior): void
    {
        $namespace = StringHelper::randomString(10);
        $view = Craft::$app->getView();
        if ($behavior->prepareScreen) {
            $view->setNamespace($namespace);
            call_user_func($behavior->prepareScreen, $response);
            $view->setNamespace(null);
        }
        $tabs = count($behavior->tabs) > 1 ? $view->namespaceInputs(fn() => $view->renderTemplate('_includes/tabs', [
            'tabs' => $behavior->tabs,
        ], View::TEMPLATE_MODE_CP), $namespace) : null;
        $content = $behavior->content ? $view->namespaceInputs($behavior->content, $namespace) : null;
        $sidebar = $behavior->sidebar ? $view->namespaceInputs($behavior->sidebar, $namespace) : null;

        $response->data = [
            'namespace' => $namespace,
            'title' => $behavior->title,
            'tabs' => $tabs,
            'action' => $behavior->action,
            'content' => $content,
            'sidebar' => $sidebar,
            'headHtml' => $view->getHeadHtml(),
            'bodyHtml' => $view->getBodyHtml(),
            'deltaNames' => $view->getDeltaNames(),
            'initialDeltaValues' => $view->getInitialDeltaValues(),
        ];

        (new JsonResponseFormatter())->format($response);
    }

    private function _formatTemplate(YiiResponse $response, CpScreenResponseBehavior $behavior): void
    {
        if ($behavior->prepareScreen) {
            call_user_func($behavior->prepareScreen, $response);
        }

        $content = is_callable($behavior->content) ? call_user_func($behavior->content) : ($behavior->content ?? '');

        if ($behavior->action) {
            $content .= Html::actionInput($behavior->action);
            if ($behavior->redirectUrl) {
                $content .= Html::redirectInput($behavior->redirectUrl);
            }
        }

        $response->attachBehavior(TemplateResponseBehavior::NAME, [
            'class' => TemplateResponseBehavior::class,
            'template' => '_layouts/cp',
            'variables' => [
                'docTitle' => $behavior->docTitle ?? strip_tags($behavior->title ?? ''),
                'title' => $behavior->title,
                'crumbs' => $behavior->crumbs,
                'tabs' => $behavior->tabs,
                'fullPageForm' => (bool)$behavior->action,
                'saveShortcutRedirect' => $behavior->saveShortcutRedirectUrl,
                'content' => $content,
                'details' => is_callable($behavior->sidebar) ? call_user_func($behavior->sidebar) : $behavior->sidebar,
            ],
            'templateMode' => View::TEMPLATE_MODE_CP,
        ]);

        (new TemplateResponseFormatter())->format($response);
    }
}
