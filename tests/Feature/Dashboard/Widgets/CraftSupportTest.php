<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Dashboard\Widgets\CraftSupport;
use CraftCms\Cms\Support\PHP;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Facades\Session;

use function Pest\Laravel\actingAs;

it('is only selectable by admins', function () {
    UserModel::first()->update(['admin' => false]);

    actingAs(User::find()->one());
    Session::start();

    expect(CraftSupport::isSelectable())->toBeFalse();

    UserModel::first()->update(['admin' => true]);
    actingAs(User::find()->one());

    expect(CraftSupport::isSelectable())->toBeTrue();
});

it('links to the documentation for the running version of Craft', function () {
    $links = collect(app(CraftSupport::class)->props()['links'])->keyBy('label');

    expect($links['Documentation']['url'])->toBe(sprintf('https://craftcms.com/docs/%s.x/', (int) Cms::VERSION))
        ->and($links->pluck('url'))->toContain('https://craftcms.com/contact')
        ->not->toContain('https://craftquest.io');
});

it('links to a bug report with the install’s environment filled in', function () {
    $links = collect(app(CraftSupport::class)->props()['links'])->keyBy('label');
    parse_str((string) parse_url($links['Report a bug on GitHub']['url'], PHP_URL_QUERY), $query);

    expect($links['Report a bug on GitHub']['url'])->toStartWith('https://github.com/craftcms/cms/issues/new?')
        ->and($query)->toMatchArray([
            'template' => sprintf('BUG-REPORT-V%s.yml', (int) Cms::VERSION),
            'title' => sprintf('[%s.x]: ', (int) Cms::VERSION),
            'phpVersion' => PHP::version(),
        ])
        ->and($query['cmsVersion'])->toStartWith(Cms::VERSION);
});
