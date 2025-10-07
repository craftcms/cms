<?php

namespace CraftCms\Cms\Plugin\Concerns;

use Craft;
use craft\i18n\PhpMessageSource;
use CraftCms\Cms\Plugin\Plugin;

/**
 * @mixin Plugin
 *
 * @internal
 */
trait HasTranslations
{
    /** @var string|null The translation category that this plugin’s translation messages should use. Defaults to the lowercased plugin handle. */
    public ?string $t9nCategory = null;

    /** @var string The language that the plugin’s messages were written in */
    public string $sourceLanguage = 'en-US';

    public function bootHasTranslations(): void
    {
        // Translation category
        $i18n = Craft::$app->getI18n();
        $plugin = self::getInstance();
        $plugin->t9nCategory ??= $plugin->handle;

        $basePath = $plugin->getBasePath();
        $translationsPath = match (true) {
            // Laravel Convention - /lang
            is_dir($baseDir = dirname($basePath).'/lang') => $baseDir,
            // Craft 5 and earlier - src/translations
            default => $basePath.'/translations',
        };

        /** @noinspection UnSafeIsSetOverArrayInspection */
        if (! isset($i18n->translations[$plugin->t9nCategory]) && ! isset($i18n->translations[$plugin->t9nCategory.'*'])) {
            $i18n->translations[$plugin->t9nCategory] = [
                'class' => PhpMessageSource::class,
                'sourceLanguage' => $plugin->sourceLanguage,
                'basePath' => $translationsPath,
                'forceTranslation' => true,
                'allowOverrides' => true,
            ];
        }

        $this->loadTranslationsFrom($translationsPath, $plugin->t9nCategory);
    }
}
