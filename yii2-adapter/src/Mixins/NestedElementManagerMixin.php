<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Mixins;

use Closure;
use CraftCms\Cms\Cp\Html\ElementHtml;
use CraftCms\Cms\Cp\Html\ElementIndexHtml;
use CraftCms\Cms\Cp\Icons;
use CraftCms\Cms\Element\Actions\ChangeSortOrder;
use CraftCms\Cms\Element\Actions\MoveDown;
use CraftCms\Cms\Element\Actions\MoveUp;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\NestedElementManager;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Str;

use function CraftCms\Cms\t;

/**
 * Legacy renderers share the manager's private payload builders without exposing
 * them as compatibility methods. Their scope stays on the core class even when
 * a plugin calls the macros on a manager subclass.
 */
class NestedElementManagerMixin
{
    public function getCardsHtml(): Closure
    {
        $createView = $this->createView();

        $render = Closure::bind(static function(NestedElementManager $manager, ?ElementInterface $owner, array $config = []) use ($createView): string {
            return $createView(
                $manager,
                $owner,
                $config,
                'cards',
                function(string $id, array $config, string $attribute, array &$settings) use ($owner, $manager): string {
                    $settings += $manager->cardsSettings($config);

                    $html = Html::beginTag('div', options: [
                        'id' => $id,
                        'class' => 'nested-element-cards grid gap-2',
                    ]);

                    $elements = $manager->cardElements($owner);

                    if (!empty($elements)) {
                        $html .= Html::ul()->items(...array_map(
                            fn(ElementInterface $element) => Html::li(app(ElementHtml::class)->elementCardHtml(
                                $element,
                                $manager->cardConfig($config, $element),
                            ))->encode(false),
                            $elements,
                        ))->class(
                            'elements',
                            $config['showInGrid'] ? 'card-grid' : 'cards',
                            $config['prevalidate'] ? 'prevalidate' : ''
                        )->render();
                    }

                    $html .= Html::tag('craft-empty', t('Nothing yet.'), [
                        'class' => array_keys(array_filter([
                            'hidden' => !empty($elements),
                        ])),
                    ]);

                    return $html . Html::endTag('div');
                },
            );
        }, null, NestedElementManager::class);

        return function(?ElementInterface $owner, array $config = []) use ($render): string {
            /**
             * @var NestedElementManager $this
             *
             * @phpstan-ignore varTag.nativeType
             */
            return $render($this, $owner, $config);
        };
    }

    public function getIndexHtml(): Closure
    {
        $createView = $this->createView();
        $indexSettings = $this->indexSettings();

        $render = Closure::bind(static function(NestedElementManager $manager, ?ElementInterface $owner, array $config = []) use ($createView, $indexSettings): string {
            $config = $manager->getIndexConfig($owner, $config);

            return $createView(
                $manager,
                $owner,
                $config,
                'index',
                function(string $id, array $config, string $attribute, array &$settings) use ($owner, $indexSettings, $manager): string {
                    $settings['indexSettings'] = $indexSettings($manager, $owner, $config, $attribute);

                    return app(ElementIndexHtml::class)->html($manager->elementType, [
                        'class' => [$config['prevalidate'] ? 'prevalidate' : ''],
                        'context' => 'embedded-index',
                        'defaultSort' => $config['defaultSort'],
                        'defaultTableColumns' => $config['defaultTableColumns'],
                        'defaultViewMode' => $config['defaultViewMode'],
                        'fieldLayouts' => $config['fieldLayouts'],
                        'id' => $id,
                        'prevalidate' => $config['prevalidate'] ?? false,
                        'registerJs' => false,
                        'showSiteMenu' => false,
                        'sources' => false,
                    ]);
                },
            );
        }, null, NestedElementManager::class);

        return function(?ElementInterface $owner, array $config = []) use ($render): string {
            /**
             * @var NestedElementManager $this
             *
             * @phpstan-ignore varTag.nativeType
             */
            return $render($this, $owner, $config);
        };
    }

    /**
     * Builds the `indexSettings` portion of the view settings: the owner
     * criteria, view-mode/pagination options, and (when sortable) the
     * reorder action configs.
     */
    private function indexSettings(): Closure
    {
        return Closure::bind(static function(NestedElementManager $manager, ElementInterface $owner, array $config, string $attribute): array {
            $criteria = [
                $manager->ownerIdParam => $owner->id,
            ];

            if ($owner->getIsRevision()) {
                $criteria['revisions'] = null;
                $criteria['trashed'] = null;
                $criteria['drafts'] = false;
            }

            $indexSettings = [
                'namespace' => InputNamespace::get(),
                'allowedViewModes' => $config['allowedViewModes']
                    ? array_map(fn($mode) => Str::toString($mode), $config['allowedViewModes'])
                    : null,
                'showHeaderColumn' => $config['showHeaderColumn'],
                'criteria' => array_merge($criteria, $manager->criteria),
                'batchSize' => $config['pageSize'],
                'actions' => [],
                'canHaveDrafts' => $config['canHaveDrafts'] ?? $manager->elementType::hasDrafts(),
                'storageKey' => $config['storageKey'],
                'static' => $config['static'],
            ];

            if (!$config['static'] && ($config['sortable'] ?? false)) {
                $manager->authorizeNestedElementReordering($owner, $attribute);

                foreach ([
                    new ChangeSortOrder($owner, $attribute),
                    new MoveUp($owner, $attribute),
                    new MoveDown($owner, $attribute),
                ] as $action) {
                    HtmlStack::startJsBuffer();
                    $actionConfig = ElementHelper::actionConfig($action);
                    $actionConfig['bodyHtml'] = HtmlStack::clearJsBuffer();
                    $indexSettings['actions'][] = $actionConfig;
                }
            }

            return $indexSettings;
        }, null, NestedElementManager::class);
    }

    /**
     * Adapts manager settings for `<craft-nested-element-manager>`, which
     * expects a lone create option as its attributes and icons as SVG markup.
     */
    public function htmlManagerSettings(): Closure
    {
        return function(array $settings): array {
            if (empty($settings['createAttributes']) || !array_is_list($settings['createAttributes'])) {
                return $settings;
            }

            if (count($settings['createAttributes']) === 1) {
                $settings['createAttributes'] = array_first($settings['createAttributes'])['attributes'];

                return $settings;
            }

            $settings['createAttributes'] = array_map(function(array $attributes): array {
                if (isset($attributes['icon'])) {
                    $attributes['icon'] = Icons::svg($attributes['icon']);
                }

                return $attributes;
            }, $settings['createAttributes']);

            return $settings;
        };
    }

    private function createView(): Closure
    {
        $htmlManagerSettings = $this->htmlManagerSettings();

        return Closure::bind(static function(NestedElementManager $manager, ?ElementInterface $owner, array $config, string $mode, Closure $renderHtml) use ($htmlManagerSettings): string {
            if (!$owner?->id) {
                $message = t('{nestedType} can only be created after the {ownerType} has been saved.', [
                    'nestedType' => $manager->elementType::pluralDisplayName(),
                    'ownerType' => $owner ? $owner::lowerDisplayName() : t('element'),
                ]);

                return Html::tag('div', $message, ['class' => 'pane no-border zilch small']);
            }

            if ($mode === 'cards') {
                $config = $manager->normalizeCardsConfig($config);
            }

            $config = $manager->normalizeViewConfig($config);
            $attribute = $manager->viewAttribute();
            if (!$config['static']) {
                $manager->authorizeNestedElementManagement($owner, $attribute);
            }

            return InputNamespace::namespaceInputs(function() use ($mode, $attribute, $owner, $config, $renderHtml, $htmlManagerSettings, $manager) {
                $id = sprintf('element-index-%s', mt_rand());

                $settings = $htmlManagerSettings($manager->viewSettings($owner, $config, $mode, $attribute));

                $html = $renderHtml($id, $config, $attribute, $settings);

                return Html::tag('craft-nested-element-manager', $html, [
                    'element-type' => $manager->elementType,
                    'settings' => Json::encode($settings),
                ]);
            }, Html::id($manager->field->handle ?? $attribute));
        }, null, NestedElementManager::class);
    }
}
