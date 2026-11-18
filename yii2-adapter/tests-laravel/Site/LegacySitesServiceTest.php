<?php

declare(strict_types=1);

use craft\models\Site as LegacySite;
use craft\models\SiteGroup as LegacySiteGroup;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Yii2Adapter\Tests\DatabaseTestCase;
use Illuminate\Support\Facades\DB;

uses(DatabaseTestCase::class);

test('saveSite() saves the passed site with its raw values', function() {
    $service = Craft::$app->getSites();
    $site = new LegacySite([
        'name' => 'Repro',
        'handle' => 'repro',
        'language' => 'en',
        'groupId' => $service->getPrimarySite()->groupId,
        'hasUrls' => true,
        'baseUrl' => 'https://repro.test',
    ]);

    expect($service->saveSite($site))->toBeTrue()
        ->and(DB::table('sites')->where('handle', 'repro')->value('baseUrl'))->toBe('https://repro.test')
        ->and($site->id)->not->toBeNull();
});

test('site getters return the raw values', function() {
    Sites::saveSite(new LegacySite([
        'name' => 'Repro',
        'handle' => 'repro',
        'language' => 'en',
        'groupId' => Sites::getPrimarySite()->groupId,
        'hasUrls' => true,
        'baseUrl' => 'https://repro.test',
    ]));

    expect(Craft::$app->getSites()->getSiteByHandle('repro')->getBaseUrl(false))->toBe('https://repro.test');
});

test('saveSite() leaves validation errors on the passed site', function() {
    $site = new LegacySite(['handle' => 'repro']);

    expect(Craft::$app->getSites()->saveSite($site))->toBeFalse()
        ->and($site->errors()->isEmpty())->toBeFalse();
});

test('saveGroup() sets the id on the passed group', function() {
    $group = new LegacySiteGroup(['name' => 'Repro']);

    expect(Craft::$app->getSites()->saveGroup($group))->toBeTrue()
        ->and($group->id)->not->toBeNull();
});

test('setCurrentSite() keeps the passed site object', function() {
    $service = Craft::$app->getSites();
    $site = clone $service->getPrimarySite();
    $site->setBaseUrl('https://modified.test');

    $service->setCurrentSite($site);

    expect(Sites::getCurrentSite()->getBaseUrl(false))->toBe('https://modified.test');
});
