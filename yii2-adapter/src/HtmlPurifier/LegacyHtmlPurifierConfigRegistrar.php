<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\HtmlPurifier;

use CraftCms\Cms\Support\HtmlSanitizer\HtmlSanitizerManager;
use CraftCms\Cms\Support\Json;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;

#[Singleton]
readonly class LegacyHtmlPurifierConfigRegistrar
{
    public function __construct(
        private HtmlSanitizerManager $sanitizers,
    ) {
    }

    public function boot(): void
    {
        $path = config_path('craft/htmlpurifier');

        if (!File::isDirectory($path)) {
            return;
        }

        foreach (File::files($path) as $file) {
            if ($file->getExtension() !== 'json') {
                continue;
            }

            $name = $file->getFilenameWithoutExtension();

            try {
                $legacyConfig = Json::decodeFromFile($file->getPathname());
            } catch (InvalidArgumentException) {
                continue;
            }

            if (!is_array($legacyConfig)) {
                continue;
            }

            $this->sanitizers->extend($name, new HtmlPurifierSanitizer($legacyConfig));
        }
    }
}
