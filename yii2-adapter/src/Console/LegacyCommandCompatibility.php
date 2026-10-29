<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Console;

use craft\console\controllers\HelpController;
use CraftCms\Cms\Support\Str;
use Illuminate\Console\Application;

readonly class LegacyCommandCompatibility
{
    public function boot()
    {
        /**
         * Don't need these for Laravel tests.
         */
        if (app()->environment('testing')) {
            return;
        }

        /** @var \craft\console\Application $app */
        $app = app('Craft');

        $controller = new HelpController('help', $app);
        $commands = $controller->allCommandsInfo();

        foreach ($commands as $command) {
            if (str_contains($command['description'], '. ')) {
                $command['description'] = Str::before($command['description'], '. ') . '. ';
            }

            $artisanName = str_replace('/', ':', $command['name']);

            Application::starting(function(Application $artisan) use ($app, $command, $artisanName) {
                if ($artisanName === 'help') {
                    return;
                }

                if ($artisan->has("craft:{$artisanName}")) {
                    return;
                }

                if ($artisan->has($artisanName)) {
                    return;
                }

                $signature = $artisanName;
                $legacyOptions = [];

                foreach ($command['definition']['arguments'] as $definition) {
                    $signature .= $this->convertDefinition($definition, 'argument');
                }

                foreach ($command['definition']['options'] as $definition) {
                    $optionName = ltrim($definition['name'], '-');
                    $legacyOptions[] = $optionName;

                    /**
                     * Options such as `--verbose` are already defined globally by Artisan.
                     * LegacyCraftCommand forwards them to the Yii command instead.
                     */
                    if ($artisan->getDefinition()->hasOption($optionName)) {
                        continue;
                    }

                    $signature .= $this->convertDefinition($definition, 'option');
                }

                $artisan->resolve(new LegacyCraftCommand(
                    app: $app,
                    signature: "craft:{$signature}",
                    description: $command['description'],
                    hidden: str_ends_with($artisanName, ':index'),
                    legacyOptions: $legacyOptions,
                ));

                // Add with slash for backwards compatibility
                $signatureWithSlash = Str::replaceFirst(':', '/', $signature);
                $nameWithSlash = Str::replaceFirst(':', '/', $artisanName);
                $artisan->resolve(new LegacyCraftCommand(
                    app: $app,
                    signature: "craft:{$signatureWithSlash}",
                    description: $command['description'],
                    hidden: true,
                    legacyOptions: $legacyOptions,
                    deprecationMessage: "Calling `php craft $nameWithSlash` is deprecated use `php craft $artisanName` instead.",
                ));
            });
        }
    }

    private function convertDefinition(array $definition, string $type): string
    {
        if ($definition['name'] === '--help') {
            return '';
        }

        $definitionSignature = $definition['name'];

        if (!$definition['default'] && !($definition['required'] ?? true)) {
            $definitionSignature .= '?';
        }

        if (str_starts_with($definition['description'] ?? '', '...')) {
            $definitionSignature .= '*';
        }

        if ($definition['default']) {
            if (is_array($definition['default'])) {
                $definition['default'] = implode(',', $definition['default']);
            }

            $definitionSignature .= "={$definition['default']}";
        } elseif ($type === 'option' && ($definition['required'] ?? true)) {
            $definitionSignature .= '=';
        }

        if ($definition['description']) {
            $definitionSignature .= " : {$definition['description']}";
        }

        return " {{$definitionSignature}}";
    }
}
