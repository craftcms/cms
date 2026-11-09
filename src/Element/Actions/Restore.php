<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Actions;

use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Support\Facades\Elements;
use Illuminate\Support\Facades\Gate;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class Restore extends ElementAction
{
    public ?string $successMessage = null;

    public ?string $partialSuccessMessage = null;

    public ?string $failMessage = null;

    public bool $restorableElementsOnly = false;

    #[\Override]
    public function setElementType(string $elementType): void
    {
        parent::setElementType($elementType);

        $this->successMessage ??= t('{type} restored.', [
            'type' => $elementType::pluralDisplayName(),
        ]);

        $this->partialSuccessMessage ??= t('Some {type} restored.', [
            'type' => $elementType::pluralLowerDisplayName(),
        ]);

        $this->failMessage ??= t('{type} not restored.', [
            'type' => $elementType::pluralDisplayName(),
        ]);
    }

    #[\Override]
    public function getTriggerLabel(): string
    {
        return t('Restore');
    }

    #[\Override]
    public function performAction(ElementQueryInterface $query): bool
    {
        $anySuccess = false;
        $anyFail = false;

        foreach ($query->cursor() as $element) {
            if (! Gate::check('save', $element)) {
                continue;
            }

            if (Elements::restoreElement($element)) {
                $anySuccess = true;
            } else {
                $anyFail = true;
            }
        }

        if (! $anySuccess && $anyFail) {
            $this->setMessage($this->failMessage);

            return false;
        }

        if ($anyFail) {
            $this->setMessage($this->partialSuccessMessage);
        } else {
            $this->setMessage($this->successMessage);
        }

        return true;
    }
}
