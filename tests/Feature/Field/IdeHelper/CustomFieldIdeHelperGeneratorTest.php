<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Entry\Models\EntryType as EntryTypeModel;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Field\IdeHelper\CustomFieldIdeHelperGenerator;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\File;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::findOne());

    $this->ideHelperPath = storage_path('framework/testing/ide-helper');
    Cms::config()->ideHelperEnabled = true;
    Cms::config()->ideHelperPath = $this->ideHelperPath;
    File::ensureDirectoryExists($this->ideHelperPath);
    File::cleanDirectory($this->ideHelperPath);
});

afterEach(function () {
    if (File::isDirectory($this->ideHelperPath)) {
        File::deleteDirectory($this->ideHelperPath);
    }
});

describe('CustomFieldIdeHelperGenerator', function () {
    it('writes the IDE helper file with element field properties', function () {
        $field = Field::factory()->create([
            'handle' => 'blogContent',
            'type' => PlainText::class,
        ]);

        $fieldLayout = FieldLayout::factory()->forField($field)->create();
        $entryModel = Entry::factory()->create();
        $entryModel->element->update(['fieldLayoutId' => $fieldLayout->id]);
        $entryModel->entryType->update(['fieldLayoutId' => $fieldLayout->id]);

        app(Fields::class)->invalidateCaches();
        app(Fields::class)->refreshFields();

        $generator = app(CustomFieldIdeHelperGenerator::class);
        $generator->generate();

        $helperFile = $this->ideHelperPath.'/custom-fields.php';
        expect(File::exists($helperFile))->toBeTrue();

        $content = File::get($helperFile);
        expect($content)
            ->toContain('@property')
            ->toContain('$blogContent');
    });

    it('regenerates IDE helper when a field is saved via project config', function () {
        $field = Field::factory()->create([
            'handle' => 'testRegenField',
            'type' => PlainText::class,
        ]);

        $fieldLayout = FieldLayout::factory()->forField($field)->create();
        $entryModel = Entry::factory()->create();
        $entryModel->element->update(['fieldLayoutId' => $fieldLayout->id]);
        $entryModel->entryType->update(['fieldLayoutId' => $fieldLayout->id]);

        app(Fields::class)->invalidateCaches();
        $fieldsService = app(Fields::class);
        $fieldsService->refreshFields();

        // Verify the helper file doesn't exist yet
        $helperFile = $this->ideHelperPath.'/custom-fields.php';
        expect(File::exists($helperFile))->toBeFalse();

        // Save the field through the real code path, which triggers project config events
        $fieldType = $fieldsService->getFieldById($field->id);
        $fieldType->name = $fieldType->name.' Updated';

        $fieldsService->saveField($fieldType);

        expect(File::exists($helperFile))->toBeTrue();

        $content = File::get($helperFile);
        expect($content)->toContain('testRegenField');
    });

    it('normalizes layout handles to StudlyCase', function () {
        $generator = app(CustomFieldIdeHelperGenerator::class);

        expect($generator->normalizeHandle('blog-posts'))->toBe('BlogPosts')
            ->and($generator->normalizeHandle('my_category'))->toBe('MyCategory')
            ->and($generator->normalizeHandle('simpleHandle'))->toBe('SimpleHandle');
    });

    it('generates entry type classes', function () {
        $field = Field::factory()->create([
            'handle' => 'articleBody',
            'type' => PlainText::class,
        ]);

        $fieldLayout = FieldLayout::factory()->forField($field)->create();
        $entryModel = Entry::factory()->create();
        $entryModel->element->update(['fieldLayoutId' => $fieldLayout->id]);
        $entryModel->entryType->update(['fieldLayoutId' => $fieldLayout->id]);

        app(Fields::class)->invalidateCaches();
        app(Fields::class)->refreshFields();

        $generator = app(CustomFieldIdeHelperGenerator::class);
        $generator->generate();

        $helperFile = $this->ideHelperPath.'/custom-fields.php';
        $content = File::get($helperFile);

        $entryTypeHandle = Str::studly($entryModel->entryType->handle);

        expect($content)
            ->toContain("class {$entryTypeHandle} extends Entry")
            ->toContain('$articleBody');
    });

    it('generates use imports inside namespace blocks for matrix fields', function () {
        $imageLayout = FieldLayout::factory()->withContentTab()->create();

        $imageEntryType = EntryTypeModel::factory()
            ->withFieldLayout($imageLayout)
            ->create([
                'name' => 'Image',
                'handle' => 'image',
            ]);

        $headingEntryType = EntryTypeModel::factory()->create([
            'name' => 'Heading',
            'handle' => 'heading',
        ]);

        $matrixField = Field::factory()->create([
            'handle' => 'contentBlocks',
            'type' => Matrix::class,
            'settings' => ['entryTypes' => [$imageEntryType->id, $headingEntryType->id]],
        ]);

        $fieldLayout = FieldLayout::factory()->forField($matrixField)->create();
        $entryModel = Entry::factory()->create();
        $entryModel->element->update(['fieldLayoutId' => $fieldLayout->id]);
        $entryModel->entryType->update(['fieldLayoutId' => $fieldLayout->id]);

        app(Fields::class)->invalidateCaches();
        app(Fields::class)->refreshFields();

        $generator = app(CustomFieldIdeHelperGenerator::class);
        $generator->generate();

        $helperFile = $this->ideHelperPath.'/custom-fields.php';
        $content = File::get($helperFile);

        // use imports should be inside namespace blocks, not at the top level
        expect($content)
            ->toContain("namespace CraftCms\\Cms\\Entry\\Elements {\n    use CraftCms\\Cms\\Element\\ElementCollection;")
            ->toContain('use CraftCms\\Cms\\Element\\Queries\\EntryQuery;')
            ->toContain('EntryQuery<Heading|Image>|ElementCollection<Heading|Image>')
            ->toContain('$contentBlocks');
    });

    it('generates volume-specific asset classes', function () {
        $field = Field::factory()->create([
            'handle' => 'altText',
            'type' => PlainText::class,
        ]);

        $fieldLayout = FieldLayout::factory()->forField($field)->create([
            'type' => Asset::class,
        ]);

        Volume::factory()->create([
            'handle' => 'images',
            'fieldLayoutId' => $fieldLayout->id,
        ]);

        app(Fields::class)->invalidateCaches();
        app(Fields::class)->refreshFields();

        $generator = app(CustomFieldIdeHelperGenerator::class);
        $generator->generate();

        $helperFile = $this->ideHelperPath.'/custom-fields.php';
        $content = File::get($helperFile);

        expect($content)
            ->toContain('class Images_Asset')
            ->toContain('$altText');
    });

    it('generates multiple volume-specific asset classes', function () {
        $field1 = Field::factory()->create([
            'handle' => 'altText',
            'type' => PlainText::class,
        ]);

        $field2 = Field::factory()->create([
            'handle' => 'caption',
            'type' => PlainText::class,
        ]);

        $fieldLayout1 = FieldLayout::factory()->forField($field1)->create([
            'type' => Asset::class,
        ]);

        $fieldLayout2 = FieldLayout::factory()->forField($field2)->create([
            'type' => Asset::class,
        ]);

        Volume::factory()->create([
            'handle' => 'images',
            'fieldLayoutId' => $fieldLayout1->id,
        ]);

        Volume::factory()->create([
            'handle' => 'documents',
            'fieldLayoutId' => $fieldLayout2->id,
        ]);

        app(Fields::class)->invalidateCaches();
        app(Fields::class)->refreshFields();

        $generator = app(CustomFieldIdeHelperGenerator::class);
        $generator->generate();

        $helperFile = $this->ideHelperPath.'/custom-fields.php';
        $content = File::get($helperFile);

        expect($content)
            ->toContain('class Images_Asset')
            ->toContain('$altText')
            ->toContain('class Documents_Asset')
            ->toContain('$caption');
    });

    it('creates a .gitignore in the output directory', function () {
        $generator = app(CustomFieldIdeHelperGenerator::class);
        $generator->generate();

        $gitignore = $this->ideHelperPath.'/.gitignore';
        expect(File::exists($gitignore))->toBeTrue()
            ->and(File::get($gitignore))->toBe('*');
    });

    it('does not overwrite an existing .gitignore', function () {
        File::put($this->ideHelperPath.'/.gitignore', "custom\n");

        $generator = app(CustomFieldIdeHelperGenerator::class);
        $generator->generate();

        expect(File::get($this->ideHelperPath.'/.gitignore'))->toBe("custom\n");
    });

    it('can be generated via artisan command', function () {
        $field = Field::factory()->create([
            'handle' => 'commandTestField',
            'type' => PlainText::class,
        ]);

        $fieldLayout = FieldLayout::factory()->forField($field)->create();
        $entryModel = Entry::factory()->create();
        $entryModel->element->update(['fieldLayoutId' => $fieldLayout->id]);
        $entryModel->entryType->update(['fieldLayoutId' => $fieldLayout->id]);

        app(Fields::class)->invalidateCaches();
        app(Fields::class)->refreshFields();

        $this->artisan('craft:ide-helper:custom-fields')
            ->assertSuccessful();

        $helperFile = $this->ideHelperPath.'/custom-fields.php';
        expect(File::exists($helperFile))->toBeTrue();

        $content = File::get($helperFile);
        expect($content)->toContain('$commandTestField');
    });
});
