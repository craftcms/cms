<?php

declare(strict_types=1);

use craft\fields\Categories;
use craft\fields\Tags;
use craft\models\CategoryGroup;
use craft\models\TagGroup;
use craft\services\Categories as CategoriesService;
use craft\services\Tags as TagsService;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Yii2Adapter\Form\Controls\LegacyHtmlControl;
use CraftCms\Yii2Adapter\Form\LegacyHtml;
use Mockery\MockInterface;

class LegacyRelationElement extends Element
{
    #[\Override]
    public static function displayName(): string
    {
        return 'Legacy relation element';
    }

    #[\Override]
    public static function isLocalized(): bool
    {
        return true;
    }
}

it('keeps persisted Category and Tag fields usable through legacy HTML islands', function(
    string $fieldType,
    string $handle,
    array $settings,
    string $service,
    string $serviceClass,
    Closure $configureService,
) {
    $originalService = Craft::$app->get($service);
    Craft::$app->set($service, Mockery::mock($serviceClass, $configureService));

    try {
        $field = app(Fields::class)->createField([
            'type' => $fieldType,
            'name' => ucfirst($handle),
            'handle' => $handle,
            'settings' => $settings,
        ]);
        $restored = app(Fields::class)->createField([
            'type' => $field::class,
            'name' => $field->name,
            'handle' => $field->handle,
            'settings' => $field->getSettings(),
        ]);
        $element = new LegacyRelationElement();
        $element->id = null;
        $element->siteId = 1;

        $settingsNode = app(LegacyHtml::class)->settings(
            component: $restored,
            path: ['settings'],
            namespace: 'settings',
        );
        $fieldNode = app(LegacyHtml::class)->field(
            field: $restored,
            value: new ElementCollection(),
            element: $element,
            path: ['fields', $handle],
            namespace: 'fields',
        );

        $payload = app(FormResolver::class)->resolve(
            Form::make([$settingsNode, $fieldNode]),
            new FormContext(refreshable: true),
        );
        $html = app(FormHtmlRenderer::class)->render($payload);
        $fieldValue = $payload->values['fields'][$handle];
        assert(is_array($fieldValue));

        expect($restored)->toBeInstanceOf($fieldType)
            ->and($payload->nodes)->toHaveCount(2)
            ->and($payload->nodes[0]->control?->type)->toBe(LegacyHtmlControl::class)
            ->and($payload->nodes[1]->control?->type)->toBe(LegacyHtmlControl::class)
            ->and($payload->nodes[1]->control?->props['fragment']['html'])->toContain("name=\"fields[$handle]\"")
            ->and($payload->nodes[1]->control?->props['fragment']['bodyHtml'])->not->toBeEmpty()
            ->and($html)->toContain("name=\"fields[$handle]\"")
            ->and(app(LegacyHtml::class)->expand($fieldValue))->toBe(['fields' => [$handle => '']])
            ->and(json_encode($payload, JSON_THROW_ON_ERROR))->toBeString();
    } finally {
        Craft::$app->set($service, $originalService);
    }
})->with([
    'Categories' => [
        Categories::class,
        'topics',
        ['source' => 'group:aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa', 'maintainHierarchy' => false, 'showSearchInput' => false],
        'categories',
        CategoriesService::class,
        function(MockInterface $service): void {
            $group = new CategoryGroup([
                'id' => 1,
                'name' => 'Categories',
                'handle' => 'categories',
                'uid' => 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa',
                'structureId' => 1,
            ]);
            $service->shouldReceive('getAllGroups')->andReturn([$group]);
            $service->shouldReceive('getGroupByUid')->with('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa')->andReturn($group);
        },
    ],
    'Tags' => [
        Tags::class,
        'topics',
        ['source' => 'taggroup:bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb'],
        'tags',
        TagsService::class,
        function(MockInterface $service): void {
            $group = new TagGroup([
                'id' => 1,
                'name' => 'Tags',
                'handle' => 'tags',
                'uid' => 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb',
            ]);
            $service->shouldReceive('getAllTagGroups')->andReturn([$group]);
            $service->shouldReceive('getTagGroupByUid')->with('bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb')->andReturn($group);
        },
    ],
]);
