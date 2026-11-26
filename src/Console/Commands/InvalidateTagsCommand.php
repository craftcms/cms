<?php

declare(strict_types=1);

namespace CraftCms\Cms\Console\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Element\ElementCaches;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Utility\Utilities\ClearCaches;
use Illuminate\Console\Command;

use function CraftCms\Cms\t;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\table;

/**
 * @since 6.0.0
 */
class InvalidateTagsCommand extends Command
{
    use CraftCommand;

    /**
     * @param  list<string>  $aliases
     */
    public function __construct(string $signature, string $description, array $aliases = [])
    {
        $this->signature = $signature;
        $this->description = $description;
        $this->aliases = $aliases;

        parent::__construct();
    }

    public function handle(): int
    {
        if ($this->signature === 'craft:invalidate-tags') {
            return $this->list();
        }

        $tag = Str::after($this->signature, 'craft:invalidate-tags:');

        if ($tag === 'all') {
            $result = self::SUCCESS;

            collect(ClearCaches::tagOptions())
                ->flatMap(fn (array $option) => array_values(Arr::only($option, ['tag'])))
                ->each(function (string $tag) use (&$result) {
                    if ($this->call("craft:invalidate-tags:$tag") !== self::SUCCESS) {
                        $result = self::FAILURE;
                    }
                });

            return $result;
        }

        $option = collect(ClearCaches::tagOptions())->firstWhere('tag', $tag);

        $elementCaches = app(ElementCaches::class);
        $elementCaches->invalidateTags([$option['tag']]);

        // The tag stays pending if the cache store failed; the error is reported, and the tag retried later.
        $invalidated = ! $elementCaches->hasPendingInvalidations([$option['tag']]);

        $this->components->task(t('Invalidating:').' '.$option['label'], fn () => $invalidated);

        return $invalidated ? self::SUCCESS : self::FAILURE;
    }

    public function list(): int
    {
        $this->components->info('The following tags can be invalidated:');

        table(
            headers: ['Tag', 'Label'],
            rows: collect(ClearCaches::tagOptions())
                ->map(fn (array $option) => Arr::only($option, ['tag', 'label']))
                ->all(),
        );

        if (! $this->input->isInteractive()) {
            return self::SUCCESS;
        }

        $tags = multiselect(
            label: 'Which caches do you want to clear?',
            options: collect(ClearCaches::tagOptions())
                ->mapWithKeys(fn (array $option) => [$option['tag'] => $option['label']])
                ->all(),
        );

        foreach ($tags as $tag) {
            $this->call("craft:invalidate-tags:$tag");
        }

        return self::SUCCESS;
    }

    /**
     * @return list<array{signature: string, description: string, aliases?: list<string>}>
     */
    public static function signatures(): array
    {
        $signatures = [
            [
                'signature' => 'craft:invalidate-tags',
                'description' => 'Lists the caches that can be cleared.',
            ],
            [
                'signature' => 'craft:invalidate-tags:all',
                'description' => 'Clear all caches.',
                'aliases' => ['invalidate-tags/all'],
            ],
        ];

        foreach (ClearCaches::tagOptions() as $option) {
            $signatures[] = [
                'signature' => 'craft:invalidate-tags:'.$option['tag'],
                'description' => $option['label'],
                'aliases' => ['invalidate-tags/'.$option['tag']],
            ];
        }

        return $signatures;
    }
}
