<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web;

use Craft;
use craft\helpers\Cp;
use craft\helpers\Html;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
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
            if (!$containerId) {
                throw new BadRequestHttpException('Request missing the X-Craft-Container-Id header.');
            }
            $view->setNamespace($namespace);
            call_user_func($behavior->prepareScreen, $response, $containerId);
            $view->setNamespace(null);
        }

        $extraToolbarItems = is_callable($behavior->toolbarHtml) ? call_user_func($behavior->toolbarHtml) : $behavior->toolbarHtml;
        $notice = $behavior->noticeHtml ? $view->namespaceInputs($behavior->noticeHtml, $namespace) : null;

        $tabs = count($behavior->tabs) > 1 ? $view->namespaceInputs(fn() => $view->renderTemplate('_includes/tabs.twig', [
            'tabs' => $behavior->tabs,
        ], View::TEMPLATE_MODE_CP), $namespace) : null;

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

        $sidebar = $behavior->metaSidebarHtml ? $view->namespaceInputs($behavior->metaSidebarHtml, $namespace) : null;
        $errorSummary = $behavior->errorSummary ? $view->namespaceInputs($behavior->errorSummary, $namespace) : null;

        $response->data = [
            'editUrl' => $behavior->editUrl ? UrlHelper::cpUrl($behavior->editUrl) : null,
            'namespace' => $namespace,
            'title' => $behavior->title,
            'notice' => $notice,
            'tabs' => $tabs,
            'bodyClass' => $behavior->slideoutBodyClass,
            'formAttributes' => $behavior->formAttributes,
            'action' => $behavior->action,
            'extraToolbarItems' => $extraToolbarItems,
            'submitButtonLabel' => $behavior->submitButtonLabel,
            'actionMenu' => $this->_actionMenu($behavior, false, [
                'withButton' => false,
            ], $namespace),
            'content' => $content,
            'sidebar' => $sidebar,
            'errorSummary' => $errorSummary,
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
        $isForm = (bool)$behavior->action;

        if ($behavior->prepareScreen) {
            call_user_func($behavior->prepareScreen, $response, $isForm ? 'main-form' : 'main');
        }

        $docTitle = $behavior->docTitle ?? strip_tags($behavior->title ?? '');
        $crumbs = (is_callable($behavior->crumbs) ? call_user_func($behavior->crumbs) : $behavior->crumbs) ?? [];
        $toolbar = is_callable($behavior->toolbarHtml) ? call_user_func($behavior->toolbarHtml) : $behavior->toolbarHtml;
        $addlButtons = is_callable($behavior->additionalButtonsHtml) ? call_user_func($behavior->additionalButtonsHtml) : $behavior->additionalButtonsHtml;
        $altActions = is_callable($behavior->altActions) ? call_user_func($behavior->altActions) : $behavior->altActions;
        $notice = is_callable($behavior->noticeHtml) ? call_user_func($behavior->noticeHtml) : $behavior->noticeHtml;
        $content = is_callable($behavior->contentHtml) ? call_user_func($behavior->contentHtml) : ($behavior->contentHtml ?? '');
        $sidebar = is_callable($behavior->metaSidebarHtml) ? call_user_func($behavior->metaSidebarHtml) : $behavior->metaSidebarHtml;
        $pageSidebar = is_callable($behavior->pageSidebarHtml) ? call_user_func($behavior->pageSidebarHtml) : $behavior->pageSidebarHtml;
        $errorSummary = is_callable($behavior->errorSummary) ? call_user_func($behavior->errorSummary) : $behavior->errorSummary;

        if (Craft::$app->getIsMultiSite() && isset($behavior->site)) {
            $siteMenuItems = !empty($behavior->selectableSites)
                ? Cp::siteMenuItems($behavior->selectableSites, $behavior->site, [
                    'includeOmittedSites' => true,
                ])
                : [];

            array_unshift($crumbs, [
                'id' => 'site-crumb',
                'icon' => Cp::earthIcon(),
                'label' => Craft::t('site', $behavior->site->name),
                'menu' => [
                    'label' => Craft::t('app', 'Select site'),
                    'items' => $siteMenuItems,
                ],
            ]);
        }

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
                'docTitle' => $docTitle,
                'title' => $behavior->title,
                'selectedSubnavItem' => $behavior->selectedSubnavItem,
                'crumbs' => array_map(function(array $crumb): array {
                    if (isset($crumb['url'])) {
                        $crumb['url'] = UrlHelper::cpUrl($crumb['url']);
                    }
                    return $crumb;
                }, $crumbs ?? []),
                'contextMenu' => $this->_contextMenu($behavior),
                'toolbar' => $toolbar,
                'actionMenu' => $this->_actionMenu($behavior, config: [
                    'hiddenLabel' => Craft::t('app', 'Actions'),
                    'buttonAttributes' => [
                        'id' => 'action-btn',
                        'class' => ['action-btn', 'hairline-dark', 'm'],
                        'title' => Craft::t('app', 'Actions'),
                    ],
                ]),
                'submitButtonLabel' => $behavior->submitButtonLabel,
                'additionalButtons' => $addlButtons,
                'tabs' => $behavior->tabs,
                'fullPageForm' => $isForm,
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
                'sidebar' => $pageSidebar,
                'errorSummary' => $errorSummary,
            ],
            'templateMode' => View::TEMPLATE_MODE_CP,
        ]);

        (new TemplateResponseFormatter())->format($response);
    }

    private function _contextMenu(
        CpScreenResponseBehavior $behavior,
        ?string $namespace = null,
    ): ?string {
        return $this->_menu($behavior->contextMenuItems, [
            'id' => 'context-menu',
            'class' => 'padded',
            'autoLabel' => true,
            'hiddenLabel' => Craft::t('app', 'Select context'),
        ], $namespace);
    }

    private function _actionMenu(
        CpScreenResponseBehavior $behavior,
        bool $withDestructive = true,
        array $config = [],
        ?string $namespace = null,
    ): ?string {
        if ($behavior->actionMenuItems === null) {
            return null;
        }

        if ($withDestructive) {
            $itemsFactory = $behavior->actionMenuItems;
        } else {
            $itemsFactory = fn() => array_filter(
                call_user_func($behavior->actionMenuItems),
                fn(array $item) => !($item['destructive'] ?? false),
            );
        }

        return $this->_menu($itemsFactory, $config + [
            'id' => 'action-menu',
        ], $namespace);
    }

    private function _menu(?callable $itemsFactory, array $config, ?string $namespace): ?string
    {
        if ($itemsFactory === null) {
            return null;
        }

        $render = function() use ($itemsFactory, $config): ?string {
            $items = Cp::normalizeMenuItems($itemsFactory() ?? []);

            if (empty($items)) {
                return null;
            }

            return Cp::disclosureMenu($items, $config);
        };

        if ($namespace) {
            return Craft::$app->getView()->namespaceInputs($render, $namespace);
        }

        return $render();
    }
}
