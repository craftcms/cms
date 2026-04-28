<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp;

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Cp\Events\RegisterCpAlerts;
use CraftCms\Cms\Edition;
use CraftCms\Cms\License\License;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Api;
use CraftCms\Cms\Support\Exceptions\InvalidHtmlTagException;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Update\Updates;
use CraftCms\Cms\Utility\Utilities;
use CraftCms\Cms\Utility\Utilities\ProjectConfig as ProjectConfigUtility;
use CraftCms\Cms\Utility\Utilities\Updates as UpdatesUtility;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Uri;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

#[Singleton]
readonly class Alerts
{
    public function __construct(
        private GeneralConfig $generalConfig,
        private License $license,
        private Utilities $utilities,
        private Plugins $plugins,
        private ProjectConfig $projectConfig,
        private Updates $updates,
    ) {}

    /** @return array<int, string|array{content: string, showIcon: bool}> */
    public function get(?string $path = null, bool $fetch = false): array
    {
        $alerts = [];
        $user = Auth::user();
        $consoleUrl = rtrim(Api::craftIdEndpoint(), '/');

        if (! $user) {
            return $alerts;
        }

        $resolvableLicenseAlerts = [];
        $resolvableLicenseItems = [];

        foreach ($this->license->issues(fetch: $fetch) as [$name, $message, $resolveItem]) {
            if (! $resolveItem) {
                $alerts[] = $message;
            } elseif (! Edition::canTest()) {
                $resolvableLicenseAlerts[] = $message;
                $resolvableLicenseItems[] = $resolveItem;
            }
        }

        if (! empty($resolvableLicenseAlerts)) {
            $cartUrl = Uri::of("$consoleUrl/cart/new")
                ->withQuery(['items' => $resolvableLicenseItems])
                ->value();

            array_unshift($alerts, [
                'content' => Html::tag('h2', t('License purchase required.')).
                    Html::tag('p', t('The following licensing {total, plural, =1{issue} other{issues}} can be resolved with a single purchase on Craft Console:', [
                        'total' => count($resolvableLicenseAlerts),
                    ])).
                    Html::ul()
                        ->items(...array_map(Html::li(...), $resolvableLicenseAlerts))
                        ->class('errors').
                    Html::beginTag('p', [
                        'class' => ['flex', 'flex-nowrap', 'resolvable-alert-buttons'],
                    ]).
                    sprintf('<a class="go" href="%s" target="_blank">%s</a>', $cartUrl, t('Resolve now')).
                    Html::endTag('p'),
                'showIcon' => false,
            ]);
        }

        if (
            $path !== 'utilities/updates' &&
            $this->utilities->checkAuthorization(UpdatesUtility::class) &&
            $this->updates->isCriticalUpdateAvailable()
        ) {
            $alerts[] = t('A critical update is available.').
                ' <a class="go nowrap" href="'.Url::url('utilities/updates').'">'.t('Go to Updates').'</a>';
        }

        if (Edition::get() < Edition::Pro) {
            foreach ($this->plugins->getAllPlugins() as $plugin) {
                if ($plugin->minCmsEdition->value > Edition::get()->value) {
                    $alerts[] = t('{plugin} requires Craft CMS {edition} edition.', [
                        'plugin' => $plugin->name,
                        'edition' => $plugin->minCmsEdition->name,
                    ]);
                }
            }
        }

        if (
            $path !== 'utilities/project-config' &&
            $this->utilities->checkAuthorization(ProjectConfigUtility::class) &&
            $this->projectConfig->areChangesPending() &&
            ($this->projectConfig->writeYamlAutomatically || $this->projectConfig->get('dateModified') <= $this->projectConfig->get('dateModified', true))
        ) {
            $alerts[] = t('Your project config YAML files contain pending changes.').
                ' '.'<a class="go" href="'.Url::url('utilities/project-config').'">'.t('Review').'</a>';
        }

        if (
            $user->admin &&
            $this->generalConfig->allowAdminChanges &&
            $this->projectConfig->getHadFileWriteIssues()
        ) {
            $alerts[] = t('Your {folder} folder isn\'t writable.', [
                'folder' => "config/{$this->projectConfig->folderName}/",
            ]);
        }

        event($event = new RegisterCpAlerts);

        $alerts = array_merge($alerts, $event->alerts);

        foreach ($alerts as $i => $alert) {
            if (! is_array($alert)) {
                $alert = [
                    'content' => $alert,
                    'showIcon' => true,
                ];
            }

            $offset = 0;
            while (true) {
                try {
                    $tagInfo = Html::parseTag($alert['content'], $offset);
                } catch (InvalidHtmlTagException) {
                    break;
                }

                $newTagHtml = self::alertTagHtml($tagInfo);
                $alert['content'] = substr($alert['content'], 0, $tagInfo['start']).
                    $newTagHtml.
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
            case 'ul':
            case 'p':
                $style['display'] = 'block';
                break;
            case 'li':
                $style['display'] = 'list-item';
                break;
            case 'a':
                if (isset($tagInfo['attributes']['class']) && array_intersect(['go', 'btn'], $tagInfo['attributes']['class'])) {
                    $style['display'] = 'inline-flex';
                }
                break;
        }

        $childTagHtml = array_map(self::alertTagHtml(...), $tagInfo['children'] ?? []);

        return trim(template('_layouts/components/tag', [
            'type' => $tagInfo['type'],
            'attributes' => $tagInfo['attributes'] ?? [],
            'style' => $style,
            'content' => implode('', $childTagHtml),
        ], templateMode: TemplateMode::Cp));
    }
}
