<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Tests\Bridge;

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Address\Policies\AddressPolicy;
use CraftCms\Cms\Element\Events\ElementEditorContentResolving;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Http\Controllers\Elements\EditElementController;
use CraftCms\Cms\Http\Controllers\Elements\ElementDraftsController;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use CraftCms\Yii2Adapter\Tests\DatabaseTestCase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Inertia\Response as InertiaResponse;
use Override;
use Symfony\Component\HttpFoundation\Response;

uses(DatabaseTestCase::class);

class CustomizedEditorAddress extends Address
{
    #[Override]
    public function prepareEditScreen(Response|CpScreenResponse $response, string $containerId): void
    {
        $response->title('Shipping address');
        $response->contentHtml('<section aria-label="Shipping">' . $response->contentHtml . '</section>');
        HtmlStack::js('window.shippingEditorReady = true;');
    }
}

beforeEach(function() {
    $this->actingAs(User::findOne());
    Gate::policy(CustomizedEditorAddress::class, AddressPolicy::class);
});

it('preserves HTML customizations when autosave refreshes the editor payload', function() {
    config()->set('auth.providers.users.model', UserModel::class);
    Auth::forgetGuards();
    $this->actingAs(UserModel::query()->firstOrFail());

    $entry = EntryModel::factory()->createElement(['title' => 'Canonical title']);
    Event::listen(ElementEditorContentResolving::class, function(ElementEditorContentResolving $event): void {
        $event->html .= '<label>Delivery instructions<input name="instructions"></label>';
        HtmlStack::js('window.deliveryEditorReady = true;');
    });

    $response = $this->postJson(action([ElementDraftsController::class, 'store']), [
        'elementType' => Entry::class,
        'elementId' => $entry->id,
        'siteId' => $entry->siteId,
        'title' => 'Updated title',
        'provisional' => true,
        'editorContainerId' => 'slideout-1',
    ])->assertOk();

    expect($response->json('screen.title'))->toBe('Updated title')
        ->and($response->json('screen.editorContentHtml'))->toContain('Delivery instructions', 'data-element-editor-form')
        ->and($response->json('screen.editorAssets.bodyHtml'))->toContain('deliveryEditorReady')
        ->and($response->json('screen.editorContainerId'))->toBe('slideout-1');
});

it('preserves element HTML events and screen customizations in the Inertia editor', function() {
    $owner = UserModel::factory()->createElement();
    $address = new CustomizedEditorAddress(['ownerId' => $owner->id, 'countryCode' => 'US']);
    Event::listen(ElementEditorContentResolving::class, function(ElementEditorContentResolving $event) {
        $event->html .= '<label>Delivery instructions<input name="instructions"></label>';
    });

    request()->setUserResolver(fn() => auth()->user());
    request()->headers->set('Accept', 'application/json');
    request()->headers->set('X-Inertia', 'true');
    $response = app(EditElementController::class)->setElement($address)();
    expect($response)->toBeInstanceOf(InertiaResponse::class);

    $page = json_decode($response->toResponse(request())->getContent(), true);

    expect($page['props']['editorComponent'])->toBe('craft-legacy:element-editor')
        ->and($page['props']['title'])->toBe('Shipping address')
        ->and($page['props']['editorContentHtml'])->toContain('aria-label="Shipping"', 'Delivery instructions', 'data-element-editor-form')
        ->and($page['props']['editorAssets']['bodyHtml'])->toContain('shippingEditorReady');
});
