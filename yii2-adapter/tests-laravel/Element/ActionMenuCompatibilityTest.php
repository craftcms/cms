<?php

declare(strict_types=1);

use craft\base\Event;
use craft\elements\Entry as LegacyEntry;
use craft\events\DefineMenuItemsEvent;
use CraftCms\Cms\Element\Enums\ElementActionContext;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Cms\User\Models\User;
use CraftCms\Yii2Adapter\Tests\DatabaseTestCase;
use Illuminate\Support\Facades\Crypt;

uses(DatabaseTestCase::class);

beforeEach(function(): void {
    $this->actingAs(User::first());
    $this->entry = EntryElement::find()->id(Entry::factory()->create()->id)->one();
});

afterEach(function(): void {
    Event::off(LegacyEntry::class, LegacyEntry::EVENT_DEFINE_ACTION_MENU_ITEMS);
});

function legacyMenuDescriptor(EntryElement $entry, string $label, ElementActionContext $context = ElementActionContext::Editor): ?array
{
    return collect($entry->actionMenuDescriptors($context))->firstWhere('label', $label);
}

function legacyMenuDeprecation(string $key): ?object
{
    return collect(Deprecator::getRequestLogs())->firstWhere('key', $key);
}

it('converts legacy event items into action menu descriptors', function(): void {
    Event::on(LegacyEntry::class, LegacyEntry::EVENT_DEFINE_ACTION_MENU_ITEMS, function(DefineMenuItemsEvent $event) {
        $event->items[] = ['label' => 'Docs', 'icon' => 'book', 'url' => 'https://example.com/docs', 'attributes' => ['target' => '_blank']];
        $event->items[] = [
            'label' => 'Sync',
            'action' => 'my-plugin/sync',
            'params' => ['id' => 1],
            'redirect' => 'entries',
            'confirm' => 'Sync it?',
        ];
        $event->items[] = ['label' => 'Purge', 'action' => 'my-plugin/purge', 'destructive' => true];
    });

    $docs = legacyMenuDescriptor($this->entry, 'Docs');
    $sync = legacyMenuDescriptor($this->entry, 'Sync');
    $labels = array_column($this->entry->actionMenuDescriptors(), 'label');

    expect($docs['icon'])->toBe('book')
        ->and($docs['behavior'])->toBe(['type' => 'link', 'href' => 'https://example.com/docs', 'newTab' => true])
        ->and($sync['behavior']['type'])->toBe('submit')
        ->and($sync['behavior']['actionUrl'])->toContain('my-plugin/sync')
        ->and($sync['behavior']['params'])->toBe(['id' => 1])
        ->and($sync['behavior']['confirm'])->toBe('Sync it?')
        ->and(Crypt::decrypt($sync['behavior']['redirect']))->toBe('entries')
        ->and(array_search('Sync', $labels))->toBeLessThan(array_search('Delete entry', $labels))
        ->and(end($labels))->toBe('Purge')
        ->and(legacyMenuDescriptor($this->entry, 'Purge', ElementActionContext::Field))->toBeNull()
        ->and(legacyMenuDeprecation('Element::EVENT_DEFINE_ACTION_MENU_ITEMS')?->file)->toBe(__FILE__);
});

it('drops legacy items that rely on JavaScript and says so', function(): void {
    Event::on(LegacyEntry::class, LegacyEntry::EVENT_DEFINE_ACTION_MENU_ITEMS, function(DefineMenuItemsEvent $event) {
        $event->items[] = ['id' => 'my-plugin-action', 'label' => 'Do the thing'];
    });

    expect(legacyMenuDescriptor($this->entry, 'Do the thing'))->toBeNull()
        ->and(legacyMenuDeprecation('action-menu-item:Do the thing')?->file)->toBe(__FILE__);
});

it('converts items a plugin element type adds by overriding the legacy methods', function(): void {
    $entry = new class(['id' => $this->entry->id, 'siteId' => $this->entry->siteId, 'sectionId' => $this->entry->sectionId, 'typeId' => $this->entry->typeId, 'title' => $this->entry->title]) extends EntryElement {
        protected function safeActionMenuItems(): array
        {
            return [...parent::safeActionMenuItems(), ['label' => 'Plugin docs', 'url' => 'https://example.com']];
        }
    };

    $plain = new class(['id' => $this->entry->id, 'siteId' => $this->entry->siteId, 'sectionId' => $this->entry->sectionId, 'typeId' => $this->entry->typeId, 'title' => $this->entry->title]) extends EntryElement {
    };

    expect(array_column($entry->actionMenuDescriptors(), 'label'))
        ->toBe([...array_column($plain->actionMenuDescriptors(), 'label'), 'Plugin docs'])
        ->and(legacyMenuDeprecation($entry::class . '::safeActionMenuItems')?->file)->toBe(__FILE__);
});

it('leaves legacy overrides alone once the element type provides descriptors', function(): void {
    $entry = new class(['id' => $this->entry->id, 'siteId' => $this->entry->siteId, 'sectionId' => $this->entry->sectionId, 'typeId' => $this->entry->typeId, 'title' => $this->entry->title]) extends EntryElement {
        protected function safeActionMenuItems(): array
        {
            return [...parent::safeActionMenuItems(), ['label' => 'Plugin docs', 'url' => 'https://example.com']];
        }

        protected function extraActionMenuDescriptors(ElementActionContext $context = ElementActionContext::Editor): array
        {
            return [['label' => 'Plugin docs', 'behavior' => ['type' => 'link', 'href' => 'https://example.com']]];
        }
    };

    expect(array_count_values(array_column($entry->actionMenuDescriptors(), 'label'))['Plugin docs'])->toBe(1);
});
