<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\HtmlPurifier;

use Closure;
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
            // For array or null configs, use them directly. Closures can edit the HTML definition
            // themselves, so it's only cached for these.
            $config = HTMLPurifier_Config::create($this->config);
            HtmlPurifier::enableDefinitionCache($config, 'craft');
        }
        $config->autoFinalize = false;

        $purifier = \HTMLPurifier::instance($config);
        HtmlPurifier::useCraftCache($purifier->config);

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
