<?php

declare(strict_types=1);

use craft\fields\Matrix as LegacyMatrix;
use CraftCms\Cms\Auth\SessionAuth;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType as EntryTypeModel;
use CraftCms\Cms\Field\FieldContext;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Form\Controls\NestedElementCards;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\User\Models\User;
use CraftCms\Yii2Adapter\Tests\DatabaseTestCase;
use Symfony\Component\DomCrawler\Crawler;

uses(DatabaseTestCase::class);

beforeEach(function() {
    $this->actingAs(User::factory()->admin()->create());
});

it('renders legacy Matrix cards with the nested element cards control in read-only forms', function() {
    $entryType = EntryTypeModel::factory()->create([
        'name' => 'Card',
        'handle' => 'card',
    ]);
    $fieldModel = Field::factory()->create([
        'name' => 'Cards',
        'handle' => 'cards',
        'type' => LegacyMatrix::class,
        'settings' => [
            'entryTypes' => [$entryType->id],
            'viewMode' => LegacyMatrix::VIEW_MODE_CARDS,
        ],
    ]);
    $ownerModel = EntryModel::factory()
        ->withFieldLayout(FieldLayout::factory()->forField($fieldModel))
        ->create();
    /** @var Entry $owner */
    $owner = Entry::find()->id($ownerModel->id)->one();
    $owner->setFieldValue('cards', [
        'new1' => [
            'type' => $entryType->handle,
            'title' => 'Nested card',
        ],
    ]);
    expect(Elements::saveElement($owner))->toBeTrue();
    /** @var Entry $owner */
    $owner = Entry::find()->id($ownerModel->id)->one();
    /** @var LegacyMatrix $field */
    $field = Fields::getFieldByHandle('cards');

    $control = $field->formControl(new FieldContext(
        path: 'cards',
        value: $owner->getFieldValue('cards'),
        element: $owner,
        form: new FormContext(mode: ControlMode::ReadOnly),
        mode: ControlMode::ReadOnly,
    ));
    $html = $field->getStaticHtml($owner->getFieldValue('cards'), $owner);
    $settings = json_decode(
        new Crawler($html)->filter('craft-nested-element-manager')->attr('settings'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect($control)->toBeInstanceOf(NestedElementCards::class)
        ->and($settings)->toMatchArray([
            'sortable' => false,
            'canCreate' => false,
            'canPaste' => false,
        ])->and($html)->toContain('Nested card')
        ->not->toContain('data-delete-action', 'data-duplicate-action')
        ->and(SessionAuth::checkAuthorization("manageNestedElements::{$owner->id}::field:cards"))->toBeFalse()
        ->and(SessionAuth::checkAuthorization("reorderNestedElements::{$owner->id}::field:cards"))->toBeFalse();
});
