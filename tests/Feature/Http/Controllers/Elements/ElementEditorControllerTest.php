<?php

declare(strict_types=1);

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Address\Policies\AddressPolicy;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Http\Controllers\Elements\EditElementController;
use CraftCms\Cms\Http\Controllers\Elements\ElementEditorController;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Http\ViewModels\ElementEditViewModel;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Facades\Gate;
use Inertia\Response as InertiaResponse;

use function Pest\Laravel\actingAs;

class TestEditorAddressViewModel extends ElementEditViewModel
{
    #[Override]
    protected function elementSaveUrl(): string
    {
        return Url::actionUrl('elements/save');
    }
}

/** @extends ElementEditorController<Address> */
class TestAddressEditorController extends ElementEditorController
{
    #[Override]
    protected function elementType(): string
    {
        return Address::class;
    }

    #[Override]
    protected function component(): string
    {
        return 'test/AddressEdit';
    }

    #[Override]
    protected function viewModel(ElementInterface $element, bool $canSave, bool $mergedCanonicalChanges): ElementEditViewModel
    {
        return new TestEditorAddressViewModel($element, $this->request, $canSave, $mergedCanonicalChanges);
    }
}

class TestEditorAddress extends Address
{
    #[Override]
    public static function editControllerClass(): string
    {
        return TestAddressEditorController::class;
    }
}

beforeEach(function () {
    actingAs(User::findOne());
    Gate::policy(TestEditorAddress::class, AddressPolicy::class);
});

function editElementControllerFor(ElementInterface $element, bool $inertia): InertiaResponse|CpScreenResponse
{
    request()->setUserResolver(fn () => auth()->user());
    request()->headers->set('Accept', 'application/json');

    if ($inertia) {
        request()->headers->set('X-Inertia', 'true');
    } else {
        request()->headers->remove('X-Inertia');
    }

    return app(EditElementController::class)->setElement($element)();
}

function inertiaComponent(InertiaResponse $response): string
{
    return new ReflectionProperty($response, 'component')->getValue($response);
}

it('hands elements with an Inertia editor to their editor controller', function () {
    $user = UserModel::factory()->createElement();
    $address = new TestEditorAddress(['ownerId' => $user->id, 'countryCode' => 'US']);

    $response = editElementControllerFor($address, inertia: true);

    expect($response)->toBeInstanceOf(InertiaResponse::class)
        ->and(inertiaComponent($response))->toBe('test/AddressEdit');
});

it('keeps legacy jQuery slideouts on the legacy editor', function () {
    $user = UserModel::factory()->createElement();
    $address = new TestEditorAddress(['ownerId' => $user->id, 'countryCode' => 'US']);

    expect(editElementControllerFor($address, inertia: false))->toBeInstanceOf(CpScreenResponse::class);
});

it('keeps element types without an Inertia editor on the legacy editor', function () {
    $user = UserModel::factory()->createElement();
    $address = new Address(['ownerId' => $user->id, 'countryCode' => 'US']);

    expect(Address::editControllerClass())->toBeNull()
        ->and(editElementControllerFor($address, inertia: true))->toBeInstanceOf(CpScreenResponse::class);
});
