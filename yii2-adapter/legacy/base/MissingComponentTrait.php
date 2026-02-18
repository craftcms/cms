<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use craft\helpers\Component as ComponentHelper;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Component\Contracts\ComponentInterface;
use CraftCms\Cms\Plugin\Exceptions\InvalidPluginException;
use CraftCms\Cms\Plugin\Plugins;
use Illuminate\Support\Facades\Auth;
use yii\base\Arrayable;
use function CraftCms\Cms\template;

/**
 * MissingComponentTrait implements the common methods and properties for classes implementing [[MissingComponentInterface]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
trait MissingComponentTrait
{
    /**
     * @var class-string<ComponentInterface> The expected component class name.
     */
    public string $expectedType;

    /**
     * @var string|null The exception message that explains why the component class was invalid
     */
    public ?string $errorMessage = null;

    /**
     * @var array|null The custom settings associated with the component, if it is savable
     */
    public ?array $settings = null;

    /**
     * Creates a new component of a given type based on this one’s properties.
     *
     * @param class-string<ComponentInterface> $type The component class that should be used as the fallback
     * @return ComponentInterface
     */
    public function createFallback(string $type): ComponentInterface
    {
        assert($this instanceof Arrayable);

        $config = $this->toArray();
        unset($config['expectedType'], $config['errorMessage'], $config['settings']);
        $config['type'] = $type;

        return ComponentHelper::createComponent($config);
    }

    /**
     * Displays an error message (and possibly a plugin install button) in place of the normal component UI.
     *
     * @return string
     * @since 3.0.6
     */
    public function getPlaceholderHtml(): string
    {
        $error = $this->errorMessage ?? "Unable to find component class '$this->expectedType'.";
        $showPlugin = false;
        $isComposerInstalled = false;
        $isInstalled = false;
        $name = null;
        $handle = null;
        $iconUrl = null;
        $iconSvg = null;

        if (
            Auth::user()?->isAdmin() &&
            Cms::config()->allowAdminChanges
        ) {
            $pluginsService = app(Plugins::class);

            // Special cases for removed 1st party components
            switch ($this->expectedType) {
                case 'craft\redactor\Field':
                    $showPlugin = true;
                    $isInstalled = false;
                    $name = 'Redactor';
                    $handle = 'redactor';
                    $iconUrl = 'https://s3-us-west-2.amazonaws.com/plugin-icons.craftcms/redactor.svg';
                    $error = "Support for $name fields has been moved to a plugin.";
                    break;
                case 'craft\awss3\Volume':
                    $showPlugin = true;
                    $isInstalled = false;
                    $name = 'Amazon S3';
                    $handle = 'aws-s3';
                    $iconUrl = 'https://s3-us-west-2.amazonaws.com/plugin-icons.craftcms/aws-s3.svg';
                    $error = "Support for $name volumes has been moved to a plugin.";
                    break;
                case 'craft\googlecloud\Volume':
                    $showPlugin = true;
                    $isInstalled = false;
                    $name = 'Google Cloud Storage';
                    $handle = 'google-cloud';
                    $iconUrl = 'https://s3-us-west-2.amazonaws.com/plugin-icons.craftcms/google-cloud.svg';
                    $error = "Support for $name volumes has been moved to a plugin.";
                    break;
                case 'craft\rackspace\Volume':
                    $showPlugin = true;
                    $isInstalled = false;
                    $name = 'Rackspace Cloud Files';
                    $handle = 'rackspace';
                    $iconUrl = 'https://s3-us-west-2.amazonaws.com/plugin-icons.craftcms/rackspace.svg';
                    $error = "Support for $name volumes has been moved to a plugin.";
                    break;
                default:
                    if ($handle = $pluginsService->getPluginHandleByClass($this->expectedType)) {
                        $showPlugin = true;
                    }
            }

            if ($showPlugin) {
                try {
                    $info = app(Plugins::class)->getPluginInfo($handle);
                    $isComposerInstalled = true;
                    $isInstalled = $info['isInstalled'];
                    $name = $info['name'];
                    $iconSvg = $pluginsService->getPluginIconSvg($handle);
                } catch (InvalidPluginException) {
                }
            }
        }

        return template('_special/missing-component', compact(
            'error',
            'showPlugin',
            'isComposerInstalled',
            'isInstalled',
            'name',
            'handle',
            'iconUrl',
            'iconSvg'
        ));
    }
}
