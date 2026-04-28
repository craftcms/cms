<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use craft\base\Event as YiiEvent;
use craft\events\DefineElementHtmlEvent;
use craft\events\DefineElementInnerHtmlEvent;
use craft\events\RegisterCpAlertsEvent;
use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Component\Contracts\Chippable;
use CraftCms\Cms\Component\Contracts\Statusable;
use CraftCms\Cms\Cp\Alerts;
use CraftCms\Cms\Cp\Events\DefineElementCardHtml;
use CraftCms\Cms\Cp\Events\DefineElementChipHtml;
use CraftCms\Cms\Cp\Events\RegisterCpAlerts;
use CraftCms\Cms\Cp\FieldLayoutDesigner\CardDesigner;
use CraftCms\Cms\Cp\FieldLayoutDesigner\FieldLayoutDesigner;
use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Cp\Html\ContentHtml;
use CraftCms\Cms\Cp\Html\ElementHtml;
use CraftCms\Cms\Cp\Html\ElementIndexHtml;
use CraftCms\Cms\Cp\Html\MenuHtml;
use CraftCms\Cms\Cp\Html\PreviewHtml;
use CraftCms\Cms\Cp\Html\StatusHtml;
use CraftCms\Cms\Cp\Icons;
use CraftCms\Cms\Cp\RequestedSite;
use CraftCms\Cms\Element\Contracts\ElementInterface;

use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\FieldLayoutElement;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use function CraftCms\Cms\template;

/**
 * Class Cp
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see Alerts}, {@see ElementHtml}, {@see StatusHtml}, {@see PreviewHtml}, {@see ElementIndexHtml}, {@see ContentHtml}, {@see MenuHtml}, {@see FormFields}, {@see Icons}, {@see RequestedSite} instead.
 */
class Cp
{
    /**
     * @event RegisterCpAlertsEvent The event that is triggered when registering control panel alerts.
     */
    public const EVENT_REGISTER_ALERTS = 'registerAlerts';

    /**
     * @event DefineElementHtmlEvent The event that is triggered when defining an element’s chip HTML.
     *
     * @see elementChipHtml()
     * @since 5.0.0
     */
    public const EVENT_DEFINE_ELEMENT_CHIP_HTML = 'defineElementChipHtml';

    /**
     * @event DefineElementHtmlEvent The event that is triggered when defining an element’s card HTML.
     *
     * @see elementCardHtml()
     * @since 5.0.0
     */
    public const EVENT_DEFINE_ELEMENT_CARD_HTML = 'defineElementCardHtml';

    /**
     * @event DefineElementInnerHtmlEvent The event that is triggered when defining an element’s inner HTML.
     *
     * @since 4.0.0
     * @deprecated in 5.0.0. [[EVENT_DEFINE_ELEMENT_CHIP_HTML]] should be used instead.
     */
    public const EVENT_DEFINE_ELEMENT_INNER_HTML = 'defineElementInnerHtml';

    /**
     * @since 3.5.8
     * @deprecated in 5.0.0. [[CHIP_SIZE_SMALL]] should be used instead.
     */
    public const ELEMENT_SIZE_SMALL = ElementHtml::CHIP_SIZE_SMALL;

    /**
     * @since 3.5.8
     * @deprecated in 5.0.0. [[CHIP_SIZE_LARGE]] should be used instead.
     */
    public const ELEMENT_SIZE_LARGE = ElementHtml::CHIP_SIZE_LARGE;

    /**
     * @since 5.0.0
     */
    public const CHIP_SIZE_SMALL = ElementHtml::CHIP_SIZE_SMALL;

    /**
     * @since 5.0.0
     */
    public const CHIP_SIZE_LARGE = ElementHtml::CHIP_SIZE_LARGE;

    /**
     * Renders a control panel template.
     *
     * @param string $template
     * @param array $variables
     *
     * @return string
     * @throws \CraftCms\Cms\Twig\Exceptions\TemplateLoaderException if `$template` is an invalid template path
     */
    public static function renderTemplate(string $template, array $variables = []): string
    {
        return template('' . $template, $variables, templateMode: TemplateMode::Cp);
    }

    /**
     * @internal
     */
    public static function alerts(?string $path = null, bool $fetch = false): array
    {
        return app(Alerts::class)->get(path: $path, fetch: $fetch);
    }

    /**
     * Returns an SVG icon's contents for the control panel.
     *
     * @since 5.0.0
     */
    public static function iconSvg(?string $icon, ?string $fallbackLabel = null, ?string $altText = null): ?string
    {
        return Icons::svg(icon: $icon, fallbackLabel: $fallbackLabel, altText: $altText);
    }

    /**
     * Returns a fallback icon SVG for a component with a given label.
     *
     * @since 5.0.0
     */
    public static function fallbackIconSvg(string $label): string
    {
        return Icons::fallbackSvg(label: $label);
    }

    /**
     * Returns the appropriate Earth icon, depending on the system time zone.
     *
     * @since 5.0.0
     */
    public static function earthIcon(): string
    {
        return Icons::earth();
    }

    /**
     * Returns the site the control panel is currently working with, via a `site` query string param if sent.
     *
     * @return Site|null The site, or `null` if the user doesn't have permission to edit any sites.
     *
     * @since 4.0.0
     */
    public static function requestedSite(): ?Site
    {
        return app(RequestedSite::class)->get();
    }

    /**
     * Resets [[requestedSite()]].
     *
     * @since 5.7.0
     */
    public static function reset(): void
    {
        app(RequestedSite::class)->reset();
    }

    /**
     * Renders a component's chip HTML.
     *
     * @param Chippable $component The component that the chip represents
     * @param array $config Chip configuration
     *
     * @since 5.0.0
     */
    public static function chipHtml(Chippable $component, array $config = []): string
    {
        return app(ElementHtml::class)->chipHtml(component: $component, config: $config);
    }

    /**
     * Renders an element's chip HTML.
     *
     * @since 5.0.0
     */
    public static function elementChipHtml(ElementInterface $element, array $config = []): string
    {
        return app(ElementHtml::class)->elementChipHtml(element: $element, config: $config);
    }

    /**
     * Renders an element's card HTML.
     *
     * @since 5.0.0
     */
    public static function elementCardHtml(ElementInterface $element, array $config = []): string
    {
        return app(ElementHtml::class)->elementCardHtml(element: $element, config: $config);
    }

    /**
     * Returns a status indicator's HTML.
     */
    public static function statusIndicatorHtml(string $status, array $attributes = []): ?string
    {
        return app(StatusHtml::class)->statusIndicatorHtml(status: $status, attributes: $attributes);
    }

    /**
     * Returns a component's status indicator HTML.
     *
     * @since 5.0.0
     */
    public static function componentStatusIndicatorHtml(Statusable $component): ?string
    {
        return app(StatusHtml::class)->componentStatusIndicatorHtml(component: $component);
    }

    /**
     * Returns a status label's HTML.
     */
    public static function statusLabelHtml(array $config = []): ?string
    {
        return app(StatusHtml::class)->statusLabelHtml(config: $config);
    }

    /**
     * Returns a component's status label HTML.
     *
     * @since 5.0.0
     */
    public static function componentStatusLabelHtml(Statusable $component): ?string
    {
        return app(StatusHtml::class)->componentStatusLabelHtml(component: $component);
    }

    /**
     * Returns an element's HTML.
     *
     * @since 3.5.8
     * @deprecated in 5.0.0. [[elementChipHtml()]] or [[elementCardHtml()]] should be used instead.
     */
    public static function elementHtml(
        ElementInterface $element,
        string $context = 'index',
        string $size = self::CHIP_SIZE_SMALL,
        ?string $inputName = null,
        bool $showStatus = true,
        bool $showThumb = true,
        bool $showLabel = true,
        bool $showDraftName = true,
        bool $single = false,
        bool $autoReload = true,
    ): string {
        $resolvedInputName = null;

        if ($inputName !== null) {
            $resolvedInputName = $inputName . ($single ? '' : '[]');
        }

        $html = app(ElementHtml::class)->elementChipHtml($element, [
            'autoReload' => $autoReload,
            'context' => $context,
            'inputName' => $resolvedInputName,
            'showDraftName' => $showDraftName,
            'showLabel' => $showLabel,
            'showStatus' => $showStatus,
            'showThumb' => $showThumb,
            'size' => $size,
        ]);

        // Fire a 'defineElementInnerHtml' event
        if (YiiEvent::hasHandlers(self::class, self::EVENT_DEFINE_ELEMENT_INNER_HTML)) {
            $parsed = Html::parseTag($html);
            $innerHtml = substr($html, $parsed['htmlStart'], $parsed['htmlEnd'] - $parsed['htmlStart']);
            $event = new DefineElementInnerHtmlEvent(compact(
                'element',
                'context',
                'size',
                'showStatus',
                'showThumb',
                'showLabel',
                'showDraftName',
                'innerHtml',
            ));
            YiiEvent::trigger(self::class, self::EVENT_DEFINE_ELEMENT_INNER_HTML, $event);

            return substr($html, 0, $parsed['htmlStart']) .
                $event->innerHtml .
                substr($html, $parsed['htmlEnd']);
        }

        return $html;
    }

    /**
     * Returns element preview HTML, for a list of elements.
     *
     * @param ElementInterface[] $elements The elements
     *
     * @since 3.6.3
     */
    public static function elementPreviewHtml(
        array $elements,
        string $size = self::CHIP_SIZE_SMALL,
        bool $showStatus = true,
        bool $showThumb = true,
        bool $showLabel = true,
        bool $showDraftName = true,
    ): string {
        return app(PreviewHtml::class)->elementPreviewHtml(
            elements: $elements,
            size: $size,
            showStatus: $showStatus,
            showThumb: $showThumb,
            showLabel: $showLabel,
            showDraftName: $showDraftName,
        );
    }

    /**
     * Returns component preview HTML, for a list of components.
     *
     * @param Chippable[] $components The components
     *
     * @since 5.4.0
     */
    public static function componentPreviewHtml(array $components, array $chipConfig = []): string
    {
        return app(PreviewHtml::class)->componentPreviewHtml(components: $components, chipConfig: $chipConfig);
    }

    /**
     * Returns the HTML for an element index.
     *
     * @param class-string<ElementInterface> $elementType
     *
     * @since 5.0.0
     */
    public static function elementIndexHtml(string $elementType, array $config = []): string
    {
        return app(ElementIndexHtml::class)->html(elementType: $elementType, config: $config);
    }

    /**
     * Returns a metadata component's HTML.
     *
     * @param array $data The data, with keys representing the labels.
     */
    public static function metadataHtml(array $data): string
    {
        return app(ContentHtml::class)->metadataHtml(data: $data);
    }

    /**
     * Returns a disclosure menu's HTML.
     *
     * @param array $items The menu items.
     *
     * @since 5.0.0
     */
    public static function disclosureMenu(array $items, array $config = []): string
    {
        return app(MenuHtml::class)->disclosureMenu(items: $items, config: $config);
    }

    /**
     * Returns a menu item's HTML.
     *
     * @since 5.0.0
     */
    public static function menuItem(array $config, string $menuId): string
    {
        return app(MenuHtml::class)->menuItem(config: $config, menuId: $menuId);
    }

    /**
     * Normalizes menu items.
     *
     * @return array
     *
     * @since 5.0.0
     */
    public static function normalizeMenuItems(array|Collection $items): array
    {
        return app(MenuHtml::class)->normalizeMenuItems(items: $items);
    }

    /**
     * Returns a menu item array for the given sites.
     *
     * @param array<int,Site|array{site:Site,status?:string}> $sites
     *
     * @since 5.0.0
     */
    public static function siteMenuItems(
        array|Collection|null $sites = null,
        ?Site $selectedSite = null,
        array $config = [],
    ): array {
        return app(MenuHtml::class)->siteMenuItems(sites: $sites, selectedSite: $selectedSite, config: $config);
    }

    /**
     * Returns the notice that should show when admin is viewing the available settings pages
     * while `allowAdminChanges` is set to false.
     *
     * @since 5.6.0
     */
    public static function readOnlyNoticeHtml(): string
    {
        return app(ContentHtml::class)->readOnlyNoticeHtml();
    }

    /**
     * Processes the given text as Markdown.
     *
     * @since 5.8.3
     */
    public static function parseMarkdown(string $text, string $flavor = 'gfm-comment'): string
    {
        return app(ContentHtml::class)->parseMarkdown(text: $text, flavor: $flavor);
    }

    /**
     * Renders a field's HTML, for the given input HTML or a template.
     *
     * @param string|callable $input The input HTML or template path.
     * @param array $config
     *
     * @return string
     * @throws \CraftCms\Cms\Twig\Exceptions\TemplateLoaderException
     * @throws \InvalidArgumentException
     *
     * @since 3.5.8
     */
    public static function fieldHtml(string|callable $input, array $config = []): string
    {
        return FormFields::fieldHtml(input: $input, config: $config);
    }

    /**
     * @since 5.0.0
     */
    public static function buttonHtml(array $config): string
    {
        return FormFields::buttonHtml(config: $config);
    }

    /**
     * @since 5.0.0
     */
    public static function buttonGroupHtml(array $config): string
    {
        return FormFields::buttonGroupHtml(config: $config);
    }

    /**
     * @since 5.0.0
     */
    public static function buttonGroupFieldHtml(array $config): string
    {
        return FormFields::buttonGroupFieldHtml(config: $config);
    }

    /**
     * @since 3.6.0
     */
    public static function checkboxFieldHtml(array $config): string
    {
        return FormFields::checkboxFieldHtml(config: $config);
    }

    /**
     * @since 3.6.0
     */
    public static function checkboxSelectFieldHtml(array $config): string
    {
        return FormFields::checkboxSelectFieldHtml(config: $config);
    }

    /**
     * @since 5.6.0
     */
    public static function checkboxGroupHtml(array $config): string
    {
        return FormFields::checkboxGroupHtml(config: $config);
    }

    /**
     * @since 5.6.0
     */
    public static function checkboxGroupFieldHtml(array $config): string
    {
        return FormFields::checkboxGroupFieldHtml(config: $config);
    }

    /**
     * @since 5.0.0
     */
    public static function colorHtml(array $config): string
    {
        return FormFields::colorHtml(config: $config);
    }

    /**
     * @since 3.6.0
     */
    public static function colorFieldHtml(array $config): string
    {
        return FormFields::colorFieldHtml(config: $config);
    }

    /**
     * @since 5.0.0
     */
    public static function colorSelectFieldHtml(array $config): string
    {
        return FormFields::colorSelectFieldHtml(config: $config);
    }

    /**
     * @since 5.0.0
     */
    public static function iconPickerHtml(array $config): string
    {
        return FormFields::iconPickerHtml(config: $config);
    }

    /**
     * @since 5.0.0
     */
    public static function iconPickerFieldHtml(array $config): string
    {
        return FormFields::iconPickerFieldHtml(config: $config);
    }

    /**
     * @since 3.6.0
     */
    public static function editableTableHtml(array $config): string
    {
        return FormFields::editableTableHtml(config: $config);
    }

    /**
     * @since 3.6.0
     */
    public static function editableTableFieldHtml(array $config): string
    {
        return FormFields::editableTableFieldHtml(config: $config);
    }

    /**
     * @since 3.6.0
     */
    public static function lightswitchHtml(array $config): string
    {
        return FormFields::lightswitchHtml(config: $config);
    }

    /**
     * @since 3.6.0
     */
    public static function lightswitchFieldHtml(array $config): string
    {
        return FormFields::lightswitchFieldHtml(config: $config);
    }

    /**
     * @since 5.0.0
     */
    public static function rangeHtml(array $config): string
    {
        return FormFields::rangeHtml(config: $config);
    }

    /**
     * @since 5.0.0
     */
    public static function rangeFieldHtml(array $config): string
    {
        return FormFields::rangeFieldHtml(config: $config);
    }

    /**
     * @since 4.0.0
     */
    public static function moneyInputHtml(array $config): string
    {
        return FormFields::moneyInputHtml(config: $config);
    }

    /**
     * @since 4.0.0
     */
    public static function moneyFieldHtml(array $config): string
    {
        return FormFields::moneyFieldHtml(config: $config);
    }

    /**
     * @since 3.6.0
     */
    public static function selectHtml(array $config): string
    {
        return FormFields::selectHtml(config: $config);
    }

    /**
     * @since 3.6.0
     */
    public static function selectFieldHtml(array $config): string
    {
        return FormFields::selectFieldHtml(config: $config);
    }

    /**
     * @since 5.0.0
     */
    public static function customSelectHtml(array $config): string
    {
        return FormFields::customSelectHtml(config: $config);
    }

    /**
     * @since 5.0.0
     */
    public static function customSelectFieldHtml(array $config): string
    {
        return FormFields::customSelectFieldHtml(config: $config);
    }

    /**
     * @since 3.6.0
     */
    public static function selectizeHtml(array $config): string
    {
        return FormFields::selectizeHtml(config: $config);
    }

    /**
     * @since 3.6.0
     */
    public static function selectizeFieldHtml(array $config): string
    {
        return FormFields::selectizeFieldHtml(config: $config);
    }

    /**
     * @since 3.6.0
     */
    public static function multiSelectHtml(array $config): string
    {
        return FormFields::multiSelectHtml(config: $config);
    }

    /**
     * @since 3.6.0
     */
    public static function multiSelectFieldHtml(array $config): string
    {
        return FormFields::multiSelectFieldHtml(config: $config);
    }

    /**
     * @since 3.6.0
     */
    public static function textHtml(array $config): string
    {
        return FormFields::textHtml(config: $config);
    }

    /**
     * @since 3.6.0
     */
    public static function textFieldHtml(array $config): string
    {
        return FormFields::textFieldHtml(config: $config);
    }

    /**
     * @since 3.6.0
     */
    public static function textareaHtml(array $config): string
    {
        return FormFields::textareaHtml(config: $config);
    }

    /**
     * @since 3.6.0
     */
    public static function textareaFieldHtml(array $config): string
    {
        return FormFields::textareaFieldHtml(config: $config);
    }

    /**
     * @since 5.0.0
     */
    public static function dateHtml(array $config): string
    {
        return FormFields::dateHtml(config: $config);
    }

    /**
     * @since 5.0.0
     */
    public static function dateFieldHtml(array $config): string
    {
        return FormFields::dateFieldHtml(config: $config);
    }

    /**
     * @since 5.0.0
     */
    public static function timeHtml(array $config): string
    {
        return FormFields::timeHtml(config: $config);
    }

    /**
     * @since 5.0.0
     */
    public static function timeFieldHtml(array $config): string
    {
        return FormFields::timeFieldHtml(config: $config);
    }

    /**
     * @since 5.0.0
     */
    public static function dateTimeFieldHtml(array $config): string
    {
        return FormFields::dateTimeFieldHtml(config: $config);
    }

    /**
     * @since 5.0.0
     */
    public static function elementSelectHtml(array $config): string
    {
        return FormFields::elementSelectHtml(config: $config);
    }

    /**
     * @since 5.0.0
     */
    public static function elementSelectFieldHtml(array $config): string
    {
        return FormFields::elementSelectFieldHtml(config: $config);
    }

    /**
     * @since 5.0.0
     */
    public static function entryTypeSelectHtml(array $config): string
    {
        return FormFields::entryTypeSelectHtml(config: $config);
    }

    /**
     * @since 5.0.0
     */
    public static function entryTypeSelectFieldHtml(array $config): string
    {
        return FormFields::entryTypeSelectFieldHtml(config: $config);
    }

    /**
     * @since 3.6.0
     */
    public static function autosuggestFieldHtml(array $config): string
    {
        return FormFields::autosuggestFieldHtml(config: $config);
    }

    /**
     * Returns address fields' HTML (sans country) for a given address.
     *
     * @since 4.0.0
     */
    public static function addressFieldsHtml(Address $address, bool $static = false): string
    {
        return FormFields::addressFieldsHtml($address, $static);
    }

    /**
     * Renders a field layout designer.
     *
     * @since 4.0.0
     */
    public static function fieldLayoutDesignerHtml(FieldLayout $fieldLayout, array $config = []): string
    {
        return app(FieldLayoutDesigner::class)->html(fieldLayout: $fieldLayout, config: $config);
    }

    /**
     * Renders a field layout element's selector HTML.
     *
     * @since 5.0.0
     */
    public static function layoutElementSelectorHtml(
        FieldLayoutElement $element,
        bool $forLibrary = false,
        array $attributes = [],
    ): string {
        return app(FieldLayoutDesigner::class)->layoutElementSelectorHtml(
            element: $element,
            forLibrary: $forLibrary,
            attributes: $attributes,
        );
    }

    /**
     * Renders a Generated Fields table for a field layout.
     */
    public static function generatedFieldsTableHtml(FieldLayout $fieldLayout, array $config = []): string
    {
        return app(FieldLayoutDesigner::class)->generatedFieldsTableHtml(fieldLayout: $fieldLayout, config: $config);
    }

    /**
     * Renders a card view designer.
     *
     * @since 5.5.0
     */
    public static function cardViewDesignerHtml(FieldLayout $fieldLayout, array $config = []): string
    {
        return app(CardDesigner::class)->html(fieldLayout: $fieldLayout, config: $config);
    }

    /**
     * Returns an array of available card preview options for the given field layout.
     *
     * @return array{label:string,value:string}[]
     *
     * @since 5.9.0
     */
    public static function cardPreviewOptions(FieldLayout $fieldLayout, bool $withAttributes = true): array
    {
        return app(CardDesigner::class)->previewOptions(fieldLayout: $fieldLayout, withAttributes: $withAttributes);
    }

    /**
     * Returns an array of available card thumb options for the given field layout.
     */
    public static function cardThumbOptions(FieldLayout $fieldLayout): array
    {
        return app(CardDesigner::class)->thumbOptions(fieldLayout: $fieldLayout);
    }

    /**
     * Returns a card's preview HTML.
     */
    public static function cardPreviewHtml(FieldLayout $fieldLayout, array $cardElements = [], ?bool $showThumb = null): string
    {
        return app(CardDesigner::class)->previewHtml(fieldLayout: $fieldLayout, cardElements: $cardElements, showThumb: $showThumb);
    }

    public static function registerEvents(): void
    {
        Event::listen(function(RegisterCpAlerts $event) {
            // Fire legacy Yii2 'registerAlerts' event
            if (YiiEvent::hasHandlers(self::class, self::EVENT_REGISTER_ALERTS)) {
                $yiiEvent = new RegisterCpAlertsEvent();
                YiiEvent::trigger(self::class, self::EVENT_REGISTER_ALERTS, $yiiEvent);
                $event->alerts = array_merge($event->alerts, $yiiEvent->alerts);
            }
        });

        Event::listen(function(DefineElementChipHtml $event) {
            if (YiiEvent::hasHandlers(self::class, self::EVENT_DEFINE_ELEMENT_CHIP_HTML)) {
                $yiiEvent = new DefineElementHtmlEvent([
                    'element' => $event->element,
                    'context' => $event->context,
                    'html' => $event->html,
                ]);

                YiiEvent::trigger(self::class, self::EVENT_DEFINE_ELEMENT_CHIP_HTML, $yiiEvent);

                $event->html = $yiiEvent->html;
            }
        });

        Event::listen(function(DefineElementCardHtml $event) {
            if (YiiEvent::hasHandlers(self::class, self::EVENT_DEFINE_ELEMENT_CARD_HTML)) {
                $yiiEvent = new DefineElementHtmlEvent([
                    'element' => $event->element,
                    'context' => $event->context,
                    'html' => $event->html,
                ]);

                YiiEvent::trigger(self::class, self::EVENT_DEFINE_ELEMENT_CARD_HTML, $yiiEvent);

                $event->html = $yiiEvent->html;
            }
        });
    }
}
