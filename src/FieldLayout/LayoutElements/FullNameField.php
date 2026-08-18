<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\LayoutElements;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\FieldLayout\FieldLayoutElementContext;
use CraftCms\Cms\Form\Contracts\Node;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Form\Nodes\Group;
use CraftCms\Cms\Support\Arr;
use InvalidArgumentException;
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
    public function formNode(FieldLayoutElementContext $context): ?Node
    {
        $element = $context->element;

        if (
            ! $element ||
            ! Cms::config()->showFirstAndLastNameFields ||
            count(array_intersect($element->safeAttributes(), ['firstName', 'lastName'])) !== 2
        ) {
            return parent::formNode($context);
        }

        if (! $this->uid) {
            throw new InvalidArgumentException('Persisted Full Name FieldLayout elements require stable UIDs.');
        }

        return Group::make($this->uid, [
            Field::make(t('First Name'), Text::make('firstName')->value($element->firstName ?? null))
                ->required($this->required),
            Field::make(t('Last Name'), Text::make('lastName')->value($element->lastName ?? null))
                ->required($this->required),
        ]);
    }

    #[Override]
    protected function settingsNodes(FormContext $context): array
    {
        if (Cms::config()->showFirstAndLastNameFields) {
            // can't know for sure if the element will support firstName and lastName, but probably?
            return [];
        }

        return parent::settingsNodes($context);
    }

    protected function defaultLabel(?ElementInterface $element = null, bool $static = false): ?string
    {
        return t('Full Name');
    }
}
