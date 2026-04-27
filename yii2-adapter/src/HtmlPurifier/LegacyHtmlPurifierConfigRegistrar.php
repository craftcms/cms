<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\HtmlPurifier;

use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Cms\Support\HtmlSanitizer\HtmlSanitizers;
use CraftCms\Cms\Support\Json;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;

#[Singleton]
readonly class LegacyHtmlPurifierConfigRegistrar
{
    public function __construct(
        private HtmlSanitizers $sanitizers,
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
                Deprecator::log(
                    "htmlpurifier-config-invalid:$name",
                    "Invalid HTML Purifier config at {$file->getPathname()}.",
                    $file->getPathname(),
                );

                continue;
            }

            if (!is_array($legacyConfig)) {
                Deprecator::log(
                    "htmlpurifier-config-invalid:$name",
                    "HTML Purifier config at {$file->getPathname()} must decode to an array.",
                    $file->getPathname(),
                );

                continue;
            }

            $this->sanitizers->register($name, new HtmlPurifierSanitizer($legacyConfig));

            $message = "HTML Purifier config file [craft/htmlpurifier/$name.json] is deprecated. Register this sanitizer on the HtmlSanitizers service instead.";

            Deprecator::log(
                "htmlpurifier-config:$name",
                $message,
                $file->getPathname(),
            );
        }
    }
}
