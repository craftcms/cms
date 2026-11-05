<?php

declare(strict_types=1);

use craft\elements\Category;
use craft\elements\Tag;
use craft\gql\resolvers\mutations\Category as CategoryMutationResolver;
use craft\gql\resolvers\mutations\Tag as TagMutationResolver;
use craft\models\CategoryGroup;
use craft\models\CategoryGroup_SiteSettings;
use craft\models\TagGroup;
use craft\services\ProjectConfig as LegacyProjectConfig;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Gql\Data\GqlSchema;
use CraftCms\Cms\Gql\Gql;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Yii2Adapter\DeprecatedConcepts;
use CraftCms\Yii2Adapter\Tests\DatabaseTestCase;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Support\Facades\DB;

uses(DatabaseTestCase::class);

beforeEach(function() {
    $this->artisan('craft:add-categories-support --force --no-interaction')->assertSuccessful();
    $this->artisan('craft:add-tags-support --force --no-interaction')->assertSuccessful();

    app(ProjectConfig::class)
        ->onAdd(LegacyProjectConfig::PATH_CATEGORY_GROUPS . '.{uid}', fn($event) => Craft::$app->getCategories()->handleChangedCategoryGroup($event))
        ->onAdd(LegacyProjectConfig::PATH_TAG_GROUPS . '.{uid}', fn($event) => Craft::$app->getTags()->handleChangedTagGroup($event));

    $this->siteA = Site::first();
    $this->siteB = Site::factory()->create();
    Sites::refreshSites();
});

afterEach(function() {
    DeprecatedConcepts::resetSupport();
});

function siteScopedLegacyElement(string $type): Category|Tag
{
    if ($type === 'category') {
        $group = new CategoryGroup(['name' => 'Topics', 'handle' => 'topics']);
        $group->setSiteSettings(array_map(
            fn(int $siteId) => new CategoryGroup_SiteSettings(['siteId' => $siteId, 'hasUrls' => false]),
            Sites::getAllSiteIds()->all(),
        ));
        expect(Craft::$app->getCategories()->saveGroup($group))->toBeTrue();

        $element = new Category(['groupId' => $group->id, 'title' => 'Original']);
    } else {
        $group = new TagGroup(['name' => 'Keywords', 'handle' => 'keywords']);
        expect(Craft::$app->getTags()->saveTagGroup($group))->toBeTrue();

        $element = new Tag(['groupId' => $group->id, 'title' => 'Original']);
    }

    expect(Elements::saveElement($element))->toBeTrue();

    return $element;
}

function siteScopedLegacyDelete(string $type, int $id): bool
{
    $resolveInfo = Mockery::mock(ResolveInfo::class);

    return $type === 'category'
        ? (new CategoryMutationResolver())->deleteCategory(null, ['id' => $id], null, $resolveInfo)
        : (new TagMutationResolver())->deleteTag(null, ['id' => $id], null, $resolveInfo);
}

function siteScopedLegacySiteCount(int $elementId): int
{
    return DB::table(Table::ELEMENTS_SITES)
        ->join(Table::ELEMENTS, 'elements.id', '=', 'elements_sites.elementId')
        ->where('elementId', $elementId)
        ->whereNull('elements.dateDeleted')
        ->count();
}

it('refuses to delete an element that exists in sites outside the schema', function(string $type) {
    $element = siteScopedLegacyElement($type);
    $resource = $type === 'category' ? "categorygroups.{$element->getGroup()->uid}" : "taggroups.{$element->getGroup()->uid}";

    $setSchema = function(array $sites) use ($resource): void {
        app(Gql::class)->flushCaches();
        app(Gql::class)->setActiveSchema(new GqlSchema([
            'scope' => [
                "$resource:read",
                "$resource:delete",
                ...array_map(fn(Site $site) => "sites.$site->uid:read", $sites),
            ],
        ]));
    };

    expect(siteScopedLegacySiteCount($element->id))->toBe(2);

    $setSchema([$this->siteA]);

    expect(fn() => siteScopedLegacyDelete($type, $element->id))
        ->toThrow('Unable to perform the action.')
        ->and(siteScopedLegacySiteCount($element->id))->toBe(2);

    $setSchema([$this->siteA, $this->siteB]);

    expect(siteScopedLegacyDelete($type, $element->id))->toBeTrue()
        ->and(siteScopedLegacySiteCount($element->id))->toBe(0);
})->with(['category', 'tag']);
