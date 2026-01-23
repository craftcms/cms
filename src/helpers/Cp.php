<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use CommerceGuys\Addressing\Subdivision\SubdivisionRepository as BaseSubdivisionRepository;
use Craft;
use craft\base\Actionable;
use craft\base\Chippable;
use craft\base\Colorable;
use craft\base\CpEditable;
use craft\base\Describable;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\FieldLayoutElement;
use craft\base\Grippable;
use craft\base\Iconic;
use craft\base\Indicative;
use craft\base\NestedElementInterface;
use craft\base\Statusable;
use craft\base\Thumbable;
use craft\behaviors\DraftBehavior;
use craft\elements\Address;
use craft\enums\AttributeStatus;
use craft\enums\CmsEdition;
use craft\enums\Color;
use craft\enums\MenuItemType;
use craft\errors\FieldNotFoundException;
use craft\errors\InvalidHtmlTagException;
use craft\events\DefineElementHtmlEvent;
use craft\events\DefineElementInnerHtmlEvent;
use craft\events\RegisterCpAlertsEvent;
use craft\fieldlayoutelements\BaseField;
use craft\fieldlayoutelements\CustomField;
use craft\fields\ContentBlock;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\models\Site;
use craft\services\ElementSources;
use craft\utilities\ProjectConfig as ProjectConfigUtility;
use craft\utilities\Updates;
use craft\web\twig\TemplateLoaderException;
use craft\web\View;
use DateTime;
use Illuminate\Support\Collection;
use yii\base\Event;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\helpers\Markdown;
use yii\validators\RequiredValidator;

/**
 * Class Cp
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Cp
{
    /**
     * @event RegisterCpAlertsEvent The event that is triggered when registering control panel alerts.
     */
    public const EVENT_REGISTER_ALERTS = 'registerAlerts';

    /**
     * @event DefineElementHtmlEvent The event that is triggered when defining an element’s chip HTML.
     * @see elementChipHtml()
     * @since 5.0.0
     */
    public const EVENT_DEFINE_ELEMENT_CHIP_HTML = 'defineElementChipHtml';

    /**
     * @event DefineElementHtmlEvent The event that is triggered when defining an element’s card HTML.
     * @see elementCardHtml()
     * @since 5.0.0
     */
    public const EVENT_DEFINE_ELEMENT_CARD_HTML = 'defineElementCardHtml';

    /**
     * @event DefineElementInnerHtmlEvent The event that is triggered when defining an element’s inner HTML.
     * @since 4.0.0
     * @deprecated in 5.0.0. [[EVENT_DEFINE_ELEMENT_CHIP_HTML]] should be used instead.
     */
    public const EVENT_DEFINE_ELEMENT_INNER_HTML = 'defineElementInnerHtml';

    /**
     * @since 3.5.8
     * @deprecated in 5.0.0. [[CHIP_SIZE_SMALL]] should be used instead.
     */
    public const ELEMENT_SIZE_SMALL = 'small';
    /**
     * @since 3.5.8
     * @deprecated in 5.0.0. [[CHIP_SIZE_LARGE]] should be used instead.
     */
    public const ELEMENT_SIZE_LARGE = 'large';

    /**
     * @since 5.0.0
     */
    public const CHIP_SIZE_SMALL = 'small';
    /**
     * @since 5.0.0
     */
    public const CHIP_SIZE_LARGE = 'large';

    /**
     * @var Site|false|null
     * @see requestedSite()
     */
    private static Site|false|null $_requestedSite = null;

    /**
     * Renders a control panel template.
     *
     * @param string $template
     * @param array $variables
     * @return string
     * @throws TemplateLoaderException if `$template` is an invalid template path
     */
    public static function renderTemplate(string $template, array $variables = []): string
    {
        return Craft::$app->getView()->renderTemplate($template, $variables, View::TEMPLATE_MODE_CP);
    }

    /**
     * @param string|null $path
     * @param bool $fetch
     * @return array
     * @internal
     */
    public static function alerts(?string $path = null, bool $fetch = false): array
    {
        $alerts = [];
        $user = Craft::$app->getUser()->getIdentity();
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $consoleUrl = rtrim(Craft::$app->getPluginStore()->craftIdEndpoint, '/');

        if (!$user) {
            return $alerts;
        }

        $canTestEditions = Craft::$app->getCanTestEditions();
        $resolvableLicenseAlerts = [];
        $resolvableLicenseItems = [];

        foreach (App::licensingIssues(fetch: $fetch) as [$name, $message, $resolveItem]) {
            if (!$resolveItem) {
                $alerts[] = $message;
            } elseif (!$canTestEditions) {
                $resolvableLicenseAlerts[] = $message;
                $resolvableLicenseItems[] = $resolveItem;
            }
        }

        if (!empty($resolvableLicenseAlerts)) {
            $cartUrl = UrlHelper::urlWithParams("$consoleUrl/cart/new", [
                'items' => $resolvableLicenseItems,
            ]);
            array_unshift($alerts, [
                'content' => Html::tag('h2', Craft::t('app', 'License purchase required.')) .
                    Html::tag('p', Craft::t('app', 'The following licensing {total, plural, =1{issue} other{issues}} can be resolved with a single purchase on Craft Console:', [
                        'total' => count($resolvableLicenseAlerts),
                    ])) .
                    Html::ul($resolvableLicenseAlerts, [
                        'class' => 'errors',
                    ]) .
                    // can't use Html::a() because it's encoding &amp;'s, which is causing issues
                    Html::beginTag('p', [
                        'class' => ['flex', 'flex-nowrap', 'resolvable-alert-buttons'],
                    ]) .
                    sprintf('<a class="go" href="%s" target="_blank">%s</a>', $cartUrl, Craft::t('app', 'Resolve now')) .
                    Html::endTag('p'),
                'showIcon' => false,
            ]);
        }

        $utilitiesService = Craft::$app->getUtilities();

        // Critical update available?
        if (
            $path !== 'utilities/updates' &&
            $utilitiesService->checkAuthorization(Updates::class) &&
            Craft::$app->getUpdates()->getIsCriticalUpdateAvailable()
        ) {
            $alerts[] = Craft::t('app', 'A critical update is available.') .
                ' <a class="go nowrap" href="' . UrlHelper::url('utilities/updates') . '">' . Craft::t('app', 'Go to Updates') . '</a>';
        }

        // Do any plugins require a higher edition?
        if (Craft::$app->edition < CmsEdition::Pro) {
            foreach (Craft::$app->getPlugins()->getAllPlugins() as $plugin) {
                if ($plugin->minCmsEdition->value > Craft::$app->edition->value) {
                    $alerts[] = Craft::t('app', '{plugin} requires Craft CMS {edition} edition.', [
                        'plugin' => $plugin->name,
                        'edition' => $plugin->minCmsEdition->name,
                    ]);
                }
            }
        }

        // Display an alert if there are pending project config YAML changes
        $projectConfig = Craft::$app->getProjectConfig();
        if (
            $path !== 'utilities/project-config' &&
            $utilitiesService->checkAuthorization(ProjectConfigUtility::class) &&
            $projectConfig->areChangesPending() &&
            ($projectConfig->writeYamlAutomatically || $projectConfig->get('dateModified') <= $projectConfig->get('dateModified', true))
        ) {
            $alerts[] = Craft::t('app', 'Your project config YAML files contain pending changes.') .
                ' ' . '<a class="go" href="' . UrlHelper::url('utilities/project-config') . '">' . Craft::t('app', 'Review') . '</a>';
        }

        // Display a warning if admin changes are allowed, and project.yaml is being used but not writable
        if (
            $user->admin &&
            $generalConfig->allowAdminChanges &&
            $projectConfig->getHadFileWriteIssues()
        ) {
            $alerts[] = Craft::t('app', 'Your {folder} folder isn’t writable.', [
                'folder' => "config/$projectConfig->folderName/",
            ]);
        }

        // Fire a 'registerAlerts' event
        if (Event::hasHandlers(self::class, self::EVENT_REGISTER_ALERTS)) {
            $event = new RegisterCpAlertsEvent();
            Event::trigger(self::class, self::EVENT_REGISTER_ALERTS, $event);
            $alerts = array_merge($alerts, $event->alerts);
        }

        // Inline CSS styles
        foreach ($alerts as $i => $alert) {
            if (!is_array($alert)) {
                $alert = [
                    'content' => $alert,
                    'showIcon' => true,
                ];
            }

            $offset = 0;
            while (true) {
                try {
                    $tagInfo = Html::parseTag($alert['content'], $offset);
                } catch (InvalidHtmlTagException $e) {
                    break;
                }

                $newTagHtml = self::alertTagHtml($tagInfo);
                $alert['content'] = substr($alert['content'], 0, $tagInfo['start']) .
                    $newTagHtml .
                    substr($alert['content'], $tagInfo['end']);
                $offset = $tagInfo['start'] + strlen($newTagHtml);
            }

            $alerts[$i] = $alert;
        }

        return $alerts;
    }

    private static function alertTagHtml(array $tagInfo): string
    {
        if ($tagInfo['type'] === 'text') {
            return $tagInfo['value'];
        }

        $style = [];
        switch ($tagInfo['type']) {
            case 'h2':
                $style = array_merge($style, [
                    'display' => 'block',
                ]);
                break;
            case 'ul':
            case 'p':
                $style = array_merge($style, [
                    'display' => 'block',
                ]);
                break;
            case 'li':
                $style = array_merge($style, [
                    'display' => 'list-item',
                ]);
                break;
            case 'a':
                if (isset($tagInfo['attributes']['class']) && array_intersect(['go', 'btn'], $tagInfo['attributes']['class'])) {
                    $style = array_merge($style, [
                        'display' => 'inline-flex',
                    ]);
                }
                break;
        }

        $childTagHtml = array_map(fn(array $childTagInfo): string => self::alertTagHtml($childTagInfo), $tagInfo['children'] ?? []);

        return trim(static::renderTemplate('_layouts/components/tag.twig', [
            'type' => $tagInfo['type'],
            'attributes' => $tagInfo['attributes'] ?? [],
            'style' => $style,
            'content' => implode('', $childTagHtml),
        ]));
    }

    /**
     * Renders a component’s chip HTML.
     *
     * The following config settings can be passed to `$config`:
     *
     * - `attributes` – Any custom HTML attributes that should be set on the chip
     * - `autoReload` – Whether the chip should auto-reload itself when it’s saved
     * - `class` – Class name(s) that should be added to the container element
     * - `hyperlink` – Whether the chip label should be hyperlinked to the component’s URL (only applies if the component implements [[CpEditable]])
     * - `id` – The chip’s `id` attribute
     * - `inputName` – The `name` attribute that should be set on a hidden input, if set
     * - `inputValue` – The `value` attribute that should be set on the hidden input, if `inputName` is set. Defaults to [[\craft\base\Identifiable::getId()`]].
     * - `labelHtml` – The label HTML, if it should be different from [[Chippable::getUiLabel()]]
     * - `overrides` – Any config overrides that should persist when the chip is re-rendered
     * - `selectable` – Whether the chip should include a checkbox input
     * - `showActionMenu` – Whether the chip should include an action menu (only applies if the component implements [[Actionable]])
     * - `showDescription` – Whether the chip should include the component’s description (only applies if the component implements [[Describable]])
     * - `showHandle` – Whether the component’s handle should be show (only applies if the component implements [[Grippable]])
     * - `showIndicators` – Whether the component’s indicators should be shown (only applies if the component implements [[Indicative]])
     * - `showLabel` – Whether the component’s label should be shown
     * - `showStatus` – Whether the component’s status should be shown (only applies if the component implements [[Statusable]])
     * - `showThumb` – Whether the component’s thumbnail should be shown (only applies if the component implements [[Thumbable]] or [[Iconic]])
     * - `size` – The size of the chip (`small` or `large`)
     * - `sortable` – Whether the chip should include a drag handle
     *
     * @param Chippable $component The component that the chip represents
     * @param array $config Chip configuration
     * @return string
     * @since 5.0.0
     */
    public static function chipHtml(Chippable $component, array $config = []): string
    {
        $config += [
            'attributes' => [],
            'autoReload' => true,
            'class' => null,
            'hyperlink' => false,
            'id' => sprintf('chip-%s', mt_rand()),
            'inputName' => null,
            'inputValue' => null,
            'labelHtml' => null,
            'overrides' => [],
            'selectable' => false,
            'showActionMenu' => false,
            'showDescription' => false,
            'showHandle' => false,
            'showIndicators' => false,
            'showLabel' => true,
            'showStatus' => true,
            'showThumb' => true,
            'size' => self::CHIP_SIZE_SMALL,
            'sortable' => false,
        ];

        $config['showActionMenu'] = $config['showActionMenu'] && $component instanceof Actionable;
        $config['showHandle'] = $config['showHandle'] && $component instanceof Grippable;
        $config['showStatus'] = $config['showStatus'] && $component instanceof Statusable;
        $config['showThumb'] = $config['showThumb'] && ($component instanceof Thumbable || $component instanceof Iconic);
        $config['showIndicators'] = $config['showIndicators'] && $component instanceof Indicative;
        $config['showDescription'] = $config['showDescription'] && $component instanceof Describable;

        $color = $component instanceof Colorable ? $component->getColor() : null;

        $attributes = ArrayHelper::merge([
            'id' => $config['id'],
            'class' => [
                'chip',
                $config['size'],
                ...Html::explodeClass($config['class']),
            ],
            'style' => array_filter([
                '--custom-border-color' => $color?->cssVar(200),
                '--custom-bg-color' => $color?->cssVar(50),
                '--custom-text-color' => $color?->cssVar(900),
                '--custom-sel-bg-color' => $color?->cssVar(900),
            ]),
            'data' => array_filter([
                'type' => get_class($component),
                'id' => $component->getId(),
                'settings' => $config['autoReload'] ? [
                    'selectable' => $config['selectable'],
                    'id' => Craft::$app->getView()->namespaceInputId($config['id']),
                    'hyperlink' => $config['hyperlink'],
                    'showLabel' => $config['showLabel'],
                    'showHandle' => $config['showHandle'],
                    'showStatus' => $config['showStatus'],
                    'showThumb' => $config['showThumb'],
                    'showDescription' => $config['showDescription'],
                    'overrides' => $config['overrides'],
                    'size' => $config['size'],
                    'ui' => 'chip',
                ] : false,
            ]),
        ], $config['attributes']);

        $html = Html::beginTag('div', $attributes);

        if ($config['showThumb']) {
            if ($component instanceof Thumbable) {
                $thumbSize = $config['size'] === self::CHIP_SIZE_SMALL ? 30 : 120;
                $html .= $component->getThumbHtml($thumbSize) ?? '';
            } else {
                /** @var Chippable&Iconic $component */
                $icon = $component->getIcon();
                if ($icon || $icon === '0') {
                    $html .= Html::tag('div', static::iconSvg($icon), [
                        'class' => array_filter(['thumb', 'cp-icon', $color?->value]),
                    ]);
                }
            }
        }

        $html .= Html::beginTag('div', ['class' => 'chip-content']);

        if ($config['selectable']) {
            $html .= self::componentCheckboxHtml(sprintf('%s-label', $config['id']));
        }

        if ($config['showStatus']) {
            /** @var Chippable&Statusable $component */
            $html .= self::componentStatusIndicatorHtml($component) ?? '';
        }

        if (isset($config['labelHtml'])) {
            $html .= $config['labelHtml'];
        } elseif ($config['showLabel']) {
            $labelHtml = Html::encode($component->getUiLabel());
            if ($config['hyperlink'] && $component instanceof CpEditable) {
                $url = $component->getCpEditUrl();
                if ($url) {
                    $labelHtml = Html::a($labelHtml, $url);
                }
            }

            if ($config['showDescription']) {
                /** @var Chippable&Describable $component */
                $description = $component->getDescription();
                if ($description) {
                    $labelHtml .= Html::tag('span',
                        static::parseMarkdown(Html::encode($description)),
                        ['class' => 'info']);
                }
            }

            $labelHtml = Html::tag('div', $labelHtml);

            if ($config['showHandle']) {
                /** @var Chippable&Grippable $component */
                $handle = $component->getHandle();
                if ($handle) {
                    $labelHtml .= Html::tag('div', Html::encode($handle), [
                        'class' => ['smalltext', 'light', 'code'],
                    ]);
                }
            }
            if ($config['showIndicators']) {
                /** @var Chippable&Indicative $component */
                $indicators = $component->getIndicators();
                if (!empty($indicators)) {
                    $labelHtml .= Html::beginTag('div', ['class' => 'indicators']) .
                        implode('', array_map(function(array $indicator) {
                            $color = $indicator['iconColor'] ?? null;
                            if ($color instanceof Color) {
                                $color = $color->value;
                            }
                            return Html::tag('div', Cp::iconSvg($indicator['icon']), [
                                'class' => array_filter(['cp-icon', 'puny', $color]),
                                'title' => $indicator['label'],
                                'aria' => ['label' => $indicator['label']],
                            ]);
                        }, $indicators)) .
                        Html::endTag('div');
                }
            }
            $html .= Html::tag('div', $labelHtml, [
                'id' => sprintf('%s-label', $config['id']),
                'class' => 'chip-label',
            ]);
        }

        $html .= Html::beginTag('div', ['class' => 'chip-actions']);
        if ($config['showActionMenu']) {
            /** @var Chippable&Actionable $component */
            $html .= self::componentActionMenu($component);
        }
        if ($config['sortable']) {
            $html .= static::buttonHtml([
                'class' => ['chromeless', 'small', 'move-btn'],
                'icon' => 'move',
                'attributes' => [
                    'title' => Craft::t('app', 'Reorder'),
                    'aria' => [
                        'label' => Craft::t('app', 'Reorder'),
                    ],
                    'role' => 'none',
                    'tabindex' => '-1',
                ],
            ]);
        }
        $html .= Html::endTag('div'); // .chip-actions

        if ($config['inputName'] !== null) {
            $inputValue = $config['inputValue'] ?? $component->getId();
            $html .= Html::hiddenInput($config['inputName'], (string)$inputValue);
        }

        $html .= Html::endTag('div') . // .chip-content
            Html::endTag('div'); // .element

        return $html;
    }

    /**
     * Renders an element’s chip HTML.
     *
     * The following config settings can be passed to `$config`:
     *
     * - `attributes` – Any custom HTML attributes that should be set on the chip
     * - `autoReload` – Whether the chip should auto-reload itself when it’s saved
     * - `class` – Class name(s) that should be added to the container element
     * - `context` – The context the chip is going to be shown in (`index`, `field`, etc.)
     * - `hyperlink` – Whether the chip label should be hyperlinked to the element’s URL
     * - `id` – The chip’s `id` attribute
     * - `inputName` – The `name` attribute that should be set on a hidden input, if set
     * - `inputValue` – The `value` attribute that should be set on the hidden input, if `inputName` is set. Defaults to [[\craft\base\Identifiable::getId()`]].
     * - `labelHtml` – The label HTML, if it should be different from [[Chippable::getUiLabel()]]
     * - `overrides` – Any config overrides that should persist when the chip is re-rendered
     * - `selectable` – Whether the chip should include a checkbox input
     * - `showActionMenu` – Whether the chip should include an action menu
     * - `showDescription` – Whether the chip should include the element’s description
     * - `showDraftName` – Whether to show the draft name beside the label if the element is a draft of a published element
     * - `showHandle` – Whether the element’s handle should be show (only applies if the element implements [[Grippable]])
     * - `showIndicators` – Whether the element’s indicators should be shown (only applies if the element implements [[Indicative]])
     * - `showLabel` – Whether the element’s label should be shown
     * - `showProvisionalDraftLabel` – Whether an “Edited” badge should be added to the label if the element is a provisional draft
     * - `showStatus` – Whether the element’s status should be shown
     * - `showThumb` – Whether the element’s thumbnail should be shown
     * - `size` – The size of the chip (`small` or `large`)
     * - `sortable` – Whether the chip should include a drag handle
     *
     * @param ElementInterface $element The element to be rendered
     * @param array $config Chip configuration
     * @return string
     * @since 5.0.0
     */
    public static function elementChipHtml(ElementInterface $element, array $config = []): string
    {
        $config += [
            'attributes' => [],
            'autoReload' => true,
            'context' => 'index',
            'id' => sprintf('chip-%s', mt_rand()),
            'inputName' => null,
            'selectable' => false,
            'showActionMenu' => false,
            'showDraftName' => true,
            'showLabel' => true,
            'showProvisionalDraftLabel' => null,
            'showStatus' => true,
            'showThumb' => true,
            'size' => self::CHIP_SIZE_SMALL,
            'sortable' => false,
        ];

        $config['attributes'] = ArrayHelper::merge(
            self::baseElementAttributes($element, $config),
            [
                'data' => array_filter([
                    'settings' => $config['autoReload'] ? [
                        'context' => $config['context'],
                        'showDraftName' => $config['showDraftName'],
                        'showProvisionalDraftLabel' => $config['showProvisionalDraftLabel'],
                    ] : false,
                ]),
            ],
            $config['attributes'],
        );

        $config['showStatus'] = $config['showStatus'] && ($element->getIsDraft() || $element->showStatusIndicator());

        if ($config['showLabel']) {
            $config['labelHtml'] = self::elementLabelHtml(
                $element,
                $config,
                $config['attributes'],
                fn() => $element->getChipLabelHtml(),
            );
        }

        if (
            ($config['showProvisionalDraftLabel'] ?? $config['showLabel']) &&
            ($element->isProvisionalDraft || $element->hasProvisionalChanges)
        ) {
            $config['labelHtml'] = ($config['labelHtml'] ?? '') . self::changeStatusLabelHtml();
        }

        if ($config['inputName'] !== null && $element->isProvisionalDraft) {
            $config['inputValue'] = $element->getCanonicalId();
        }

        $html = static::chipHtml($element, $config);

        // Fire a 'defineElementChipHtml' event
        if (Event::hasHandlers(self::class, self::EVENT_DEFINE_ELEMENT_CHIP_HTML)) {
            $event = new DefineElementHtmlEvent([
                'element' => $element,
                'context' => $config['context'],
                'html' => $html,
            ]);
            Event::trigger(self::class, self::EVENT_DEFINE_ELEMENT_CHIP_HTML, $event);
            return $event->html;
        }

        return $html;
    }

    /**
     * Renders an element’s card HTML.
     *
     * The following config settings can be passed to `$config`:
     *
     * - `attributes` – Any custom HTML attributes that should be set on the card
     * - `autoReload` – Whether the card should auto-reload itself when it’s saved
     * - `context` – The context the card is going to be shown in (`index`, `field`, etc.)
     * - `hyperlink` – Whether the card label should be hyperlinked to the element’s URL
     * - `id` – The card’s `id` attribute
     * - `inputName` – The `name` attribute that should be set on the hidden input, if `context` is set to `field`
     * - `selectable` – Whether the card should include a checkbox input
     * - `showActionMenu` – Whether the card should include an action menu
     * - `showEditButton` – Whether the card should include an edit button
     * - `sortable` – Whether the card should include a drag handle
     *
     * @param ElementInterface $element The element to be rendered
     * @param array $config Card configuration
     * @return string
     * @since 5.0.0
     */
    public static function elementCardHtml(ElementInterface $element, array $config = []): string
    {
        $config += [
            'attributes' => [],
            'autoReload' => true,
            'context' => 'index',
            'hyperlink' => false,
            'id' => sprintf('card-%s', mt_rand()),
            'inputName' => null,
            'selectable' => false,
            'showActionMenu' => false,
            'showEditButton' => true,
            'sortable' => false,
        ];

        $showEditButton = $config['showEditButton'] && Craft::$app->getElements()->canView($element);

        if ($showEditButton) {
            $editId = sprintf('action-edit-%s', mt_rand());
            $view = Craft::$app->getView();
            $view->registerJsWithVars(fn($id, $elementType, $settings, $cpEditUrl) => <<<JS
$('#' + $id).on('activate', (ev) => {
  if ($cpEditUrl && Garnish.isCtrlKeyPressed(ev.originalEvent)) {
    window.open($cpEditUrl);
  } else {
    // focus on the button so that when the slideout is closed, it's returned to the button
    $(ev.currentTarget).focus();

    const settings = $settings;
    // if settings have draftId but the replaced card doesn't have the data-draft-id attribute anymore,
    // remove the draftId from the settings before creating element editor, so the correct element can be retrieved
    if (settings.draftId && !Garnish.hasAttr($(ev.currentTarget).parents('.card'), 'data-draft-id')) {
      delete settings.draftId;
    }
    Craft.createElementEditor($elementType, settings);
  }
});
JS, [
                $view->namespaceInputId($editId),
                $element::class,
                [
                    'elementId' => $element->isProvisionalDraft ? $element->getCanonicalId() : $element->id,
                    'draftId' => $element->isProvisionalDraft ? null : $element->draftId,
                    'revisionId' => $element->revisionId,
                    'siteId' => $element->siteId,
                    'ownerId' => $element instanceof NestedElementInterface ? $element->getOwnerId() : null,
                ],
                'cpEditUrl' => $element->getCpEditUrl(),
            ]);
        }

        if ($element->getIsRevision()) {
            $config['showActionMenu'] = false;
            $config['selectable'] = false;
        }

        $color = $element instanceof Colorable ? $element->getColor() : null;

        $classes = ['card'];
        if ($element->hasErrors()) {
            $classes[] = 'error';
        }

        $thumb = $element->getThumbHtml(120);
        $thumbAlignment = $element->getFieldLayout()?->getCardThumbAlignment() ?? 'end';

        if ($thumb) {
            if ($thumbAlignment) {
                $classes[] = 'thumb-' . $thumbAlignment;
            }
        }

        $attributes = ArrayHelper::merge(
            self::baseElementAttributes($element, $config),
            [
                'class' => $classes,
                'style' => array_filter([
                    '--custom-border-color' => $color?->cssVar(200),
                    '--custom-titlebar-bg-color' => $color?->cssVar(100),
                    '--custom-bg-color' => $color?->cssVar(50),
                    '--custom-text-color' => $color?->cssVar(900),
                    '--custom-sel-titlebar-bg-color' => $color?->cssVar(900),
                    '--custom-sel-bg-color' => $color?->cssVar(800),
                ]),
                'data' => array_filter([
                    'settings' => $config['autoReload'] ? [
                        'hyperlink' => $config['hyperlink'],
                        'selectable' => $config['selectable'],
                        'context' => $config['context'],
                        'id' => Craft::$app->getView()->namespaceInputId($config['id']),
                        'ui' => 'card',
                    ] : false,
                ]),
            ],
            $config['attributes'],
        );

        $headingContent = self::elementLabelHtml($element, $config, $attributes, fn() => Html::encode($element->getUiLabel()));
        $bodyContent = $element->getCardBodyHtml() ?? '';

        $labels = array_filter([
            $element->showStatusIndicator() ? static::componentStatusLabelHtml($element) : null,
            $element->isProvisionalDraft || $element->hasProvisionalChanges ? self::changeStatusLabelHtml() : null,
        ]);

        if (!empty($labels)) {
            $bodyContent .= Html::ul($labels, [
                'class' => ['flex', 'gap-xs'],
                'encode' => false,
            ]);
        }

        // is this a nested element that will end up replacing its canonical
        // counterpart when the owner is saved?
        if (
            $element instanceof NestedElementInterface &&
            $element->getOwnerId() !== null &&
            $element->getOwnerId() === $element->getPrimaryOwnerId() &&
            !$element->getIsDraft() &&
            !$element->getIsRevision() &&
            $element->getOwner()->getIsDerivative()
        ) {
            if ($element->getIsCanonical()) {
                // this element was created for the owner
                $statusLabel = Craft::t('app', 'This is a new {type}.', [
                    'type' => $element::lowerDisplayName(),
                ]);
            } else {
                // this element is a derivative of another element owned by the canonical owner
                $statusLabel = Craft::t('app', 'This {type} has been edited.', [
                    'type' => $element::lowerDisplayName(),
                ]);
            }

            $status = Html::beginTag('div', [
                    'class' => ['status-badge', AttributeStatus::Modified->value],
                    'title' => $statusLabel,
                ]) .
                Html::tag('span', $statusLabel, [
                    'class' => 'visually-hidden',
                ]) .
                Html::endTag('div');
        }

        $icon = $element instanceof Iconic ? $element->getIcon() : null;
        $title = $element->getCardTitle();

        $html = Html::beginTag('div', $attributes) .
            Html::beginTag('div', ['class' => 'card-titlebar']) .
            Html::beginTag('div', [
                'class' => ['flex', 'flex-nowrap', 'flex-gap-s'],
            ]) .
            ($icon ? Html::tag('div', Cp::iconSvg($icon), [
                'class' => array_filter([
                    'cp-icon',
                    'small',
                    $element instanceof Colorable ? $element->getColor()?->value : null,
                ]),
                'aria' => ['hidden' => true],
            ]) : '') .
            ($title ? Html::tag('div', Html::encode($title), ['class' => 'card-titlebar-label']) : '') .
            Html::endTag('div') . // .flex
            ($status ?? '') .
            Html::beginTag('div', ['class' => 'card-actions-container']) .
            Html::beginTag('div', ['class' => 'card-actions']) .
            ($config['selectable'] ? self::componentCheckboxHtml(sprintf('%s-label', $config['id'])) : '') .
            ($showEditButton ? static::buttonHtml([
                'class' => ['chromeless', 'small', 'edit-btn'],
                'icon' => 'edit',
                'attributes' => [
                    'id' => $editId,
                    'title' => StringHelper::upperCaseFirst(Craft::t('app', 'Edit {type}', [
                        'type' => $element::lowerDisplayName(),
                    ])),
                    'aria' => [
                        'label' => StringHelper::upperCaseFirst(Craft::t('app', 'Edit {type}', [
                            'type' => $element::lowerDisplayName(),
                        ])),
                    ],
                ],
            ]) : '') .
            ($config['showActionMenu'] ? self::componentActionMenu($element, !$showEditButton) : '') .
            ($config['sortable'] ? static::buttonHtml([
                'class' => ['chromeless', 'small', 'move-btn'],
                'icon' => 'move',
                'attributes' => [
                    'title' => Craft::t('app', 'Reorder'),
                    'aria' => [
                        'label' => Craft::t('app', 'Reorder'),
                    ],
                    'role' => 'none',
                    'tabindex' => '-1',
                ],
            ]) : '') .
            Html::endTag('div') . // .card-actions
            Html::endTag('div') . // .card-actions-container
            Html::endTag('div') . // .card-titlebar
            Html::beginTag('div', ['class' => 'card-main']);

        $contentHtml =
            Html::beginTag('div', ['class' => 'card-content']) .
            ($headingContent !== '' ? Html::tag('div', $headingContent, ['class' => 'card-heading']) : '') .
            ($bodyContent !== '' ? Html::tag('div', $bodyContent, ['class' => 'card-body']) : '') .
            Html::endTag('div'); // .card-content

        $thumbHtml = $element->getThumbHtml(120);

        if ($thumbAlignment === 'start') {
            $html .= $thumbHtml . $contentHtml;
        } else {
            $html .= $contentHtml . $thumbHtml;
        }

        $html .=
            Html::endTag('div'); // .card-main

        if ($config['context'] === 'field' && $config['inputName'] !== null) {
            $inputValue = $element->isProvisionalDraft ? $element->getCanonicalId() : $element->id;
            $html .= Html::hiddenInput($config['inputName'], (string)$inputValue);
        }

        $html .= Html::endTag('div'); // .card

        // Fire a 'defineElementCardHtml' event
        if (Event::hasHandlers(self::class, self::EVENT_DEFINE_ELEMENT_CARD_HTML)) {
            $event = new DefineElementHtmlEvent([
                'element' => $element,
                'context' => $config['context'],
                'html' => $html,
            ]);
            Event::trigger(self::class, self::EVENT_DEFINE_ELEMENT_CARD_HTML, $event);
            return $event->html;
        }

        return $html;
    }

    /**
     * Renders status indicator HTML.
     *
     * When the `status` is equal to "draft" the draft icon will be displayed. The attributes passed as the
     * second argument should be a status definition from [[\craft\base\ElementInterface::statuses]]
     *
     * @param string $status Status string
     * @param array $attributes Attributes to be passed along.
     * @return string|null
     * @since 5.0.0
     */
    public static function statusIndicatorHtml(string $status, array $attributes = []): ?string
    {
        $attributes += [
            'color' => null,
            'label' => ucfirst($status),
            'class' => $status,
        ];

        if ($status === 'draft') {
            return Html::tag('span', '', [
                'data' => ['icon' => 'draft'],
                'class' => 'icon',
                'role' => 'img',
                'aria' => [
                    'label' => sprintf('%s %s',
                        Craft::t('app', 'Status:'),
                        $attributes['label'] ?? Craft::t('app', 'Draft'),
                    ),
                ],
            ]);
        }

        if ($attributes['color'] instanceof Color) {
            $attributes['color'] = $attributes['color']->value;
        }

        $options = [
            'class' => array_filter([
                'status',
                $attributes['class'],
                $attributes['color'],
            ]),
        ];

        if ($attributes['label'] !== null) {
            $options['role'] = 'img';
            $options['aria']['label'] = sprintf('%s %s', Craft::t('app', 'Status:'), $attributes['label']);
        }

        return Html::tag('span', '', $options);
    }

    /**
     * Renders status indicator HTML for a [[Statusable]] component.
     *
     * @param Statusable $component
     * @return string|null
     * @since 5.2.0
     */
    public static function componentStatusIndicatorHtml(Statusable $component): ?string
    {
        $status = $component->getStatus();

        if ($status === 'draft') {
            return self::statusIndicatorHtml('draft');
        }

        $statusDef = $component::statuses()[$status] ?? [];

        // Just to give the `statusIndicatorHtml` clean types
        if (is_string($statusDef)) {
            $statusDef = ['label' => $statusDef];
        }

        return self::statusIndicatorHtml($status, $statusDef);
    }

    /**
     * Renders status label HTML.
     *
     * When the `status` is equal to "draft" the draft icon will be displayed. The attributes passed as the
     * second argument should be a status definition from [[\craft\base\ElementInterface::statuses]]
     *
     * @param array $config Config options
     * @return string|null
     * @since 5.2.0
     */
    public static function statusLabelHtml(array $config = []): ?string
    {
        $config += [
            'color' => Color::Gray->value,
            'icon' => null,
            'label' => null,
            'indicatorClass' => null,
        ];

        if ($config['color'] instanceof Color) {
            $config['color'] = $config['color']->value;
        }

        if ($config['icon']) {
            $html = Html::tag('span', static::iconSvg($config['icon']), [
                'class' => ['cp-icon', 'puny', $config['color']],
            ]);
        } else {
            $html = static::statusIndicatorHtml($config['color'], [
                'label' => null,
                'class' => $config['indicatorClass'] ?? $config['color'],
            ]);
        }

        if ($config['label']) {
            $html .= ' ' . Html::tag('span', Html::encode($config['label']), ['class' => 'status-label-text']);
        }

        return Html::tag('span', $html, [
            'class' => array_filter([
                'status-label',
                $config['color'],
            ]),
        ]);
    }

    private static function changeStatusLabelHtml(): string
    {
        return static::statusLabelHtml([
            'color' => Color::Blue,
            'icon' => 'pen-circle',
            'label' => Craft::t('app', 'Edited'),
        ]);
    }

    /**
     * Renders status label HTML for a [[Statusable]] component.
     *
     * @param Statusable $component
     * @return string|null
     * @since 5.2.0
     */
    public static function componentStatusLabelHtml(Statusable $component): ?string
    {
        $status = $component->getStatus();

        if (!$status) {
            return null;
        }

        $config = $component::statuses()[$status] ?? [];
        if (is_string($config)) {
            $config = ['label' => $config];
        }
        $config['color'] ??= Color::tryFromStatus($status) ?? Color::Gray;
        $config['label'] ??= match ($status) {
            'draft' => Craft::t('app', 'Draft'),
            default => ucfirst($status),
        };
        $config['indicatorClass'] = match ($status) {
            'pending', 'off', 'suspended', 'expired', 'disabled', 'inactive' => $status,
            default => $config['color']->value,
        };

        return self::statusLabelHtml($config);
    }

    private static function baseElementAttributes(ElementInterface $element, array $config): array
    {
        $elementsService = Craft::$app->getElements();
        $user = Craft::$app->getUser()->getIdentity();
        $editable = $user && $elementsService->canView($element, $user);

        return ArrayHelper::merge(
            Html::normalizeTagAttributes($element->getHtmlAttributes($config['context'])),
            [
                'id' => $config['id'],
                'class' => array_filter([
                    'element',
                    $config['context'] === 'field' ? 'removable' : null,
                    ($config['context'] === 'field' && $element->hasErrors()) ? 'error' : null,
                ]),
                'data' => array_filter([
                    'type' => get_class($element),
                    'id' => $element->isProvisionalDraft ? $element->getCanonicalId() : $element->id,
                    'draft-id' => $element->isProvisionalDraft ? null : $element->draftId,
                    'revision-id' => $element->revisionId,
                    'field-id' => $element instanceof NestedElementInterface ? $element->getField()?->id : null,
                    'primary-owner-id' => $element instanceof NestedElementInterface ? $element->getPrimaryOwnerId() : null,
                    'owner-id' => $element instanceof NestedElementInterface ? $element->getOwnerId() : null,
                    'owner-is-canonical' => self::elementOwnerIsCanonical($element),
                    'site-id' => $element->siteId,
                    'is-unpublished-draft' => $element->getIsUnpublishedDraft(),
                    'status' => $element->getStatus(),
                    'label' => (string)$element,
                    'url' => $element->getUrl(),
                    'cp-url' => $editable ? $element->getCpEditUrl() : null,
                    'level' => $element->level,
                    'trashed' => $element->trashed,
                    'editable' => $editable,
                    'savable' => $editable && self::contextIsAdministrative($config['context']) && $elementsService->canSave($element, $user),
                    'duplicatable' => $editable && self::contextIsAdministrative($config['context']) && $elementsService->canDuplicate($element, $user),
                    'duplicatable-as-draft' => $editable && self::contextIsAdministrative($config['context']) && $elementsService->canDuplicateAsDraft($element, $user),
                    'copyable' => $editable && self::contextIsAdministrative($config['context']) && $elementsService->canCopy($element, $user),
                    'deletable' => $editable && self::contextIsAdministrative($config['context']) && $elementsService->canDelete($element, $user),
                    'deletable-for-site' => (
                        $editable &&
                        self::contextIsAdministrative($config['context']) &&
                        ElementHelper::isMultiSite($element) &&
                        $elementsService->canDeleteForSite($element, $user)
                    ),
                ]),
            ],
        );
    }

    private static function elementOwnerIsCanonical(ElementInterface $element): bool
    {
        // figure out if the element has any non-canonical owners
        $ownerIsCanonical = false;

        do {
            $owner = null;
            try {
                $owner = $element instanceof NestedElementInterface ? $element->getPrimaryOwner() : null;
            } catch (InvalidConfigException) {
            }
            if (!$owner) {
                break;
            }
            $ownerIsCanonical = $owner->getIsCanonical();
            if (!$ownerIsCanonical) {
                break;
            }
            $element = $owner;
        } while (true);

        return $ownerIsCanonical;
    }

    private static function componentCheckboxHtml(string $labelId): string
    {
        return Html::tag('div', options: [
            'class' => 'checkbox',
            'title' => Craft::t('app', 'Select'),
            'role' => 'checkbox',
            'tabindex' => '0',
            'aria' => [
                'checked' => 'false',
                'labelledby' => $labelId,
            ],
        ]);
    }

    private static function elementLabelHtml(ElementInterface $element, array $config, array $attributes, callable $uiLabel): string
    {
        $content = implode('', array_map(
                fn(string $segment) => Html::tag('span', Html::encode($segment), ['class' => 'segment']),
                $element->getUiLabelPath()
            )) .
            $uiLabel();

        // show the draft name?
        if (($config['showDraftName'] ?? true) && $element->getIsDraft() && !$element->isProvisionalDraft && !$element->getIsUnpublishedDraft()) {
            /** @var DraftBehavior&ElementInterface $element */
            $content .= Html::tag('span', $element->draftName ?: Craft::t('app', 'Draft'), [
                'class' => 'context-label',
            ]);
        }

        // the inner span is needed for `text-overflow: ellipsis` (e.g. within breadcrumbs)
        if ($content !== '') {
            if (
                ($config['hyperlink'] ?? false) &&
                !$element->trashed &&
                $config['context'] !== 'modal' &&
                ($url = $attributes['data']['cp-url'] ?? null)
            ) {
                $content = Html::tag('a', Html::tag('span', $content), [
                    'class' => ['label-link'],
                    'href' => $url,
                ]);
            } else {
                $content = Html::tag('span', $content, ['class' => 'label-link']);
            }
        }

        if ($config['context'] === 'field' && $element->hasErrors()) {
            $content .= Html::tag('span', '', [
                'data' => ['icon' => 'triangle-exclamation'],
                'aria' => ['label' => Craft::t('app', 'Error')],
                'role' => 'img',
            ]);
        }

        if ($content === '') {
            return '';
        }

        return Html::tag('craft-element-label', $content, [
            'id' => sprintf('%s-label', $config['id']),
            'class' => 'label',
        ]);
    }

    private static function componentActionMenu(Actionable $component, bool $withEdit = true): string
    {
        return Craft::$app->getView()->namespaceInputs(
            function() use ($component, $withEdit): string {
                $actionMenuItems = array_filter(
                    $component->getActionMenuItems(),
                    fn(array $item) => $item['showInChips'] ?? !($item['destructive'] ?? false)
                );

                foreach ($actionMenuItems as $i => &$item) {
                    if (str_starts_with($item['id'] ?? '', 'action-edit-')) {
                        if (!$withEdit) {
                            unset($actionMenuItems[$i]);
                        } else {
                            $item['attributes']['data']['edit-action'] = true;
                        }
                    } elseif (str_starts_with($item['id'] ?? '', 'action-copy-')) {
                        $item['attributes']['data']['copy-action'] = true;
                    }
                }

                return static::disclosureMenu($actionMenuItems, [
                    'hiddenLabel' => Craft::t('app', 'Actions'),
                    'buttonAttributes' => [
                        'class' => array_keys(array_filter([
                            'action-btn' => true,
                            'small' => true,
                            'hidden' => empty($actionMenuItems),
                        ])),
                        'removeClass' => 'menubtn',
                        'data' => ['icon' => 'ellipsis'],
                    ],
                    'omitIfEmpty' => false,
                ]);
            },
            sprintf('action-menu-%s', mt_rand()),
        );
    }

    /**
     * Renders an element’s chip HTML.
     *
     * @param ElementInterface $element The element to be rendered
     * @param string $context The context the chip is going to be shown in (`index`, `field`, etc.)
     * @param string $size The size of the chip (`small` or `large`)
     * @param string|null $inputName The `name` attribute that should be set on the hidden input, if `$context` is set to `field`
     * @param bool $showStatus Whether the element status should be shown (if the element type has statuses)
     * @param bool $showThumb Whether the element thumb should be shown (if the element has one)
     * @param bool $showLabel Whether the element label should be shown
     * @param bool $showDraftName Whether to show the draft name beside the label if the element is a draft of a published element
     * @param bool $single Whether the input name should omit the trailing `[]`
     * @param bool $autoReload Whether the element should auto-reload itself when it’s saved
     * @return string
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
        $html = static::elementChipHtml($element, [
            'autoReload' => $autoReload,
            'context' => $context,
            'inputName' => $inputName . ($single ? '' : '[]'),
            'showDraftName' => $showDraftName,
            'showLabel' => $showLabel,
            'showStatus' => $showStatus,
            'showThumb' => $showThumb,
            'size' => $size,
        ]);

        // Fire a 'defineElementInnerHtml' event
        if (Event::hasHandlers(self::class, self::EVENT_DEFINE_ELEMENT_INNER_HTML)) {
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
            Event::trigger(self::class, self::EVENT_DEFINE_ELEMENT_INNER_HTML, $event);
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
     * @param string $size The size of the element (`small` or `large`)
     * @param bool $showStatus Whether the element status should be shown (if the element type has statuses)
     * @param bool $showThumb Whether the element thumb should be shown (if the element has one)
     * @param bool $showLabel Whether the element label should be shown
     * @param bool $showDraftName Whether to show the draft name beside the label if the element is a draft of a published element
     * @return string
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
        if (empty($elements)) {
            return '';
        }

        $first = array_shift($elements);
        $html = Html::beginTag('div', ['class' => ['inline-chips', 'no-truncate']]) .
            static::elementChipHtml($first, [
                'showDraftName' => $showDraftName,
                'showLabel' => $showLabel,
                'showStatus' => $showStatus,
                'showThumb' => $showThumb,
                'size' => $size,
            ]);

        if (!empty($elements)) {
            $otherHtml = '';
            foreach ($elements as $other) {
                $otherHtml .= static::elementChipHtml($other, [
                    'showDraftName' => $showDraftName,
                    'showLabel' => $showLabel,
                    'showStatus' => $showStatus,
                    'showThumb' => $showThumb,
                    'size' => $size,
                ]);
            }
            $html .= Html::tag('span', '+' . Craft::$app->getFormatter()->asInteger(count($elements)), [
                'title' => implode(', ', array_map(fn(ElementInterface $element) => $element->id, $elements)),
                'class' => 'btn small',
                'role' => 'button',
                'tabindex' => 0,
                'data' => [
                    'other' => Json::encode($otherHtml),
                ],
                'aria-expanded' => 'false',
                'onkeydown' => 'Craft.cp.previewCountBadge(event, this, true)', // have to use keydown or the page will scroll
                'onclick' => 'Craft.cp.previewCountBadge(event, this, true)',
            ]);
        }

        $html .= Html::endTag('div'); // .inline-chips
        return $html;
    }

    /**
     * Returns component preview HTML, for a list of elements.
     *
     * @param Chippable[] $components The components
     * @param array $chipConfig
     * @return string
     * @since 5.4.0
     */
    public static function componentPreviewHtml(array $components, array $chipConfig = []): string
    {
        if (empty($components)) {
            return '';
        }

        $first = array_shift($components);
        $html = Html::beginTag('div', ['class' => 'inline-chips']) .
            static::chipHtml($first, $chipConfig);

        if (!empty($components)) {
            $otherHtml = '';
            foreach ($components as $other) {
                $otherHtml .= static::chipHtml($other, $chipConfig);
            }
            $html .= Html::tag('span', '+' . Craft::$app->getFormatter()->asInteger(count($components)), [
                'title' => implode(', ', array_map(fn(Chippable $component) => $component->getId(), $components)),
                'class' => 'btn small',
                'role' => 'button',
                'tabindex' => '0',
                'data' => [
                    'other' => Json::encode($otherHtml),
                ],
                'onkeydown' => 'Craft.cp.previewCountBadge(event, this, false)', // have to use keydown or the page will scroll
                'onclick' => 'Craft.cp.previewCountBadge(event, this, false)',
            ]);
        }

        $html .= Html::endTag('div'); // .inline-chips
        return $html;
    }

    /**
     * Returns the HTML for an element index.
     *
     * @param class-string<ElementInterface> $elementType
     * @param array $config
     * @return string
     * @since 5.0.0
     */
    public static function elementIndexHtml(string $elementType, array $config = []): string
    {
        $config += [
            'class' => null,
            'context' => 'index',
            'defaultSort' => null,
            'defaultTableColumns' => null,
            'defaultViewMode' => 'table',
            'fieldLayouts' => [],
            'id' => sprintf('element-index-%s', mt_rand()),
            'jsSettings' => [],
            'registerJs' => true,
            'showSiteMenu' => 'auto',
            'showStatusMenu' => 'auto',
            'statuses' => null,
            'sources' => null,
        ];

        if ($config['showStatusMenu'] !== 'auto') {
            $config['showStatusMenu'] = (bool)$config['showStatusMenu'];
        }

        $config['showSiteMenu'] = $config['showSiteMenu'] === 'auto'
            ? $elementType::isLocalized()
            : (bool)$config['showSiteMenu'];

        $siteIds = Craft::$app->getSites()->getEditableSiteIds();

        $sortOptions = Collection::make($elementType::sortOptions())
            ->map(fn($option, $key) => [
                'label' => $option['label'] ?? $option,
                'attr' => $option['attribute'] ?? $option['orderBy'] ?? $key,
                'defaultDir' => $option['defaultDir'] ?? 'asc',
            ])
            ->values()
            ->all();
        $sortOptionsKey = 'baseSortOptions';

        $tableColumns = Craft::$app->getElementSources()->getAvailableTableAttributes($elementType);

        if ($config['sources'] !== false) {
            if (is_array($config['sources'])) {
                $indexedSourceKeys = array_flip($config['sources']);
                $allSources = Craft::$app->getElementSources()->getSources($elementType, $config['context']);
                $sources = [];

                foreach ($allSources as $source) {
                    if ($source['type'] === ElementSources::TYPE_HEADING) {
                        $sources[] = $source;
                    } elseif (isset($indexedSourceKeys[$source['key']])) {
                        $sources[] = $source;
                        // Unset so we can keep track of which keys couldn't be found
                        unset($indexedSourceKeys[$source['key']]);
                    }
                }

                $sources = ElementSources::filterExtraHeadings($sources);

                // Did we miss any source keys? (This could happen if some are nested)
                if (!empty($indexedSourceKeys)) {
                    foreach (array_keys($indexedSourceKeys) as $key) {
                        $source = ElementHelper::findSource($elementType, $key, $config['context']);
                        if ($source !== null) {
                            // If it was listed after another source key that made it in, insert it there
                            $pos = array_search($key, $config['sources']);
                            $inserted = false;
                            if ($pos > 0) {
                                $prevKey = $config['sources'][$pos - 1];
                                foreach ($sources as $i => $otherSource) {
                                    if (($otherSource['key'] ?? null) === $prevKey) {
                                        array_splice($sources, $i + 1, 0, [$source]);
                                        $inserted = true;
                                        break;
                                    }
                                }
                            }
                            if (!$inserted) {
                                $sources[] = $source;
                            }
                        }
                    }
                }
            } else {
                $sources = Craft::$app->getElementSources()->getSources($elementType, $config['context']);
            }

            // Show the sidebar if there are at least two (non-heading) sources
            $showSidebar = (function() use ($sources): bool {
                $foundSource = false;
                foreach ($sources as $source) {
                    if ($source['type'] !== ElementSources::TYPE_HEADING) {
                        if ($foundSource || !empty($source['nested'])) {
                            return true;
                        }
                        $foundSource = true;
                    }
                }
                return false;
            })();
        } else {
            $showSidebar = false;
            $sources = [
                [
                    'type' => ElementSources::TYPE_NATIVE,
                    'key' => '__IMP__',
                    'label' => Craft::t('app', 'All elements'),
                    'hasThumbs' => $elementType::hasThumbs(),
                    'defaultSort' => $config['defaultSort'],
                    'defaultViewMode' => $config['defaultViewMode'],
                    'fieldLayouts' => $config['fieldLayouts'],
                ],
            ];

            // if field layouts were supplied, merge in additional table columns and sort columns
            if (!empty($config['fieldLayouts'])) {
                $elementSourcesService = Craft::$app->getElementSources();
                $sortOptions = array_merge(
                    $sortOptions,
                    array_map(fn(array $option) => [
                        'label' => $option['label'],
                        'attr' => $option['attribute'],
                        'defaultDir' => $option['defaultDir'],
                    ], $elementSourcesService->getSortOptionsForFieldLayouts($config['fieldLayouts'])),
                );
                // Don't let sources.twig merge sortOptions with anything else!
                $sortOptionsKey = 'sortOptions';

                $tableColumns = array_merge(
                    $tableColumns,
                    $elementSourcesService->getTableAttributesForFieldLayouts($config['fieldLayouts']),
                );
            }
        }

        // If all the sources are site-specific, filter out any unneeded site IDs
        if (
            $config['showSiteMenu'] &&
            ArrayHelper::onlyContains($sources, fn(array $source) => $source['type'] === 'heading' || isset($source['sites']))
        ) {
            $representedSiteIds = [];
            foreach ($sources as $source) {
                if (isset($source['sites'])) {
                    foreach ($source['sites'] as $siteId) {
                        $representedSiteIds[$siteId] = true;
                    }
                }
            }
            $siteIds = array_filter($siteIds, fn(int $siteId) => isset($representedSiteIds[$siteId]));
        }

        $view = Craft::$app->getView();

        if ($config['registerJs']) {
            $view->registerJsWithVars(fn($elementType, $id, $settings) => <<<JS
Craft.createElementIndex($elementType, $('#' + $id), $settings);
JS, [
                $elementType,
                $view->namespaceInputId($config['id']),
                array_merge(
                    [
                        'context' => $config['context'],
                        'namespace' => $view->getNamespace(),
                        'prevalidate' => $config['prevalidate'] ?? false,
                    ],
                    $config['jsSettings']
                ),
            ]);
        }

        $html = Html::beginTag('div', [
                'id' => $config['id'],
                'class' => array_merge(
                    ['element-index'],
                    ($showSidebar ? ['has-sidebar'] : []),
                    ($config['context'] === 'embedded-index' ? ['pane', 'padding-s', 'hairline'] : []),
                    Html::explodeClass($config['class']),
                ),
            ]) .
            Html::beginTag('div', [
                'class' => array_filter([
                    'sidebar',
                    (!$showSidebar ? 'hidden' : null),
                ]),
            ]) .
            Html::tag('nav', $view->renderTemplate('_elements/sources', [
                'elementType' => $elementType,
                'sources' => $sources,
                $sortOptionsKey => $sortOptions,
                'tableColumns' => $tableColumns,
                'defaultTableColumns' => $config['defaultTableColumns'],
            ], View::TEMPLATE_MODE_CP)) .
            Html::endTag('div') .
            Html::beginTag('div', ['class' => 'main']) .
            Html::beginTag('div', ['class' => ['toolbar', 'flex']]) .
            $view->renderTemplate('_elements/toolbar', [
                'elementType' => $elementType,
                'context' => $config['context'],
                'showStatusMenu' => $config['showStatusMenu'],
                'elementStatuses' => $config['statuses'],
                'showSiteMenu' => $config['showSiteMenu'],
                'siteIds' => $siteIds,
                'canHaveDrafts' => $elementType::hasDrafts(),
            ], View::TEMPLATE_MODE_CP) .
            Html::endTag('div') . // .toolbar
            Html::tag('div', options: ['class' => 'elements']) .
            Html::endTag('div'); // .main

        if (self::contextIsAdministrative($config['context'])) {
            $html .= Html::beginTag('div', [
                    'class' => ['footer', 'flex', 'flex-justify'],
                ]) .
                $view->renderTemplate('_elements/footer', templateMode: View::TEMPLATE_MODE_CP) .
                Html::endTag('div'); // .footer
        }

        return $html .
            Html::endTag('div'); // .element-index;
    }

    private static function contextIsAdministrative(string $context): bool
    {
        return in_array($context, ['index', 'embedded-index', 'field']);
    }

    /**
     * Renders a field’s HTML, for the given input HTML or a template.
     *
     * @param string|callable $input The input HTML or template path. If passing a template path, it must begin with `template:`.
     * @param array $config
     * @return string
     * @throws TemplateLoaderException if $input begins with `template:` and is followed by an invalid template path
     * @throws InvalidArgumentException if `$config['siteId']` is invalid
     * @since 3.5.8
     */
    public static function fieldHtml(string|callable $input, array $config = []): string
    {
        $attribute = $config['attribute'] ?? $config['id'] ?? null;
        $id = $config['id'] ??= 'field' . mt_rand();
        $labelId = $config['labelId'] ?? "$id-label";
        $instructionsId = $config['instructionsId'] ?? "$id-instructions";
        $tipId = $config['tipId'] ?? "$id-tip";
        $warningId = $config['warningId'] ?? "$id-warning";
        $errorsId = $config['errorsId'] ?? "$id-errors";
        $statusId = $config['statusId'] ?? "$id-status";

        $instructions = $config['instructions'] ?? null;
        $tip = $config['tip'] ?? null;
        $warning = $config['warning'] ?? null;
        $errors = $config['errors'] ?? null;
        $status = $config['status'] ?? null;
        $disabled = $config['disabled'] ?? false;
        $static = $config['static'] ?? false;

        $fieldset = $config['fieldset'] ?? false;
        $fieldId = $config['fieldId'] ?? "$id-field";
        $label = $config['fieldLabel'] ?? $config['label'] ?? null;

        $data = $config['data'] ?? [];

        if ($label === '__blank__') {
            $label = null;
        }

        $siteId = Craft::$app->getIsMultiSite() && isset($config['siteId']) ? (int)$config['siteId'] : null;

        if (is_callable($input) || str_starts_with($input, 'template:')) {
            // Set labelledBy and describedBy values in case the input template supports it
            if (!isset($config['labelledBy']) && $label) {
                $config['labelledBy'] = $labelId;
            }
            if (!isset($config['describedBy'])) {
                $descriptorIds = array_filter([
                    $errors ? $errorsId : null,
                    $status ? $statusId : null,
                    $instructions ? $instructionsId : null,
                    $tip ? $tipId : null,
                    $warning ? $warningId : null,
                ]);
                $config['describedBy'] = $descriptorIds ? implode(' ', $descriptorIds) : null;
            }

            if (is_callable($input)) {
                $input = $input($config);
            } else {
                $input = static::renderTemplate(substr($input, 9), $config);
            }
        }

        if ($siteId) {
            $site = Craft::$app->getSites()->getSiteById($siteId);
            if (!$site) {
                throw new InvalidArgumentException("Invalid site ID: $siteId");
            }
        } else {
            $site = null;
        }

        $required = (bool)($config['required'] ?? false);
        $instructionsPosition = $config['instructionsPosition'] ?? 'before';
        $orientation = $config['orientation'] ?? ($site ? $site->getLocale() : Craft::$app->getLocale())->getOrientation();
        $translatable = Craft::$app->getIsMultiSite() ? ($config['translatable'] ?? ($site !== null)) : false;

        $fieldClass = array_merge(array_filter([
            'field',
            ($config['first'] ?? false) ? 'first' : null,
            $errors ? 'has-errors' : null,
        ]), Html::explodeClass($config['fieldClass'] ?? []));

        $userSessionService = Craft::$app->getUser();
        $showAttribute = (
            ($config['showAttribute'] ?? false) &&
            $userSessionService->getIsAdmin() &&
            $userSessionService->getIdentity()->getPreference('showFieldHandles')
        );
        $showActionMenu = (
            !empty($config['actionMenuItems']) &&
            ($label || $showAttribute || isset($config['labelExtra']))
        );
        $showLabelExtra = $showAttribute || $showActionMenu || isset($config['labelExtra']);

        $instructionsHtml = $instructions
            ? Html::tag('div', static::parseMarkdown($instructions), [
                'id' => $instructionsId,
                'class' => ['instructions'],
            ])
            : '';

        $translationDescription = $config['translationDescription'] ?? Craft::t('app', 'This field is translatable.');
        $translationIconHtml = Html::button('', [
            'class' => ['t9n-indicator', 'prevent-autofocus'],
            'data' => [
                'icon' => 'language',
            ],
            'aria' => [
                'label' => $translationDescription,
            ],
        ]);

        $translationIconHtml = Html::tag('craft-tooltip', $translationIconHtml, [
            'placement' => 'bottom',
            'max-width' => '200px',
            'text' => $translationDescription,
            'delay' => '1000',
        ]);

        if ($label) {
            $labelHtml = $label . (
                    ($required
                        ? Html::tag('span', Craft::t('app', 'Required'), [
                            'class' => ['visually-hidden'],
                        ]) .
                        Html::tag('span', '', [
                            'class' => ['required'],
                            'aria' => [
                                'hidden' => 'true',
                            ],
                        ])
                        : '') .
                    ($translatable ? $translationIconHtml : '')
                );
        } else {
            $labelHtml = '';
        }

        return
            Html::beginTag('div', ArrayHelper::merge(
                [
                    'class' => $fieldClass,
                    'id' => $fieldId,
                    'aria' => [
                        'labelledby' => $fieldset ? $labelId : null,
                    ],
                    'role' => $fieldset ? 'group' : null,
                    'data' => [
                        'attribute' => $attribute,
                    ] + $data,
                ],
                $config['fieldAttributes'] ?? []
            )) .
            ($status
                ? Html::beginTag('div', [
                    'id' => $statusId,
                    'class' => ['status-badge', StringHelper::toString($status[0])],
                    'title' => $status[1],
                    'aria-hidden' => 'true',
                ]) .
                Html::tag('span', $status[1], [
                    'class' => 'visually-hidden',
                ]) .
                Html::endTag('div')
                : '') .
            (($label || $showLabelExtra)
                ? (
                    Html::beginTag('div', ['class' => 'heading']) .
                    ($config['headingPrefix'] ?? '') .
                    ($label
                        ? Html::tag($fieldset ? 'legend' : 'label', $labelHtml, ArrayHelper::merge([
                            'id' => $labelId,
                            'class' => $config['labelClass'] ?? null,
                            'for' => !$fieldset ? $id : null,
                        ], $config['labelAttributes'] ?? []))
                        : '') .
                    ($static ? Html::tag('span', Craft::t('app', 'Read Only'), [
                        'class' => ['read-only-badge'],
                    ]) : '') .
                    ($showLabelExtra
                        ? Html::tag('div', '', ['class' => 'flex-grow']) .
                        ($showActionMenu ? static::disclosureMenu($config['actionMenuItems'], [
                            'hiddenLabel' => Craft::t('app', 'Actions'),
                            'buttonAttributes' => [
                                'class' => ['action-btn', 'small', 'prevent-autofocus'],
                            ],
                        ]) : '') .
                        ($showAttribute ? static::renderTemplate('_includes/forms/copytextbtn.twig', [
                            'id' => "$id-attribute",
                            'class' => ['code', 'small', 'light'],
                            'value' => $config['attribute'],
                        ]) : '') .
                        ($config['labelExtra'] ?? '')
                        : '') .
                    ($config['headingSuffix'] ?? '') .
                    Html::endTag('div')
                )
                : '') .
            ($instructionsPosition === 'before' ? $instructionsHtml : '') .
            Html::tag('div', $input, ArrayHelper::merge(
                [
                    'class' => array_filter([
                        'input',
                        $orientation,
                        $errors ? 'errors' : null,
                        $disabled ? 'disabled' : null,
                    ]),
                ],
                $config['inputContainerAttributes'] ?? []
            )) .
            ($instructionsPosition === 'after' ? $instructionsHtml : '') .
            self::_noticeHtml($tipId, 'notice', Craft::t('app', 'Tip:'), $tip) .
            self::_noticeHtml($warningId, 'warning', Craft::t('app', 'Warning:'), $warning) .
            ($errors
                ? static::renderTemplate('_includes/forms/errorList.twig', [
                    'id' => $errorsId,
                    'errors' => $errors,
                ])
                : '') .
            Html::endTag('div');
    }

    /**
     * Returns the HTML for a field tip/warning.
     *
     * @param string $id
     * @param string $class
     * @param string $label
     * @param string|null $message
     * @return string
     */
    private static function _noticeHtml(string $id, string $class, string $label, ?string $message): string
    {
        if (!$message) {
            return '';
        }

        return
            Html::beginTag('p', [
                'id' => $id,
                'class' => [$class, 'has-icon'],
            ]) .
            Html::tag('span', '', [
                'class' => 'icon',
                'aria' => [
                    'hidden' => 'true',
                ],
            ]) .
            Html::tag('span', "$label ", [
                'class' => 'visually-hidden',
            ]) .
            Html::tag('span', Html::decodeDoubles(Markdown::processParagraph(Html::encodeInvalidTags($message)))) .
            Html::endTag('p');
    }

    /**
     * Renders a button’s HTML.
     *
     * @param array $config
     * @return string
     * @throws TemplateLoaderException
     * @since 5.7.0
     */
    public static function buttonHtml(array $config): string
    {
        return static::renderTemplate('_includes/forms/button.twig', $config);
    }

    /**
     * Renders a button group.
     *
     * @param array $config
     * @return string
     * @since 5.8.0
     */
    public static function buttonGroupHtml(array $config): string
    {
        return static::renderTemplate('_includes/forms/buttonGroup.twig', $config);
    }

    /**
     * Renders a button group field’s HTML.
     *
     * @param array $config
     * @return string
     * @throws InvalidArgumentException if `$config['siteId']` is invalid
     * @since 5.8.0
     */
    public static function buttonGroupFieldHtml(array $config): string
    {
        $config['id'] ??= 'buttongroup' . mt_rand();
        $config['fieldset'] = true;
        return static::fieldHtml('template:_includes/forms/buttonGroup.twig', $config);
    }

    /**
     * Renders a checkbox field’s HTML.
     *
     * Note that unlike the `checkboxField` macro in `_includes/forms.html`, you must set the checkbox label via
     * `$config['checkboxLabel']`.
     *
     * @param array $config
     * @return string
     * @throws InvalidArgumentException if `$config['siteId']` is invalid
     * @since 3.6.0
     */
    public static function checkboxFieldHtml(array $config): string
    {
        $config['id'] ??= 'checkbox' . mt_rand();

        $config['fieldClass'] = Html::explodeClass($config['fieldClass'] ?? []);
        $config['fieldClass'][] = 'checkboxfield';
        $config['instructionsPosition'] ??= 'after';

        // Don't pass along `label` since it's ambiguous
        unset($config['label']);

        return static::fieldHtml('template:_includes/forms/checkbox.twig', $config);
    }

    /**
     * Renders a checkbox select field’s HTML.
     *
     * @param array $config
     * @return string
     * @throws InvalidArgumentException if `$config['siteId']` is invalid
     * @since 3.6.0
     */
    public static function checkboxSelectFieldHtml(array $config): string
    {
        $config['id'] ??= 'checkboxselect' . mt_rand();
        $config['fieldset'] = true;
        return static::fieldHtml('template:_includes/forms/checkboxSelect.twig', $config);
    }

    /**
     * Renders a checkbox group input.
     *
     * @param array $config
     * @return string
     * @since 5.0.0
     */
    public static function checkboxGroupHtml(array $config): string
    {
        return static::renderTemplate('_includes/forms/checkboxGroup.twig', $config);
    }

    /**
     * Renders a checkbox group field’s HTML.
     *
     * @param array $config
     * @return string
     * @since 5.0.0
     */
    public static function checkboxGroupFieldHtml(array $config): string
    {
        $config['id'] ??= 'checkboxgroup' . mt_rand();
        return static::fieldHtml('template:_includes/forms/checkboxGroup.twig', $config);
    }

    /**
     * Renders a color input’s HTML.
     *
     * @param array $config
     * @return string
     * @throws TemplateLoaderException
     * @since 5.6.0
     */
    public static function colorHtml(array $config): string
    {
        return static::renderTemplate('_includes/forms/color.twig', $config);
    }

    /**
     * Renders a color field’s HTML.
     *
     * @param array $config
     * @return string
     * @throws InvalidArgumentException if `$config['siteId']` is invalid
     * @since 3.6.0
     */
    public static function colorFieldHtml(array $config): string
    {
        $config['id'] ??= 'color' . mt_rand();
        $config['fieldset'] = true;
        return static::fieldHtml('template:_includes/forms/color.twig', $config);
    }

    /**
     * Renders a color select field’s HTML.
     *
     * @param array $config
     * @return string
     * @since 5.0.0
     */
    public static function colorSelectFieldHtml(array $config): string
    {
        $config['id'] ??= 'colorselect' . mt_rand();
        return static::fieldHtml('template:_includes/forms/colorSelect.twig', $config);
    }

    /**
     * Renders an icon picker’s HTML.
     *
     * @param array $config
     * @return string
     * @since 5.0.0
     */
    public static function iconPickerHtml(array $config): string
    {
        return static::renderTemplate('_includes/forms/iconPicker.twig', $config);
    }

    /**
     * Renders an icon picker field’s HTML.
     *
     * @param array $config
     * @return string
     * @since 5.0.0
     */
    public static function iconPickerFieldHtml(array $config): string
    {
        $config['id'] ??= 'iconpicker' . mt_rand();
        return static::fieldHtml('template:_includes/forms/iconPicker.twig', $config);
    }

    /**
     * Renders an editable table’s HTML.
     *
     * @param array $config
     * @return string
     * @throws TemplateLoaderException
     * @since 5.8.0
     */
    public static function editableTableHtml(array $config): string
    {
        return static::renderTemplate('_includes/forms/editableTable.twig', $config);
    }

    /**
     * Renders an editable table field’s HTML.
     *
     * @param array $config
     * @return string
     * @throws InvalidArgumentException if `$config['siteId']` is invalid
     * @since 3.6.0
     */
    public static function editableTableFieldHtml(array $config): string
    {
        $config['id'] ??= 'editabletable' . mt_rand();
        return static::fieldHtml('template:_includes/forms/editableTable.twig', $config);
    }

    /**
     * Renders a lightswitch input’s HTML.
     *
     * @param array $config
     * @return string
     * @throws TemplateLoaderException
     * @since 4.0.0
     */
    public static function lightswitchHtml(array $config): string
    {
        return static::renderTemplate('_includes/forms/lightswitch.twig', $config);
    }

    /**
     * Renders a lightswitch field’s HTML.
     *
     * @param array $config
     * @return string
     * @throws InvalidArgumentException if `$config['siteId']` is invalid
     * @since 3.6.0
     */
    public static function lightswitchFieldHtml(array $config): string
    {
        $config['id'] ??= 'lightswitch' . mt_rand();

        $config['fieldClass'] = Html::explodeClass($config['fieldClass'] ?? []);
        $config['fieldClass'][] = 'lightswitch-field';

        // Don't pass along `label` since it's ambiguous
        $config['fieldLabel'] ??= $config['label'] ?? null;
        unset($config['label']);

        return static::fieldHtml('template:_includes/forms/lightswitch.twig', $config);
    }

    /**
     * Renders a range input’s HTML.
     *
     * @param array $config
     * @return string
     * @throws TemplateLoaderException
     * @since 5.5.0
     */
    public static function rangeHtml(array $config): string
    {
        return static::renderTemplate('_includes/forms/range.twig', $config);
    }

    /**
     * Renders a range field’s HTML.
     *
     * @param array $config
     * @return string
     * @throws InvalidArgumentException if `$config['siteId']` is invalid
     * @since 5.5.0
     */
    public static function rangeFieldHtml(array $config): string
    {
        $config['id'] ??= 'range' . mt_rand();
        return static::fieldHtml('template:_includes/forms/range.twig', $config);
    }

    /**
     * Renders a money input’s HTML.
     *
     * @param array $config
     * @return string
     * @throws TemplateLoaderException
     * @since 5.0.0
     */
    public static function moneyInputHtml(array $config): string
    {
        return static::renderTemplate('_includes/forms/money.twig', $config);
    }

    /**
     * Renders a money field’s HTML.
     *
     * @param array $config
     * @return string
     * @throws TemplateLoaderException
     * @since 5.0.0
     */
    public static function moneyFieldHtml(array $config): string
    {
        $config['id'] ??= 'money' . mt_rand();
        return static::fieldHtml('template:_includes/forms/money.twig', $config);
    }

    /**
     * Renders a select input.
     *
     * @param array $config
     * @return string
     * @since 3.6.0
     */
    public static function selectHtml(array $config): string
    {
        return static::renderTemplate('_includes/forms/select.twig', $config);
    }

    /**
     * Renders a select field’s HTML.
     *
     * @param array $config
     * @return string
     * @throws InvalidArgumentException if `$config['siteId']` is invalid
     * @since 3.6.0
     */
    public static function selectFieldHtml(array $config): string
    {
        $config['id'] ??= 'select' . mt_rand();
        return static::fieldHtml('template:_includes/forms/select.twig', $config);
    }

    /**
     * Renders a custom select input.
     *
     * @param array $config
     * @return string
     * @since 5.0.0
     */
    public static function customSelectHtml(array $config): string
    {
        return static::renderTemplate('_includes/forms/customSelect.twig', $config);
    }

    /**
     * Renders a selectize field’s HTML.
     *
     * @param array $config
     * @return string
     * @since 5.0.0
     */
    public static function customSelectFieldHtml(array $config): string
    {
        $config['id'] ??= 'customselect' . mt_rand();
        return static::fieldHtml('template:_includes/forms/customSelect.twig', $config);
    }

    /**
     * Renders a selectize input.
     *
     * @param array $config
     * @return string
     * @since 4.0.0
     */
    public static function selectizeHtml(array $config): string
    {
        return static::renderTemplate('_includes/forms/selectize.twig', $config);
    }

    /**
     * Renders a selectize field’s HTML.
     *
     * @param array $config
     * @return string
     * @throws InvalidArgumentException if `$config['siteId']` is invalid
     * @since 4.0.0
     */
    public static function selectizeFieldHtml(array $config): string
    {
        $config['id'] ??= 'selectize' . mt_rand();
        return static::fieldHtml('template:_includes/forms/selectize.twig', $config);
    }

    /**
     * Renders a multi-select input.
     *
     * @param array $config
     * @return string
     * @since 4.0.0
     */
    public static function multiSelectHtml(array $config): string
    {
        return static::renderTemplate('_includes/forms/multiselect.twig', $config);
    }

    /**
     * Renders a multi-select field’s HTML.
     *
     * @param array $config
     * @return string
     * @throws InvalidArgumentException if `$config['siteId']` is invalid
     * @since 4.0.0
     */
    public static function multiSelectFieldHtml(array $config): string
    {
        $config['id'] ??= 'multiselect' . mt_rand();
        return static::fieldHtml('template:_includes/forms/multiselect.twig', $config);
    }

    /**
     * Renders a text input’s HTML.
     *
     * @param array $config
     * @return string
     * @throws TemplateLoaderException
     * @since 4.0.0
     */
    public static function textHtml(array $config): string
    {
        return static::renderTemplate('_includes/forms/text.twig', $config);
    }

    /**
     * Renders a text field’s HTML.
     *
     * @param array $config
     * @return string
     * @throws InvalidArgumentException if `$config['siteId']` is invalid
     * @since 3.6.0
     */
    public static function textFieldHtml(array $config): string
    {
        $config['id'] ??= 'text' . mt_rand();
        return static::fieldHtml('template:_includes/forms/text.twig', $config);
    }

    /**
     * Renders a textarea input’s HTML.
     *
     * @param array $config
     * @return string
     * @throws TemplateLoaderException
     * @since 4.0.0
     */
    public static function textareaHtml(array $config): string
    {
        return static::renderTemplate('_includes/forms/textarea.twig', $config);
    }

    /**
     * Renders a textarea field’s HTML.
     *
     * @param array $config
     * @return string
     * @throws InvalidArgumentException if `$config['siteId']` is invalid
     * @since 3.6.0
     */
    public static function textareaFieldHtml(array $config): string
    {
        $config['id'] ??= 'textarea' . mt_rand();
        return static::fieldHtml('template:_includes/forms/textarea.twig', $config);
    }

    /**
     * Returns a date input’s HTML.
     *
     * @param array $config
     * @return string
     * @throws TemplateLoaderException
     * @since 4.0.0
     */
    public static function dateHtml(array $config): string
    {
        return static::renderTemplate('_includes/forms/date.twig', $config);
    }

    /**
     * Returns a date field’s HTML.
     *
     * @param array $config
     * @return string
     * @throws InvalidArgumentException if `$config['siteId']` is invalid
     * @since 4.0.0
     */
    public static function dateFieldHtml(array $config): string
    {
        $config['id'] ??= 'date' . mt_rand();
        return static::fieldHtml('template:_includes/forms/date.twig', $config);
    }

    /**
     * Returns a time input’s HTML.
     *
     * @param array $config
     * @return string
     * @throws TemplateLoaderException
     * @since 4.0.0
     */
    public static function timeHtml(array $config): string
    {
        return static::renderTemplate('_includes/forms/time.twig', $config);
    }

    /**
     * Returns a date field’s HTML.
     *
     * @param array $config
     * @return string
     * @throws InvalidArgumentException if `$config['siteId']` is invalid
     * @since 4.0.0
     */
    public static function timeFieldHtml(array $config): string
    {
        $config['id'] ??= 'time' . mt_rand();
        return static::fieldHtml('template:_includes/forms/time.twig', $config);
    }

    /**
     * Renders a date + time field’s HTML.
     *
     * @param array $config
     * @return string
     * @throws InvalidArgumentException if `$config['siteId']` is invalid
     * @since 3.7.0
     */
    public static function dateTimeFieldHtml(array $config): string
    {
        $config += [
            'id' => 'datetime' . mt_rand(),
            'fieldset' => true,
        ];
        return static::fieldHtml('template:_includes/forms/datetime.twig', $config);
    }

    /**
     * Renders an element select input’s HTML
     *
     * @param array $config
     * @return string
     * @throws TemplateLoaderException
     * @since 4.0.0
     */
    public static function elementSelectHtml(array $config): string
    {
        return static::renderTemplate('_includes/forms/elementSelect.twig', $config);
    }

    /**
     * Renders an element select field’s HTML.
     *
     * @param array $config
     * @return string
     * @throws InvalidArgumentException if `$config['siteId']` is invalid
     * @since 3.7.0
     */
    public static function elementSelectFieldHtml(array $config): string
    {
        $config['id'] ??= 'elementselect' . mt_rand();
        return static::fieldHtml('template:_includes/forms/elementSelect.twig', $config);
    }

    /**
     * Renders an entry type select input’s HTML
     *
     * @param array $config
     * @return string
     * @since 5.0.0
     */
    public static function entryTypeSelectHtml(array $config): string
    {
        return static::renderTemplate('_includes/forms/entryTypeSelect.twig', $config);
    }

    /**
     * Renders an entry type select field’s HTML.
     *
     * @param array $config
     * @return string
     * @since 5.0.0
     */
    public static function entryTypeSelectFieldHtml(array $config): string
    {
        $config['id'] ??= 'entrytypeselect' . mt_rand();
        return static::fieldHtml('template:_includes/forms/entryTypeSelect.twig', $config);
    }

    /**
     * Renders an autosuggest field’s HTML.
     *
     * @param array $config
     * @return string
     * @throws InvalidArgumentException if `$config['siteId']` is invalid
     * @since 3.7.0
     */
    public static function autosuggestFieldHtml(array $config): string
    {
        $config['id'] ??= 'autosuggest' . mt_rand();

        // Suggest an environment variable / alias?
        if ($config['suggestEnvVars'] ?? false) {
            $value = $config['value'] ?? '';
            if (!isset($config['tip']) && (!isset($value[0]) || !in_array($value[0], ['$', '@']))) {
                if ($config['suggestAliases'] ?? false) {
                    $config['tip'] = Craft::t('app', 'This can begin with an environment variable or alias.');
                } else {
                    $config['tip'] = Craft::t('app', 'This can begin with an environment variable.');
                }
                $config['tip'] .= ' ' .
                    Html::a(Craft::t('app', 'Learn more'), 'https://craftcms.com/docs/5.x/configure.html#control-panel-settings', [
                        'class' => 'go',
                    ]);
            } elseif (
                !isset($config['warning']) &&
                ($value === '@web' || str_starts_with($value, '@web/'))
            ) {
                $config['warning'] = Craft::t('app', 'The `@web` alias is not recommended.');
            }
        }

        return static::fieldHtml('template:_includes/forms/autosuggest.twig', $config);
    }

    /**
     * Returns address fields’ HTML (sans country) for a given address.
     *
     * @param Address $address
     * @param bool $static
     * @return string
     * @since 4.0.0
     */
    public static function addressFieldsHtml(Address $address, bool $static = false): string
    {
        $requiredFields = [];
        $scenario = $address->getScenario();
        $address->setScenario(Element::SCENARIO_LIVE);
        $activeValidators = $address->getActiveValidators();
        $address->setScenario($scenario);
        $belongsToCurrentUser = $address->getBelongsToCurrentUser();

        foreach ($activeValidators as $validator) {
            if ($validator instanceof RequiredValidator) {
                foreach ($validator->getAttributeNames() as $attr) {
                    if ($validator->when === null || call_user_func($validator->when, $address, $attr)) {
                        $requiredFields[$attr] = true;
                    }
                }
            }
        }

        $addressesService = Craft::$app->getAddresses();
        $visibleFields = array_flip(array_merge(
                $addressesService->getUsedFields($address->countryCode),
                $addressesService->getUsedSubdivisionFields($address->countryCode),
            )) + $requiredFields;

        $parents = self::_subdivisionParents($address, $visibleFields);

        return
            static::textFieldHtml([
                'status' => $address->getAttributeStatus('addressLine1'),
                'label' => $address->getAttributeLabel('addressLine1'),
                'id' => 'addressLine1',
                'name' => 'addressLine1',
                'value' => $address->addressLine1,
                'autocomplete' => $belongsToCurrentUser ? 'address-line1' : 'off',
                'required' => isset($requiredFields['addressLine1']),
                'errors' => !$static ? $address->getErrors('addressLine1') : [],
                'data' => [
                    'error-key' => 'addressLine1',
                ],
                'disabled' => $static,
            ]) .
            static::textFieldHtml([
                'status' => $address->getAttributeStatus('addressLine2'),
                'label' => $address->getAttributeLabel('addressLine2'),
                'id' => 'addressLine2',
                'name' => 'addressLine2',
                'value' => $address->addressLine2,
                'autocomplete' => $belongsToCurrentUser ? 'address-line2' : 'off',
                'required' => isset($requiredFields['addressLine2']),
                'errors' => !$static ? $address->getErrors('addressLine2') : [],
                'data' => [
                    'error-key' => 'addressLine2',
                ],
                'disabled' => $static,
            ]) .
            static::textFieldHtml([
                'status' => $address->getAttributeStatus('addressLine3'),
                'label' => $address->getAttributeLabel('addressLine3'),
                'id' => 'addressLine3',
                'name' => 'addressLine3',
                'value' => $address->addressLine3,
                'autocomplete' => $belongsToCurrentUser ? 'address-line3' : 'off',
                'required' => isset($requiredFields['addressLine3']),
                'errors' => !$static ? $address->getErrors('addressLine3') : [],
                'data' => [
                    'error-key' => 'addressLine3',
                ],
                'disabled' => $static,
            ]) .
            self::_subdivisionField(
                $address,
                'administrativeArea',
                $belongsToCurrentUser ? 'address-level1' : 'off',
                isset($visibleFields['administrativeArea']),
                isset($requiredFields['administrativeArea']),
                [$address->countryCode],
                true,
                $static,
            ) .
            self::_subdivisionField(
                $address,
                'locality',
                $belongsToCurrentUser ? 'address-level2' : 'off',
                isset($visibleFields['locality']),
                isset($requiredFields['locality']),
                $parents['locality'],
                true,
                $static,
            ) .
            self::_subdivisionField(
                $address,
                'dependentLocality',
                $belongsToCurrentUser ? 'address-level3' : 'off',
                isset($visibleFields['dependentLocality']),
                isset($requiredFields['dependentLocality']),
                $parents['dependentLocality'],
                false,
                $static,
            ) .
            static::textFieldHtml([
                'fieldClass' => array_filter([
                    'width-50',
                    !isset($visibleFields['postalCode']) ? 'hidden' : null,
                ]),
                'status' => $address->getAttributeStatus('postalCode'),
                'label' => $address->getAttributeLabel('postalCode'),
                'id' => 'postalCode',
                'name' => 'postalCode',
                'value' => $address->postalCode,
                'autocomplete' => $belongsToCurrentUser ? 'postal-code' : 'off',
                'required' => isset($requiredFields['postalCode']),
                'errors' => !$static ? $address->getErrors('postalCode') : [],
                'data' => [
                    'error-key' => 'postalCode',
                ],
                'disabled' => $static,
            ]) .
            static::textFieldHtml([
                'fieldClass' => array_filter([
                    'width-50',
                    !isset($visibleFields['sortingCode']) ? 'hidden' : null,
                ]),
                'status' => $address->getAttributeStatus('sortingCode'),
                'label' => $address->getAttributeLabel('sortingCode'),
                'id' => 'sortingCode',
                'name' => 'sortingCode',
                'value' => $address->sortingCode,
                'required' => isset($requiredFields['sortingCode']),
                'errors' => !$static ? $address->getErrors('sortingCode') : [],
                'data' => [
                    'error-key' => 'sortingCode',
                ],
                'disabled' => $static,
            ]);
    }

    /**
     * Get parents array that needs to be passed to the subdivision repository getList() method to get the list of subdivisions back.
     *
     * For the administrativeArea, the parent is always just the country code.
     *
     * For the locality:
     *      - it could be just the country code
     *          - for countries that don't use administrativeArea field; that's the case with Andorra
     *      - it could be the country code and the administrative area code
     *          - for countries that use both administrative areas and localities; e.g. Chile (Chile => Araucania > Carahue)
     *          - the administrative area can be passed as null too;
     *              this will be triggered for the United Kingdom (GB), where you can conditionally turn on administrativeArea;
     *              in the case of GB, not passing null as the second value would result
     *              in the administrativeAreas list being returned for the locality field (https://github.com/craftcms/cms/issues/15551);
     *
     * For the dependentLocality:
     *      - as above but taking locality into consideration too; e.g. China has all 3 levels of subdivisions and has lists for all 3 of them
     *          (China => Heilongjiang Sheng > Hegang Shi > Dongshan Qu)
     *
     * @param Address $address
     * @param array $visibleFields
     * @return array
     */
    private static function _subdivisionParents(Address $address, array $visibleFields): array
    {
        $baseSubdivisionRepository = new BaseSubdivisionRepository();

        $localityParents = [$address->countryCode];
        $administrativeAreas = $baseSubdivisionRepository->getList([$address->countryCode]);

        if (array_key_exists('administrativeArea', $visibleFields) || empty($administrativeAreas)) {
            $localityParents[] = $address->administrativeArea;
        }

        $dependentLocalityParents = $localityParents;
        $localities = $baseSubdivisionRepository->getList($localityParents);
        if (array_key_exists('locality', $visibleFields) || empty($localities)) {
            $dependentLocalityParents[] = $address->locality;
        }

        return ['locality' => $localityParents, 'dependentLocality' => $dependentLocalityParents];
    }

    private static function _subdivisionField(
        Address $address,
        string $name,
        string $autocomplete,
        bool $visible,
        bool $required,
        ?array $parents,
        bool $spinner,
        bool $static = false,
    ): string {
        $value = $address->$name;
        $options = Craft::$app->getAddresses()->getSubdivisionRepository()->getList($parents, Craft::$app->language);

        if ($options) {
            // Persist invalid values in the UI
            if ($value && !isset($options[$value])) {
                $options[$value] = $value;
            }

            if ($spinner) {
                $errors = !$static ? $address->getErrors($name) : [];
                $input =
                    Html::beginTag('div', [
                        'class' => ['flex', 'flex-nowrap'],
                    ]) .
                    static::selectizeHtml([
                        'id' => $name,
                        'name' => $name,
                        'value' => $value,
                        'options' => $options,
                        'errors' => $errors,
                        'autocomplete' => $autocomplete,
                        'disabled' => $static,
                    ]) .
                    Html::tag('div', '', [
                        'id' => "$name-spinner",
                        'class' => ['spinner', 'hidden'],
                    ]) .
                    Html::endTag('div');

                return static::fieldHtml($input, [
                    'fieldClass' => !$visible ? 'hidden' : null,
                    'label' => $address->getAttributeLabel($name),
                    'id' => $name,
                    'required' => $required,
                    'errors' => $errors,
                    'data' => [
                        'error-key' => $name,
                    ],
                    'disabled' => $static,
                ]);
            }

            return static::selectizeFieldHtml([
                'fieldClass' => !$visible ? 'hidden' : null,
                'status' => $address->getAttributeStatus($name),
                'label' => $address->getAttributeLabel($name),
                'id' => $name,
                'name' => $name,
                'value' => $value,
                'options' => $options,
                'required' => $required,
                'errors' => $address->getErrors($name),
                'autocomplete' => $autocomplete,
                'data' => [
                    'error-key' => $name,
                ],
                'disabled' => $static,
            ]);
        }

        // No preconfigured subdivisions for the given parents, so just output a text input
        return static::textFieldHtml([
            'fieldClass' => !$visible ? 'hidden' : null,
            'status' => $address->getAttributeStatus($name),
            'label' => $address->getAttributeLabel($name),
            'autocomplete' => $autocomplete,
            'id' => $name,
            'name' => $name,
            'value' => $value,
            'required' => $required,
            'errors' => !$static ? $address->getErrors($name) : [],
            'data' => [
                'error-key' => $name,
            ],
            'disabled' => $static,
        ]);
    }

    /**
     * Renders a card view designer.
     *
     * @param FieldLayout $fieldLayout
     * @param array $config
     * @return string
     * @since 5.5.0
     */
    public static function cardViewDesignerHtml(FieldLayout $fieldLayout, array $config = []): string
    {
        $config += [
            'id' => 'cvd' . mt_rand(),
            'disabled' => false,
        ];

        $allOptions = self::cardPreviewOptions($fieldLayout);
        $selectedOptions = [];
        $remainingOptions = [...$allOptions];

        foreach ($fieldLayout->getCardView() as $key) {
            if (isset($allOptions[$key])) {
                $selectedOptions[$key] = $allOptions[$key];
                unset($remainingOptions[$key]);
            }
        }

        // sort the remaining attributes alphabetically, by label
        usort($remainingOptions, fn(array $a, array $b) => $a['label'] <=> $b['label']);

        $checkboxSelect = self::checkboxSelectFieldHtml([
            'label' => Craft::t('app', 'Card Attributes'),
            'id' => $config['id'],
            'options' => [...$selectedOptions, ...$remainingOptions],
            'values' => array_keys($selectedOptions),
            'sortable' => true,
            'disabled' => $config['disabled'],
        ]);

        // js is initiated via Craft.FieldLayoutDesigner
        $previewHtml = self::cardPreviewHtml($fieldLayout, showThumb: $fieldLayout->getThumbField() !== null);

        return
            Html::beginTag('div', [
                'id' => $config['id'] . '-container',
                'class' => 'card-view-designer',
            ]) .
            Html::tag('h2', Craft::t('app', 'Card Layout Editor'), ['class' => 'visually-hidden']) .
            Html::beginTag('div', ['class' => 'cvd-container']) .
            Html::beginTag('div', ['class' => 'cvd-library']) .
            $checkboxSelect .
            Html::endTag('div') . // .cvd-library
            Html::beginTag('div', ['class' => 'cvd-preview-container']) .
            Html::beginTag('div', ['class' => 'cvd-preview']) .
            Html::tag('h3', Craft::t('app', 'Card Layout Preview'), [
                'class' => 'visually-hidden',
            ]) .
            Html::tag('p', Craft::t('app', 'The following content is for preview only.'), [
                'class' => 'visually-hidden',
            ]) .
            $previewHtml .
            Html::endTag('div') . // .cvd-preview-container
            Html::endTag('div') . // .cvd-preview
            Html::endTag('div') . // .cvd-container
            self::_thumbManagementHtml($fieldLayout, $config) .
            Html::endTag('div'); // .card-view-designer
    }

    /**
     * Returns an array of available card preview options for the given field layout.
     *
     * @param FieldLayout $fieldLayout
     * @return array{label:string,value:string}[]
     * @since 5.9.0
     */
    public static function cardPreviewOptions(FieldLayout $fieldLayout, bool $withAttributes = true): array
    {
        return self::cardPreviewOptionsInternal($fieldLayout, '', '', $withAttributes);
    }

    private static function cardPreviewOptionsInternal(
        FieldLayout $fieldLayout,
        string $keyPrefix,
        string $labelPrefix,
        bool $withAttributes,
    ): array {
        $allOptions = [];

        if ($withAttributes) {
            foreach ($fieldLayout->type::cardAttributes($fieldLayout) as $key => $attribute) {
                $allOptions[$keyPrefix . $key] = [
                    'label' => $labelPrefix . $attribute['label'],
                    'placeholder' => $attribute['placeholder'] ?? null,
                ];
            }
        }

        foreach ($fieldLayout->getAllElements() as $layoutElement) {
            if ($layoutElement instanceof CustomField) {
                try {
                    $field = $layoutElement->getField();
                } catch (FieldNotFoundException) {
                    continue;
                }
                if ($field instanceof ContentBlock) {
                    $allOptions += self::cardPreviewOptionsInternal(
                        $field->getFieldLayout(),
                        "{$keyPrefix}contentBlock:$layoutElement->uid.",
                        sprintf('%s%s → ', $labelPrefix, $layoutElement->label()),
                        false,
                    );
                    continue;
                }
            }

            if ($layoutElement instanceof BaseField && $layoutElement->previewable()) {
                $allOptions[$keyPrefix . $layoutElement->key()] = [
                    'label' => sprintf('%s%s', $labelPrefix, $layoutElement->label()),
                ];
            }
        }

        foreach ($fieldLayout->getGeneratedFields() as $field) {
            if (($field['name'] ?? '') !== '') {
                $allOptions["generatedField:{$field['uid']}"] = [
                    'label' => $field['name'],
                ];
            }
        }

        foreach ($allOptions as $key => &$option) {
            if (!isset($option['value'])) {
                $option['value'] = $key;
            }
        }

        return $allOptions;
    }

    /**
     * Return HTML for managing thumbnail provider and position.
     *
     * @param FieldLayout $fieldLayout
     * @param array $config
     * @return string
     * @throws TemplateLoaderException
     */
    private static function _thumbManagementHtml(FieldLayout $fieldLayout, array $config): string
    {
        $config += [
            'disabled' => false,
        ];

        if ($fieldLayout->type::hasThumbs()) {
            $options = [
                ['label' => Craft::t('app', 'Default'), 'value' => '__default__'],
            ];
        } else {
            $options = [
                ['label' => Craft::t('app', 'None'), 'value' => '__none__'],
            ];
        }

        $thumbnailAlignment = $fieldLayout->getCardThumbAlignment();

        /** @var BaseField[] $thumbableElements */
        $thumbableElements = array_filter(
            $fieldLayout->getAllElements(),
            fn($element) => $element instanceof BaseField && $element->thumbable()
        );

        foreach ($thumbableElements as $thumbableElement) {
            $options[] = [
                'label' => $thumbableElement->label(),
                'value' => $thumbableElement->key(),
            ];
        }

        $thumbHtml = Html::beginTag('div', ['class' => 'thumb-management']) .
            Html::tag('h2', Craft::t('app', 'Manage element thumbnails'), ['class' => 'visually-hidden']) .
            Html::beginTag('div', ['class' => ['flex', 'flex-nowrap', 'items-start']]);

        // dropdown field that contains all thumbable fields + 'None' option
        $thumbHtml .= self::selectFieldHtml([
            'label' => Craft::t('app', 'Thumbnail Source'),
            'id' => 'thumb-source',
            'options' => $options,
            'value' => $fieldLayout->thumbFieldKey,
            'disabled' => $config['disabled'],
        ]);

        // radio button switch that lets you choose whether the thumb alignment should be start or end
        $orientation = Craft::$app->getLocale()->getOrientation();
        $thumbHtml .= self::buttonGroupFieldHtml([
            'label' => Craft::t('app', 'Thumbnail Alignment'),
            'id' => 'thumb-alignment',
            'fieldClass' => $fieldLayout->getThumbField() === null ? 'hidden' : false,
            'options' => [
                [
                    'icon' => $orientation == 'ltr' ? 'slideout-left' : 'slideout-right',
                    'value' => 'start',
                    'attributes' => [
                        'title' => $orientation == 'ltr' ? Craft::t('app', 'Left') : Craft::t('app', 'Right'),
                        'aria' => [
                            'label' => $orientation == 'ltr' ? Craft::t('app', 'Left') : Craft::t('app', 'Right'),
                        ],
                    ],
                ],
                [
                    'icon' => $orientation == 'ltr' ? 'slideout-right' : 'slideout-left',
                    'value' => 'end',
                    'attributes' => [
                        'title' => $orientation == 'ltr' ? Craft::t('app', 'Right') : Craft::t('app', 'Left'),
                        'aria' => [
                            'label' => $orientation == 'ltr' ? Craft::t('app', 'Right') : Craft::t('app', 'Left'),
                        ],
                    ],
                ],
            ],
            'value' => $thumbnailAlignment,
            'disabled' => $config['disabled'],
        ]);


        $thumbHtml .= Html::endTag('div') . // .flex
            Html::endTag('div'); // .thumb-management

        return $thumbHtml;
    }

    /**
     * Returns HTML for the card preview based on selected fields and attributes.
     *
     * @param FieldLayout $fieldLayout
     * @param array $cardElements (deprecated)
     * @param bool|null $showThumb
     * @return string
     * @throws \Throwable
     */
    public static function cardPreviewHtml(FieldLayout $fieldLayout, array $cardElements = [], ?bool $showThumb = null): string
    {
        $showThumb ??= $fieldLayout->getThumbField() !== null || $fieldLayout->type::hasThumbs();
        $thumbAlignment = $fieldLayout->getCardThumbAlignment();

        // get heading
        $heading = Html::tag('craft-element-label',
            Html::tag('a', Html::tag('span', Craft::t('app', 'Title')), [
                'class' => ['label-link'],
                'href' => '#',
                'aria-disabled' => 'true',
            ]),
            [
                'class' => 'label',
            ]
        );

        // get status label placeholder
        $labels = [$fieldLayout->type::hasStatuses() ? static::componentStatusLabelHtml(new ($fieldLayout->type)()) : null];

        $previewHtml =
            Html::beginTag('div', [
                'class' => array_filter([
                    'element',
                    'card',
                    $showThumb ? "thumb-$thumbAlignment" : null,
                ]),
            ]);

        $previewHtml .=
            Html::tag('div', options: ['class' => 'card-titlebar']) .
            Html::beginTag('div', ['class' => 'card-main']) .
            Html::beginTag('div', ['class' => 'card-content']) .
            Html::tag('div', $heading, ['class' => 'card-heading']) .
            Html::beginTag('div', ['class' => 'card-body']);

        // get body elements (fields and attributes)
        $cardElements = $fieldLayout->getCardBodyElements();

        foreach ($cardElements as $cardElement) {
            $previewHtml .= Html::tag('div', $cardElement['html'], [
                'class' => 'card-attribute-preview',
            ]);
        }

        if (!empty(array_filter($labels))) {
            $previewHtml .= Html::ul($labels, [
                'class' => ['flex', 'gap-xs'],
                'encode' => false,
            ]);
        }

        $previewHtml .=
            Html::endTag('div') . // .card-body
            Html::endTag('div'); // .card-content

        // get thumb placeholder
        if ($showThumb) {
            $previewThumb = Html::tag('div',
                Html::tag('div', Cp::iconSvg('image'), ['class' => 'cp-icon']),
                ['class' => 'cvd-thumbnail']
            );

            $previewHtml .= Html::tag('div', $previewThumb, ['class' => ['thumb']]);
        }

        $previewHtml .=
            Html::endTag('div') . // .card-main
            Html::tag('div', '', ['class' => 'spinner spinner-absolute']) .
            Html::endTag('div'); // .element.card

        return $previewHtml;
    }

    /**
     * Renders a field layout designer.
     *
     * @param FieldLayout $fieldLayout
     * @param array $config
     * @return string
     * @since 4.0.0
     */
    public static function fieldLayoutDesignerHtml(FieldLayout $fieldLayout, array $config = []): string
    {
        $config += [
            'id' => 'fld' . mt_rand(),
            'customizableTabs' => true,
            'customizableUi' => true,
            'disabled' => false,
        ];

        $tabs = array_values($fieldLayout->getTabs());

        if (!$config['customizableTabs']) {
            $tab = array_shift($tabs) ?? new FieldLayoutTab([
                'uid' => StringHelper::UUID(),
                'layout' => $fieldLayout,
            ]);
            $tab->name = $config['pretendTabName'] ?? Craft::t('app', 'Content');

            // Any extra tabs?
            if (!empty($tabs)) {
                $elements = $tab->getElements();
                foreach ($tabs as $extraTab) {
                    array_push($elements, ...$extraTab->getElements());
                }
                $tab->setElements($elements);
            }

            $tabs = [$tab];
        }

        // Make sure all tabs and their elements have UUIDs
        // (We do this here instead of from FieldLayoutComponent::init() because the we don't want field layout forms to
        // get the impression that tabs/elements have persisting UUIDs if they don't.)
        foreach ($tabs as $tab) {
            if (!isset($tab->uid)) {
                $tab->uid = StringHelper::UUID();
            }

            $layoutElements = [];

            foreach ($tab->getElements() as $layoutElement) {
                // If this is a custom field, make sure the field still exists
                if ($layoutElement instanceof CustomField) {
                    try {
                        $layoutElement->getField();
                    } catch (FieldNotFoundException) {
                        continue;
                    }
                }

                if (!isset($layoutElement->uid)) {
                    $layoutElement->uid = StringHelper::UUID();
                }

                $layoutElements[] = $layoutElement;
            }

            $tab->setElements($layoutElements);
        }

        $view = Craft::$app->getView();
        $jsSettings = Json::encode([
            'elementType' => $fieldLayout->type,
            'customizableTabs' => $config['customizableTabs'],
            'customizableUi' => $config['customizableUi'],
            'withCardViewDesigner' => $config['withCardViewDesigner'] ?? false,
            'alwaysShowThumbAlignmentBtns' => $fieldLayout->type::hasThumbs(),
            'readOnly' => $config['disabled'],
        ]);
        $namespacedId = $view->namespaceInputId($config['id']);

        $js = <<<JS
new Craft.FieldLayoutDesigner("#$namespacedId", $jsSettings);
JS;
        $view->registerJs($js);

        $availableCustomFields = $fieldLayout->getAvailableCustomFields();
        $availableNativeFields = $fieldLayout->getAvailableNativeFields();
        $availableUiElements = $fieldLayout->getAvailableUiElements();

        // Make sure everything has the field layout set properly
        foreach ($availableCustomFields as $groupFields) {
            self::_setLayoutOnElements($groupFields, $fieldLayout);
        }
        self::_setLayoutOnElements($availableNativeFields, $fieldLayout);
        self::_setLayoutOnElements($availableUiElements, $fieldLayout);

        $fieldLayoutConfig = [
            'uid' => $fieldLayout->uid,
            ...(array)$fieldLayout->getConfig(),
        ];

        // Default `dateAdded` to a minute ago for each element, so there’s no chance that an element that predated 5.3
        // would get the same timestamp as a newly-added element, if the layout was saved within a minute of being
        // edited, after updating to Craft 5.3+.
        if (isset($fieldLayoutConfig['tabs'])) {
            foreach ($fieldLayoutConfig['tabs'] as &$tabConfig) {
                foreach ($tabConfig['elements'] as &$elementConfig) {
                    if (!isset($elementConfig['dateAdded'])) {
                        $elementConfig['dateAdded'] = DateTimeHelper::toIso8601((new DateTime())->modify('-1 minute'));
                    }
                }
            }
        }

        if ($fieldLayout->id) {
            $fieldLayoutConfig['id'] = $fieldLayout->id;
        }

        if ($fieldLayout->type) {
            $fieldLayoutConfig['type'] = $fieldLayout->type;
        }

        return
            Html::beginTag('div', [
                'id' => $config['id'],
                'class' => 'layoutdesigner',
            ]) .
            Html::hiddenInput('fieldLayout', Json::encode($fieldLayoutConfig), [
                'data' => ['config-input' => true],
            ]) .
            Html::beginTag('div', ['class' => 'fld-container']) .
            Html::beginTag('div', ['class' => 'fld-workspace']) .
            Html::beginTag('div', ['class' => 'fld-tabs']) .
            implode('', array_map(
                fn(FieldLayoutTab $tab) => self::_fldTabHtml($tab, $config['customizableTabs'], $config['disabled']),
                $tabs,
            )) .
            Html::endTag('div') . // .fld-tabs
            ($config['customizableTabs']
                ? Html::button(Craft::t('app', 'New Tab'), [
                    'type' => 'button',
                    'class' => ['fld-new-tab-btn', 'btn', 'add', 'icon'],
                    'disabled' => $config['disabled'],
                ])
                : '') .
            Html::endTag('div') . // .fld-workspace
            Html::beginTag('div', ['class' => 'fld-library']) .
            ($config['customizableUi']
                ? Html::beginTag('section', [
                    'class' => ['btngroup', 'btngroup--exclusive', 'small', 'fullwidth'],
                    'aria' => ['label' => Craft::t('app', 'Layout element types')],
                ]) .
                Html::button(Craft::t('app', 'Fields'), [
                    'type' => 'button',
                    'class' => ['btn', 'small', 'active'],
                    'aria' => ['pressed' => 'true'],
                    'data' => ['library' => 'field'],
                    'disabled' => $config['disabled'],
                ]) .
                Html::button(Craft::t('app', 'UI Elements'), [
                    'type' => 'button',
                    'class' => ['btn', 'small'],
                    'aria' => ['pressed' => 'false'],
                    'data' => ['library' => 'ui'],
                    'disabled' => $config['disabled'],
                ]) .
                Html::endTag('section') // .btngroup
                : '') .
            Html::beginTag('div', ['class' => 'fld-field-library']) .
            Html::beginTag('div', ['class' => ['texticon', 'search', 'icon', 'clearable']]) .
            static::textHtml([
                'class' => 'fullwidth',
                'inputmode' => 'search',
                'placeholder' => Craft::t('app', 'Search'),
                'disabled' => $config['disabled'],
            ]) .
            Html::tag('div', '', [
                'class' => ['clear-btn', 'hidden'],
                'title' => Craft::t('app', 'Clear'),
                'aria' => ['label' => Craft::t('app', 'Clear')],
            ]) .
            Html::endTag('div') . // .texticon
            self::_fldFieldSelectorsHtml(Craft::t('app', 'Native Fields'), $availableNativeFields, $fieldLayout) .
            implode('', array_map(fn(string $groupName) => self::_fldFieldSelectorsHtml($groupName, $availableCustomFields[$groupName], $fieldLayout), array_keys($availableCustomFields))) .
            Html::endTag('div') . // .fld-field-library
            ($config['customizableUi']
                ? Html::beginTag('div', ['class' => ['fld-ui-library', 'hidden']]) .
                implode('', array_map(fn(FieldLayoutElement $element) => self::layoutElementSelectorHtml($element, true, [
                    'class' => array_filter([
                        !self::_showFldUiElementSelector($fieldLayout, $element) ? 'hidden' : null,
                    ]),
                ]), $availableUiElements)) .
                Html::endTag('div') // .fld-ui-library
                : '') .
            Html::endTag('div') . // .fld-library
            Html::endTag('div') . // .fld-container
            Html::endTag('div'); // .layoutdesigner
    }

    /**
     * @param FieldLayoutElement[] $elements
     * @param FieldLayout $fieldLayout
     */
    private static function _setLayoutOnElements(array $elements, FieldLayout $fieldLayout): void
    {
        foreach ($elements as $element) {
            $element->setLayout($fieldLayout);
        }
    }

    /**
     * @param FieldLayoutTab $tab
     * @param bool $customizable
     * @param bool $disabled
     * @return string
     */
    private static function _fldTabHtml(FieldLayoutTab $tab, bool $customizable, $disabled): string
    {
        return
            Html::beginTag('div', [
                'class' => 'fld-tab',
                'data' => [
                    'uid' => $tab->uid,
                ],
            ]) .
            Html::beginTag('div', ['class' => 'tabs']) .
            Html::tag('div', $tab->labelHtml(), [
                'class' => array_filter([
                    'tab',
                    'sel',
                    $customizable ? 'draggable' : null,
                ]),
            ]) .
            Html::endTag('div') . // .tabs
            Html::beginTag('div', ['class' => 'fld-tabcontent']) .
            implode('', array_map(fn(FieldLayoutElement $element) => self::layoutElementSelectorHtml($element, false), $tab->getElements())) .
            Html::button(Craft::t('app', 'Add'), [
                'class' => ['btn', 'add', 'icon', 'dashed', 'fullwidth', 'fld-add-btn'],
                'disabled' => $disabled,
            ]) .
            Html::endTag('div') . // .fld-tabcontent
            Html::endTag('div'); // .fld-tab
    }

    /**
     * Renders a field layout element’s selector HTML.
     *
     * @param FieldLayoutElement $element
     * @param bool $forLibrary
     * @param array $attributes
     * @return string
     * @since 5.0.0
     */
    public static function layoutElementSelectorHtml(
        FieldLayoutElement $element,
        bool $forLibrary = false,
        array $attributes = [],
    ): string {
        // ignore invalid custom fields
        if ($element instanceof CustomField) {
            try {
                $element->getField();
            } catch (FieldNotFoundException) {
                return '';
            }
        }

        if ($element instanceof BaseField) {
            $attributes = ArrayHelper::merge($attributes, [
                'data' => [
                    'keywords' => $forLibrary ? implode(' ', array_map('mb_strtolower', $element->keywords())) : false,
                ],
            ]);
        }

        if ($element instanceof CustomField) {
            $originalField = Craft::$app->getFields()->getFieldByUid($element->getFieldUid());
            if ($originalField) {
                $attributes['data']['default-handle'] = $originalField->handle;
            }
        }

        $attributes = ArrayHelper::merge($attributes, [
            'class' => array_filter([
                'fld-element',
                $forLibrary ? 'unused' : null,
            ]),
            'data' => [
                'uid' => !$forLibrary ? $element->uid : false,
                'config' => $forLibrary ? ['type' => get_class($element)] + $element->toArray() : false,
                'ui-label' => $forLibrary && $element instanceof CustomField ? $element->getField()->getUiLabel() : false,
                'is-multi-instance' => $element->isMultiInstance(),
                'has-custom-width' => $element->hasCustomWidth(),
                'has-settings' => $element->hasSettings(),
            ],
        ]);

        return Html::modifyTagAttributes($element->selectorHtml(), $attributes);
    }

    /**
     * @param string $groupName
     * @param BaseField[] $groupFields
     * @param FieldLayout $fieldLayout
     * @return string
     */
    private static function _fldFieldSelectorsHtml(string $groupName, array $groupFields, FieldLayout $fieldLayout): string
    {
        $showGroup = ArrayHelper::contains(
            $groupFields,
            fn(BaseField $field) => self::_showFldFieldSelector($fieldLayout, $field),
        );

        return
            Html::beginTag('div', [
                'class' => array_filter([
                    'fld-field-group',
                    $showGroup ? null : 'hidden',
                ]),
                'data' => ['name' => mb_strtolower($groupName)],
            ]) .
            Html::tag('h6', Html::encode($groupName)) .
            implode('', array_map(fn(BaseField $field) => self::layoutElementSelectorHtml($field, true, [
                'class' => array_filter([
                    !self::_showFldFieldSelector($fieldLayout, $field) ? 'hidden' : null,
                ]),
            ]), $groupFields)) .
            Html::endTag('div'); // .fld-field-group
    }

    private static function _showFldFieldSelector(FieldLayout $fieldLayout, BaseField $field): bool
    {
        $attribute = $field->attribute();
        $uid = $field instanceof CustomField ? $field->getField()->uid : null;

        return (
            $field->isMultiInstance() ||
            !$fieldLayout->isFieldIncluded(function(BaseField $field) use ($attribute, $uid) {
                if ($field instanceof CustomField) {
                    return $field->getFieldUid() === $uid;
                }
                return $field->attribute() === $attribute;
            })
        );
    }

    private static function _showFldUiElementSelector(FieldLayout $fieldLayout, FieldLayoutElement $uiElement): bool
    {
        return $uiElement->isMultiInstance() ||
            !$fieldLayout->isUiElementIncluded(
                fn(FieldLayoutElement $element) => $uiElement::class === $element::class
            );
    }

    /**
     * Renders a Generated Fields table for a field layout
     *
     * @param FieldLayout $fieldLayout
     * @param array $config
     * @return string
     */
    public static function generatedFieldsTableHtml(FieldLayout $fieldLayout, array $config = []): string
    {
        $config += [
            'id' => sprintf('generated-fields-table-%s', mt_rand()),
            'disabled' => false,
        ];

        $name = 'generatedFields';

        $cols = [
            'name' => [
                'heading' => Craft::t('app', 'Name'),
                'type' => 'singleline',
                'width' => '15%',
            ],
            'handle' => [
                'heading' => Craft::t('app', 'Handle'),
                'type' => 'singleline',
                'code' => true,
                'width' => '15%',
            ],
            'template' => [
                'heading' => Craft::t('app', 'Template'),
                'type' => 'multiline',
                'code' => true,
            ],
        ];

        $rows = array_map(function(array $field) {
            if (isset($field['uid'])) {
                $field['hiddenInputs'] = [
                    'uid' => $field['uid'],
                ];
            }
            return $field;
        }, $fieldLayout->getGeneratedFields());

        $settings = [
            'allowAdd' => true,
            'allowReorder' => true,
            'allowDelete' => true,
            'static' => $config['disabled'],
        ];

        $view = Craft::$app->getView();
        $view->registerJsWithVars(fn($id, $name, $cols, $settings) => <<<JS
(() => {
  new Craft.GeneratedFieldsTable($id, $name, $cols, $settings);
})();
JS, [
            $view->namespaceInputId($config['id']),
            $view->namespaceInputName($name),
            $cols,
            $settings,
        ]);

        return static::editableTableHtml([
            'id' => $config['id'],
            'name' => $name,
            'cols' => $cols,
            'rows' => $rows,
            'addRowLabel' => Craft::t('app', 'Add a field'),
            'static' => $config['disabled'],
            'initJs' => false,
            ...$settings,
        ]);
    }

    /**
     * Returns a metadata component’s HTML.
     *
     * @param array $data The data, with keys representing the labels. The values can either be strings or callables.
     * If a value is `false`, it will be omitted.
     * @return string
     */
    public static function metadataHtml(array $data): string
    {
        $defs = [];

        foreach ($data as $label => $value) {
            if (is_callable($value)) {
                $value = $value();
            }
            if ($value !== false) {
                $defs[] =
                    Html::beginTag('div', [
                        'class' => 'data',
                    ]) .
                    Html::tag('dt', Html::encode($label), ['class' => 'heading']) . "\n" .
                    Html::tag('dd', $value, ['class' => 'value']) . "\n" .
                    Html::endTag('div');
            }
        }

        if (empty($defs)) {
            return '';
        }

        return Html::tag('dl', implode("\n", $defs), [
            'class' => ['meta', 'read-only'],
        ]);
    }

    /**
     * Returns a disclosure menu’s HTML.
     *
     * See [[menuItem()]] for a list of supported item config options.
     *
     * Horizontal rules can be defined with the following key:
     *
     * - `hr` – Set to `true`
     *
     * Groups of items can be defined as well, using the following keys:
     *
     * - `group` – Set to `true`
     * - `heading` – The group heading
     * - `items` – The nested item definitions
     * - `listAttributes` – any HTML attributes that should be included on the `<ul>`
     *
     * @param array $items The menu items.
     * @param array $config
     * @return string
     * @since 5.0.0
     */
    public static function disclosureMenu(array $items, array $config = []): string
    {
        $config += [
            'id' => sprintf('menu-%s', mt_rand()),
            'class' => null,
            'withButton' => true,
            'buttonLabel' => null,
            'buttonHtml' => null,
            'autoLabel' => false,
            'buttonAttributes' => [],
            'hiddenLabel' => null,
            'omitIfEmpty' => true,
        ];

        // Item normalization & cleanup
        $items = Collection::make(self::normalizeMenuItems($items));

        // Place all the destructive items at the end
        $destructiveItems = $items->filter(fn(array $item) => $item['destructive'] ?? false);
        $items = $items->filter(fn(array $item) => !($item['destructive'] ?? false))
            ->push(['type' => MenuItemType::HR->value])
            ->push(...$destructiveItems->all());

        // Remove leading/trailing/repetitive HRs
        while (($items->first()['type'] ?? null) === MenuItemType::HR->value) {
            $items->shift();
        }
        while (($items->last()['type'] ?? null) === MenuItemType::HR->value) {
            $items->pop();
        }
        $items = $items->values();
        $items = $items->filter(fn(array $item, int $i) => (
            ($item['type'] ?? null) !== MenuItemType::HR->value ||
            ($items->get($i + 1)['type'] ?? null) !== MenuItemType::HR->value
        ));

        // If we're left without any items, just return an empty string
        if ($config['omitIfEmpty'] && $items->isEmpty()) {
            return '';
        }

        $config['items'] = $items->all();

        if ($config['withButton'] && $config['autoLabel']) {
            // Find the selected item, also looking within nested item groups
            $selectedItem = $items
                ->map(fn(array $i) => $i['type'] === MenuItemType::Group->value ? ($i['items'] ?? []) : [$i])
                ->flatten(1)
                ->first(fn(array $i) => $i['selected'] ?? false);

            if ($selectedItem) {
                $config['label'] = $selectedItem['label'] ?? null;
                $config['html'] = $selectedItem['html'] ?? null;
            }
        }

        return Craft::$app->getView()->renderTemplate('_includes/disclosuremenu.twig', $config, View::TEMPLATE_MODE_CP);
    }

    /**
     * Returns a menu item’s HTML.
     *
     * The item config can contain a `type` key set to a [[MenuItemType]] case. By default, it will be set to:
     *
     * - [[MenuItemType::Link]] if `url` is set
     * - [[MenuItemType::Group]] if `heading` or `items` are set
     * - [[MenuItemType::Button]] in all other cases
     *
     * Link and button item configs can contain the following keys:
     *
     * - `id` – The item’s ID
     * - `label` – The item label, to be HTML-encoded
     * - `icon` – The item icon name
     * - `html` – The item label, which will be output verbatim, without being HTML-encoded
     * - `description` – The item description
     * - `status` – The status indicator that should be shown beside the item label
     * - `url` – The URL that the item should link to
     * - `action` – The controller action that the item should trigger
     * - `params` – Request parameters that should be sent to the `action`
     * - `confirm` – A confirmation message that should be presented to the user before triggering the `action`
     * - `redirect` – The redirect path that the `action` should use
     * - `requireElevatedSession` – Whether an elevated session is required before the `action` is triggered
     * - `selected` – Whether the item should be marked as selected
     * - `hidden` – Whether the item should be hidden
     * - `attributes` – Any HTML attributes that should be set on the item’s `<a>` or `<button>` tag
     * - `liAttributes` – Any HTML attributes that should be set on the item’s `<li>` tag
     *
     * @param array $config
     * @param string $menuId
     * @return string
     * @since 5.0.0
     */
    public static function menuItem(array $config, string $menuId): string
    {
        return Craft::$app->getView()->renderTemplate('_includes/menuitem.twig', [
            'item' => $config,
            'menuId' => $menuId,
        ], View::TEMPLATE_MODE_CP);
    }

    /**
     * Normalizes and cleans up the given disclosure menu items.
     *
     * @param array $items
     * @return array
     * @since 5.0.0
     */
    public static function normalizeMenuItems(array $items): array
    {
        return array_map(function(array $item) {
            if (!isset($item['type'])) {
                if (isset($item['url'])) {
                    $item['type'] = MenuItemType::Link;
                } elseif ($item['hr'] ?? false) {
                    $item['type'] = MenuItemType::HR;
                } elseif (isset($item['heading']) || isset($item['items'])) {
                    $item['type'] = MenuItemType::Group;
                } else {
                    $item['type'] = MenuItemType::Button;
                }
            }

            if ($item['type'] instanceof MenuItemType) {
                $item['type'] = $item['type']->value;
            }

            if ($item['type'] === MenuItemType::Group->value) {
                $item['items'] = self::normalizeMenuItems($item['items'] ?? []);
            }

            return $item;
        }, $items);
    }

    /**
     * Returns a menu item array for the given sites, possibly grouping them by site group.
     *
     * If only one site is meant to be shown, an empty array will be returned.
     *
     * @param array<int,Site|array{site:Site,status?:string}> $sites
     * @param Site|null $selectedSite
     * @param array $config
     * @return array
     * @since 5.0.0
     */
    public static function siteMenuItems(
        ?array $sites = null,
        ?Site $selectedSite = null,
        array $config = [],
    ): array {
        if ($sites === null) {
            $sites = Craft::$app->getSites()->getEditableSites();
        }

        $config += [
            'showSiteGroupHeadings' => null,
            'includeOmittedSites' => false,
        ];

        $items = [];

        $siteGroups = Craft::$app->getSites()->getAllGroups();
        $config['showSiteGroupHeadings'] ??= count($siteGroups) > 1;

        // Normalize and index the sites
        /** @var array<int,array{site:Site,status?:string}> $sites */
        $sites = Collection::make($sites)
            ->map(fn(Site|array $site) => $site instanceof Site ? ['site' => $site] : $site)
            ->keyBy(fn(array $site) => $site['site']->id)
            ->all();

        $request = Craft::$app->getRequest();
        $path = $request->getPathInfo();
        $params = $request->getQueryParamsWithoutPath();
        unset($params['fresh']);

        $totalSites = 0;

        foreach ($siteGroups as $siteGroup) {
            $groupSites = $siteGroup->getSites();
            if (!$config['includeOmittedSites']) {
                $groupSites = array_filter($groupSites, fn(Site $site) => isset($sites[$site->id]));
            }

            if (empty($groupSites)) {
                continue;
            }

            $totalSites += count($groupSites);

            $groupSiteItems = array_map(fn(Site $site) => [
                'status' => $sites[$site->id]['status'] ?? null,
                'label' => Craft::t('site', $site->name),
                'url' => UrlHelper::cpUrl($path, ['site' => $site->handle] + $params),
                'hidden' => !isset($sites[$site->id]),
                'selected' => $site->id === $selectedSite?->id,
                'attributes' => [
                    'data' => [
                        'site-id' => $site->id,
                    ],
                ],
            ], $groupSites);

            if ($config['showSiteGroupHeadings']) {
                $items[] = [
                    'heading' => Craft::t('site', $siteGroup->name),
                    'items' => $groupSiteItems,
                    'hidden' => !ArrayHelper::contains($groupSiteItems, fn(array $item) => !$item['hidden']),
                ];
            } else {
                array_push($items, ...$groupSiteItems);
            }
        }

        return $totalSites > 1 ? $items : [];
    }

    /**
     * Returns an SVG icon’s contents for the control panel.
     *
     * The icon can be a system icon’s name (e.g. `'whiskey-glass-ice'`), the
     * path to an SVG file, or raw SVG markup.
     *
     * System icons can be found in `src/icons/solid/`.
     *
     * @param string|null $icon
     * @param string|null $fallbackLabel
     * @param string|null $altText
     * @return string|null
     * @since 5.0.0
     */
    public static function iconSvg(?string $icon, ?string $fallbackLabel = null, ?string $altText = null): ?string
    {
        if ($icon === null) {
            return null;
        }

        $locale = Craft::$app->getLocale();
        $orientation = $locale->getOrientation();
        $attributes = [
            'focusable' => 'false',
        ];

        // BC support for some legacy icon names
        $icon = match ($icon) {
            'alert' => 'triangle-exclamation',
            'asc' => 'arrow-down-short-wide',
            'asset', 'assets' => 'image',
            'circleuarr' => 'circle-arrow-up',
            'collapse' => 'down-left-and-up-right-to-center',
            'condition' => 'diamond',
            'darr' => 'arrow-down',
            'date' => 'calendar',
            'desc' => 'arrow-down-wide-short',
            'disabled' => 'circle-dashed',
            'done' => 'circle-check',
            'downangle' => 'angle-down',
            'draft' => 'scribble',
            'edit' => 'pencil',
            'enabled' => 'circle',
            'expand' => 'up-right-and-down-left-from-center',
            'external' => 'arrow-up-right-from-square',
            'field' => 'pen-to-square',
            'help' => 'circle-question',
            'home' => 'house',
            'info' => 'circle-info',
            'insecure' => 'unlock',
            'larr' => 'arrow-left',
            'layout' => 'table-layout',
            'leftangle' => 'angle-left',
            'listrtl' => 'list-flip',
            'location' => 'location-dot',
            'mail' => 'envelope',
            'menu' => 'bars',
            'move' => 'grip-dots',
            'newstamp' => 'certificate',
            'paperplane' => 'paper-plane',
            'plugin' => 'plug',
            'rarr' => 'arrow-right',
            'refresh' => 'arrows-rotate',
            'remove' => 'xmark',
            'rightangle' => 'angle-right',
            'rotate' => 'rotate-left',
            'routes' => 'signs-post',
            'search' => 'magnifying-glass',
            'secure' => 'lock',
            'settings' => 'gear',
            'shareleft' => 'share-flip',
            'shuteye' => 'eye-slash',
            'sidebar-left' => 'sidebar',
            'sidebar-right' => 'sidebar-flip',
            'sidebar-start' => $orientation === 'ltr' ? 'sidebar' : 'sidebar-flip',
            'sidebar-end' => $orientation === 'ltr' ? 'sidebar-flip' : 'sidebar',
            'structure' => 'list-tree',
            'structurertl' => 'list-tree-flip',
            'template' => 'file-code',
            'time' => 'clock',
            'tool' => 'wrench',
            'uarr' => 'arrow-up',
            'upangle' => 'angle-up',
            'view' => 'eye',
            'wand' => 'wand-magic-sparkles',
            'world', 'earth' => self::earthIcon(),
            default => $icon,
        };

        try {
            // system icon name?
            if (preg_match('/^[\da-z\-]+$/', $icon)) {
                $path = match ($icon) {
                    'asterisk-slash',
                    'diamond-slash',
                    'element-card',
                    'element-card-slash',
                    'element-cards',
                    'gear-slash',
                    'graphql',
                    'grip-dots',
                    'image-slash',
                    'list-flip',
                    'list-tree-flip',
                    'notification-bottom-left',
                    'notification-bottom-right',
                    'notification-top-left',
                    'notification-top-right',
                    'share-flip',
                    'slideout-left',
                    'slideout-right',
                    'thumb-left',
                    'thumb-right',
                    => Craft::getAlias("@app/icons/custom-icons/$icon.svg"),
                    default => Craft::getAlias("@appicons/$icon.svg"),
                };
                if (!file_exists($path)) {
                    throw new InvalidArgumentException("Invalid system icon: $icon");
                }
                $svg = file_get_contents($path);
            } else {
                $svg = Html::svg($icon, true, throwException: true);
            }
        } catch (InvalidArgumentException $e) {
            Craft::warning("Could not load icon: {$e->getMessage()}", __METHOD__);
            if (!$fallbackLabel) {
                return '';
            }
            return self::fallbackIconSvg($fallbackLabel);
        }

        if ($altText !== null) {
            $attributes['aria'] = ['label' => $altText];
            $attributes['role'] = 'img';
        } else {
            $attributes['aria'] = [
                'hidden' => 'true',
                'labelledby' => false,
            ];
        }

        // Remove title tag
        $svg = preg_replace(Html::TITLE_TAG_RE, '', $svg);

        // Add attributes for accessibility
        try {
            $svg = Html::modifyTagAttributes($svg, $attributes);
        } catch (InvalidArgumentException) {
        }

        return $svg;
    }

    /**
     * Returns a fallback icon SVG for a component with a given label.
     *
     * @param string $label
     * @return string
     * @since 5.0.0
     */
    public static function fallbackIconSvg(string $label): string
    {
        return Craft::$app->getView()->renderTemplate('_includes/fallback-icon.svg.twig', [
            'label' => $label,
        ]);
    }

    /**
     * Returns the appropriate Earth icon, depending on the system time zone.
     *
     * @return string
     * @since 5.0.0
     */
    public static function earthIcon(): string
    {
        $tzGroup = explode('/', Craft::$app->getTimeZone(), 2)[0];
        return match ($tzGroup) {
            'Africa' => 'earth-africa',
            'Asia' => 'earth-asia',
            'Australia' => 'earth-oceania',
            'Europe', 'GMT', 'UTC' => 'earth-europe',
            default => 'earth-americas',
        };
    }

    /**
     * Returns the site the control panel is currently working with, via a `site` query string param if sent.
     *
     * @return Site|null The site, or `null` if the user doesn’t have permission to edit any sites.
     * @since 4.0.0
     */
    public static function requestedSite(): ?Site
    {
        if (!isset(self::$_requestedSite)) {
            $sitesService = Craft::$app->getSites();
            $editableSiteIds = $sitesService->getEditableSiteIds();

            if (!empty($editableSiteIds)) {
                $request = Craft::$app->getRequest();
                if (
                    !$request->getIsConsoleRequest() &&
                    ($handle = $request->getQueryParam('site')) !== null &&
                    ($site = $sitesService->getSiteByHandle($handle, true)) !== null &&
                    in_array($site->id, $editableSiteIds, false)
                ) {
                    self::$_requestedSite = $site;
                } else {
                    self::$_requestedSite = $sitesService->getCurrentSite();

                    if (!in_array(self::$_requestedSite->id, $editableSiteIds, false)) {
                        // Just go with the first editable site
                        self::$_requestedSite = $sitesService->getSiteById($editableSiteIds[0]);
                    }
                }
            } else {
                self::$_requestedSite = false;
            }
        }

        return self::$_requestedSite ?: null;
    }

    /**
     * Returns the notice that should show when admin is viewing the available settings pages
     * while `allowAdminChanges` is set to false.
     *
     * @return string
     * @since 5.6.0
     */
    public static function readOnlyNoticeHtml(): string
    {
        return
            Html::beginTag('div', [
                'class' => 'content-notice',
            ]) .
            Html::beginTag('div', [
                'class' => ['content-notice-icon'],
                'aria' => ['hidden' => 'true'],
            ]) .
            Html::tag('div', Cp::iconSvg('gear-slash'), [
                'class' => 'cp-icon',
            ]) .
            Html::endTag('div') .
            Html::tag('p', Craft::t(
                'app',
                'Changes to these settings aren’t permitted in this environment.',
            )) .
            Html::endTag('div');
    }

    /**
     * Resets [[requestedSite()]].
     *
     * @since 5.7.0
     */
    public static function reset(): void
    {
        self::$_requestedSite = null;
    }

    /**
     * Processes the given text as Markdown, with extra defenses against invalid tags and double-encoded entities.
     *
     * @param string $text
     * @param string $flavor
     * @return string
     * @since 5.8.3
     */
    public static function parseMarkdown(string $text, string $flavor = 'gfm-comment'): string
    {
        return Html::decodeDoubles(Markdown::process(Html::encodeInvalidTags($text), $flavor));
    }
}
