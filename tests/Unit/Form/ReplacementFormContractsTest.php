<?php

declare(strict_types=1);

use CraftCms\Cms\Component\Contracts\ConfigurableComponentInterface;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\FieldContext;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Field\Number;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\FieldLayoutElement;
use CraftCms\Cms\FieldLayout\FieldLayoutElementContext;
use CraftCms\Cms\FieldLayout\FieldLayoutForm;
use CraftCms\Cms\Http\ViewModels\FieldEditViewModel;
use CraftCms\Cms\Plugin\Plugin;
use CraftCms\Cms\Ui\Contracts\Control;
use CraftCms\Cms\Ui\Contracts\Node;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;

it('declares the replacement Form operations on their public contracts', function () {
    $settingsForm = new ReflectionMethod(ConfigurableComponentInterface::class, 'settingsUi');
    $formControl = new ReflectionMethod(FieldInterface::class, 'formControl');
    $formNode = new ReflectionMethod(FieldLayoutElement::class, 'formNode');

    expect($settingsForm->getParameters()[0]->getType()->getName())->toBe(UiContext::class)
        ->and((string) $settingsForm->getReturnType())->toBe('?'.Ui::class)
        ->and($formControl->getParameters()[0]->getType()->getName())->toBe(FieldContext::class)
        ->and((string) $formControl->getReturnType())->toBe(Control::class)
        ->and($formNode->getParameters()[0]->getType()->getName())->toBe(FieldLayoutElementContext::class)
        ->and((string) $formNode->getReturnType())->toBe('?'.Node::class);
});

it('does not expose the replaced HTML rendering contracts', function () {
    expect(method_exists(ConfigurableComponentInterface::class, 'getSettingsHtml'))->toBeFalse()
        ->and(method_exists(Plugin::class, 'settingsHtml'))->toBeFalse()
        ->and(method_exists(FieldInterface::class, 'getInputHtml'))->toBeFalse()
        ->and(method_exists(FieldLayoutElement::class, 'formHtml'))->toBeFalse()
        ->and(method_exists(FieldLayout::class, 'createForm'))->toBeFalse()
        ->and(class_exists(FieldLayoutForm::class))->toBeFalse();
});

it('uses a non-PlainText field settings Form through the public contract', function () {
    $field = new class extends Number
    {
        public function settingsUi(UiContext $context = new UiContext): Ui
        {
            return Ui::make([
                Field::make()->control(CraftCms\Cms\Ui\Controls\Number::make('decimals')),
            ]);
        }
    };
    $viewModel = new FieldEditViewModel($field, app(Fields::class));

    expect($viewModel->settingsForm()?->nodes)->toHaveCount(1);
});
