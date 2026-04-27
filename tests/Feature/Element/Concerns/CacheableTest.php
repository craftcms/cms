<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Events\DefineCacheTags;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\actingAs;

class TestCacheableElement extends Element
{
    protected array $customCacheTags = [];

    #[Override]
    public static function displayName(): string
    {
        return 'Test Element';
    }

    public function setCustomCacheTags(array $tags): void
    {
        $this->customCacheTags = $tags;
    }

    #[Override]
    protected function cacheTags(): array
    {
        return $this->customCacheTags;
    }
}

describe('getCacheTags', function () {
    test('returns empty array by default', function () {
        $element = new TestCacheableElement;
        expect($element->getCacheTags())->toBe([]);
    });

    test('returns custom cache tags from cacheTags method', function () {
        $element = new TestCacheableElement;
        $element->setCustomCacheTags(['tag1', 'tag2']);

        expect($element->getCacheTags())->toBe(['tag1', 'tag2']);
    });

    test('EVENT_DEFINE_CACHE_TAGS event can modify cache tags', function () {
        $element = new TestCacheableElement;
        $element->setCustomCacheTags(['original']);

        Event::listen(function (DefineCacheTags $event) {
            $event->tags = array_merge($event->tags, ['added-by-event']);
        });

        $tags = $element->getCacheTags();

        expect($tags)->toContain('original');
        expect($tags)->toContain('added-by-event');
    });

    test('works with real Entry element', function () {
        actingAs(User::findOne());
        $entryModel = EntryModel::factory()->create();
        $entry = Entry::find()->id($entryModel->id)->one();

        expect($entry->getCacheTags())
            ->toContain("entryType:$entryModel->typeId")
            ->toContain("section:$entryModel->sectionId");
    });
});
