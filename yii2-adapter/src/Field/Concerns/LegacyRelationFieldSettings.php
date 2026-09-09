<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Field\Concerns;

use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Field\BaseRelationField;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\View\LegacyAssets\CpAsset;
use CraftCms\Cms\View\LegacyAssets\InternalAssetRegistry;

use function CraftCms\Cms\craftAsset;
use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

/**
 * @internal
 * @phpstan-require-extends BaseRelationField
 */
trait LegacyRelationFieldSettings
{
    public function getSettingsHtml(): ?string
    {
        $variables = $this->settingsTemplateVariables();

        HtmlStack::jsWithVars(fn($args) => <<<JS
new Craft.ElementFieldSettings(...$args)
JS, [
            [
                $this->allowMultipleSources,
                InputNamespace::namespaceId('maintain-hierarchy-field'),
                InputNamespace::namespaceId($this->allowMultipleSources ? 'sources-field' : 'source-field'),
                InputNamespace::namespaceId('branch-limit-field'),
                InputNamespace::namespaceId('min-relations-field'),
                InputNamespace::namespaceId('max-relations-field'),
                InputNamespace::namespaceId('default-placement-field'),
                InputNamespace::namespaceId('viewMode-field'),
            ],
        ]);

        return template($this->settingsTemplate, $variables);
    }

    /**
     * Returns the HTML for the Target Site setting.
     */
    public function getTargetSiteFieldHtml(): ?string
    {
        $class = static::elementType();

        if (!Sites::isMultiSite() || !$class::isLocalized()) {
            return null;
        }

        $type = $class::lowerDisplayName();
        $pluralType = $class::pluralLowerDisplayName();
        $showTargetSite = !empty($this->targetSiteId);
        $siteOptions = [];

        foreach (Sites::getAllSites() as $site) {
            $siteOptions[] = [
                'label' => t($site->getName(), category: 'site'),
                'value' => $site->uid,
            ];
        }

        $html =
            FormFields::checkboxFieldHtml([
                'checkboxLabel' => t('Relate {type} from a specific site?', ['type' => $pluralType]),
                'name' => 'useTargetSite',
                'checked' => $showTargetSite,
                'toggle' => 'target-site-field',
                'reverseToggle' => 'show-site-menu-field',
            ]) .
            FormFields::selectFieldHtml([
                'fieldClass' => !$showTargetSite ? ['hidden'] : null,
                'label' => t('Which site should {type} be related from?', ['type' => $pluralType]),
                'id' => 'target-site',
                'name' => 'targetSiteId',
                'options' => $siteOptions,
                'value' => $this->targetSiteId,
            ]);

        if (static::canShowSiteMenu()) {
            $html .= FormFields::checkboxFieldHtml([
                'fieldset' => true,
                'fieldClass' => $showTargetSite ? ['hidden'] : null,
                'checkboxLabel' => t('Show the site menu'),
                'instructions' => t('Whether the site menu should be shown for {type} selection modals.',
                    [
                        'type' => $type,
                    ]),
                'warning' => t(
                    'Relations don’t store the selected site, so this should only be enabled if some {type} aren’t propagated to all sites.',
                    [
                        'type' => $pluralType,
                    ]),
                'id' => 'show-site-menu',
                'name' => 'showSiteMenu',
                'checked' => $this->showSiteMenu,
            ]);
        }

        return $html;
    }

    /**
     * Returns the HTML for the View Mode setting.
     */
    public function getViewModeFieldHtml(): ?string
    {
        $supportedViewModes = $this->supportedViewModes();

        if (count($supportedViewModes) === 1) {
            return null;
        }

        if (empty(array_diff(array_keys($supportedViewModes), [
            self::VIEW_MODE_LIST,
            self::VIEW_MODE_LIST_INLINE,
            self::VIEW_MODE_THUMBS,
            self::VIEW_MODE_CARDS,
            self::VIEW_MODE_CARDS_GRID,
        ]))) {
            $html = Html::beginTag('div', ['class' => ['flex', 'items-start', 'gap-l']]);
            app(InternalAssetRegistry::class)->register(CpAsset::class);
            $baseIconsUrl = craftAsset('legacy/cp/dist/images/view-modes');

            foreach ($supportedViewModes as $key => $label) {
                $html .= Html::beginTag('label', ['class' => 'nowrap']) .
                    Html::img("$baseIconsUrl/$key.svg", '', [
                        'class' => 'mb-xs',
                        'width' => $key === self::VIEW_MODE_LIST ? 48 : 80,
                        'height' => 60,
                    ]) .
                    Html::radio('viewMode', $key, [
                        'value' => $key,
                        'checked' => $this->viewMode === $key,
                    ]) .
                    ' ' . $label .
                    Html::endTag('label');
            }

            $html .= Html::endTag('div');
        } else {
            $viewModeOptions = [];

            foreach ($supportedViewModes as $key => $label) {
                $viewModeOptions[] = ['label' => $label, 'value' => $key];
            }

            $html = FormFields::selectHtml([
                'id' => 'viewMode',
                'name' => 'viewMode',
                'options' => $viewModeOptions,
                'value' => $this->viewMode,
            ]);
        }

        return FormFields::fieldHtml($html, [
            'label' => t('View Mode'),
            'instructions' => t('Choose how the field should look for authors.'),
            'id' => 'viewMode',
        ]);
    }

    /**
     * Returns an array of variables that should be passed to the settings template.
     *
     * @return array{
     *     field:static,
     *     upperElementType:string,
     *     elementType:string,
     *     pluralElementType:string,
     *     selectionCondition:string|null,
     * }
     */
    protected function settingsTemplateVariables(): array
    {
        $elementType = static::elementType();

        $selectionCondition = $this->getSelectionCondition() ?? $this->createSelectionCondition();
        $selectionConditionHtml = null;

        if ($selectionCondition) {
            $selectionCondition->mainTag = 'div';
            $selectionCondition->id = 'selection-condition';
            $selectionCondition->name = 'selectionCondition';
            $selectionCondition->forProjectConfig = true;

            $selectionConditionHtml = FormFields::fieldHtml($selectionCondition->getBuilderHtml(), [
                'label' => t('Selectable {type} Condition', [
                    'type' => $elementType::pluralDisplayName(),
                ]),
                'instructions' => mb_ucfirst(t('Only allow {type} to be selected if they match the following rules:', [
                    'type' => $elementType::pluralLowerDisplayName(),
                ])),
            ]);
        }

        return [
            'field' => $this,
            'upperElementType' => $elementType::displayName(),
            'elementType' => $elementType::lowerDisplayName(),
            'pluralElementType' => $elementType::pluralLowerDisplayName(),
            'selectionCondition' => $selectionConditionHtml,
        ];
    }
}
