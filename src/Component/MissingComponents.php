<?php

declare(strict_types=1);

namespace CraftCms\Cms\Component;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Plugin\Exceptions\InvalidPluginException;
use CraftCms\Cms\Plugin\Plugins;
use Illuminate\Container\Attributes\Singleton;

use function CraftCms\Cms\cp_url;
use function CraftCms\Cms\currentUser;
use function CraftCms\Cms\t;

#[Singleton]
class MissingComponents
{
    public function __construct(private readonly Plugins $plugins) {}

    /**
     * @return array{
     *     error:string,
     *     pluginName:?string,
     *     iconUrl:?string,
     *     iconSvg:?string,
     *     action:?array{label:string,url:string,method:'get'|'post'},
     * }
     */
    public function resolve(string $expectedType, ?string $errorMessage = null): array
    {
        $presentation = [
            'error' => $errorMessage ?? "Unable to find component class '$expectedType'.",
            'pluginName' => null,
            'iconUrl' => null,
            'iconSvg' => null,
            'action' => null,
        ];

        if (! currentUser()?->isAdmin() || ! Cms::config()->allowAdminChanges) {
            return $presentation;
        }

        $special = match ($expectedType) {
            'craft\redactor\Field' => [
                'name' => 'Redactor',
                'components' => 'fields',
                'handle' => 'redactor',
                'iconUrl' => 'https://s3-us-west-2.amazonaws.com/plugin-icons.craftcms/redactor.svg',
            ],
            'craft\awss3\Volume' => [
                'name' => 'Amazon S3',
                'components' => 'volumes',
                'handle' => 'aws-s3',
                'iconUrl' => 'https://s3-us-west-2.amazonaws.com/plugin-icons.craftcms/aws-s3.svg',
            ],
            'craft\googlecloud\Volume' => [
                'name' => 'Google Cloud Storage',
                'components' => 'volumes',
                'handle' => 'google-cloud',
                'iconUrl' => 'https://s3-us-west-2.amazonaws.com/plugin-icons.craftcms/google-cloud.svg',
            ],
            'craft\rackspace\Volume' => [
                'name' => 'Rackspace Cloud Files',
                'components' => 'volumes',
                'handle' => 'rackspace',
                'iconUrl' => 'https://s3-us-west-2.amazonaws.com/plugin-icons.craftcms/rackspace.svg',
            ],
            default => null,
        };

        if ($special !== null) {
            $presentation['error'] = "Support for {$special['name']} {$special['components']} has been moved to a plugin.";
            $presentation['pluginName'] = $special['name'];
            $presentation['iconUrl'] = $special['iconUrl'];
        }

        $handle = $special['handle'] ?? $this->plugins->getPluginHandleByClass($expectedType);

        if ($handle === null) {
            return $presentation;
        }

        try {
            $info = $this->plugins->getPluginInfo($handle);
        } catch (InvalidPluginException) {
            if ($special === null) {
                return $presentation;
            }

            $presentation['action'] = [
                'label' => t('Install'),
                'url' => cp_url("plugin-store/$handle"),
                'method' => 'get',
            ];

            return $presentation;
        }

        $isInstalled = (bool) $info['isInstalled'];
        $operation = $isInstalled ? 'enable' : 'install';
        $presentation['pluginName'] = (string) $info['name'];
        $presentation['iconSvg'] = $this->plugins->getPluginIconSvg($handle);
        $presentation['action'] = [
            'label' => t($isInstalled ? 'Enable' : 'Install'),
            'url' => cp_url("settings/plugins/$handle/$operation"),
            'method' => 'post',
        ];

        return $presentation;
    }
}
