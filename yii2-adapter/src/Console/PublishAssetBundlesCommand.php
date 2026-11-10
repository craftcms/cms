<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Console;

use Composer\Autoload\ClassLoader;
use Craft;
use craft\helpers\App;
use craft\web\AssetManager;
use craft\web\assets\datepickeri18n\DatepickerI18nAsset;
use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Process\Pool;
use Illuminate\Support\Facades\Process;
use ReflectionClass;
use yii\web\AssetBundle;

use function Illuminate\Support\artisan_binary;
use function Illuminate\Support\php_binary;

/**
 * Publishes legacy asset bundles to `resourceBasePath` ahead of time, for deployments where
 * `publishAssetBundlesAtRuntime` is disabled because the runtime can’t write there.
 *
 * @since 6.0.0
 */
class PublishAssetBundlesCommand extends Command
{
    use CraftCommand;

    protected $signature = 'craft:asset-bundles:publish
        {class?* : Asset bundle classes to publish. Defaults to every asset bundle class in Composer’s class map.}
    ';

    protected $description = 'Publishes legacy asset bundles to resourceBasePath, so they can be served without publishing at runtime.';

    public function handle(): int
    {
        $classes = $this->argument('class');

        if ($classes === []) {
            return $this->publishAll();
        }

        foreach ($classes as $class) {
            $this->publishBundle($class);
        }

        return self::SUCCESS;
    }

    /**
     * Publishes each candidate class in its own process, so a class that can’t be loaded doesn’t stop the others.
     */
    private function publishAll(): int
    {
        $candidates = $this->candidateClasses();

        if ($candidates === []) {
            $this->warn('No asset bundle classes found in Composer’s class map. Run `composer dump-autoload --optimize` first.');

            return self::SUCCESS;
        }

        foreach (array_chunk($candidates, 8) as $chunk) {
            $results = Process::pool(function(Pool $pool) use ($chunk) {
                foreach ($chunk as $class) {
                    $pool->as($class)->path(base_path())->command([php_binary(), artisan_binary(), $this->getName(), $class]);
                }
            })->start()->wait();

            foreach ($results as $class => $result) {
                if ($result->failed()) {
                    $this->warn("Couldn’t publish $class:");
                    $this->line(trim($result->output() . $result->errorOutput()));
                } elseif (($output = trim($result->output())) !== '') {
                    $this->line($output);
                }
            }
        }

        return self::SUCCESS;
    }

    private function publishBundle(string $class): void
    {
        $reflection = new ReflectionClass($class);

        if (!$reflection->isSubclassOf(AssetBundle::class) || !$reflection->isInstantiable()) {
            return;
        }

        // Publish the whole datepicker translations directory rather than only the current language’s file
        if ($class === DatepickerI18nAsset::class) {
            Craft::$app->language = 'en-GB';
        }

        /** @var AssetManager $assetManager */
        $assetManager = Craft::createObject([
            ...App::assetManagerConfig(),
            'publishAtRuntime' => true,
            'stableHashes' => true,
            'forceCopy' => true,
        ]);

        $bundle = $assetManager->getBundle($class);

        if ($bundle->sourcePath !== null) {
            $this->line("Published $class");
        }
    }

    /**
     * Returns class names that might be asset bundles, without loading them.
     *
     * @return string[]
     */
    private function candidateClasses(): array
    {
        $classes = [];

        foreach (ClassLoader::getRegisteredLoaders() as $loader) {
            foreach (array_keys($loader->getClassMap()) as $class) {
                if (
                    preg_match('/(asset|bundle)/i', $class) &&
                    !Str::startsWith($class, ['CraftCms\\', 'yii\\web\\AssetBundle', 'craft\\elements\\', 'craft\\fieldlayoutelements\\', 'craft\\gql\\', 'craft\\records\\'])
                ) {
                    $classes[$class] = true;
                }
            }
        }

        return array_keys($classes);
    }
}
