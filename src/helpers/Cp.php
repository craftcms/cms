<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\FieldLayoutElement;
use craft\behaviors\DraftBehavior;
use craft\elements\Address;
use craft\enums\LicenseKeyStatus;
use craft\errors\InvalidHtmlTagException;
use craft\events\DefineElementInnerHtmlEvent;
use craft\events\RegisterCpAlertsEvent;
use craft\fieldlayoutelements\BaseField;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\models\Site;
use craft\web\twig\TemplateLoaderException;
use craft\web\View;
use yii\base\Event;
use yii\base\InvalidArgumentException;
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
     * @event DefineElementInnerHtmlEvent The event that is triggered when defining an element’s inner HTML.
     * @since 4.0.0
     */
    public const EVENT_DEFINE_ELEMENT_INNER_HTML = 'defineElementInnerHtml';

    /**
     * @since 3.5.8
     */
    public const ELEMENT_SIZE_SMALL = 'small';
    /**
     * @since 3.5.8
     */
    public const ELEMENT_SIZE_LARGE = 'large';

    /**
     * @var Site|false
     * @see requestedSite()
     */
    private static Site|false $_requestedSite;

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
        $user = Craft::$app->getUser()->getIdentity();
        $generalConfig = Craft::$app->getConfig()->getGeneral();

        if (!$user) {
            return $alerts;
        }

        $updatesService = Craft::$app->getUpdates();
        $canSettleUp = true;
        $licenseAlerts = [];

        if ($updatesService->getIsUpdateInfoCached() || $fetch) {
            // Fetch the updates regardless of whether we're on the Updates page or not, because the other alerts are
            // relying on cached Craftnet info
            $updatesService->getUpdates();

            // Get the license key status
            $licenseKeyStatus = Craft::$app->getCache()->get('licenseKeyStatus');

            if ($path !== 'plugin-store/upgrade-craft') {
                // Invalid license?
                if ($licenseKeyStatus === LicenseKeyStatus::Invalid) {
                    $alerts[] = Craft::t('app', 'Your Craft license key is invalid.');
                } elseif (Craft::$app->getHasWrongEdition()) {
                    $message = Craft::t('app', 'You’re running Craft {edition} with a Craft {licensedEdition} license.', [
                            'edition' => Craft::$app->getEditionName(),
                            'licensedEdition' => Craft::$app->getLicensedEditionName(),
                        ]) . ' ';
                    if ($user->admin) {
                        if ($generalConfig->allowAdminChanges) {
                            $message .= '<a class="go" href="' . UrlHelper::url('plugin-store/upgrade-craft') . '">' . Craft::t('app', 'Resolve') . '</a>';
                        } else {
                            $message .= Craft::t('app', 'Please fix on an environment where administrative changes are allowed.');
                        }
                    } else {
                        $message .= Craft::t('app', 'Please notify one of your site’s admins.');
                    }

                    $licenseAlerts[] = $message;
                }
            }

            if ($licenseKeyStatus === LicenseKeyStatus::Astray) {
                // todo: swap Console link with a Resolve button
                $alerts[] = Craft::t('app', 'Your Craft license isn’t allowed to run version {version}.', [
                        'version' => Craft::$app->getVersion(),
                    ]) . ' Please renew it from <a href="https://console.craftcms.com">Craft Console</a>.';
            }

            // Any plugin issues?
            if ($path != 'settings/plugins') {
                $pluginsService = Craft::$app->getPlugins();
                $issuePlugins = [];
                foreach ($pluginsService->getAllPlugins() as $pluginHandle => $plugin) {
                    if ($pluginsService->hasIssues($pluginHandle)) {
                        $issuePlugins[] = [$plugin->name, $plugin->handle];
                    }
                }
                if (!empty($issuePlugins)) {
                    if (count($issuePlugins) === 1) {
                        $message = Craft::t('app', 'There’s a licensing issue with the {name} plugin.', [
                            'name' => reset($issuePlugins)[0],
                        ]);
                    } else {
                        $message = Craft::t('app', '{num} plugins have licensing issues.', [
                            'num' => count($issuePlugins),
                        ]);
                    }
                    $message .= ' ';
                    if ($user->admin) {
                        if ($generalConfig->allowAdminChanges) {
                            $message .= '<a class="go" href="' . UrlHelper::cpUrl('settings/plugins') . '">' . Craft::t('app', 'Resolve') . '</a>';
                        } else {
                            $message .= Craft::t('app', 'Please fix on an environment where administrative changes are allowed.');
                        }
                    } else {
                        $message .= Craft::t('app', 'Please notify one of your site’s admins.');
                    }

                    $licenseAlerts[] = $message;

                    // Is this reconcilable?
                    foreach ($issuePlugins as [$pluginName, $pluginHandle]) {
                        if ($pluginsService->getPluginLicenseKeyStatus($pluginHandle) !== LicenseKeyStatus::Trial) {
                            $canSettleUp = false;
                            break;
                        }
                    }
                }
            }

            if (!empty($licenseAlerts)) {
                if ($canSettleUp) {
                    if ($path !== 'plugin-store/buy-all-trials') {
                        $alerts[] = Craft::t('app', 'There are trial licenses that require payment.') . ' ' .
                            Html::a(Craft::t('app', 'Buy now'), UrlHelper::cpUrl('plugin-store/buy-all-trials'), ['class' => 'go']);
                    }
                } else {
                    array_push($alerts, ...$licenseAlerts);
                }
            }

            if (
                $path !== 'utilities/updates' &&
                $user->can('utility:updates') &&
                $updatesService->getIsCriticalUpdateAvailable()
            ) {
                $alerts[] = Craft::t('app', 'A critical update is available.') .
                    ' <a class="go nowrap" href="' . UrlHelper::url('utilities/updates') . '">' . Craft::t('app', 'Go to Updates') . '</a>';
            }

            // Domain mismatch?
            if ($licenseKeyStatus === LicenseKeyStatus::Mismatched) {
                $licensedDomain = Craft::$app->getCache()->get('licensedDomain');
                $domainLink = '<a href="http://' . $licensedDomain . '" rel="noopener" target="_blank">' . $licensedDomain . '</a>';

                if (defined('CRAFT_LICENSE_KEY')) {
                    $message = Craft::t('app', 'The license key in use belongs to {domain}', [
                        'domain' => $domainLink,
                    ]);
                } else {
                    $keyPath = Craft::$app->getPath()->getLicenseKeyPath();

                    // If the license key path starts with the root project path, trim the project path off
                    $rootPath = Craft::getAlias('@root');
                    if (str_starts_with($keyPath, $rootPath . '/')) {
                        $keyPath = substr($keyPath, strlen($rootPath) + 1);
                    }

                    $message = Craft::t('app', 'The license located at {file} belongs to {domain}.', [
                        'file' => $keyPath,
                        'domain' => $domainLink,
                    ]);
                }

                $alerts[] = $message . ' <a class="go" href="https://craftcms.com/support/resolving-mismatched-licenses">' . Craft::t('app', 'Learn more') . '</a>';
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
            $offset = 0;
            while (true) {
                try {
                    $tagInfo = Html::parseTag($alert, $offset);
                } catch (InvalidHtmlTagException $e) {
                    break;
                }

                $newTagHtml = self::alertTagHtml($tagInfo);
                $alert = substr($alert, 0, $tagInfo['start']) .
                    $newTagHtml .
                    substr($alert, $tagInfo['end']);
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
        if ($tagInfo['type'] === 'a') {
            $style = array_merge($style, [
                'color' => 'var(--error-color)',
                'text-decoration' => 'underline',
            ]);

            if (isset($tagInfo['attributes']['class']) && in_array('go', $tagInfo['attributes']['class'])) {
                $style = array_merge($style, [
                    'text-decoration' => 'none',
                    'white-space' => 'nowrap',
                    'border' => '1px solid #cf112480',
                    'border-radius' => 'var(--medium-border-radius)',
                    'padding' => '3px 5px',
                    'margin' => '0 2px',
                ]);
            }
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
     * @param bool $autoReload Whether the element should auto-reload itself when it’s saved
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
        bool $single = false,
        bool $autoReload = true,
    ): string {
        $isDraft = $element->getIsDraft();
        $isRevision = !$isDraft && $element->getIsRevision();
        $label = $element->getUiLabel();
        $showStatus = $showStatus && ($isDraft || $element::hasStatuses());

        // Create the thumb/icon image, if there is one
        if ($showThumb) {
            $thumbSizePx = $size === self::ELEMENT_SIZE_SMALL ? 34 : 120;
            $thumbUrl = $element->getThumbUrl($thumbSizePx);
        } else {
            $thumbSizePx = $thumbUrl = null;
        }

        if ($thumbUrl !== null) {
            $imageSize2x = $thumbSizePx * 2;
            $thumbUrl2x = $element->getThumbUrl($imageSize2x);

            $srcsets = [
                "$thumbUrl {$thumbSizePx}w",
                "$thumbUrl2x {$imageSize2x}w",
            ];
            $sizesHtml = "{$thumbSizePx}px";
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
                    'alt' => $element->getThumbAlt(),
                ],
            ]);
        } else {
            $imgHtml = '';
        }

        $attributes = ArrayHelper::merge(
            Html::normalizeTagAttributes($element->getHtmlAttributes($context)),
            [
                'class' => ['element', $size],
                'title' => $label . (Craft::$app->getIsMultiSite() ? ' – ' . Craft::t('site', $element->getSite()->getName()) : ''),
                'data' => array_filter([
                    'type' => get_class($element),
                    'id' => $element->id,
                    'draft-id' => $element->draftId,
                    'revision-id' => $element->revisionId,
                    'site-id' => $element->siteId,
                    'status' => $element->getStatus(),
                    'label' => (string)$element,
                    'url' => $element->getUrl(),
                    'level' => $element->level,
                    'settings' => $autoReload ? compact(
                        'context',
                        'size',
                        'showStatus',
                        'showThumb',
                        'showLabel',
                        'showDraftName',
                    ) : false,
                ]),
            ]
        );

        if ($context === 'field') {
            $attributes['class'][] = 'removable';
        }

        if ($element->hasErrors()) {
            $attributes['class'][] = 'error';
        }

        if ($showStatus) {
            $attributes['class'][] = 'hasstatus';
        }

        if ($thumbUrl !== null) {
            $attributes['class'][] = 'hasthumb';
        }

        $elementsService = Craft::$app->getElements();
        $user = Craft::$app->getUser()->getIdentity();

        if ($user) {
            if ($elementsService->canView($element, $user)) {
                $attributes['data']['editable'] = true;
            }

            if ($context === 'index') {
                if ($elementsService->canSave($element, $user)) {
                    $attributes['data']['savable'] = true;
                }

                if ($elementsService->canDelete($element, $user)) {
                    $attributes['data']['deletable'] = true;
                }
            }
        }

        if ($element->trashed) {
            $attributes['data']['trashed'] = true;
        }

        $innerHtml = '';

        if ($context === 'field' && $inputName !== null) {
            $innerHtml .= Html::hiddenInput($inputName . ($single ? '' : '[]'), (string)$element->id) .
                Html::button('', [
                    'class' => ['delete', 'icon'],
                    'title' => Craft::t('app', 'Remove'),
                    'aria' => [
                        'label' => Craft::t('app', 'Remove {label}', [
                            'label' => $label,
                        ]),
                    ],
                ]);
        }

        $innerHtml .= $imgHtml;

        if ($showLabel) {
            $innerHtml .= '<div class="label">';
            $innerHtml .= '<span class="title">';

            $encodedLabel = Html::encode($label);

            if ($showDraftName && $isDraft && !$element->getIsUnpublishedDraft()) {
                /** @var DraftBehavior|ElementInterface $element */
                $encodedLabel .= Html::tag('span', $element->draftName ?: Craft::t('app', 'Draft'), [
                    'class' => 'draft-label',
                ]);
            }

            // Should we make the element a link?
            if (
                $user &&
                $context === 'index' &&
                !$element->trashed &&
                ($cpEditUrl = $element->getCpEditUrl()) &&
                $elementsService->canView($element, $user)
            ) {
                $innerHtml .= Html::a($encodedLabel, $cpEditUrl);
            } else {
                $innerHtml .= $encodedLabel;
            }

            if ($element->hasErrors()) {
                $innerHtml .= Html::tag('span', '', [
                    'data' => [
                        'icon' => 'alert',
                    ],
                    'aria' => [
                        'label' => Craft::t('app', 'Error'),
                    ],
                    'role' => 'img',
                ]);
            }

            $innerHtml .= '</span></div>';
        }

        if ($showStatus) {
            if ($isDraft) {
                $innerHtml .= Html::tag('span', '', [
                    'data' => ['icon' => 'draft'],
                    'class' => 'icon',
                    'role' => 'img',
                    'aria' => [
                        'label' => sprintf('%s %s', Craft::t('app', 'Status:'), Craft::t('app', 'Draft')),
                    ],
                ]);
            } else {
                $status = $element->getStatus();
                $statusDef = $element::statuses()[$status] ?? null;
                $innerHtml .= Html::tag('span', '', [
                    'class' => array_filter([
                        'status',
                        $status,
                        $statusDef['color'] ?? null,
                    ]),
                    'role' => 'img',
                    'aria' => [
                        'label' => sprintf('%s %s', Craft::t('app', 'Status:'), $statusDef['label'] ?? $statusDef ?? ucfirst($status)),
                    ],
                ]);
            }
        }

        // Allow plugins to modify the inner HTML
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

        return Html::tag('div', $event->innerHtml, $attributes);
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
        bool $showDraftName = true,
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
        $attribute = $config['attribute'] ?? $config['id'] ?? null;
        $id = $config['id'] = $config['id'] ?? 'field' . mt_rand();
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

        if (str_starts_with($input, 'template:')) {
            // Set labelledBy and describedBy values in case the input template supports it
            if (!isset($config['labelledBy'])) {
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

            $input = static::renderTemplate(substr($input, 9), $config);
        }

        $fieldset = $config['fieldset'] ?? false;
        $fieldId = $config['fieldId'] ?? "$id-field";
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
            ($translatable
                ? Html::tag('span', '', [
                    'class' => ['t9n-indicator'],
                    'title' => $config['translationDescription'] ?? Craft::t('app', 'This field is translatable.'),
                    'data' => [
                        'icon' => 'language',
                    ],
                    'aria' => [
                        'label' => $config['translationDescription'] ?? Craft::t('app', 'This field is translatable.'),
                    ],
                    'role' => 'img',
                ])
                : '')
            );

        $containerTag = $fieldset ? 'fieldset' : 'div';

        return
            Html::beginTag($containerTag, ArrayHelper::merge(
                [
                    'class' => $fieldClass,
                    'id' => $fieldId,
                    'data' => [
                        'attribute' => $attribute,
                    ],
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
                            'class' => $config['labelClass'] ?? null,
                            'for' => !$fieldset ? $id : null,
                            'aria' => [
                                'hidden' => $fieldset ? 'true' : null,
                            ],
                        ], $config['labelAttributes'] ?? []))
                        : '') .
                    ($showAttribute
                        ? Html::tag('div', '', [
                            'class' => ['flex-grow'],
                        ]) . static::renderTemplate('_includes/forms/copytextbtn.twig', [
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
                ? static::renderTemplate('_includes/forms/errorList.twig', [
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
        $config['id'] = $config['id'] ?? 'checkboxselect' . mt_rand();
        $config['fieldset'] = true;
        return static::fieldHtml('template:_includes/forms/checkboxSelect.twig', $config);
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
        return static::fieldHtml('template:_includes/forms/color.twig', $config);
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
        return static::fieldHtml('template:_includes/forms/editableTable.twig', $config);
    }

    /**
     * Renders a lightswitch input’s HTML.
     *
     * @param array $config
     * @return string
     * @throws InvalidArgumentException if `$config['siteId']` is invalid
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
        $config['id'] = $config['id'] ?? 'lightswitch' . mt_rand();

        $config['fieldClass'] = Html::explodeClass($config['fieldClass'] ?? []);
        $config['fieldClass'][] = 'lightswitch-field';

        // Don't pass along `label` since it's ambiguous
        $config['fieldLabel'] = $config['fieldLabel'] ?? $config['label'] ?? null;
        unset($config['label']);

        return static::fieldHtml('template:_includes/forms/lightswitch.twig', $config);
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
        $config['id'] = $config['id'] ?? 'select' . mt_rand();
        return static::fieldHtml('template:_includes/forms/select.twig', $config);
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
        $config['id'] = $config['id'] ?? 'selectize' . mt_rand();
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
        $config['id'] = $config['id'] ?? 'multiselect' . mt_rand();
        return static::fieldHtml('template:_includes/forms/multiselect.twig', $config);
    }

    /**
     * Renders a text input’s HTML.
     *
     * @param array $config
     * @return string
     * @throws InvalidArgumentException if `$config['siteId']` is invalid
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
        $config['id'] = $config['id'] ?? 'text' . mt_rand();
        return static::fieldHtml('template:_includes/forms/text.twig', $config);
    }

    /**
     * Renders a textarea input’s HTML.
     *
     * @param array $config
     * @return string
     * @throws InvalidArgumentException if `$config['siteId']` is invalid
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
        $config['id'] = $config['id'] ?? 'textarea' . mt_rand();
        return static::fieldHtml('template:_includes/forms/textarea.twig', $config);
    }

    /**
     * Returns a date input’s HTML.
     *
     * @param array $config
     * @return string
     * @throws InvalidArgumentException if `$config['siteId']` is invalid
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
        $config['id'] = $config['id'] ?? 'date' . mt_rand();
        return static::fieldHtml('template:_includes/forms/date.twig', $config);
    }

    /**
     * Returns a time input’s HTML.
     *
     * @param array $config
     * @return string
     * @throws InvalidArgumentException if `$config['siteId']` is invalid
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
        $config['id'] = $config['id'] ?? 'time' . mt_rand();
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
        $config['id'] = $config['id'] ?? 'datetime' . mt_rand();
        return static::fieldHtml('template:_includes/forms/datetime.twig', $config);
    }

    /**
     * Renders an element select input’s HTML
     *
     * @param array $config
     * @return string
     * @throws InvalidArgumentException if `$config['siteId']` is invalid
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
        $config['id'] = $config['id'] ?? 'elementselect' . mt_rand();
        return static::fieldHtml('template:_includes/forms/elementSelect.twig', $config);
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
                    Html::a(Craft::t('app', 'Learn more'), 'https://craftcms.com/docs/4.x/config#control-panel-settings', [
                        'class' => 'go',
                    ]);
            } elseif (
                !isset($config['warning']) &&
                ($value === '@web' || str_starts_with($value, '@web/')) &&
                Craft::$app->getRequest()->isWebAliasSetDynamically
            ) {
                $config['warning'] = Craft::t('app', 'The `@web` alias is not recommended if it is determined automatically.');
            }
        }

        return static::fieldHtml('template:_includes/forms/autosuggest.twig', $config);
    }

    /**
     * Renders address cards.
     *
     * @param Address[] $addresses
     * @param array $config
     * @return string
     * @since 4.0.0
     */
    public static function addressCardsHtml(array $addresses, array $config = []): string
    {
        $config += [
            'id' => sprintf('addresses%s', mt_rand()),
            'ownerId' => null,
            'maxAddresses' => null,
        ];

        $view = Craft::$app->getView();

        $view->registerJsWithVars(fn($selector, $settings) => <<<JS
new Craft.AddressesInput($($selector), $settings);
JS, [
            sprintf('#%s', $view->namespaceInputId($config['id'])),
            [
                'ownerId' => $config['ownerId'],
                'maxAddresses' => $config['maxAddresses'],
            ],
        ]);

        return
            Html::beginTag('ul', [
                'id' => $config['id'],
                'class' => 'address-cards',
            ]) .
            implode("\n", array_map(fn(Address $address) => static::addressCardHtml($address, $config), $addresses)) .
            Html::beginTag('li') .
            Html::beginTag('button', [
                'type' => 'button',
                'class' => ['btn', 'dashed', 'add', 'icon', 'address-cards__add-btn'],
            ]) .
            Html::tag('div', '', [
                'class' => ['spinner', 'spinner-absolute'],
            ]) .
            Html::tag('div', Craft::t('app', 'Add an address'), [
                'class' => 'label',
            ]) .
            Html::endTag('button') . // .add
            Html::endTag('li') .
            Html::endTag('ul'); // .address-cards
    }

    /**
     * Renders an address card for an Addresses input.
     *
     * @param Address $address
     * @param array $config
     * @return string
     * @since 4.0.0
     */
    public static function addressCardHtml(Address $address, array $config = []): string
    {
        $config += [
            'name' => null,
        ];

        $canDelete = Craft::$app->getElements()->canDelete($address);
        $actionMenuId = sprintf('address-card-action-menu-%s', mt_rand());

        return
            Html::beginTag('li', [
                'class' => 'address-card',
                'data' => [
                    'id' => $address->id,
                    'draftId' => $address->draftId,
                ],
            ]) .
            ($config['name'] ? Html::hiddenInput("{$config['name']}[]", (string)$address->id) : '') .
            Html::beginTag('div', ['class' => 'address-card-header']) .
            Html::tag('h2', Html::encode($address->title), [
                'class' => array_filter([
                    'address-card-label',
                    !$address->title ? 'hidden' : null,
                ]),
            ]) .
            ($canDelete
                ? Html::beginTag('div', [
                    'class' => 'address-card-header-actions',
                    'data' => [
                        'wrapper' => true,
                    ],
                ]) .
                Html::button('', [
                    'class' => ['btn', 'menubtn'],
                    'title' => Craft::t('app', 'Actions'),
                    'aria' => [
                        'controls' => $actionMenuId,
                        'label' => sprintf('%s %s', $address->title ? Html::encode($address->title) : Craft::t('app', 'New Address'), Craft::t('app', 'Settings')),
                    ],
                    'data' => [
                        'icon' => 'settings',
                        'disclosure-trigger' => true,
                    ],
                ]) .
                Html::beginTag('div', [
                    'id' => $actionMenuId,
                    'class' => ['menu', 'menu--disclosure'],
                ]) .
                Html::beginTag('ul', ['class' => 'padded']) .
                Html::beginTag('li') .
                Html::button(Craft::t('app', 'Edit'), [
                    'class' => 'menu-option',
                    'type' => 'button',
                    'aria' => [
                        'label' => Craft::t('app', 'Edit'),
                    ],
                    'data' => [
                        'icon' => 'edit',
                        'action' => 'edit',
                    ],
                ]) .
                Html::endTag('li') .
                Html::beginTag('li') .
                Html::button(Craft::t('app', 'Delete'), [
                    'class' => 'error menu-option',
                    'type' => 'button',
                    'aria' => [
                        'label' => Craft::t('app', 'Delete'),
                    ],
                    'data' => [
                        'icon' => 'remove',
                        'action' => 'delete',
                    ],
                ]) .
                Html::endTag('li') .
                Html::endTag('ul') .
                Html::endTag('div') . // .menu
                Html::endTag('div') // .address-card-header-actions
                : ''
            ) .
            Html::endTag('div') . // .address-card-header
            Html::tag('div', Craft::$app->getAddresses()->formatAddress($address), [
                'class' => 'address-card-body',
            ]) .
            Html::endTag('li'); // .address-card
    }

    /**
     * Returns address fields’ HTML (sans country) for a given address.
     *
     * @param Address $address
     * @return string
     * @since 4.0.0
     */
    public static function addressFieldsHtml(Address $address): string
    {
        $requiredFields = [];
        $scenario = $address->getScenario();
        $address->setScenario(Element::SCENARIO_LIVE);
        $activeValidators = $address->getActiveValidators();
        $address->setScenario($scenario);

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

        return
            static::textFieldHtml([
                'status' => $address->getAttributeStatus('addressLine1'),
                'label' => $address->getAttributeLabel('addressLine1'),
                'id' => 'addressLine1',
                'name' => 'addressLine1',
                'value' => $address->addressLine1,
                'required' => isset($requiredFields['addressLine1']),
                'errors' => $address->getErrors('addressLine1'),
                'autocomplete' => 'address-line1',
            ]) .
            static::textFieldHtml([
                'status' => $address->getAttributeStatus('addressLine2'),
                'label' => $address->getAttributeLabel('addressLine2'),
                'id' => 'addressLine2',
                'name' => 'addressLine2',
                'value' => $address->addressLine2,
                'required' => isset($requiredFields['addressLine2']),
                'errors' => $address->getErrors('addressLine2'),
                'autocomplete' => 'address-line2',
            ]) .
            self::_subdivisionField(
                $address,
                'administrativeArea',
                isset($visibleFields['administrativeArea']),
                isset($requiredFields['administrativeArea']),
                [$address->countryCode],
                true,
            ) .
            self::_subdivisionField(
                $address,
                'locality',
                isset($visibleFields['locality']),
                isset($requiredFields['locality']),
                [$address->countryCode, $address->administrativeArea],
                true,
            ) .
            self::_subdivisionField(
                $address,
                'dependentLocality',
                isset($visibleFields['dependentLocality']),
                isset($requiredFields['dependentLocality']),
                [$address->countryCode, $address->administrativeArea, $address->locality],
                false,
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
                'required' => isset($requiredFields['postalCode']),
                'errors' => $address->getErrors('postalCode'),
                'autocomplete' => 'postal-code',
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
                'errors' => $address->getErrors('sortingCode'),
            ]);
    }

    private static function _subdivisionField(
        Address $address,
        string $name,
        bool $visible,
        bool $required,
        ?array $parents,
        bool $spinner,
    ): string {
        $value = $address->$name;
        $options = Craft::$app->getAddresses()->getSubdivisionRepository()->getList($parents, Craft::$app->language);

        if ($options) {
            // Persist invalid values in the UI
            if ($value && !isset($options[$value])) {
                $options[$value] = $value;
            }

            if ($spinner) {
                $errors = $address->getErrors($name);
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
            ]);
        }

        // No preconfigured subdivisions for the given parents, so just output a text input
        return static::textFieldHtml([
            'fieldClass' => !$visible ? 'hidden' : null,
            'status' => $address->getAttributeStatus($name),
            'label' => $address->getAttributeLabel($name),
            'id' => $name,
            'name' => $name,
            'value' => $value,
            'required' => $required,
            'errors' => $address->getErrors($name),
        ]);
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
        ];

        $tabs = array_filter($fieldLayout->getTabs(), fn(FieldLayoutTab $tab) => !empty($tab->getElements()));

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

            foreach ($tab->getElements() as $layoutElement) {
                if (!isset($layoutElement->uid)) {
                    $layoutElement->uid = StringHelper::UUID();
                }
            }
        }

        $view = Craft::$app->getView();
        $jsSettings = Json::encode([
            'customizableTabs' => $config['customizableTabs'],
            'customizableUi' => $config['customizableUi'],
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

        // Don't call FieldLayout::getConfig() here because we want to include *all* tabs, not just non-empty ones
        $fieldLayoutConfig = [
            'uid' => $fieldLayout->uid,
            'tabs' => array_map(fn(FieldLayoutTab $tab) => $tab->getConfig(), $tabs),
        ];

        if ($fieldLayout->id) {
            $fieldLayoutConfig['id'] = $fieldLayout->id;
        }

        $newTabSettingsData = self::_fldTabSettingsData(new FieldLayoutTab([
            'uid' => 'TAB_UID',
            'name' => 'TAB_NAME',
            'layout' => $fieldLayout,
        ]));

        return
            Html::beginTag('div', [
                'id' => $config['id'],
                'class' => 'layoutdesigner',
                'data' => [
                    'new-tab-settings-namespace' => $newTabSettingsData['settings-namespace'],
                    'new-tab-settings-html' => $newTabSettingsData['settings-html'],
                    'new-tab-settings-js' => $newTabSettingsData['settings-js'],
                ],
            ]) .
            Html::hiddenInput('fieldLayout', Json::encode($fieldLayoutConfig), [
                'data' => ['config-input' => true],
            ]) .
            Html::beginTag('div', ['class' => 'fld-workspace']) .
            Html::beginTag('div', ['class' => 'fld-tabs']) .
            implode('', array_map(fn(FieldLayoutTab $tab) => self::_fldTabHtml($tab, $config['customizableTabs']), $tabs)) .
            Html::endTag('div') . // .fld-tabs
            ($config['customizableTabs']
                ? Html::button(Craft::t('app', 'New Tab'), [
                    'type' => 'button',
                    'class' => ['fld-new-tab-btn', 'btn', 'add', 'icon'],
                ])
                : '') .
            Html::endTag('div') . // .fld-workspace
            Html::beginTag('div', ['class' => 'fld-sidebar']) .
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
                ]) .
                Html::button(Craft::t('app', 'UI Elements'), [
                    'type' => 'button',
                    'class' => ['btn', 'small'],
                    'aria' => ['pressed' => 'false'],
                    'data' => ['library' => 'ui'],
                ]) .
                Html::endTag('section') // .btngroup
                : '') .
            Html::beginTag('div', ['class' => 'fld-field-library']) .
            Html::beginTag('div', ['class' => ['texticon', 'search', 'icon', 'clearable']]) .
            static::textHtml([
                'class' => 'fullwidth',
                'inputmode' => 'search',
                'placeholder' => Craft::t('app', 'Search'),
            ]) .
            Html::tag('div', '', [
                'class' => ['clear', 'hidden'],
                'title' => Craft::t('app', 'Clear'),
                'aria' => ['label' => Craft::t('app', 'Clear')],
            ]) .
            Html::endTag('div') . // .texticon
            self::_fldFieldSelectorsHtml(Craft::t('app', 'Native Fields'), $availableNativeFields, $fieldLayout) .
            implode('', array_map(fn(string $groupName) => self::_fldFieldSelectorsHtml($groupName, $availableCustomFields[$groupName], $fieldLayout), array_keys($availableCustomFields))) .
            Html::endTag('div') . // .fld-field-library
            ($config['customizableUi']
                ? Html::beginTag('div', ['class' => ['fld-ui-library', 'hidden']]) .
                implode('', array_map(fn(FieldLayoutElement $element) => self::_fldElementSelectorHtml($element, true), $availableUiElements)) .
                Html::endTag('div') // .fld-ui-library
                : '') .
            Html::endTag('div') . // .fld-sidebar
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
     * @return string
     */
    private static function _fldTabHtml(FieldLayoutTab $tab, bool $customizable): string
    {
        return
            Html::beginTag('div', [
                'class' => 'fld-tab',
                'data' => array_merge([
                    'uid' => $tab->uid,
                ], self::_fldTabSettingsData($tab)),
            ]) .
            Html::beginTag('div', ['class' => 'tabs']) .
            Html::beginTag('div', [
                'class' => array_filter([
                    'tab',
                    'sel',
                    $customizable ? 'draggable' : null,
                ]),
            ]) .
            Html::tag('span', Html::encode($tab->name)) .
            ($customizable
                ? Html::a('', null, [
                    'role' => 'button',
                    'class' => ['settings', 'icon'],
                    'title' => Craft::t('app', 'Edit'),
                    'aria' => ['label' => Craft::t('app', 'Edit')],
                ]) :
                '') .
            Html::endTag('div') . // .tab
            Html::endTag('div') . // .tabs
            Html::beginTag('div', ['class' => 'fld-tabcontent']) .
            implode('', array_map(fn(FieldLayoutElement $element) => self::_fldElementSelectorHtml($element, false), $tab->getElements())) .
            Html::endTag('div') . // .fld-tabcontent
            Html::endTag('div'); // .fld-tab
    }

    /**
     * @param FieldLayoutTab $tab
     * @return array
     */
    private static function _fldTabSettingsData(FieldLayoutTab $tab): array
    {
        $view = Craft::$app->getView();
        $oldNamespace = $view->getNamespace();
        $namespace = $view->namespaceInputName("tab-$tab->uid");
        $view->setNamespace($namespace);
        $view->startJsBuffer();
        $settingsHtml = $view->namespaceInputs($tab->getSettingsHtml());
        $settingsJs = $view->clearJsBuffer(false);
        $view->setNamespace($oldNamespace);

        return [
            'settings-namespace' => $namespace,
            'settings-html' => $settingsHtml,
            'settings-js' => $settingsJs,
        ];
    }

    /**
     * @param FieldLayoutElement $element
     * @param bool $forLibrary
     * @param array $attr
     * @return string
     */
    private static function _fldElementSelectorHtml(FieldLayoutElement $element, bool $forLibrary, array $attr = []): string
    {
        if ($element instanceof BaseField) {
            $attr = ArrayHelper::merge($attr, [
                'class' => !$forLibrary && $element->required ? ['fld-required'] : [],
                'data' => [
                    'keywords' => $forLibrary ? implode(' ', array_map('mb_strtolower', $element->keywords())) : false,
                ],
            ]);
        }

        $view = Craft::$app->getView();
        $oldNamespace = $view->getNamespace();
        $namespace = $view->namespaceInputName('element-' . ($forLibrary ? 'ELEMENT_UID' : $element->uid));
        $view->setNamespace($namespace);
        $view->startJsBuffer();
        $settingsHtml = $view->namespaceInputs($element->getSettingsHtml());
        $settingsJs = $view->clearJsBuffer(false);
        $view->setNamespace($oldNamespace);

        $attr = ArrayHelper::merge($attr, [
            'class' => array_filter([
                'fld-element',
                $forLibrary ? 'unused' : null,
                !$forLibrary && $element->hasConditions() ? 'has-conditions' : null,
            ]),
            'data' => [
                'uid' => !$forLibrary ? $element->uid : false,
                'config' => $forLibrary ? ['type' => get_class($element)] + $element->toArray() : false,
                'has-custom-width' => $element->hasCustomWidth(),
                'settings-namespace' => $namespace,
                'settings-html' => $settingsHtml ?: false,
                'settings-js' => $settingsJs ?: false,
            ],
        ]);

        return Html::modifyTagAttributes($element->selectorHtml(), $attr);
    }

    /**
     * @param string $groupName
     * @param BaseField[] $groupFields
     * @param FieldLayout $fieldLayout
     * @return string
     */
    private static function _fldFieldSelectorsHtml(string $groupName, array $groupFields, FieldLayout $fieldLayout): string
    {
        $showGroup = ArrayHelper::contains($groupFields, fn(BaseField $field) => !$fieldLayout->isFieldIncluded($field->attribute()));

        return
            Html::beginTag('div', [
                'class' => array_filter([
                    'fld-field-group',
                    $showGroup ? null : 'hidden',
                ]),
                'data' => ['name' => mb_strtolower($groupName)],
            ]) .
            Html::tag('h6', Html::encode($groupName)) .
            implode('', array_map(fn(BaseField $field) => self::_fldElementSelectorHtml($field, true, [
                'class' => array_filter([
                    $fieldLayout->isFieldIncluded($field->attribute()) ? 'hidden' : null,
                ]),
            ]), $groupFields)) .
            Html::endTag('div'); // .fld-field-group
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
}
