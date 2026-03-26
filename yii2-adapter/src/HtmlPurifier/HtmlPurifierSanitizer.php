<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\HtmlPurifier;

use Closure;
use Craft;
use craft\helpers\HtmlPurifier;
use HTMLPurifier_Config;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

class HtmlPurifierSanitizer implements HtmlSanitizerInterface
{
    /**
     * @param  array<string, mixed>|\Closure|null  $config
     */
    public function __construct(
        private readonly array|Closure|null $config = null,
    ) {
    }

    public function sanitize(string $input): string
    {
        if ($this->config instanceof Closure) {
            // For Closure configs, start from the default configuration so we can
            // apply Craft's configuration and then let the Closure further modify it.
            $config = HTMLPurifier_Config::createDefault();
        } else {
            // For array or null configs, use them directly.
            $config = HTMLPurifier_Config::create($this->config);
        }
        $config->autoFinalize = false;

        $purifier = \HTMLPurifier::instance($config);
        $purifier->config->set('Cache.SerializerPath', Craft::$app->getRuntimePath());
        $purifier->config->set('Cache.SerializerPermissions', 0775);

        HtmlPurifier::configure($config);

        if ($this->config instanceof Closure) {
            ($this->config)($config);
        }

        return $purifier->purify($input);
    }

    public function sanitizeFor(string $element, string $input): string
    {
        return $this->sanitize($input);
    }
}
