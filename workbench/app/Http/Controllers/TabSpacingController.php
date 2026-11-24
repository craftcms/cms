<?php

declare(strict_types=1);

namespace Workbench\App\Http\Controllers;

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Http\Responses\CpScreenResponse;

use function CraftCms\Cms\cp_url;

/**
 * Screens with and without content tabs, framed side by side so their
 * spacing can be compared.
 */
class TabSpacingController
{
    public function __invoke(): CpScreenResponse
    {
        return new CpScreenResponse()
            ->title('Tab Spacing')
            ->addCrumb('Workbench', 'workbench/tab-spacing')
            ->inertiaPage('workbench/TabSpacing', [
                'cases' => array_values(array_filter([
                    $this->entryCase(withTabs: true),
                    $this->entryCase(withTabs: false),
                    [
                        'label' => 'Preferences',
                        'notes' => 'UI nodes with tabs, inside an account screen. Switch tabs inside the frame to compare them.',
                        'url' => cp_url('myaccount/preferences'),
                    ],
                    [
                        'label' => 'Addresses',
                        'notes' => 'Account screen without tabs.',
                        'url' => cp_url('myaccount/addresses'),
                    ],
                    [
                        'label' => 'Passkeys',
                        'notes' => 'Account screen without tabs.',
                        'url' => cp_url('myaccount/passkeys'),
                    ],
                ])),
            ]);
    }

    /** @return array{label: string, notes: string, url: string}|null */
    private function entryCase(bool $withTabs): ?array
    {
        $entry = collect(Entry::find()->limit(200)->all())
            ->first(fn (Entry $entry): bool => (count($entry->getFieldLayout()?->getTabs() ?? []) > 1) === $withTabs);

        $url = $entry?->getCpEditUrl();

        if ($url === null) {
            return null;
        }

        return $withTabs
            ? [
                'label' => "Entry with tabs: {$entry->title}",
                'notes' => 'Hover the second tab to check the gap between hover fills.',
                'url' => $url,
            ]
            : [
                'label' => "Entry without tabs: {$entry->title}",
                'notes' => 'The first field should start where a tabbed entry’s tab labels do.',
                'url' => $url,
            ];
    }
}
