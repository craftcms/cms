<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\LayoutElements;

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\FieldLayout\FieldLayoutElementContext;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Ui\Contracts\Node;
use CraftCms\Cms\Ui\Controls\Text;
use CraftCms\Cms\Ui\Enums\ControlMode;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Nodes\Group;
use CraftCms\Cms\Ui\UiContext;
use InvalidArgumentException;
use Override;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
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
    public function uiNode(FieldLayoutElementContext $context): ?Node
    {
        $element = $context->element;

        if (
            ! $element ||
            ! Cms::config()->showFirstAndLastNameFields ||
            count(array_intersect($element->safeAttributes(), ['firstName', 'lastName'])) !== 2
        ) {
            return parent::uiNode($context);
        }

        if (! $this->uid) {
            throw new InvalidArgumentException('Persisted Full Name FieldLayout elements require stable UIDs.');
        }

        // Both halves inherit the Full Name field’s change-tracking status.
        $static = $context->mode !== ControlMode::Editable || $context->ui->mode !== ControlMode::Editable;
        $status = $this->showStatus() ? $this->statusClass($element, $static) : null;
        $statusLabel = $status !== null
            ? ($this->statusLabel($element, $static) ?? ucfirst($status))
            : null;

        return Group::make($this->uid, [
            Field::make(t('First Name'), Text::make('firstName')
                ->value($element->firstName ?? null)
                ->autocomplete($this->nameBelongsToCurrentUser($element) ? 'given-name' : false))
                ->required(! $static && $this->required)
                ->showStatus($this->showStatus())
                ->status($status, $statusLabel),
            Field::make(t('Last Name'), Text::make('lastName')
                ->value($element->lastName ?? null)
                ->autocomplete($this->nameBelongsToCurrentUser($element) ? 'family-name' : false))
                ->required(! $static && $this->required)
                ->showStatus($this->showStatus())
                ->status($status, $statusLabel),
        ]);
    }

    /** @return array<string, string|array<string, string>> */
    #[Override]
    protected function inputAttributes(?ElementInterface $element = null, bool $static = false): array
    {
        return [
            'autocomplete' => $element && $this->nameBelongsToCurrentUser($element) ? 'name' : 'off',
        ];
    }

    /**
     * Returns whether the name is the current user’s own, so its inputs get `autocomplete` tokens.
     */
    protected function nameBelongsToCurrentUser(ElementInterface $element): bool
    {
        return $element instanceof Address && $element->getBelongsToCurrentUser();
    }

    #[Override]
    protected function settingsNodes(UiContext $context): array
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

    #[Override]
    protected function uiIcon(): ?string
    {
        return 'id-card';
    }
}
