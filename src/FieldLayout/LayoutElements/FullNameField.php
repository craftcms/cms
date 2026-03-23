<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\LayoutElements;

use craft\base\ElementInterface;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Html as HtmlHelper;
use Illuminate\Support\Facades\Auth;
use Override;

use function CraftCms\Cms\t;

class FullNameField extends TextField
{
    #[Override]
    public string $attribute = 'fullName';

    #[Override]
    public bool $requirable = true;

    public function __construct($config = [])
    {
        parent::__construct(Arr::except($config, [
            'mandatory',
            'translatable',
            'maxlength',
            'autofocus',
        ]));
    }

    #[Override]
    public function fields(): array
    {
        return Arr::except(parent::fields(), [
            'mandatory',
            'translatable',
            'maxlength',
            'autofocus',
        ]);
    }

    #[Override]
    public function formHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        if (
            $element &&
            Cms::config()->showFirstAndLastNameFields &&
            count(array_intersect($element->safeAttributes(), ['firstName', 'lastName'])) === 2
        ) {
            return $this->firstAndLastNameFields($element, $static);
        }

        return parent::formHtml($element, $static);
    }

    private function firstAndLastNameFields(?ElementInterface $element, bool $static): string
    {
        $statusClass = $this->statusClass($element);
        $status = $statusClass ? [$statusClass, $this->statusLabel($element, $static) ?? ucfirst($statusClass)] : null;
        $required = ! $static && $this->required;
        $isAdmin = Auth::user()?->isAdmin();

        return HtmlHelper::beginTag('div', ['class' => ['flex', 'flex-nowrap', 'fullwidth']]).
            FormFields::textFieldHtml([
                'id' => 'firstName',
                'status' => $status,
                'fieldClass' => 'flex-grow',
                'label' => t('First Name'),
                'attribute' => 'firstName',
                'showAttribute' => $this->showAttribute(),
                'required' => $required,
                'autocomplete' => false,
                'name' => 'firstName',
                'value' => $element->firstName ?? null,
                'errors' => ! $static ? $element->errors()->get('firstName') : [],
                'disabled' => $static,
                'data' => [
                    'error-key' => 'firstName',
                ],
                'actionMenuItems' => array_filter([
                    $isAdmin ? $this->copyAttributeAction(['attribute' => 'firstName']) : null,
                ]),
            ]).
            FormFields::textFieldHtml([
                'id' => 'lastName',
                'status' => $status,
                'fieldClass' => 'flex-grow',
                'label' => t('Last Name'),
                'attribute' => 'lastName',
                'showAttribute' => $this->showAttribute(),
                'required' => $required,
                'autocomplete' => false,
                'name' => 'lastName',
                'value' => $element->lastName ?? null,
                'errors' => ! $static ? $element->errors()->get('lastName') : [],
                'disabled' => $static,
                'data' => [
                    'error-key' => 'lastName',
                ],
                'actionMenuItems' => array_filter([
                    $isAdmin ? $this->copyAttributeAction(['attribute' => 'lastName']) : null,
                ]),
            ]).
            HtmlHelper::endTag('div');
    }

    #[Override]
    protected function settingsHtml(): ?string
    {
        if (Cms::config()->showFirstAndLastNameFields) {
            // can't know for sure if the element will support firstName and lastName, but probably?
            return null;
        }

        return parent::settingsHtml();
    }

    protected function defaultLabel(?ElementInterface $element = null, bool $static = false): ?string
    {
        return t('Full Name');
    }
}
