<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Concerns;

use Craft;
use CraftCms\Cms\Plugin\Plugin;
use CraftCms\Cms\Support\Facades\I18N;
use Yiisoft\Translator\CategorySource;
use Yiisoft\Translator\IntlMessageFormatter;
use Yiisoft\Translator\Message\Php\MessageSource;

/**
 * @mixin Plugin
 *
 * @internal
 */
trait HasTranslations
{
    /** @var string|null The translation category that this plugin’s translation messages should use. Defaults to the lowercased plugin handle. */
    public ?string $t9nCategory = null;

    public function bootHasTranslations(): void
    {
        $plugin = self::getInstance();
        $plugin->t9nCategory ??= $plugin->handle;

        $basePath = $plugin->getBasePath();
        $translationsPath = match (true) {
            // Laravel Convention - /lang
            is_dir($baseDir = dirname($basePath).'/lang') => $baseDir,
            // Craft 5 and earlier - src/translations
            default => $basePath.'/translations',
        };

        $pluginMessageSource = new MessageSource($translationsPath);
        $formatter = new IntlMessageFormatter;
        $category = new CategorySource(
            name: $plugin->t9nCategory,
            reader: $pluginMessageSource,
            formatter: $formatter,
        );

        I18N::addCategorySources($category);

        $this->loadTranslationsFrom($translationsPath, $plugin->t9nCategory);
    }
}
