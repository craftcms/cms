<?php

declare(strict_types=1);

use JMac\Testing\Double;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\Revisions;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Http\Controllers\Elements\PreviewElementController;
use CraftCms\Cms\Http\Requests\ElementRequest;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    actingAs(User::findOne());
});

it('renders preview pages for saved drafts and honors signed return urls', function () {
    $entry = EntryModel::factory()->createElement([
        'title' => 'Canonical Title',
        'slug' => 'canonical-title',
    ]);

    $draft = app(Drafts::class)->createDraft($entry, auth()->id(), name: 'Preview Draft');
    $draft->title = 'Draft Title';
    Elements::saveElement($draft);

    $request = Double::for(ElementRequest::class);
    $request->expects('element')->with(['id' => $entry->id], true)->returns($draft);
    $request->expects('getSigned')->with('returnUrl', ElementHelper::postEditUrl($draft))->returns('entries');

    HtmlStack::clear();

    $view = new PreviewElementController($request)->__invoke($entry->id, "-$entry->slug");
    $html = $view->render();

    expect($view->getData()['title'])->toBe('Draft Title')
        ->and($view->getData()['docTitle'])->toContain("($draft->draftName)")
        ->and($html)->toContain('new Craft.Preview({')
        ->and($html)->toContain(sprintf('elementId: %d', $draft->id))
        ->and($html)->toContain(sprintf('draftId: %d', $draft->draftId))
        ->and($html)->toContain('revisionId: null')
        ->and($html)->toContain('redirectUrl: "entries"');
});

it('renders preview pages for provisional drafts using canonical ids in the preview config', function () {
    $entry = EntryModel::factory()->createElement([
        'title' => 'Canonical Title',
        'slug' => 'canonical-title',
    ]);

    $draft = app(Drafts::class)->createDraft($entry, auth()->id(), provisional: true);
    $draft->title = 'Edited Title';
    Elements::saveElement($draft);

    $request = Double::for(ElementRequest::class);
    $request->expects('element')->with(['id' => $entry->id], true)->returns($draft);
    $request->expects('getSigned')->with('returnUrl', ElementHelper::postEditUrl($draft))->returns(ElementHelper::postEditUrl($draft));

    HtmlStack::clear();

    $view = new PreviewElementController($request)->__invoke($entry->id, "-$entry->slug");
    $html = $view->render();

    expect($view->getData()['title'])->toBe('Edited Title')
        ->and($view->getData()['docTitle'])->toContain('Edited')
        ->and($html)->toContain(sprintf('elementId: %d', $entry->id))
        ->and($html)->toContain('draftId: null')
        ->and($html)->toContain(
            sprintf('redirectUrl: %s', json_encode(ElementHelper::postEditUrl($draft), JSON_THROW_ON_ERROR)),
        );
});

it('renders preview pages for revisions', function () {
    $entry = EntryModel::factory()->createElement([
        'title' => 'Current Title',
        'slug' => 'current-title',
    ]);

    $revisionElementId = app(Revisions::class)->createRevision($entry, auth()->id());
    $revision = Elements::getElementById($revisionElementId, Entry::class, $entry->siteId);

    $request = Double::for(ElementRequest::class);
    $request->expects('element')->with(['id' => $entry->id], true)->returns($revision);
    $request->expects('getSigned')->with('returnUrl', ElementHelper::postEditUrl($revision))->returns(ElementHelper::postEditUrl($revision));

    HtmlStack::clear();

    $view = new PreviewElementController($request)->__invoke($entry->id, "-$entry->slug");
    $html = $view->render();

    expect($view->getData()['title'])->toBe($revision->title)
        ->and($view->getData()['docTitle'])->toContain($revision->getRevisionLabel())
        ->and($html)->toContain(sprintf('elementId: %d', $revision->id))
        ->and($html)->toContain('draftId: null')
        ->and($html)->toContain(sprintf('revisionId: %d', $revision->revisionId));
});

it('redirects to the canonical edit url when the requested draft is invalid', function () {
    $entry = EntryModel::factory()->createElement([
        'title' => 'Canonical Title',
        'slug' => 'canonical-title',
    ]);

    $redirect = redirect($entry->getCpEditUrl());

    $request = Double::for(ElementRequest::class);
    $request->expects('element')->with(['id' => $entry->id], true)->returns($redirect);

    $response = new PreviewElementController($request)->__invoke($entry->id, "-$entry->slug");

    expect($response)->toBe($redirect);
});

it('returns a bad request when no element matches the preview request', function () {
    get(action(PreviewElementController::class, [
        'id' => 999999,
        'slug' => '-missing',
    ]))->assertBadRequest();
});
