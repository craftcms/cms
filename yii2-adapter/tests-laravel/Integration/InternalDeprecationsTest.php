<?php

declare(strict_types=1);

use craft\base\Model;
use craft\elements\Tag;
use craft\helpers\FileHelper;
use craft\web\twig\variables\Paginate;
use CraftCms\Cms\Element\Models\Element;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Cms\Support\Facades\Sites;
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

it('validates tag title uniqueness without deprecated query or error methods', function() {
    $this->artisan('craft:add-tags-support --force --no-interaction')->assertSuccessful();
    DeprecatedConcepts::resetSupport();

    $schema = Craft::$app->getDb()->getSchema();
    $siteId = Sites::getPrimarySite()->id;
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

    $existingId = Element::factory()->set('type', Tag::class)->title('Paris')->create()->id;
    DB::table($schema->getRawTableName('tags'))->insert([
        'id' => $existingId,
        'groupId' => $groupId,
        'deletedWithGroup' => null,
        'dateCreated' => now(),
        'dateUpdated' => now(),
    ]);

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
