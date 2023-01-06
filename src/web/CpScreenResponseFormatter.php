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
use craft\helpers\UrlHelper;
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
    public const FORMAT = 'cp-screen';

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function format($response)
    {
        /** @var CpScreenResponseBehavior|null $behavior */
        $behavior = $response->getBehavior(CpScreenResponseBehavior::NAME);

        if (!$behavior) {
            throw new InvalidConfigException('CpScreenResponseFormatter can only be used on responses with a CpScreenResponseBehavior.');
        }

        $request = Craft::$app->getRequest();

        if ($request->getAcceptsJson()) {
            $this->_formatJson($request, $response, $behavior);
        } else {
            $this->_formatTemplate($response, $behavior);
        }
    }

    private function _formatJson(\yii\web\Request $request, YiiResponse $response, CpScreenResponseBehavior $behavior): void
    {
        $response->format = Response::FORMAT_JSON;

        $namespace = StringHelper::randomString(10);
        $view = Craft::$app->getView();

        if ($behavior->prepareScreen) {
            $containerId = $request->getHeaders()->get('X-Craft-Container-Id');
            $view->setNamespace($namespace);
            call_user_func($behavior->prepareScreen, $response, $containerId);
            $view->setNamespace(null);
        }

        $notice = $behavior->notice ? $view->namespaceInputs($behavior->notice, $namespace) : null;

        $tabs = count($behavior->tabs) > 1 ? $view->namespaceInputs(fn() => $view->renderTemplate('_includes/tabs.twig', [
            'tabs' => $behavior->tabs,
        ], View::TEMPLATE_MODE_CP), $namespace) : null;

        $content = $view->namespaceInputs(function() use ($behavior) {
            $components = [];
            if ($behavior->content) {
                $components[] = is_callable($behavior->content) ? call_user_func($behavior->content) : $behavior->content;
            }
            if ($behavior->action) {
                $components[] = Html::actionInput($behavior->action, [
                    'class' => 'action-input',
                ]);
            }
            return implode("\n", $components);
        }, $namespace);

        $sidebar = $behavior->sidebar ? $view->namespaceInputs($behavior->sidebar, $namespace) : null;

        $response->data = [
            'editUrl' => $behavior->editUrl,
            'namespace' => $namespace,
            'title' => $behavior->title,
            'notice' => $notice,
            'tabs' => $tabs,
            'formAttributes' => $behavior->formAttributes,
            'action' => $behavior->action,
            'submitButtonLabel' => $behavior->submitButtonLabel,
            'content' => $content,
            'sidebar' => $sidebar,
            'headHtml' => $view->getHeadHtml(),
            'bodyHtml' => $view->getBodyHtml(),
            'deltaNames' => $view->getDeltaNames(),
            'initialDeltaValues' => $view->getInitialDeltaValues(),
            'data' => $response->data,
        ];

        (new JsonResponseFormatter())->format($response);
    }

    private function _formatTemplate(YiiResponse $response, CpScreenResponseBehavior $behavior): void
    {
        $response->format = Response::FORMAT_HTML;

        if ($behavior->prepareScreen) {
            call_user_func($behavior->prepareScreen, $response, 'main-form');
        }

        $crumbs = is_callable($behavior->crumbs) ? call_user_func($behavior->crumbs) : $behavior->crumbs;
        $contextMenu = is_callable($behavior->contextMenu) ? call_user_func($behavior->contextMenu) : $behavior->contextMenu;
        $addlButtons = is_callable($behavior->additionalButtons) ? call_user_func($behavior->additionalButtons) : $behavior->additionalButtons;
        $altActions = is_callable($behavior->altActions) ? call_user_func($behavior->altActions) : $behavior->altActions;
        $notice = is_callable($behavior->notice) ? call_user_func($behavior->notice) : $behavior->notice;
        $content = is_callable($behavior->content) ? call_user_func($behavior->content) : ($behavior->content ?? '');
        $sidebar = is_callable($behavior->sidebar) ? call_user_func($behavior->sidebar) : $behavior->sidebar;

        if ($behavior->action) {
            $content .= Html::actionInput($behavior->action, [
                'class' => 'action-input',
            ]);
            if ($behavior->redirectUrl) {
                $content .= Html::redirectInput($behavior->redirectUrl);
            }
        }

        $security = Craft::$app->getSecurity();
        $response->attachBehavior(TemplateResponseBehavior::NAME, [
            'class' => TemplateResponseBehavior::class,
            'template' => '_layouts/cp',
            'variables' => [
                'docTitle' => $behavior->docTitle ?? strip_tags($behavior->title ?? ''),
                'title' => $behavior->title,
                'selectedSubnavItem' => $behavior->selectedSubnavItem,
                'crumbs' => array_map(function(array $crumb): array {
                    $crumb['url'] = UrlHelper::cpUrl($crumb['url'] ?? '');
                    return $crumb;
                }, $crumbs ?? []),
                'contextMenu' => $contextMenu,
                'submitButtonLabel' => $behavior->submitButtonLabel,
                'additionalButtons' => $addlButtons,
                'tabs' => $behavior->tabs,
                'fullPageForm' => (bool)$behavior->action,
                'mainAttributes' => $behavior->mainAttributes,
                'mainFormAttributes' => $behavior->formAttributes,
                'formActions' => array_map(function(array $action) use ($security): array {
                    if (isset($action['redirect'])) {
                        $action['redirect'] = $security->hashData($action['redirect']);
                    }
                    return $action;
                }, $altActions ?? []),
                'saveShortcutRedirect' => $behavior->saveShortcutRedirectUrl,
                'contentNotice' => $notice,
                'content' => $content,
                'details' => $sidebar,
            ],
            'templateMode' => View::TEMPLATE_MODE_CP,
        ]);

        (new TemplateResponseFormatter())->format($response);
    }
}
