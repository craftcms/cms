<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Revisions;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Entries;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Mcp\Events\ElementSerializing;
use CraftCms\Cms\Mcp\Public\ElementQuery;
use CraftCms\Cms\Mcp\Settings;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\Users;
use CraftCms\Cms\User\Models\User;
use CraftCms\Cms\User\Models\UserGroup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mcp\Exception\ToolCallException;

it('filters and sorts public users without widening group permissions', function (): void {
    $group = UserGroup::factory()->create();
    $first = User::factory()->create(['username' => 'public-alpha', 'firstName' => 'Public']);
    $second = User::factory()->create(['username' => 'public-zulu', 'firstName' => 'Public']);
    $private = User::factory()->create(['username' => 'private-user', 'firstName' => 'Public']);
    Users::assignUserToGroups($first->id, [$group->id]);
    Users::assignUserToGroups($second->id, [$group->id]);
    app()->instance(Settings::class, new Settings([
        'publicSiteHandles' => [Sites::getPrimarySite()->handle],
        'publicUserGroupHandles' => [$group->handle],
    ]));

    $query = app(ElementQuery::class);
    $criteria = [
        'email' => [$first->email, $second->email, $private->email],
        'firstName' => 'Public',
        'hasPhoto' => false,
        'orderBy' => 'username desc',
        'limit' => 1,
    ];

    expect(array_column($query->query('user', $criteria)['elements'], 'id'))->toBe([$second->id])
        ->and(array_column($query->query('user', [...$criteria, 'offset' => 1])['elements'], 'id'))->toBe([$first->id])
        ->and($query->query('user', [...$criteria, 'offset' => 2])['elements'])->toBe([])
        ->and(array_column($query->query('user', [...$criteria, 'orderBy' => 'firstName', 'offset' => 1])['elements'], 'id'))->toBe([$second->id]);
});

it('rejects sorting and filtering on private account attributes', function (array $criteria): void {
    app()->instance(Settings::class, new Settings([
        'publicSiteHandles' => [Sites::getPrimarySite()->handle],
        'publicElementTypes' => ['user'],
    ]));

    expect(fn () => app(ElementQuery::class)->query('user', $criteria))->toThrow(ToolCallException::class);
})->with([
    'admin filter' => [['admin' => true]],
    'login filter' => [['lastLoginDate' => '>2026-01-01']],
    'login sort' => [['orderBy' => 'lastLoginDate desc']],
    'qualified column' => [['orderBy' => 'users.password']],
    'SQL expression' => [['orderBy' => 'random()']],
]);

describe('public relations', function (): void {
    beforeEach(function (): void {
        $field = Field::factory()->create(['handle' => 'relatedEntries', 'type' => Entries::class]);
        $layout = FieldLayout::factory()->forField($field)->create();
        $type = EntryType::factory()->withFieldLayout($layout)->create();
        $public = Section::factory()->create(['handle' => 'publicContent']);
        $private = Section::factory()->create(['handle' => 'privateContent']);
        app(Fields::class)->invalidateCaches();
        app(Fields::class)->refreshFields();

        $factory = EntryModel::factory()->forEntryType($type)->withFieldLayout($layout);
        $this->publicEntryFactory = $factory->forSection($public);
        $this->source = $factory->forSection($public)->createElement(['title' => 'Source']);
        $this->target = $factory->forSection($public)->createElement(['title' => 'Public target']);
        $this->privateTarget = $factory->forSection($private)->createElement(['title' => 'Private target']);
        $this->disabledTarget = $factory->forSection($public)->createElement(['title' => 'Disabled target']);
        DB::table(Table::ELEMENTS)->where('id', $this->disabledTarget->id)->update(['enabled' => false]);

        $this->source->setFieldValue('relatedEntries', [$this->target->id, $this->privateTarget->id, $this->disabledTarget->id]);
        Elements::saveElement($this->source);

        app()->instance(Settings::class, new Settings([
            'publicSiteHandles' => [Sites::getPrimarySite()->handle],
            'publicSectionHandles' => [$public->handle],
        ]));
    });

    it('filters by public relations and rejects private or disabled references', function (): void {
        $query = app(ElementQuery::class);

        expect(array_column($query->query('entry', ['relatedTo' => [$this->target->id]])['elements'], 'id'))
            ->toBe([$this->source->id]);

        foreach ([$this->privateTarget->id, $this->disabledTarget->id] as $id) {
            expect(fn () => $query->query('entry', ['relatedTo' => [$id]]))
                ->toThrow(ToolCallException::class, 'Every relatedTo element must be publicly queryable');
        }
    });

    it('eager-loads only public targets at every relation depth', function (): void {
        $this->target->setFieldValue('relatedEntries', [$this->privateTarget->id]);
        Elements::saveElement($this->target);
        $loaded = [];
        Event::listen(
            ElementSerializing::class,
            function (ElementSerializing $event) use (&$loaded): void {
                if ($event->public && $event->element instanceof Entry) {
                    $loaded = $event->element->getEagerLoadedElements('relatedEntries')->all();
                }
            },
        );

        $result = app(ElementQuery::class)->query('entry', [
            'id' => $this->source->id,
            'with' => ['relatedEntries.relatedEntries'],
        ], fields: ['relatedEntries']);

        expect($result['elements'][0]['relatedEntries'])->toBe([$this->target->id])
            ->and(array_column($loaded, 'id'))->toBe([$this->target->id])
            ->and($loaded[0]->getEagerLoadedElements('relatedEntries')->all())->toBe([]);
    });

    it('enforces draft and revision permissions even for native eager-loading paths', function (): void {
        $draft = app(Drafts::class)->createDraft($this->target);
        $revisionId = app(Revisions::class)->createRevision($this->target, force: true);
        $loaded = [];
        Event::listen(ElementSerializing::class, function (ElementSerializing $event) use (&$loaded): void {
            if ($event->public && $event->element instanceof Entry) {
                $loaded = $event->element->getEagerLoadedElements('relatedEntries')->all();
            }
        });
        $criteria = [
            'id' => $this->source->id,
            'with' => ['relatedEntries.drafts', 'relatedEntries.revisions'],
        ];

        app(ElementQuery::class)->query('entry', $criteria);

        expect($loaded[0]->getEagerLoadedElements('drafts')->all())->toBe([])
            ->and($loaded[0]->getEagerLoadedElements('revisions')->all())->toBe([]);

        app(Settings::class)->publicAllowDrafts = true;
        app(Settings::class)->publicAllowRevisions = true;
        app(ElementQuery::class)->query('entry', $criteria);

        expect(array_column($loaded[0]->getEagerLoadedElements('drafts')->all(), 'id'))->toBe([$draft->id])
            ->and(array_column($loaded[0]->getEagerLoadedElements('revisions')->all(), 'id'))->toContain($revisionId);
    });

    it('bounds the number of eagerly loaded targets', function (): void {
        $targets = $this->publicEntryFactory->count(101)->create();
        $this->source->setFieldValue('relatedEntries', $targets->pluck('id')->all());
        Elements::saveElement($this->source);

        $result = app(ElementQuery::class)->query('entry', [
            'id' => $this->source->id,
            'with' => ['relatedEntries'],
        ], fields: ['relatedEntries']);

        expect($result['elements'][0]['relatedEntries'])->toHaveCount(100);
    });
});
