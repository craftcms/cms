<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Events\ElementAdditionalButtonDescriptorsResolving;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\User\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\get;

beforeEach(function () {
    $this->actingAs(User::first());
    $this->entry = EntryElement::find()->id(Entry::factory()->create()->id)->one();
});

it('includes element-type buttons', function () {
    $entry = $this->entry;
    $element = new class(['id' => $entry->id, 'siteId' => $entry->siteId, 'sectionId' => $entry->sectionId, 'typeId' => $entry->typeId, 'title' => $entry->title]) extends EntryElement
    {
        protected function defineAdditionalButtonDescriptors(): array
        {
            return [[
                'label' => 'Open report',
                'behavior' => ['type' => 'link', 'href' => 'https://example.com', 'newTab' => true],
            ]];
        }
    };

    expect($element->additionalButtonDescriptors())->toBe([[
        'label' => 'Open report',
        'behavior' => ['type' => 'link', 'href' => 'https://example.com', 'newTab' => true],
    ]]);
});

it('sends buttons added by event listeners with the edit page, apart from the save actions', function () {
    Event::listen(function (ElementAdditionalButtonDescriptorsResolving $event) {
        $event->items[] = [
            'label' => 'Export',
            'variant' => 'secondary',
            'behavior' => ['type' => 'download', 'actionUrl' => 'https://example.com/export'],
        ];
    });

    get($this->entry->getCpEditUrl())
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('additionalButtons.0.label', 'Export')
            ->where('additionalButtons.0.behavior.type', 'download')
            ->where('editorActions.buttons', fn (Collection $actions) => $actions->doesntContain('label', 'Export'))
            ->etc()
        );
});
