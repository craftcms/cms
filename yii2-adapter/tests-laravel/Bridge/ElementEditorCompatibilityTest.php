<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Tests\Bridge;

use craft\base\Element;
use craft\events\DefineHtmlEvent;
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
use yii\base\Event as YiiEvent;

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

class ButtonedEditorAddress extends Address
{
    #[Override]
    public function getAdditionalButtons(): string
    {
        HtmlStack::js('window.labelButtonReady = true;');

        return '<button type="button" class="btn" id="print-label">Print label</button>' . parent::getAdditionalButtons();
    }

    #[Override]
    public function prepareEditScreen(Response|CpScreenResponse $response, string $containerId): void
    {
        $response->additionalButtonsHtml('<a class="btn" href="https://example.com/track">Track</a>');
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

it('renders Craft 5 additional buttons in the Inertia editor’s footer', function() {
    $owner = UserModel::factory()->createElement();
    $address = new ButtonedEditorAddress(['ownerId' => $owner->id, 'countryCode' => 'US']);
    Gate::policy(ButtonedEditorAddress::class, AddressPolicy::class);
    YiiEvent::on(ButtonedEditorAddress::class, Element::EVENT_DEFINE_ADDITIONAL_BUTTONS, function(DefineHtmlEvent $event) {
        $event->html .= '<a class="btn" href="https://example.com/invoice">Invoice</a>';
    });

    try {
        request()->setUserResolver(fn() => auth()->user());
        request()->headers->set('Accept', 'application/json');
        request()->headers->set('X-Inertia', 'true');
        $response = app(EditElementController::class)->setElement($address)();
        $page = json_decode($response->toResponse(request())->getContent(), true);
    } finally {
        YiiEvent::off(ButtonedEditorAddress::class, Element::EVENT_DEFINE_ADDITIONAL_BUTTONS);
    }

    expect($page['props']['editorAdditionalButtonsHtml'])->toContain('Track', 'Print label', 'Invoice')
        ->and($page['props']['editorAssets']['bodyHtml'])->toContain('labelButtonReady');
});

it('leaves out additional buttons when nothing defines them', function() {
    $owner = UserModel::factory()->createElement();
    $address = new CustomizedEditorAddress(['ownerId' => $owner->id, 'countryCode' => 'US']);

    request()->setUserResolver(fn() => auth()->user());
    request()->headers->set('Accept', 'application/json');
    request()->headers->set('X-Inertia', 'true');
    $response = app(EditElementController::class)->setElement($address)();
    $page = json_decode($response->toResponse(request())->getContent(), true);

    expect($page['props']['editorAdditionalButtonsHtml'])->toBeNull();
});
