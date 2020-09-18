<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use craft\base\ElementInterface;
use craft\enums\LicenseKeyStatus;
use craft\events\RegisterCpAlertsEvent;
use http\Exception\InvalidArgumentException;
use yii\base\Event;
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
     * @param string|null $path
     * @param bool $fetch
     * @return array
     */
    public static function alerts(string $path = null, bool $fetch = false): array
    {
        $alerts = [];
        $user = Craft::$app->getUser()->getIdentity();
        $generalConfig = Craft::$app->getConfig()->getGeneral();

        if (!$user) {
            return $alerts;
        }

        $updatesService = Craft::$app->getUpdates();

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
                } else if (Craft::$app->getHasWrongEdition()) {
                    $message = Craft::t('app', 'You’re running Craft {edition} with a Craft {licensedEdition} license.', [
                            'edition' => Craft::$app->getEditionName(),
                            'licensedEdition' => Craft::$app->getLicensedEditionName()
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

                    $alerts[] = $message;
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
                        'domain' => $domainLink
                    ]);
                } else {
                    $keyPath = Craft::$app->getPath()->getLicenseKeyPath();

                    // If the license key path starts with the root project path, trim the project path off
                    $rootPath = Craft::getAlias('@root');
                    if (strpos($keyPath, $rootPath . '/') === 0) {
                        $keyPath = substr($keyPath, strlen($rootPath) + 1);
                    }

                    $message = Craft::t('app', 'The license located at {file} belongs to {domain}.', [
                        'file' => $keyPath,
                        'domain' => $domainLink
                    ]);
                }

                $alerts[] = $message . ' <a class="go" href="https://craftcms.com/support/resolving-mismatched-licenses">' . Craft::t('app', 'Learn more') . '</a>';
            }

            // Any plugin issues?
            if ($path != 'settings/plugins') {
                $pluginsService = Craft::$app->getPlugins();
                $issuePlugins = [];
                foreach ($pluginsService->getAllPlugins() as $pluginHandle => $plugin) {
                    if ($pluginsService->hasIssues($pluginHandle)) {
                        $issuePlugins[] = $plugin->name;
                    }
                }
                if (!empty($issuePlugins)) {
                    if (count($issuePlugins) === 1) {
                        $message = Craft::t('app', 'There’s a licensing issue with the {name} plugin.', [
                            'name' => reset($issuePlugins),
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

                    $alerts[] = $message;
                }
            }
        }

        // Display an alert if there are pending project config YAML changes
        $projectConfig = Craft::$app->getProjectConfig();
        if (
            $path !== 'utilities/project-config' &&
            $user->can('utility:project-config') &&
            $projectConfig->areChangesPending()
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

        return $alerts;
    }

    /**
     * Renders an element’s HTML.
     *
     * @param ElementInterface $element The element to be rendered
     * @param string $context The context the element is going to be shown in (`index`, `field`, etc.)
     * @param string $size The size of the element (`small` or `large`)
     * @param string|null $inputName The `name` attribute that should be set on the hidden input, if `$context` is set to `field`
     * @param bool $showStatus Whether the elemnet status should be shown (if the element type has statuses)
     * @param bool $showThumb Whether the element thumb should be shown (if the element has one)
     * @param bool $showLabel Whether the element label should be shown
     * @return string
     * @since 3.5.8
     */
    public static function elementHtml(
        ElementInterface $element,
        string $context = 'index',
        string $size = self::ELEMENT_SIZE_SMALL,
        string $inputName = null,
        bool $showStatus = true,
        bool $showThumb = true,
        bool $showLabel = true
    ): string {
        $label = $element->getUiLabel();

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
                'title' => $label . (Craft::$app->getIsMultiSite() ? ' – ' . $element->getSite()->name : ''),
            ]);

        if ($context === 'field') {
            $htmlAttributes['class'] .= ' removable';
        }

        if ($element->hasErrors()) {
            $htmlAttributes['class'] .= ' error';
        }

        if ($element::hasStatuses()) {
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

        if ($element->trashed) {
            $html .= ' data-trashed';
        }

        $html .= '>';

        if ($context === 'field' && $inputName !== null) {
            $html .= Html::hiddenInput($inputName . '[]', $element->id) .
                Html::tag('a', '', [
                    'class' => ['delete', 'icon'],
                    'title' => Craft::t('app', 'Remove'),
                ]);
        }

        if ($showStatus && $element::hasStatuses()) {
            $status = $element->getStatus();
            $html .= Html::tag('span', '', [
                'class' => array_filter([
                    'status',
                    $status,
                    $status ? ($element::statuses()[$status]['color'] ?? null) : null,
                ]),
            ]);
        }

        $html .= $imgHtml;

        if ($showLabel) {
            $html .= '<div class="label">';
            $html .= '<span class="title">';

            $encodedLabel = Html::encode($label);

            // Should we make the element a link?
            if (
                $context === 'index' &&
                !$element->trashed &&
                ($cpEditUrl = $element->getCpEditUrl())
            ) {
                if ($element->getIsDraft()) {
                    $cpEditUrl = UrlHelper::urlWithParams($cpEditUrl, ['draftId' => $element->draftId]);
                } else if ($element->getIsRevision()) {
                    $cpEditUrl = UrlHelper::urlWithParams($cpEditUrl, ['revisionId' => $element->revisionId]);
                }

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
     * Renders a field’s HTML.
     *
     * @param string $inputHtml
     * @param array $config
     * @return string
     * @throws InvalidArgumentException if `$config['siteId']` is invalid
     * @since 3.5.8
     */
    public static function fieldHtml(string $inputHtml, array $config = []): string
    {
        $fieldId = $config['fieldId'] ?? (isset($config['id']) ? "{$config['id']}-field" : ('field' . mt_rand()));
        $labelId = $config['labelId'] ?? "$fieldId-label";
        $instructionsId = $config['instructionsId'] ?? "$fieldId-instructions";
        $status = $config['status'] ?? null;
        $label = isset($config['label']) && $config['label'] !== '__blank__' ? $config['label'] : null;
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
        $instructions = $config['instructions'] ?? null;
        $tip = $config['tip'] ?? null;
        $warning = $config['warning'] ?? null;
        $orientation = $config['orientation'] ?? ($site ? $site->getLocale() : Craft::$app->getLocale())->getOrientation();
        $translatable = $config['translatable'] ?? ($site !== null);
        $errors = $config['errors'] ?? null;
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
        $fieldAttributes = ArrayHelper::merge([
            'class' => $fieldClass,
            'id' => $fieldId,
            'aria' => [
                'describedby' => $instructions ? $instructionsId : false,
            ],
        ], $config['fieldAttributes'] ?? []);
        $inputContainerAttributes = ArrayHelper::merge([
            'class' => array_filter([
                'input',
                $orientation,
                $errors ? 'errors' : null,
            ])
        ], $config['inputContainerAttributes'] ?? []);

        return Html::tag('div',
            ($status
                ? Html::tag('div', Html::encode(mb_strtoupper($status[1][0])), [
                    'class' => ['status-badge', $status[0]],
                    'title' => $status[1],
                ])
                : '') .
            (($label || $showAttribute)
                ? Html::tag('div',
                    ($label
                        ? Html::tag('label', $label, ArrayHelper::merge([
                            'id' => $labelId,
                            'class' => $required ? ['required'] : [],
                            'for' => $config['id'] ?? null,
                        ], $config['labelAttributes'] ?? []))
                        : '') .
                    ($translatable
                        ? Html::tag('div', '', [
                            'title' => $config['translationDescription'] ?? Craft::t('app', 'This field is translatable.'),
                            'class' => ['t9n-indicator'],
                            'data' => [
                                'icon' => 'language',
                            ],
                        ])
                        : '') .
                    ($showAttribute
                        ? Html::tag('div', '', [
                            'class' => ['flex-grow'],
                        ]) . Craft::$app->getView()->renderTemplate('_includes/forms/copytextbtn', [
                            'id' => "$fieldId-attribute",
                            'class' => ['code', 'small', 'light'],
                            'value' => $config['attribute'],
                        ])
                        : ''),
                    [
                        'class' => ['heading'],
                    ]
                )
                : '') .
            ($instructions
                ? Html::tag('div', preg_replace('/&amp;(\w+);/', '&$1;', Markdown::process($instructions, 'gfm-comment')), [
                    'id' => $instructionsId,
                    'class' => ['instructions'],
                ])
                : '') .
            Html::tag('div', $inputHtml, $inputContainerAttributes) .
            ($tip
                ? Html::tag('p', preg_replace('/&amp;(\w+);/', '&$1;', Markdown::processParagraph($tip)), [
                    'class' => ['notice', 'with-icon'],
                ])
                : '') .
            ($warning
                ? Html::tag('p', preg_replace('/&amp;(\w+);/', '&$1;', Markdown::processParagraph($warning)), [
                    'class' => ['warning', 'with-icon'],
                ])
                : '') .
            ($errors
                ? Craft::$app->getView()->renderTemplate('_includes/forms/errorList', [
                    'errors' => $errors,
                ])
                : ''),
            $fieldAttributes);
    }
}
