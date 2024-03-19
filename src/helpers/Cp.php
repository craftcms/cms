<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use craft\base\ElementInterface;
use craft\behaviors\DraftBehavior;
use craft\behaviors\RevisionBehavior;
use craft\enums\LicenseKeyStatus;
use craft\errors\InvalidHtmlTagException;
use craft\errors\InvalidPluginException;
use craft\events\RegisterCpAlertsEvent;
use craft\web\twig\TemplateLoaderException;
use craft\web\View;
use yii\base\Event;
use yii\base\InvalidArgumentException;
use yii\helpers\Markdown;

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
    const EVENT_REGISTER_ALERTS = 'registerAlerts';

    /**
     * @since 3.5.8
     */
    const ELEMENT_SIZE_SMALL = 'small';
    /**
     * @since 3.5.8
     */
    const ELEMENT_SIZE_LARGE = 'large';

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
     */
    public static function alerts(?string $path = null, bool $fetch = false): array
    {
        $alerts = [];
        $resolvableLicenseAlerts = [];
        $resolvableLicenseItems = [];
        $user = Craft::$app->getUser()->getIdentity();
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $consoleUrl = rtrim(Craft::$app->getPluginStore()->craftIdEndpoint, '/');

        if (!$user) {
            return $alerts;
        }

        $updatesService = Craft::$app->getUpdates();
        $cache = Craft::$app->getCache();
        $isInfoCached = $cache->exists('licenseInfo') && $updatesService->getIsUpdateInfoCached();

        if (!$isInfoCached && $fetch) {
            $updatesService->getUpdates(true);
            $isInfoCached = true;
        }

        if ($isInfoCached) {
            $allLicenseInfo = $cache->get('licenseInfo') ?: [];
            $pluginsService = Craft::$app->getPlugins();
            $canTestEditions = Craft::$app->getCanTestEditions();

            foreach ($allLicenseInfo as $handle => $licenseInfo) {
                $isCraft = $handle === 'craft';
                if ($isCraft) {
                    $name = 'Craft CMS';
                    $editions = ['solo', 'pro'];
                    $currentEdition = Craft::$app->getEditionHandle();
                    $currentEditionName = Craft::$app->getEditionName();
                    $licenseEditionName = App::editionName(App::editionIdByHandle($licenseInfo['edition'] ?? 'solo'));
                    $version = Craft::$app->getVersion();
                } else {
                    if (!str_starts_with($handle, 'plugin-')) {
                        continue;
                    }
                    $handle = StringHelper::removeLeft($handle, 'plugin-');

                    try {
                        $pluginInfo = $pluginsService->getPluginInfo($handle);
                    } catch (InvalidPluginException $e) {
                        continue;
                    }

                    $plugin = $pluginsService->getPlugin($handle);
                    if (!$plugin) {
                        continue;
                    }

                    $name = $plugin->name;
                    $editions = $plugin::editions();
                    $currentEdition = $pluginInfo['edition'];
                    $currentEditionName = ucfirst($currentEdition);
                    $licenseEditionName = ucfirst($licenseInfo['edition'] ?? 'standard');
                    $version = $pluginInfo['version'];
                }

                if ($licenseInfo['status'] === LicenseKeyStatus::Invalid) {
                    // invalid license
                    $alerts[] = Craft::t('app', 'The {name} license is invalid.', [
                        'name' => $name,
                    ]);
                } elseif ($licenseInfo['status'] === LicenseKeyStatus::Trial && !$canTestEditions) {
                    // no trials allowed
                    $resolvableLicenseAlerts[] = Craft::t('app', '{name} requires purchase.', [
                        'name' => $name,
                    ]);
                    $resolvableLicenseItems[] = array_filter([
                        'type' => $isCraft ? 'cms-edition' : 'plugin-edition',
                        'plugin' => !$isCraft ? $handle : null,
                        'licenseId' => $licenseInfo['id'],
                        'edition' => $currentEdition,
                    ]);
                } elseif ($licenseInfo['status'] === LicenseKeyStatus::Mismatched) {
                    if ($isCraft) {
                        // wrong domain
                        $licensedDomain = $cache->get('licensedDomain');
                        $domainLink = Html::a($licensedDomain, "http://$licensedDomain", [
                            'rel' => 'noopener',
                            'target' => '_blank',
                        ]);

                        if (defined('CRAFT_LICENSE_KEY')) {
                            $message = Craft::t('app', 'The Craft CMS license key in use belongs to {domain}', [
                                'domain' => $domainLink,
                            ]);
                        } else {
                            $keyPath = Craft::$app->getPath()->getLicenseKeyPath();

                            // If the license key path starts with the root project path, trim the project path off
                            $rootPath = Craft::getAlias('@root');
                            if (strpos($keyPath, $rootPath . '/') === 0) {
                                $keyPath = substr($keyPath, strlen($rootPath) + 1);
                            }

                            $message = Craft::t('app', 'The Craft CMS license located at {file} belongs to {domain}.', [
                                'file' => $keyPath,
                                'domain' => $domainLink,
                            ]);
                        }

                        $learnMoreLink = Html::a(Craft::t('app', 'Learn more'), 'https://craftcms.com/support/resolving-mismatched-licenses', [
                            'class' => 'go',
                        ]);
                        $alerts[] = "$message $learnMoreLink";
                    } else {
                        // wrong Craft install
                        $alerts[] = Craft::t('app', 'The {name} license is attached to a different Craft CMS license. You can <a class="go" href="{detachUrl}">detach it in Craft Console</a> or <a class="go" href="{buyUrl}">buy a new license</a>.', [
                            'name' => $name,
                            'detachUrl' => "$consoleUrl/licenses/plugins/{$licenseInfo['id']}",
                            'buyUrl' => $user->admin && $generalConfig->allowAdminChanges
                                ? UrlHelper::cpUrl("plugin-store/buy/$handle/$currentEdition")
                                : "https://plugins.craftcms.com/$handle",
                        ]);
                    }
                } elseif ($licenseInfo['edition'] !== $currentEdition && !$canTestEditions) {
                    // wrong edition
                    $message = Craft::t('app', '{name} is licensed for the {licenseEdition} edition, but the {currentEdition} edition is installed.', [
                        'name' => $name,
                        'licenseEdition' => $licenseEditionName,
                        'currentEdition' => $currentEditionName,
                    ]);
                    $currentEditionIdx = array_search($currentEdition, $editions);
                    $licenseEditionIdx = array_search($licenseInfo['edition'], $editions);
                    if ($currentEditionIdx !== false && $licenseEditionIdx !== false && $currentEditionIdx > $licenseEditionIdx) {
                        $resolvableLicenseAlerts[] = $message;
                        $resolvableLicenseItems[] = [
                            'type' => $isCraft ? 'cms-edition' : 'plugin-edition',
                            'edition' => $currentEdition,
                            'licenseId' => $licenseInfo['id'],
                        ];
                    }
                } elseif ($licenseInfo['status'] === LicenseKeyStatus::Astray) {
                    // updated too far
                    $resolvableLicenseAlerts[] = Craft::t('app', '{name} isn’t licensed to run version {version}.', [
                        'name' => $name,
                        'version' => $version,
                    ]);
                    $resolvableLicenseItems[] = array_filter([
                        'type' => $isCraft ? 'cms-renewal' : 'plugin-renewal',
                        'plugin' => !$isCraft ? $handle : null,
                        'licenseId' => $licenseInfo['id'],
                    ]);
                }
            }

            if (!empty($resolvableLicenseAlerts)) {
                $cartUrl = UrlHelper::urlWithParams("$consoleUrl/cart/new", [
                    'items' => $resolvableLicenseItems,
                ]);
                $alerts[] = [
                    'content' => Html::tag('h2', Craft::t('app', 'Licensing Issues')) .
                        Html::tag('p', Craft::t('app', 'The following licensing issues can be resolved with a single purchase on Craft Console.')) .
                        Html::ul($resolvableLicenseAlerts, [
                            'class' => 'errors',
                        ]) .
                        // can't use Html::a() because it's encoding &amp;'s, which is causing issues
                        Html::tag('p', sprintf('<a class="go" href="%s">%s</a>', $cartUrl, Craft::t('app', 'Resolve now'))),
                    'showIcon' => false,
                ];
            }

            // Critical update available?
            if (
                $path !== 'utilities/updates' &&
                $user->can('utility:updates') &&
                $updatesService->getIsCriticalUpdateAvailable()
            ) {
                $alerts[] = Craft::t('app', 'A critical update is available.') .
                    ' <a class="go nowrap" href="' . UrlHelper::url('utilities/updates') . '">' . Craft::t('app', 'Go to Updates') . '</a>';
            }
        }

        // Display an alert if there are pending project config YAML changes
        $projectConfig = Craft::$app->getProjectConfig();
        if (
            $path !== 'utilities/project-config' &&
            $user->can('utility:project-config') &&
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

        // Give plugins a chance to add their own alerts
        $event = new RegisterCpAlertsEvent();
        Event::trigger(self::class, self::EVENT_REGISTER_ALERTS, $event);
        $alerts = array_merge($alerts, $event->alerts);

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

        $childTagHtml = array_map(function(array $childTagInfo): string {
            return self::alertTagHtml($childTagInfo);
        }, $tagInfo['children'] ?? []);

        return trim(static::renderTemplate('_layouts/components/tag.twig', [
            'type' => $tagInfo['type'],
            'attributes' => $tagInfo['attributes'] ?? [],
            'style' => $style,
            'content' => implode('', $childTagHtml),
        ]));
    }

    /**
     * Renders an element’s HTML.
     *
     * @param ElementInterface $element The element to be rendered
     * @param string $context The context the element is going to be shown in (`index`, `field`, etc.)
     * @param string $size The size of the element (`small` or `large`)
     * @param string|null $inputName The `name` attribute that should be set on the hidden input, if `$context` is set to `field`
     * @param bool $showStatus Whether the element status should be shown (if the element type has statuses)
     * @param bool $showThumb Whether the element thumb should be shown (if the element has one)
     * @param bool $showLabel Whether the element label should be shown
     * @param bool $showDraftName Whether to show the draft name beside the label if the element is a draft of a published element
     * @param bool $single Whether the input name should omit the trailing `[]`
     * @return string
     * @since 3.5.8
     */
    public static function elementHtml(
        ElementInterface $element,
        string $context = 'index',
        string $size = self::ELEMENT_SIZE_SMALL,
        ?string $inputName = null,
        bool $showStatus = true,
        bool $showThumb = true,
        bool $showLabel = true,
        bool $showDraftName = true,
        bool $single = false
    ): string {
        $isDraft = $element->getIsDraft();
        $isRevision = !$isDraft && $element->getIsRevision();
        $label = $element->getUiLabel();
        $showStatus = $showStatus && ($isDraft || $element::hasStatuses());

        // Create the thumb/icon image, if there is one
        if ($showThumb) {
            $thumbSize = $size === self::ELEMENT_SIZE_SMALL ? 34 : 120;
            $thumbUrl = $element->getThumbUrl($thumbSize);
        } else {
            $thumbSize = $thumbUrl = null;
        }

        if ($thumbUrl !== null) {
            $imageSize2x = $thumbSize * 2;
            $thumbUrl2x = $element->getThumbUrl($imageSize2x);

            $srcsets = [
                "$thumbUrl {$thumbSize}w",
                "$thumbUrl2x {$imageSize2x}w",
            ];
            $sizesHtml = "{$thumbSize}px";
            $srcsetHtml = implode(', ', $srcsets);
            $imgHtml = Html::tag('div', '', [
                'class' => array_filter([
                    'elementthumb',
                    $element->getHasCheckeredThumb() ? 'checkered' : null,
                    $size === self::ELEMENT_SIZE_SMALL && $element->getHasRoundedThumb() ? 'rounded' : null,
                ]),
                'data' => [
                    'sizes' => $sizesHtml,
                    'srcset' => $srcsetHtml,
                ],
            ]);
        } else {
            $imgHtml = '';
        }

        $htmlAttributes = array_merge(
            $element->getHtmlAttributes($context),
            [
                'class' => 'element ' . $size,
                'data-type' => get_class($element),
                'data-id' => $element->id,
                'data-site-id' => $element->siteId,
                'data-status' => $element->getStatus(),
                'data-label' => (string)$element,
                'data-url' => $element->getUrl(),
                'data-level' => $element->level,
                'title' => $label . (Craft::$app->getIsMultiSite() ? ' – ' . Craft::t('site', $element->getSite()->getName()) : ''),
            ]);

        if ($context === 'field') {
            $htmlAttributes['class'] .= ' removable';
        }

        if ($element->hasErrors()) {
            $htmlAttributes['class'] .= ' error';
        }

        if ($showStatus) {
            $htmlAttributes['class'] .= ' hasstatus';
        }

        if ($thumbUrl !== null) {
            $htmlAttributes['class'] .= ' hasthumb';
        }

        $html = '<div';

        // todo: swap this with Html::renderTagAttributse in 4.0
        // (that will cause a couple breaking changes since `null` means "don't show" and `true` means "no value".)
        foreach ($htmlAttributes as $attribute => $value) {
            $html .= ' ' . $attribute . ($value !== null ? '="' . Html::encode($value) . '"' : '');
        }

        if (ElementHelper::isElementEditable($element)) {
            $html .= ' data-editable';
        }

        if ($context === 'index' && $element->getIsDeletable()) {
            $html .= ' data-deletable';
        }

        if ($element->trashed) {
            $html .= ' data-trashed';
        }

        $html .= '>';

        if ($context === 'field' && $inputName !== null) {
            $html .= Html::hiddenInput($inputName . ($single ? '' : '[]'), $element->id) .
                Html::tag('button', '', [
                    'class' => ['delete', 'icon'],
                    'title' => Craft::t('app', 'Remove'),
                    'type' => 'button',
                    'aria' => [
                        'label' => Craft::t('app', 'Remove {label}', [
                            'label' => $label,
                        ]),
                    ],
                ]);
        }

        if ($showStatus) {
            if ($isDraft) {
                $html .= Html::tag('span', '', [
                    'class' => ['icon'],
                    'aria' => [
                        'hidden' => 'true',
                    ],
                    'data' => [
                        'icon' => 'draft',
                    ],
                ]);
            } else {
                $status = !$isRevision ? $element->getStatus() : null;
                $html .= Html::tag('span', '', [
                    'class' => array_filter([
                        'status',
                        $status,
                        $status ? ($element::statuses()[$status]['color'] ?? null) : null,
                    ]),
                ]);
            }
        }

        $html .= $imgHtml;

        if ($showLabel) {
            $html .= '<div class="label">';
            $html .= '<span class="title">';

            $encodedLabel = Html::encode($label);

            if ($showDraftName && $isDraft && !$element->getIsUnpublishedDraft()) {
                /** @var DraftBehavior|ElementInterface $element */
                $encodedLabel .= Html::tag('span', $element->draftName ?: Craft::t('app', 'Draft'), [
                    'class' => 'draft-label',
                ]);
            }

            // Should we make the element a link?
            if (
                $context === 'index' &&
                !$element->trashed &&
                ($cpEditUrl = $element->getCpEditUrl())
            ) {
                $html .= Html::a($encodedLabel, $cpEditUrl);
            } else {
                $html .= $encodedLabel;
            }

            $html .= '</span></div>';
        }

        $html .= '</div>';

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
        string $size = self::ELEMENT_SIZE_SMALL,
        bool $showStatus = true,
        bool $showThumb = true,
        bool $showLabel = true,
        bool $showDraftName = true
    ): string {
        if (empty($elements)) {
            return '';
        }

        $first = array_shift($elements);
        $html = static::elementHtml($first, 'index', $size, null, $showStatus, $showThumb, $showLabel, $showDraftName);

        if (!empty($elements)) {
            $otherHtml = '';
            foreach ($elements as $other) {
                $otherHtml .= static::elementHtml($other, 'index', $size, null, $showStatus, $showThumb, $showLabel, $showDraftName);
            }
            $html .= Html::tag('span', '+' . Craft::$app->getFormatter()->asInteger(count($elements)), [
                'title' => implode(', ', ArrayHelper::getColumn($elements, 'title')),
                'class' => 'btn small',
                'role' => 'button',
                'onclick' => 'jQuery(this).replaceWith(' . Json::encode($otherHtml) . ')',
            ]);
        }

        return $html;
    }

    /**
     * Renders a field’s HTML, for the given input HTML or a template.
     *
     * @param string $input The input HTML or template path. If passing a template path, it must begin with `template:`.
     * @param array $config
     * @return string
     * @throws TemplateLoaderException if $input begins with `template:` and is followed by an invalid template path
     * @throws InvalidArgumentException if `$config['siteId']` is invalid
     * @since 3.5.8
     */
    public static function fieldHtml(string $input, array $config = []): string
    {
        $id = $config['id'] = $config['id'] ?? 'field' . mt_rand();
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

        if (StringHelper::startsWith($input, 'template:')) {
            // Set a describedBy value in case the input template supports it
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

            $input = static::renderTemplate(substr($input, 9), $config);
        }

        $fieldset = $config['fieldset'] ?? false;
        $fieldId = $config['fieldId'] ?? "$id-field";
        $labelId = $config['labelId'] ?? "$id-" . ($fieldset ? 'legend' : 'label');
        $label = $config['fieldLabel'] ?? $config['label'] ?? null;

        if ($label === '__blank__') {
            $label = null;
        }

        $siteId = Craft::$app->getIsMultiSite() && isset($config['siteId']) ? (int)$config['siteId'] : null;

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

        if (isset($config['attribute']) && ($currentUser = Craft::$app->getUser()->getIdentity())) {
            $showAttribute = $currentUser->admin && $currentUser->getPreference('showFieldHandles');
        } else {
            $showAttribute = false;
        }

        $instructionsHtml = $instructions
            ? Html::tag('div', preg_replace('/&amp;(\w+);/', '&$1;', Markdown::process(Html::encodeInvalidTags($instructions), 'gfm-comment')), [
                'id' => $instructionsId,
                'class' => ['instructions'],
            ])
            : '';

        $labelHtml = $label . (
            $required
                ? Html::tag('span', Craft::t('app', 'Required'), [
                    'class' => ['visually-hidden'],
                ]) .
                Html::tag('span', '', [
                    'class' => ['required'],
                    'aria' => [
                        'hidden' => 'true',
                    ],
                ])
                : ''
            );

        $containerTag = $fieldset ? 'fieldset' : 'div';

        return
            Html::beginTag($containerTag, ArrayHelper::merge(
                [
                    'class' => $fieldClass,
                    'id' => $fieldId,
                ],
                $config['fieldAttributes'] ?? []
            )) .
            (($label && $fieldset)
                ? Html::tag('legend', $labelHtml, [
                    'class' => ['visually-hidden'],
                    'data' => [
                        'label' => $label,
                    ],
                ])
                : '') .
            ($status
                ? Html::beginTag('div', [
                    'id' => $statusId,
                    'class' => ['status-badge', $status[0]],
                    'title' => $status[1],
                ]) .
                Html::tag('span', $status[1], [
                    'class' => 'visually-hidden',
                ]) .
                Html::endTag('div')
                : '') .
            (($label || $showAttribute)
                ? (
                    Html::beginTag('div', ['class' => 'heading']) .
                    ($config['headingPrefix'] ?? '') .
                    ($label
                        ? Html::tag($fieldset ? 'legend' : 'label', $labelHtml, ArrayHelper::merge([
                            'id' => $labelId,
                            'for' => !$fieldset ? $id : null,
                            'aria' => [
                                'hidden' => $fieldset ? 'true' : null,
                            ],
                        ], $config['labelAttributes'] ?? []))
                        : '') .
                    ($translatable
                        ? Html::tag('div', '', [
                            'class' => ['t9n-indicator'],
                            'title' => $config['translationDescription'] ?? Craft::t('app', 'This field is translatable.'),
                            'aria' => [
                                'label' => $config['translationDescription'] ?? Craft::t('app', 'This field is translatable.'),
                            ],
                            'data' => [
                                'icon' => 'language',
                            ],
                        ])
                        : '') .
                    ($showAttribute
                        ? Html::tag('div', '', [
                            'class' => ['flex-grow'],
                        ]) . static::renderTemplate('_includes/forms/copytextbtn', [
                            'id' => "$id-attribute",
                            'class' => ['code', 'small', 'light'],
                            'value' => $config['attribute'],
                        ])
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
                    ]),
                ],
                $config['inputContainerAttributes'] ?? []
            )) .
            ($instructionsPosition === 'after' ? $instructionsHtml : '') .
            self::_noticeHtml($tipId, 'notice', Craft::t('app', 'Tip:'), $tip) .
            self::_noticeHtml($warningId, 'warning', Craft::t('app', 'Warning:'), $warning) .
            ($errors
                ? static::renderTemplate('_includes/forms/errorList', [
                    'id' => $errorsId,
                    'errors' => $errors,
                ])
                : '') .
            Html::endTag($containerTag);
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
            Html::tag('span', preg_replace('/&amp;(\w+);/', '&$1;', Markdown::processParagraph(Html::encodeInvalidTags($message)))) .
            Html::endTag('p');
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
        $config['id'] = $config['id'] ?? 'checkbox' . mt_rand();

        $config['fieldClass'] = Html::explodeClass($config['fieldClass'] ?? []);
        $config['fieldClass'][] = 'checkboxfield';
        $config['instructionsPosition'] = $config['instructionsPosition'] ?? 'after';

        // Don't pass along `label` since it's ambiguous
        unset($config['label']);

        return static::fieldHtml('template:_includes/forms/checkbox', $config);
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
        $config['id'] = $config['id'] ?? 'checkboxselect' . mt_rand();
        $config['fieldset'] = true;
        return static::fieldHtml('template:_includes/forms/checkboxSelect', $config);
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
        $config['id'] = $config['id'] ?? 'color' . mt_rand();
        $config['fieldset'] = true;
        return static::fieldHtml('template:_includes/forms/color', $config);
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
        $config['id'] = $config['id'] ?? 'editabletable' . mt_rand();
        return static::fieldHtml('template:_includes/forms/editableTable', $config);
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
        $config['id'] = $config['id'] ?? 'lightswitch' . mt_rand();

        $config['fieldClass'] = Html::explodeClass($config['fieldClass'] ?? []);
        $config['fieldClass'][] = 'lightswitch-field';

        // Don't pass along `label` since it's ambiguous
        $config['fieldLabel'] = $config['fieldLabel'] ?? $config['label'] ?? null;
        unset($config['label']);

        return static::fieldHtml('template:_includes/forms/lightswitch', $config);
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
        return static::renderTemplate('_includes/forms/select', $config);
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
        $config['id'] = $config['id'] ?? 'select' . mt_rand();
        return static::fieldHtml('template:_includes/forms/select', $config);
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
        $config['id'] = $config['id'] ?? 'text' . mt_rand();
        return static::fieldHtml('template:_includes/forms/text', $config);
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
        $config['id'] = $config['id'] ?? 'textarea' . mt_rand();
        return static::fieldHtml('template:_includes/forms/textarea', $config);
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
        $config['id'] = $config['id'] ?? 'datetime' . mt_rand();
        return static::fieldHtml('template:_includes/forms/datetime', $config);
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
        $config['id'] = $config['id'] ?? 'elementselect' . mt_rand();
        return static::fieldHtml('template:_includes/forms/elementSelect', $config);
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
        $config['id'] = $config['id'] ?? 'autosuggest' . mt_rand();

        // Suggest an environment variable / alias?
        if ($config['suggestEnvVars'] ?? false) {
            $value = $config['value'] ?? '';
            if (!isset($config['tip']) && (!isset($value[0]) || !in_array($value[0], ['$', '@']))) {
                if ($config['suggestAliases'] ?? false) {
                    $config['tip'] = Craft::t('app', 'This can be set to an environment variable, or begin with an alias.');
                } else {
                    $config['tip'] = Craft::t('app', 'This can be set to an environment variable.');
                }
                $config['tip'] .= ' ' .
                    Html::a(Craft::t('app', 'Learn more'), 'https://craftcms.com/docs/3.x/config/#environmental-configuration', [
                        'class' => 'go',
                    ]);
            } elseif (
                !isset($config['warning']) &&
                ($value === '@web' || strpos($value, '@web/') === 0) &&
                Craft::$app->getRequest()->isWebAliasSetDynamically
            ) {
                $config['warning'] = Craft::t('app', 'The `@web` alias is not recommended if it is determined automatically.');
            }
        }

        return static::fieldHtml('template:_includes/forms/autosuggest', $config);
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
                $defs[] = Html::tag('div',
                    Html::tag('dt', Html::encode($label), ['class' => 'heading']) . "\n" .
                    Html::tag('dd', $value, ['class' => 'value']), [
                        'class' => 'data',
                    ]);
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
     * Returns the page title and document title that should be used for Edit Element pages.
     *
     * @param ElementInterface $element
     * @return string[]
     * @since 3.7.0
     */
    public static function editElementTitles(ElementInterface $element): array
    {
        $title = trim((string)$element->title);

        if ($title === '') {
            if (!$element->id || $element->getIsUnpublishedDraft()) {
                $title = Craft::t('app', 'Create a new {type}', [
                    'type' => $element::lowerDisplayName(),
                ]);
            } else {
                $title = Craft::t('app', 'Edit {type}', [
                    'type' => $element::displayName(),
                ]);
            }
        }

        $docTitle = $title;

        if ($element->getIsDraft()) {
            /** @var ElementInterface|DraftBehavior $element */
            if ($element->isProvisionalDraft) {
                $docTitle .= ' — ' . Craft::t('app', 'Edited');
            } else {
                $docTitle .= " ($element->draftName)";
            }
        } elseif ($element->getIsRevision()) {
            /** @var ElementInterface|RevisionBehavior $element */
            $docTitle .= ' (' . $element->getRevisionLabel() . ')';
        }

        return [$docTitle, $title];
    }
}
