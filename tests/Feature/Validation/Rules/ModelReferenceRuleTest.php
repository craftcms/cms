<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Validation\Rules\VolumeRule;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Entry\Validation\Rules\EntryTypeRule;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Section\Validation\Rules\SectionRule;
use CraftCms\Cms\Shared\Enums\ModelIdentifier;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Site\Validation\Rules\SiteRule;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\Volumes;
use CraftCms\Cms\User\Models\UserGroup;
use CraftCms\Cms\User\Validation\Rules\UserGroupRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Validator;

function referenceErrors(ValidationRule $rule, mixed $value): array
{
    return Validator::make(['ref' => $value], ['ref' => [$rule]])->errors()->get('ref');
}

it('accepts a site by id, numeric string, uid and handle', function () {
    $site = Site::factory()->create(['handle' => 'german']);
    Sites::refreshSites();

    foreach ([$site->id, (string) $site->id, $site->uid, 'german'] as $value) {
        expect(referenceErrors(new SiteRule, $value))->toBe([]);
    }
});

it('rejects identifier kinds that aren’t allowed', function () {
    $site = Site::factory()->create(['handle' => 'german']);
    Sites::refreshSites();

    expect(referenceErrors(new SiteRule([ModelIdentifier::Id]), 'german'))->not->toBe([])
        ->and(referenceErrors(new SiteRule([ModelIdentifier::Id]), $site->uid))->not->toBe([])
        ->and(referenceErrors(new SiteRule([ModelIdentifier::Uid, ModelIdentifier::Handle]), $site->id))->not->toBe([])
        ->and(referenceErrors(new SiteRule([ModelIdentifier::Id]), $site->id))->toBe([]);
});

it('rejects a site that doesn’t exist', function (mixed $value) {
    expect(referenceErrors(new SiteRule, $value))->toBe(["No site found for “{$value}”."]);
})->with([
    'id' => 999999,
    'uid' => '6f1f3c2a-8a51-4bb0-9c0e-1d2b3c4d5e6f',
    'handle' => 'nonexistent',
]);

it('builds the message from a closure that receives the value', function () {
    $rule = new SiteRule(message: fn (string $site) => "Bad site $site");

    expect(referenceErrors($rule, 999999))->toBe(['Bad site 999999']);
});

it('leaves empty values to the required rule', function () {
    expect(referenceErrors(new SiteRule, null))->toBe([])
        ->and(referenceErrors(new SiteRule, ''))->toBe([]);
});

it('only accepts disabled sites when allowed', function () {
    $site = Site::factory()->create(['handle' => 'disabled', 'enabled' => false]);
    Sites::refreshSites();

    expect(referenceErrors(new SiteRule(allowDisabled: false), $site->uid))->not->toBe([])
        ->and(referenceErrors(new SiteRule(allowDisabled: true), $site->uid))->toBe([]);
});

it('resolves other models by id, uid and handle', function (Closure $create, ValidationRule $rule) {
    $model = $create();

    foreach ([$model->id, $model->uid, $model->handle] as $value) {
        expect(referenceErrors($rule, $value))->toBe([]);
    }

    expect(referenceErrors($rule, 'nonexistent'))->not->toBe([]);
})->with([
    'volume' => [function () {
        $volume = Volume::factory()->create(['handle' => 'photos']);
        Volumes::reset();

        return $volume;
    }, new VolumeRule],
    'section' => [function () {
        $section = Section::factory()->create(['handle' => 'news']);
        Sections::refreshSections();

        return $section;
    }, new SectionRule],
    'entry type' => [function () {
        $entryType = EntryType::factory()->create(['handle' => 'article']);
        EntryTypes::refreshEntryTypes();

        return $entryType;
    }, new EntryTypeRule],
    'user group' => [fn () => UserGroup::factory()->create(['handle' => 'editors']), new UserGroupRule],
]);
