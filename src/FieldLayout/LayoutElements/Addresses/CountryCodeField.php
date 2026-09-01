<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\LayoutElements\Addresses;

use CommerceGuys\Addressing\Country\Country;
use CraftCms\Cms\Address\Addresses;
use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\FieldLayout\FieldLayoutElementContext;
use CraftCms\Cms\FieldLayout\LayoutElements\BaseNativeField;
use CraftCms\Cms\Form\Contracts\Control;
use CraftCms\Cms\Form\Controls\Choice;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Html;
use InvalidArgumentException;
use Override;

use function CraftCms\Cms\currentUser;
use function CraftCms\Cms\t;

class CountryCodeField extends BaseNativeField
{
    #[Override]
    public bool $mandatory = true;

    #[Override]
    public string $attribute = 'countryCode';

    #[Override]
    public bool $required = true;

    public function __construct($config = [])
    {
        unset(
            $config['mandatory'],
            $config['translatable'],
            $config['maxlength'],
            $config['required'],
            $config['autofocus']
        );

        parent::__construct($config);
    }

    #[Override]
    public function fields(): array
    {
        return Arr::except(parent::fields(), [
            'mandatory',
            'translatable',
            'maxlength',
            'required',
            'autofocus',
        ]);
    }

    #[Override]
    public function previewable(): bool
    {
        return true;
    }

    #[Override]
    protected function formControl(FieldLayoutElementContext $context): ?Control
    {
        if (! $context->element instanceof Address) {
            throw new InvalidArgumentException(sprintf('%s can only be used in address field layouts.', self::class));
        }

        $options = collect(app(Addresses::class)->getCountryList(app()->getLocale()))
            ->map(fn (string $label, string $value): array => compact('label', 'value'))
            ->values()
            ->all();

        return Choice::make($this->attribute())
            ->options($options)
            ->value($context->element->countryCode);
    }

    public function defaultLabel(?ElementInterface $element = null, bool $static = false): ?string
    {
        return t('Country');
    }

    protected function inputHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        if (! $element instanceof Address) {
            throw new InvalidArgumentException(sprintf('%s can only be used in address field layouts.', self::class));
        }

        return
            Html::beginTag('div', [
                'class' => ['flex', 'flex-nowrap'],
            ]).
            FormFields::selectizeHtml([
                'id' => 'countryCode',
                'name' => 'countryCode',
                'options' => app(Addresses::class)->getCountryList(app()->getLocale()),
                'value' => $element->countryCode,
                'autocomplete' => $element->getBelongsToCurrentUser() ? 'country' : 'off',
                'disabled' => $static,
            ]).
            Html::tag('div', '', [
                'id' => 'countryCode-spinner',
                'class' => ['spinner', 'hidden'],
            ]).
            Html::endTag('div');
    }

    #[Override]
    public function previewPlaceholderHtml(mixed $value, ?ElementInterface $element): string
    {
        if (! $value) {
            $countries = app(Addresses::class)->getCountryRepository()->getList(app()->getLocale());
            $value = $countries['US'];
        } else {
            if ($value instanceof Country) {
                $value = $value->getName();
            }
        }

        return $value;
    }

    /** @return list<array<string, mixed>> */
    #[Override]
    protected function actionMenuItems(?ElementInterface $element = null, bool $static = false): array
    {
        $items = [];

        if (currentUser()?->isAdmin()) {
            $items[] = $this->copyAttributeAction();
        }

        return $items;
    }
}
