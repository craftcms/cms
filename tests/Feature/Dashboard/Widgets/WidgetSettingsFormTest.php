<?php

declare(strict_types=1);

use CraftCms\Cms\Dashboard\Widgets\Feed;
use CraftCms\Cms\Dashboard\Widgets\MyDrafts;
use CraftCms\Cms\Dashboard\Widgets\NewUsers;
use CraftCms\Cms\Dashboard\Widgets\QuickPost;
use CraftCms\Cms\Dashboard\Widgets\RecentEntries;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\UserGroup;

use function Pest\Laravel\actingAs;

it('exposes native widget settings as typed forms', function () {
    actingAs(User::find()->one());
    UserGroup::factory()->create(['name' => 'Editors']);
    $entryType = EntryType::factory()->create(['name' => 'Article']);
    Section::factory()->withEntryTypes($entryType)->create([
        'name' => 'News',
        'type' => SectionType::Channel,
    ]);

    $forms = [
        Feed::class => ['url', 'title', 'limit'],
        MyDrafts::class => ['limit'],
        NewUsers::class => ['dateRange', 'userGroupId'],
        QuickPost::class => ['section', 'entryType', 'customTitle'],
        RecentEntries::class => ['section', 'limit'],
    ];

    foreach ($forms as $widgetClass => $paths) {
        $widget = new $widgetClass;
        $payload = app(FormResolver::class)->resolve(
            $widget->settingsForm(),
            new FormContext(namespace: 'settings'),
        );
        $nodes = flattenFormNodes(array_map(
            fn ($node): array => $node->jsonSerialize(),
            $payload->nodes,
        ));
        $controls = collect($nodes)->pluck('control')->filter()->values();

        expect($controls->map(
            fn (array $control): string => implode('.', array_slice($control['path'], 1)),
        )->all())->toBe($paths);

        if ($widget instanceof QuickPost) {
            expect(collect($nodes)->firstWhere('control.path', ['settings', 'entryType'])['props']['label'])
                ->toBe('Entry Type');
        }
    }
});

it('resolves widget values errors and modes through the shared form seam', function () {
    $widget = new Feed([
        'url' => 'https://craftcms.com/news.rss',
        'title' => 'Craft News',
        'limit' => 12,
    ]);

    $payload = app(FormResolver::class)->resolve($widget->settingsForm(), new FormContext(
        namespace: 'settings',
        errors: ['url' => ['Enter a valid feed URL.']],
        mode: ControlMode::ReadOnly,
    ));

    expect($payload->values)->toBe([
        'settings' => [
            'url' => 'https://craftcms.com/news.rss',
            'title' => 'Craft News',
            'limit' => 12,
        ],
    ])->and($payload->errors)->toBe([[
        'path' => ['settings', 'url'],
        'messages' => ['Enter a valid feed URL.'],
    ]])->and(array_map(
        fn ($node): ControlMode => $node->control->mode,
        $payload->nodes,
    ))->each->toBe(ControlMode::ReadOnly);
});
