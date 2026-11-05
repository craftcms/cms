<?php

declare(strict_types=1);

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\FieldLayout\FieldLayoutElementContext;
use CraftCms\Cms\FieldLayout\LayoutElements\Addresses\AddressField;
use CraftCms\Cms\FieldLayout\LayoutElements\Addresses\CountryCodeField;
use CraftCms\Cms\FieldLayout\LayoutElements\Addresses\OrganizationField;
use CraftCms\Cms\FieldLayout\LayoutElements\BaseField;
use CraftCms\Cms\FieldLayout\LayoutElements\FullNameField;
use CraftCms\Cms\Http\Controllers\AddressesController;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use CraftCms\Cms\Ui\UiResolver;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use Symfony\Component\DomCrawler\Crawler;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

/**
 * The `autocomplete` attribute of the address field's line 1 and postal code inputs.
 *
 * @return array{addressLine1: string|null, postalCode: string|null}
 */
function renderedAddressAutocompletes(User $owner): array
{
    $address = new Address(['countryCode' => 'US']);
    $address->setOwnerId($owner->id);
    $context = new UiContext;
    $node = new AddressField()->uiNode(new FieldLayoutElementContext($address, $context));
    $payload = app(UiResolver::class)->resolve(Ui::make([$node]), $context);
    $html = new Crawler(app(UiHtmlRenderer::class)->render($payload));

    return [
        'addressLine1' => $html->filter('input[name$="[addressLine1]"]')->attr('autocomplete'),
        'postalCode' => $html->filter('input[name$="[postalCode]"]')->attr('autocomplete'),
    ];
}

/**
 * The `autocomplete` attribute of each input a field renders for an address, by input name.
 *
 * @return array<string, string|null>
 */
function renderedAddressFieldAutocompletes(BaseField $field, User $owner): array
{
    $address = new Address(['countryCode' => 'US']);
    $address->setOwnerId($owner->id);
    $context = new UiContext;
    $node = $field->uiNode(new FieldLayoutElementContext($address, $context));
    $payload = app(UiResolver::class)->resolve(Ui::make([$node]), $context);
    $inputs = new Crawler(app(UiHtmlRenderer::class)->render($payload))->filter('input:not([type="hidden"])');

    return array_combine(
        $inputs->each(fn (Crawler $input) => $input->attr('name')),
        $inputs->each(fn (Crawler $input) => $input->attr('autocomplete')),
    );
}

beforeEach(function () {
    app(GeneralConfig::class)->showFirstAndLastNameFields = false;
    $this->admin = User::findOne();
    actingAs($this->admin);
});

it('gives the current user’s own address its autocomplete tokens', function () {
    expect(renderedAddressAutocompletes($this->admin))
        ->toBe(['addressLine1' => 'address-line1', 'postalCode' => 'postal-code']);
});

it('turns autocomplete off on another user’s address', function () {
    $other = UserModel::factory()->createElement();

    expect(renderedAddressAutocompletes($other))
        ->toBe(['addressLine1' => 'off', 'postalCode' => 'off']);
});

it('keeps the current user’s autocomplete tokens when the address fields are refreshed', function (bool $own, string $expected) {
    $definitions = postJson(action([AddressesController::class, 'fields']), [
        'namespace' => 'address',
        'countryCode' => 'US',
        'belongsToCurrentUser' => $own,
    ])->assertOk()->json('fieldDefinitions');

    expect(collect($definitions)->firstWhere('name', 'addressLine1')['autocomplete'])->toBe($expected);
})->with([
    'own address' => [true, 'address-line1'],
    'someone else’s' => [false, 'off'],
]);

it('gives the current user’s own country field its autocomplete token, and turns it off on another user’s', function (bool $own, string $expected) {
    $address = new Address(['countryCode' => 'CA']);
    $address->setOwnerId(($own ? $this->admin : UserModel::factory()->createElement())->id);
    $context = new UiContext;
    $node = new CountryCodeField()->uiNode(new FieldLayoutElementContext($address, $context));
    $payload = app(UiResolver::class)->resolve(Ui::make([$node]), $context);
    $select = new Crawler(app(UiHtmlRenderer::class)->render($payload))->filter('select');

    expect($select->attr('autocomplete'))->toBe($expected);
})->with([
    'own address' => [true, 'country'],
    'someone else’s' => [false, 'off'],
]);

it('gives the current user’s own address its name and organization tokens, and turns them off on another user’s', function (bool $splitName, BaseField $field, bool $own, array $expected) {
    app(GeneralConfig::class)->showFirstAndLastNameFields = $splitName;
    $owner = $own ? $this->admin : UserModel::factory()->createElement();

    expect(renderedAddressFieldAutocompletes($field, $owner))->toBe($expected);
})->with([
    'Full Name, own' => fn () => [false, new FullNameField, true, ['fullName' => 'name']],
    'Full Name, someone else’s' => fn () => [false, new FullNameField, false, ['fullName' => 'off']],
    'First and Last Name, own' => fn () => [true, new FullNameField(['uid' => 'full-name']), true, ['firstName' => 'given-name', 'lastName' => 'family-name']],
    'First and Last Name, someone else’s' => fn () => [true, new FullNameField(['uid' => 'full-name']), false, ['firstName' => 'off', 'lastName' => 'off']],
    'Organization, own' => fn () => [false, new OrganizationField, true, ['organization' => 'organization']],
    'Organization, someone else’s' => fn () => [false, new OrganizationField, false, ['organization' => 'off']],
]);
