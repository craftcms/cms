<?php

declare(strict_types=1);

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\FieldLayout\LayoutElements\BaseField;
use CraftCms\Cms\FieldLayout\LayoutElements\TextField;
use CraftCms\Cms\FieldLayout\LayoutElements\Users\EmailField;
use CraftCms\Cms\FieldLayout\LayoutElements\Users\FullNameField;
use CraftCms\Cms\FieldLayout\LayoutElements\Users\UsernameField;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;

use function Pest\Laravel\actingAs;

/** The `autocomplete` attribute of the field's one rendered input. */
function renderedAutocomplete(BaseField $field, ?ElementInterface $element): ?string
{
    $autocompletes = renderedAutocompletes($field, $element);

    expect($autocompletes)->toHaveCount(1);

    return array_first($autocompletes);
}

beforeEach(function () {
    app(GeneralConfig::class)->useEmailAsUsername = false;
    app(GeneralConfig::class)->showFirstAndLastNameFields = false;
    $this->admin = User::findOne();
    actingAs($this->admin);
});

it('gives the current user’s own account fields their autocomplete tokens', function (BaseField $field, string $token) {
    expect(renderedAutocomplete($field, $this->admin))->toBe($token);
})->with([
    'Full Name' => fn () => [new FullNameField, 'name'],
    'Username' => fn () => [new UsernameField, 'username'],
    'Email' => fn () => [new EmailField, 'email'],
]);

it('turns autocomplete off on another user’s account fields', function (BaseField $field) {
    $other = UserModel::factory()->createElement();

    expect(renderedAutocomplete($field, $other))->toBe('off');
})->with([
    'Full Name' => fn () => new FullNameField,
    'Username' => fn () => new UsernameField,
    'Email' => fn () => new EmailField,
]);

it('passes a text field’s configured autocomplete on, and defaults to off', function (array $config, string $expected) {
    $field = new TextField(['attribute' => 'title', 'label' => 'Title', ...$config]);

    expect(renderedAutocomplete($field, new Entry(['title' => 'Example'])))->toBe($expected);
})->with([
    'configured' => [['autocomplete' => 'url'], 'url'],
    'default' => [[], 'off'],
]);

it('turns autocomplete off on account fields rendered without a user', function (BaseField $field) {
    expect(renderedAutocomplete($field, null))->toBe('off');
})->with([
    'Full Name' => fn () => new FullNameField,
    'Username' => fn () => new UsernameField,
]);

it('gives the current user’s own first and last name fields their autocomplete tokens', function () {
    app(GeneralConfig::class)->showFirstAndLastNameFields = true;

    expect(renderedAutocompletes(new FullNameField(['uid' => 'full-name']), $this->admin))
        ->toBe(['firstName' => 'given-name', 'lastName' => 'family-name']);
});

it('turns autocomplete off on another user’s first and last name fields', function () {
    app(GeneralConfig::class)->showFirstAndLastNameFields = true;
    $other = UserModel::factory()->createElement();

    expect(renderedAutocompletes(new FullNameField(['uid' => 'full-name']), $other))
        ->toBe(['firstName' => 'off', 'lastName' => 'off']);
});
