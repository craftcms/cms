<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\LooseEnds;

use Carbon\CarbonImmutable;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Console\TerminalCommands;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Utility\Utility;
use Illuminate\Support\Facades\Cache;
use Override;
use stdClass;

use function CraftCms\Cms\currentUser;
use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

/**
 * Shows what in the project still depends on the Yii2 adapter.
 *
 * @internal
 */
class LooseEndsUtility extends Utility
{
    #[Override]
    public static function displayName(): string
    {
        return t('Yii2 Loose Ends', category: 'yii2-adapter');
    }

    #[Override]
    public static function id(): string
    {
        return 'yii2-loose-ends';
    }

    #[Override]
    public static function icon(): string
    {
        // Font Awesome 7.1 added `hand-shaka`, and Craft’s icon kit is still on 6.
        return dirname(__DIR__, 2) . '/resources/icons/hand-shaka.svg';
    }

    #[Override]
    public static function badgeCount(): int
    {
        return app(LooseEndsStore::class)->outstandingCount();
    }

    #[Override]
    public static function contentHtml(): string
    {
        return template('yii2-adapter/_components/utilities/LooseEnds', self::report());
    }

    /**
     * Runs the checks, and returns the report.
     *
     * @return array{
     *     sections: list<array{label: string, intro: string, items: list<array<string, mixed>>}>,
     *     outstanding: int,
     *     environment: string,
     *     commands: array{cleanup: string, remove: string},
     * }
     */
    public static function report(): array
    {
        $findings = app(LooseEndsChecks::class)->run();
        $store = app(LooseEndsStore::class);
        $usages = collect();

        // A failure to read recorded usage is left to surface, as an empty list would claim nothing depends on the adapter.
        if (Cms::isInstalled()) {
            app(LegacyUsage::class)->flush();
            $usages = $store->usages();
        }

        Cache::forever(LooseEndsStore::FINDING_COUNT_CACHE_KEY, count($findings));

        $items = [
            ...array_map(fn(Finding $finding) => self::findingItem($finding), $findings),
            ...$usages->map(fn(stdClass $row) => self::usageItem($row))->all(),
        ];

        $sections = [];

        foreach (Section::cases() as $section) {
            $sectionItems = array_values(array_filter($items, fn(array $item) => $item['section'] === $section));

            if ($sectionItems !== []) {
                $sections[] = ['label' => $section->label(), 'intro' => $section->intro(), 'items' => $sectionItems];
            }
        }

        return [
            'sections' => $sections,
            'outstanding' => count($items),
            'environment' => app()->environment(),
            'commands' => [
                'cleanup' => app(TerminalCommands::class)->craft('yii2-adapter:cleanup'),
                'remove' => app(TerminalCommands::class)->composer('remove craftcms/yii2-adapter'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function findingItem(Finding $finding): array
    {
        return [
            'section' => $finding->section,
            'severity' => $finding->severity,
            'title' => $finding->title,
            'fix' => $finding->fix,
            'detail' => $finding->detail,
            'location' => self::location($finding->file, $finding->line),
            'url' => $finding->url,
            'urlLabel' => $finding->urlLabel,
            'iconSvg' => $finding->iconSvg,
            'status' => $finding->status,
            'lastSeen' => null,
            'resolveUrl' => null,
            'action' => self::findingAction($finding),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function usageItem(stdClass $row): array
    {
        return [
            'section' => Section::tryFrom($row->section) ?? Section::Code,
            'severity' => Severity::tryFrom($row->severity) ?? Severity::Breaks,
            'title' => $row->message,
            'fix' => null,
            'detail' => null,
            'location' => self::location($row->file, $row->line !== null ? (int)$row->line : null),
            'url' => null,
            'urlLabel' => null,
            'iconSvg' => null,
            'status' => null,
            'lastSeen' => CarbonImmutable::parse($row->lastSeen, 'UTC'),
            'resolveUrl' => route('craft.cp.utilities.yii2-loose-ends.resolve', ['id' => $row->id]),
            'action' => self::promptAction([
                "Issue: $row->message",
                ...($row->file !== null ? ['Location: ' . self::location($row->file, $row->line !== null ? (int)$row->line : null) . ' (where it last ran)'] : []),
            ], 'Fix the same pattern anywhere else in the project too.', markResolved: true),
        ];
    }

    /**
     * Returns what the user can do to resolve a finding: apply its built-in fix, install the plugin update that drops
     * the adapter, or copy a prompt for an AI agent, in that order, as far as the environment and user allow.
     *
     * @return array<string, mixed>|null
     */
    private static function findingAction(Finding $finding): ?array
    {
        $fixes = app(LooseEndsFixes::class);

        if ($fixes->allowed() && $fixes->canFix($finding->key)) {
            return [
                'type' => 'fix',
                'url' => route('craft.cp.utilities.yii2-loose-ends.fix'),
                'key' => $finding->key,
            ];
        }

        if ($finding->update !== null && self::canUpdate()) {
            return [
                'type' => 'update',
                'url' => route('craft.cp.updates.index'),
                ...$finding->update,
            ];
        }

        $prompt = self::findingPrompt($finding);

        return $prompt !== null ? ['type' => 'prompt', 'prompt' => $prompt] : null;
    }

    /**
     * Returns whether the user can install updates from the control panel, as the Updates utility decides.
     */
    private static function canUpdate(): bool
    {
        return Cms::config()->allowUpdates
            && Cms::config()->allowAdminChanges
            && (currentUser()?->can('performUpdates') ?? false);
    }

    /**
     * Returns a prompt for an AI agent to resolve a finding, or null if it isn’t something an agent can do.
     */
    private static function findingPrompt(Finding $finding): ?string
    {
        if ($finding->status !== null) {
            // Only plugins and packages with an update that drops the adapter can be resolved by the project.
            if ($finding->status !== PackageStatus::UpdateAvailable) {
                return null;
            }

            return self::prompt([
                "Issue: $finding->title needs the adapter.",
                'Composer package: ' . Str::after($finding->key, 'package:'),
                "Suggested fix: $finding->fix",
                ...($finding->detail !== null ? ["Note: $finding->detail"] : []),
            ], 'Update it with Composer, and make any changes its upgrade notes call for.');
        }

        return self::prompt([
            "Issue: $finding->title",
            ...($finding->file !== null ? ['Location: ' . self::location($finding->file, $finding->line)] : []),
            ...($finding->detail !== null ? ["Details:\n$finding->detail"] : []),
            "Suggested fix: $finding->fix",
        ]);
    }

    /**
     * @param list<string> $lines
     * @return array{type: string, prompt: string}
     */
    private static function promptAction(array $lines, ?string $extra = null, bool $markResolved = false): array
    {
        return ['type' => 'prompt', 'prompt' => self::prompt($lines, $extra, $markResolved)];
    }

    /**
     * Wraps the lines describing an item in instructions for an AI agent.
     *
     * The instructions are for the agent, so they aren’t translated.
     *
     * @param list<string> $lines
     * @param bool $markResolved Whether the user has to mark the item resolved once it’s fixed. Otherwise it leaves the
     * report on its own.
     */
    private static function prompt(array $lines, ?string $extra = null, bool $markResolved = false): string
    {
        return implode("\n\n", array_filter([
            'The Yii2 Loose Ends utility found something in this Craft CMS 6 project that still depends on the Yii2 adapter (craftcms/yii2-adapter). Fix it so the project no longer depends on the adapter.',
            implode("\n", $lines),
            $extra,
            'Change only the project’s own files, keep the change as small as possible, and keep the same behavior.',
            $markResolved
                ? 'When you’re done, say what you changed, so I can mark the item resolved in the Yii2 Loose Ends utility.'
                : 'When you’re done, say what you changed.',
        ]));
    }

    private static function location(?string $file, ?int $line): ?string
    {
        if ($file === null || $file === '') {
            return null;
        }

        return $line !== null ? "$file:$line" : $file;
    }
}
