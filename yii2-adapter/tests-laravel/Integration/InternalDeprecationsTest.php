<?php

declare(strict_types=1);

use craft\base\Model;
use craft\elements\Tag;
use craft\helpers\FileHelper;
use craft\web\twig\variables\Paginate;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Models\Element;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Models\User;
use CraftCms\Yii2Adapter\DeprecatedConcepts;
use CraftCms\Yii2Adapter\Tests\DatabaseTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use yii\validators\InlineValidator;

uses(DatabaseTestCase::class);

afterEach(function() {
    DeprecatedConcepts::resetSupport();
});

function loggedDeprecationKeys(): array
{
    return collect(Deprecator::getRequestLogs())->pluck('key')->all();
}

it('reads general config settings without the deprecated config service getter', function() {
    $source = sys_get_temp_dir() . '/craft-copy-' . bin2hex(random_bytes(8));
    $destination = "$source-copy";
    mkdir($source);
    file_put_contents("$source/file.txt", 'contents');

    try {
        FileHelper::copyDirectory($source, $destination);
        $paginate = new Paginate();

        expect(file_get_contents("$destination/file.txt"))->toBe('contents')
            ->and($paginate->pageTrigger)->toBe('?page=')
            ->and(loggedDeprecationKeys())->not->toContain('Craft::$app->config->general');
    } finally {
        FileHelper::removeDirectory($source);
        FileHelper::removeDirectory($destination);
    }
});

it('copies a core component’s errors without the deprecated error getter', function() {
    $layout = new FieldLayout();
    $layout->errors()->add('tabs', 'Invalid tabs.');
    $model = new class() extends Model {
    };

    $model->addModelErrors($layout, 'fieldLayout');

    expect($model->getErrors())->toBe(['fieldLayout.tabs' => ['Invalid tabs.']])
        ->and(loggedDeprecationKeys())->not->toContain(FieldLayout::class . '->getErrors');
});

/**
 * Installs tag support and creates a tag group with the given tag titles.
 *
 * @param list<string> $titles
 * @return array{groupId: int, tagIds: list<int>}
 */
function createTagGroupWithTags(array $titles): array
{
    test()->artisan('craft:add-tags-support --force --no-interaction')->assertSuccessful();
    DeprecatedConcepts::resetSupport();

    $schema = Craft::$app->getDb()->getSchema();
    $groupId = 101;

    DB::table($schema->getRawTableName('taggroups'))->insert([
        'id' => $groupId,
        'name' => 'Tag Group',
        'handle' => 'tagGroup',
        'fieldLayoutId' => null,
        'dateCreated' => now(),
        'dateUpdated' => now(),
        'uid' => (string) Str::uuid(),
    ]);

    $tagIds = [];

    foreach ($titles as $title) {
        $tagId = Element::factory()->set('type', Tag::class)->title($title)->create()->id;
        DB::table($schema->getRawTableName('tags'))->insert([
            'id' => $tagId,
            'groupId' => $groupId,
            'deletedWithGroup' => null,
            'dateCreated' => now(),
            'dateUpdated' => now(),
        ]);
        $tagIds[] = $tagId;
    }

    return ['groupId' => $groupId, 'tagIds' => $tagIds];
}

it('validates tag title uniqueness without deprecated query or error methods', function() {
    ['groupId' => $groupId, 'tagIds' => [$existingId]] = createTagGroupWithTags(['Paris']);
    $siteId = Sites::getPrimarySite()->id;

    $existing = Tag::find()->id($existingId)->siteId($siteId)->status(null)->one();
    $duplicate = new Tag();
    $duplicate->groupId = $groupId;
    $duplicate->siteId = $siteId;
    $duplicate->title = 'Paris';

    $existing->validateTitle('title', null, new InlineValidator());
    $duplicate->validateTitle('title', null, new InlineValidator());

    expect($existing->errors()->has('title'))->toBeFalse()
        ->and($duplicate->errors()->has('title'))->toBeTrue()
        ->and(loggedDeprecationKeys())->not->toContain('ElementQuery-andWhere', Tag::class . '->addError');
});

it('searches for tags shortest first without deprecated ordering', function() {
    ['groupId' => $groupId] = createTagGroupWithTags(['Parisian', 'Paris', 'Parisiana']);
    $this->actingAs(User::factory()->admin()->create());

    $response = $this->postJson('/' . Cms::config()->cpTrigger . '/actions/tags/search-for-tags', [
        'search' => 'Paris',
        'tagGroupId' => $groupId,
    ]);

    $response->assertOk();

    expect(array_column($response->json('tags'), 'title'))->toBe(['Paris', 'Parisian', 'Parisiana'])
        ->and(array_filter(loggedDeprecationKeys(), fn(string $key) => str_contains($key, '::orderBy')))->toBe([]);
});
