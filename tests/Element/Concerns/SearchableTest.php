<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Events\DefineKeywords;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\actingAs;

class TestSearchableElement extends Element
{
    public ?string $customField = null;

    #[Override]
    public static function displayName(): string
    {
        return 'Test Element';
    }

    #[Override]
    protected function searchKeywords(string $attribute): string
    {
        if ($attribute === 'customField') {
            return 'custom-keywords-for-'.$this->customField;
        }

        return parent::searchKeywords($attribute);
    }
}

describe('getSearchKeywords', function () {
    test('returns string representation of attribute value by default', function () {
        $element = new TestSearchableElement;
        $element->title = 'Test Title';

        expect($element->getSearchKeywords('title'))->toBe('Test Title');
    });

    test('returns empty string for null attribute', function () {
        $element = new TestSearchableElement;
        $element->customField = null;

        expect($element->getSearchKeywords('customField'))->toBe('custom-keywords-for-');
    });

    test('uses custom searchKeywords implementation', function () {
        $element = new TestSearchableElement;
        $element->customField = 'value';

        expect($element->getSearchKeywords('customField'))->toBe('custom-keywords-for-value');
    });

    test('DefineKeywords event can override keywords when handled', function () {
        $element = new TestSearchableElement;
        $element->title = 'Original Title';

        Event::listen(function (DefineKeywords $event) {
            if ($event->attribute === 'title') {
                $event->keywords = 'overridden-keywords';
                $event->handled = true;
            }
        });

        $keywords = $element->getSearchKeywords('title');

        expect($keywords)->toBe('overridden-keywords');
    });

    test('DefineKeywords returns empty string when keywords is empty and handled', function () {
        $element = new TestSearchableElement;
        $element->title = 'Original Title';

        Event::listen(function (DefineKeywords $event) {
            $event->keywords = '';
            $event->handled = true;
        });

        $keywords = $element->getSearchKeywords('title');

        expect($keywords)->toBe('');
    });

    test('DefineKeywords is ignored when not handled', function () {
        $element = new TestSearchableElement;
        $element->title = 'Original Title';

        Event::listen(function (DefineKeywords $event) {
            $event->keywords = 'this-should-be-ignored';
            // Note: not setting $event->handled = true
        });

        $keywords = $element->getSearchKeywords('title');

        expect($keywords)->toBe('Original Title');
    });

    test('works with real Entry element', function () {
        actingAs(User::findOne());
        $entryModel = EntryModel::factory()->create();
        $entryModel->element->siteSettings->first()->update([
            'title' => 'My Entry Title',
        ]);

        $entry = Entry::find()->id($entryModel->id)->one();

        expect($entry->getSearchKeywords('title'))->toBe('My Entry Title');
    });
});
