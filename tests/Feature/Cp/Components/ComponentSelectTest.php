<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Components\ComponentSelect;
use CraftCms\Cms\Entry\EntryTypes;
use CraftCms\Cms\Entry\Models\EntryType as EntryTypeModel;
use CraftCms\Cms\Shared\Enums\Color;
use CraftCms\Cms\User\Elements\User;

use function CraftCms\Cms\ui;
use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::findOne());

    $news = EntryTypeModel::factory()->create([
        'name' => 'News',
        'handle' => 'news',
        'icon' => 'newspaper',
        'color' => Color::Red->value,
    ]);
    $article = EntryTypeModel::factory()->create([
        'name' => 'Article',
        'handle' => 'article',
    ]);

    $entryTypes = app(EntryTypes::class);
    $entryTypes->refreshEntryTypes();
    $this->news = $entryTypes->getEntryTypeById($news->id);
    $this->article = $entryTypes->getEntryTypeById($article->id);
});

it('renders the selected components as chips', function () {
    $html = ComponentSelect::make()
        ->id('types')
        ->name('types[]')
        ->options([$this->news, $this->article])
        ->values([$this->news])
        ->toHtml();

    expect($html)
        ->toStartWith('<input type="hidden" name="types" value>')
        ->toContainTag('craft-component-select', ['id' => 'types', 'name' => 'types[]', 'data-id' => 'component-select'])
        ->toContainTag('craft-chip', ['data-id' => $this->news->id])
        ->not->toContainTag('craft-chip', ['data-id' => $this->article->id])
        ->toContainTag('input', ['type' => 'hidden', 'name' => 'types[]', 'value' => $this->news->id]);
});

it('emits "false" for default-on settings that are turned off', function () {
    $html = ComponentSelect::make()->sortable(false)->selectable(false)->toHtml();

    expect($html)->toContainTag('craft-component-select', [
        'sortable' => 'false',
        'selectable' => 'false',
        'checkbox-options' => 'false',
    ]);
});

it('is not sortable with a limit of 1', function () {
    expect(ComponentSelect::make()->limit(1)->toHtml())
        ->toContainTag('craft-component-select', ['limit' => '1', 'sortable' => 'false']);
});

it('hides selected options and renders icons, colors and handles', function () {
    $html = ComponentSelect::make()
        ->options([$this->news, $this->article])
        ->values([$this->news])
        ->showHandles()
        ->toHtml();

    expect($html)
        ->toContainTag('craft-action-item', ['data-id' => $this->news->id, 'hidden' => true, 'icon' => 'newspaper', 'icon-color' => 'red'])
        ->toContainTag('craft-action-item', ['data-id' => $this->article->id, 'hidden' => false, 'data-keywords' => 'article'])
        ->toContainTag('span', ['class' => 'menu-item-description mt-2xs text-xs font-mono text-quiet']);
});

it('sorts options by label', function () {
    $html = ComponentSelect::make()->options([$this->news, $this->article])->toHtml();

    expect(strpos($html, '>Article<'))->toBeLessThan(strpos($html, '>News<'));
});

it('checks selected options in checkbox mode', function () {
    $html = ComponentSelect::make()
        ->options([$this->news, $this->article])
        ->values([$this->news])
        ->checkboxOptions()
        ->toHtml();

    expect($html)
        ->toContainTag('craft-action-item', ['data-id' => $this->news->id, 'type' => 'checkbox', 'checked' => true, 'hidden' => false])
        ->toContainTag('craft-action-item', ['data-id' => $this->article->id, 'type' => 'checkbox', 'checked' => false]);
});

it('makes the menu searchable with more than five options', function () {
    $select = ComponentSelect::make()->options(array_fill(0, 6, $this->article));

    expect($select->toHtml())->toContainTag('craft-action-menu', ['searchable' => true])
        ->and(ComponentSelect::make()->options([$this->article])->toHtml())
        ->not->toContainTag('craft-action-menu', ['searchable' => true]);
});

it('offers a Create button unless disabled', function () {
    $select = fn () => ComponentSelect::make()->createAction('settings/entry-types/new');

    expect($select()->toHtml())->toContainTag('craft-button', ['command' => '--create-item'])
        ->and($select()->disabled()->toHtml())
        ->not->toContainTag('craft-button', ['command' => '--create-item'])
        ->toContainTag('craft-button', ['command' => '--choose-item', 'disabled' => true]);
});

it('is available through the ui() helper', function () {
    expect((string) ui('component-select', ['name' => 'types[]', 'checkboxOptions' => true]))
        ->toContainTag('craft-component-select', ['name' => 'types[]', 'checkbox-options' => true]);
});
