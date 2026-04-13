<?php

declare(strict_types=1);

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Cp\Html\ElementIndexHtml;
use CraftCms\Cms\Element\Conditions\ElementCondition;
use CraftCms\Cms\Element\Conditions\StatusConditionRule;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Http\Controllers\Elements\ElementSelectorModalController;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Auth;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());

    $this->elementIndexHtmlState = new stdClass;

    app()->instance(ElementIndexHtml::class, new readonly class($this->elementIndexHtmlState) extends ElementIndexHtml
    {
        public function __construct(
            private stdClass $state,
        ) {}

        public function html(string $elementType, array $config = []): string
        {
            $this->state->elementType = $elementType;
            $this->state->config = $config;

            return '<div class="element-index">Modal body</div>';
        }
    });
});

it('requires authentication', function () {
    Auth::logout();

    postJson(action(ElementSelectorModalController::class), [
        'elementType' => Entry::class,
    ])->assertUnauthorized();
});

it('validates missing required payload', function () {
    postJson(action(ElementSelectorModalController::class), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['elementType']);
});

it('validates invalid request payloads', function (array $payload, array $errors) {
    postJson(action(ElementSelectorModalController::class), $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors($errors);
})->with([
    'invalid element type' => [[
        'elementType' => ElementSelectorModalController::class,
    ], ['elementType']],
    'invalid show site menu' => [[
        'elementType' => Entry::class,
        'showSiteMenu' => 'auto',
    ], ['showSiteMenu']],
    'invalid sources type' => [[
        'elementType' => Entry::class,
        'sources' => 'not-an-array',
    ], ['sources']],
    'invalid source item type' => [[
        'elementType' => Entry::class,
        'sources' => [123],
    ], ['sources.0']],
    'invalid condition type' => [[
        'elementType' => Entry::class,
        'condition' => 123,
    ], ['condition']],
    'missing condition class' => [[
        'elementType' => Entry::class,
        'condition' => [],
    ], ['condition']],
    'invalid reference element ids' => [[
        'elementType' => Entry::class,
        'referenceElementId' => 'invalid',
        'referenceElementOwnerId' => 'invalid',
        'referenceElementSiteId' => 'invalid',
    ], ['referenceElementId', 'referenceElementOwnerId', 'referenceElementSiteId']],
]);

it('renders modal HTML with the expected config', function () {
    $response = postJson(action(ElementSelectorModalController::class), [
        'elementType' => Entry::class,
        'context' => ElementSources::CONTEXT_MODAL,
        'showSiteMenu' => '1',
        'sources' => ['*', 'singles'],
    ])
        ->assertOk()
        ->assertExactJson([
            'html' => '<div class="element-index">Modal body</div>',
        ]);

    expect($response->json('html'))->toBe('<div class="element-index">Modal body</div>')
        ->and($this->elementIndexHtmlState->elementType)->toBe(Entry::class)
        ->and($this->elementIndexHtmlState->config)->toMatchArray([
            'class' => 'content',
            'context' => ElementSources::CONTEXT_MODAL,
            'registerJs' => false,
            'showSiteMenu' => '1',
            'showStatusMenu' => true,
            'sources' => ['*', 'singles'],
        ])
        ->and(array_keys($this->elementIndexHtmlState->config['statuses']))->toBe(array_keys(Entry::statuses()));
});

it('passes the provided context through to the element index html', function () {
    postJson(action(ElementSelectorModalController::class), [
        'elementType' => Entry::class,
        'context' => ElementSources::CONTEXT_INDEX,
    ])->assertOk();

    expect($this->elementIndexHtmlState->config['context'])->toBe(ElementSources::CONTEXT_INDEX);
});

it('uses auto for show site menu when it is omitted', function () {
    postJson(action(ElementSelectorModalController::class), [
        'elementType' => Entry::class,
    ])->assertOk();

    expect($this->elementIndexHtmlState->config['showSiteMenu'])->toBe('auto');
});

it('passes null statuses and disables the status menu for element types without statuses', function () {
    postJson(action(ElementSelectorModalController::class), [
        'elementType' => Address::class,
    ])->assertOk();

    expect($this->elementIndexHtmlState->elementType)->toBe(Address::class)
        ->and($this->elementIndexHtmlState->config['showStatusMenu'])->toBeFalse()
        ->and($this->elementIndexHtmlState->config['statuses'])->toBeNull();
});

it('filters statuses using an in status condition rule', function () {
    postJson(action(ElementSelectorModalController::class), [
        'elementType' => Entry::class,
        'condition' => [
            'class' => ElementCondition::class,
            'elementType' => Entry::class,
            'conditionRules' => [[
                'class' => StatusConditionRule::class,
                'operator' => 'in',
                'values' => ['live'],
            ]],
        ],
    ])->assertOk();

    expect($this->elementIndexHtmlState->config['statuses']->keys()->all())->toBe(['live']);
});

it('filters statuses using an excluding status condition rule', function () {
    postJson(action(ElementSelectorModalController::class), [
        'elementType' => Entry::class,
        'condition' => [
            'class' => ElementCondition::class,
            'elementType' => Entry::class,
            'conditionRules' => [[
                'class' => StatusConditionRule::class,
                'operator' => 'not in',
                'values' => ['pending'],
            ]],
        ],
    ])->assertOk();

    expect($this->elementIndexHtmlState->config['statuses']->keys()->all())
        ->toBe(array_values(array_diff(array_keys(Entry::statuses()), ['pending'])));
});

it('leaves statuses unchanged when the condition has no status rule', function () {
    postJson(action(ElementSelectorModalController::class), [
        'elementType' => Entry::class,
        'condition' => [
            'class' => ElementCondition::class,
            'elementType' => Entry::class,
        ],
    ])->assertOk();

    expect(array_keys($this->elementIndexHtmlState->config['statuses']))->toBe(array_keys(Entry::statuses()));
});
