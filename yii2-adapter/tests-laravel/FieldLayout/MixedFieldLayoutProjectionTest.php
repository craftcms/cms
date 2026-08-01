<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Tests\FieldLayout;

use CraftCms\Cms\Cp\FormDefinitions\Elements\FormElement;
use CraftCms\Cms\Cp\FormDefinitions\Elements\TextInput;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\Field;
use CraftCms\Cms\FieldLayout\Contracts\FieldLayoutFormElementProviderInterface;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\FieldLayoutElement;
use CraftCms\Cms\FieldLayout\FieldLayoutFormDefinitionContext;
use CraftCms\Cms\FieldLayout\FieldLayoutFormDefinitionProjector;
use CraftCms\Cms\FieldLayout\FieldLayoutFormElementContext;
use CraftCms\Cms\FieldLayout\FieldLayoutTab;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Support\Json;
use CraftCms\Yii2Adapter\Tests\TestCase;

class MixedFieldLayoutProjectionTest extends TestCase
{
    public function test_it_projects_a_policy_filtered_modern_and_legacy_layout_in_source_order(): void
    {
        $layout = new FieldLayout(['uid' => 'mixed-layout']);
        $field = new AdapterProjectableField([
            'uid' => 'body-field',
            'handle' => 'body',
            'name' => 'Body',
        ]);
        $layout->setTabs([
            new FieldLayoutTab([
                'uid' => 'content-tab',
                'name' => 'Content',
                'layout' => $layout,
                'elements' => [
                    new AdapterNonEditableCustomField($field, ['uid' => 'body-layout-element']),
                    new AdapterInapplicableLayoutElement(['uid' => 'hidden-layout-element']),
                    new LegacyLayoutElement(['uid' => 'legacy-layout-element']),
                ],
            ]),
            new FieldLayoutTab([
                'uid' => 'metadata-tab',
                'name' => 'Metadata',
                'layout' => $layout,
                'elements' => [new AdapterProjectableLayoutElement(['uid' => 'title-layout-element'])],
            ]),
            new AdapterInapplicableFieldLayoutTab([
                'uid' => 'hidden-tab',
                'name' => 'Hidden',
                'layout' => $layout,
                'elements' => [new AdapterProjectableLayoutElement(['uid' => 'hidden-tab-element'])],
            ]),
        ]);

        $definition = app(FieldLayoutFormDefinitionProjector::class)->project(
            $layout,
            new FieldLayoutFormDefinitionContext(inputNamespace: 'elements[123]'),
        )->toArray();

        $tabs = $definition['elements'][0]['children'];
        $elements = $tabs[0]['children'];

        self::assertSame(['content-tab', 'metadata-tab'], array_column($tabs, 'key'));
        self::assertSame([
            'body-layout-element',
            'legacy-layout-element',
        ], array_column($elements, 'key'));
        self::assertSame('craft:field', $elements[0]['type']);
        self::assertSame('fields.body', $elements[0]['children'][0]['name']);
        self::assertTrue($elements[0]['props']['readOnly']);
        self::assertSame('yii2-adapter:legacy-settings', $elements[1]['type']);
        self::assertSame('title-layout-element', $tabs[1]['children'][0]['key']);
        self::assertStringContainsString(
            'name="elements[123][legacyRating]"',
            $elements[1]['props']['fragment']['html'],
        );
        self::assertStringNotContainsString('hidden-layout-element', Json::encode($definition));
    }
}

class AdapterProjectableField extends Field implements FieldLayoutFormElementProviderInterface
{
    public function formElement(FieldLayoutFormElementContext $context): ?FormElement
    {
        return TextInput::make($context->inputName ?? throw new \LogicException('Input Name is required.'));
    }
}

class AdapterNonEditableCustomField extends CustomField
{
    public function editable(?ElementInterface $element): bool
    {
        return false;
    }
}

class AdapterProjectableLayoutElement extends FieldLayoutElement implements FieldLayoutFormElementProviderInterface
{
    public function selectorHtml(): string
    {
        return '';
    }

    public function formHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        return null;
    }

    public function formElement(FieldLayoutFormElementContext $context): ?FormElement
    {
        return TextInput::make('title');
    }
}

class AdapterInapplicableLayoutElement extends AdapterProjectableLayoutElement
{
    public function showInForm(?ElementInterface $element = null): bool
    {
        return false;
    }
}

class AdapterInapplicableFieldLayoutTab extends FieldLayoutTab
{
    public function showInForm(?ElementInterface $element = null): bool
    {
        return false;
    }
}

class LegacyLayoutElement extends FieldLayoutElement
{
    public function selectorHtml(): string
    {
        return '';
    }

    public function formHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        return '<input name="legacyRating">';
    }
}
