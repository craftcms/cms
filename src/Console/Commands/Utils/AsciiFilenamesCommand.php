<?php

declare(strict_types=1);

namespace CraftCms\Cms\Console\Commands\Utils;

use Craft;
use craft\elements\Asset;
use craft\errors\InvalidElementException;
use craft\helpers\FileHelper;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Console\CraftCommand;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;
use yii\db\Expression;

use function Laravel\Prompts\confirm;

final class AsciiFilenamesCommand extends Command
{
    use CraftCommand;

    protected $signature = 'craft:utils:ascii-filenames';

    protected $description = 'Converts all non-ASCII asset filenames to ASCII.';

    protected $aliases = ['utils/ascii-filenames'];

    public function handle(GeneralConfig $generalConfig): int
    {
        if (! $generalConfig->convertFilenamesToAscii) {
            $this->components->warn(<<<'EOD'
            The convertFilenamesToAscii config setting is set to false.
            To avoid saving assets with non-ASCII filenames in the future,
            change it to true in config/general.php.
            EOD);
        }

        // Find assets with non-ASCII characters
        $query = Asset::find();

        match (DB::connection()->getDriverName()) {
            // h/t https://stackoverflow.com/a/11741314/1688568
            'mysql' => $query->andWhere(new Expression('[[filename]] <> CONVERT([[filename]] USING ASCII)')),
            // h/t https://dba.stackexchange.com/a/167571/205387
            'pgsql' => $query->andWhere(new Expression("[[filename]] ~ '[^[:ascii:]]'")),
            default => throw new \Exception('Invalid driver name: '.DB::connection()->getDriverName().'.')
        };

        /** @var Asset[] $assets */
        $assets = $query->all();
        $total = count($assets);

        if ($total === 0) {
            $this->components->success('No assets found with non-ASCII filenames.');

            return self::SUCCESS;
        }

        $this->components->info("$total assets found with non-ASCII filenames:");

        $this->components->bulletList(array_map(
            fn (Asset $asset) => $asset->getFilename(),
            $assets,
        ));

        if (! confirm('Ready to rename these filenames as ASCII?')) {
            return self::SUCCESS;
        }

        $successCount = 0;
        $failCount = 0;

        foreach ($assets as $asset) {
            $asset->newFilename = FileHelper::sanitizeFilename($asset->getFilename(), [
                'asciiOnly' => true,
            ]);

            $this->components->task(
                "Renaming {$asset->getFilename()} to $asset->newFilename",
                function () use (&$failCount, &$successCount, $asset) {
                    try {
                        if (! Craft::$app->getElements()->saveElement($asset)) {
                            throw new InvalidElementException($asset, implode(', ', $asset->getFirstErrors()));
                        }

                        $successCount++;
                    } catch (Throwable $e) {
                        $this->error('error: '.$e->getMessage());

                        if (! $e instanceof InvalidElementException) {
                            report($e);
                        }

                        $failCount++;
                    }
                }
            );
        }

        if ($successCount && $failCount) {
            $this->components->info("Successfully renamed $successCount assets, but $failCount assets failed.");
        } elseif ($successCount) {
            $this->components->success("Successfully renamed $successCount assets.");
        } else {
            $this->components->error("Failed to rename $failCount assets.");
        }

        return self::SUCCESS;
    }
}
