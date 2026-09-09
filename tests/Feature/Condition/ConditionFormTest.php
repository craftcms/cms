<?php

declare(strict_types=1);

use CraftCms\Cms\Address\Conditions\AdministrativeAreaConditionRule;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Condition\Conditions;
use CraftCms\Cms\Element\Conditions\DateCreatedConditionRule;
use CraftCms\Cms\Element\Conditions\ElementCondition;
use CraftCms\Cms\Element\Conditions\IdConditionRule;
use CraftCms\Cms\Element\Conditions\RelatedToConditionRule;
use CraftCms\Cms\Element\Conditions\StatusConditionRule;
use CraftCms\Cms\Element\Conditions\TitleConditionRule;
use CraftCms\Cms\Entry\Conditions\AuthorConditionRule;
use CraftCms\Cms\Entry\Conditions\EntryCondition;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Conditions\MoneyFieldConditionRule;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Money;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\User\Models\User;

class FormMoneyConditionRule extends MoneyFieldConditionRule
{
    protected function field(): FieldInterface
    {
        return new Money(['currency' => 'EUR']);
    }

    public function getLabel(): string
    {
        return 'Price';
    }
}

it('round trips date control values in their timezone', function () {
    Cms::config()->timezone = 'Europe/Brussels';
    $rule = app(Conditions::class)->createConditionRule([
        'class' => DateCreatedConditionRule::class,
        'rangeType' => 'range',
        'startDate' => ['date' => '2026-09-08', 'timezone' => 'Europe/Brussels'],
        'endDate' => ['date' => '2026-09-09', 'timezone' => 'Europe/Brussels'],
    ]);
    $rule->condition = new ElementCondition(Entry::class);
    $payload = app(FormResolver::class)->resolve($rule->getForm(), new FormContext);

    expect($payload->values['startDate']['date'])->toBe('2026-09-08');
    expect($rule->getConfig()['startDate'])->toBe('2026-09-08T00:00:00+02:00');
});

it('round trips money input values as decimal condition values', function () {
    $rule = app(Conditions::class)->createConditionRule([
        'class' => FormMoneyConditionRule::class,
        'fieldUid' => 'a767fd6f-b381-4a60-913f-22a8f7349da8',
        'operator' => 'between',
        'value' => ['value' => '12,50', 'locale' => 'nl-BE', 'currency' => 'EUR'],
        'maxValue' => ['value' => '20,75', 'locale' => 'nl-BE', 'currency' => 'EUR'],
    ]);

    expect($rule->getConfig()['value'])->toBe('12.50');
    expect($rule->getConfig()['maxValue'])->toBe('20.75');
    $payload = app(FormResolver::class)->resolve($rule->getForm(), new FormContext);

    expect((float) $payload->values['value'])->toBe(12.5);
    expect((float) $payload->values['maxValue'])->toBe(20.75);
});

it('preserves the country and custom administrative areas', function () {
    $rule = new AdministrativeAreaConditionRule([
        'countryCode' => 'US',
        'values' => ['CA', 'Custom region'],
    ]);
    $payload = app(FormResolver::class)->resolve($rule->getForm(), new FormContext);

    expect($payload->values['countryCode'])->toBe('US');
    expect($payload->values['values'])->toBe(['CA', 'Custom region']);
    $regions = array_find($payload->nodes, fn ($node) => $node->control?->path === ['values']);

    expect($regions->control->props['requireOptionMatch'] ?? false)->toBeFalse();
});

it('clears an option membership field through its empty sentinel', function () {
    $rule = app(Conditions::class)->createConditionRule([
        'class' => StatusConditionRule::class,
        'values' => '',
    ]);
    expect($rule->getValues())->toBe([]);
});

it('keeps disabled related elements selected when rendering a condition', function () {
    $entry = CraftCms\Cms\Entry\Models\Entry::factory()->title('Disabled entry')->create();
    $entry->element->update(['enabled' => false]);
    $rule = new RelatedToConditionRule;
    $rule->condition = new ElementCondition(Entry::class);
    $rule->setElementIds([$entry->id]);
    $payload = app(FormResolver::class)->resolve($rule->getForm(), new FormContext);
    $selector = array_find($payload->nodes, fn ($node) => $node->control?->component === 'craft:element-select');

    expect(array_column($selector->control->props['elements'], 'id'))->toBe([$entry->id]);
});

it('accepts empty date control values', function (?string $date) {
    $rule = app(Conditions::class)->createConditionRule([
        'class' => DateCreatedConditionRule::class,
        'rangeType' => 'range',
        'startDate' => ['date' => $date, 'timezone' => 'Europe/Brussels'],
        'endDate' => ['date' => $date, 'timezone' => 'Europe/Brussels'],
    ]);

    expect($rule->getConfig()['startDate'])->toBeNull();
    expect($rule->getConfig()['endDate'])->toBeNull();
})->with(['empty string' => '', 'normalized null' => null]);

it('includes saved authors in the element selector', function () {
    $author = User::factory()->create();
    $rule = new EntryCondition(Entry::class)->createConditionRule(AuthorConditionRule::class);
    $rule->setElementIds([$author->id]);

    $payload = app(FormResolver::class)->resolve($rule->getForm(), new FormContext);
    $selector = array_find($payload->nodes, fn ($node) => $node->control?->component === 'craft:element-select');

    expect(array_column($selector->control->props['elements'], 'id'))->toBe([$author->id]);
});

it('provides the current text or number values to the form', function (string $class, string $operator, array $values) {
    $rule = new ElementCondition(Entry::class)->createConditionRule([
        'class' => $class,
        'operator' => $operator,
        ...$values,
    ]);

    $payload = app(FormResolver::class)->resolve($rule->getForm(), new FormContext);

    expect($payload->values)->toMatchArray(['operator' => $operator, ...$values]);
})->with([
    'text' => [TitleConditionRule::class, '=', ['value' => 'Hello']],
    'number range' => [IdConditionRule::class, 'between', ['value' => '10', 'maxValue' => '20']],
]);

it('omits text values for empty operators', function (string $operator) {
    $rule = new ElementCondition(Entry::class)->createConditionRule([
        'class' => TitleConditionRule::class,
        'operator' => $operator,
    ]);

    $payload = app(FormResolver::class)->resolve($rule->getForm(), new FormContext);

    expect($payload->values)->not->toHaveKey('value');
})->with(['empty', 'notempty']);
