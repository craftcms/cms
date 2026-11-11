<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Actions;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Support\Facades\Elements;
use Illuminate\Support\Facades\Gate;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class DeleteForSite extends ElementAction
{
    /**
     * @var string|null The confirmation message that should be shown before the elements get deleted
     */
    public ?string $confirmationMessage = null;

    /**
     * @var string|null The message that should be shown after the elements get deleted
     */
    public ?string $successMessage = null;

    #[\Override]
    public function getTriggerLabel(): string
    {
        return t('Delete for site');
    }

    #[\Override]
    public static function isDestructive(): bool
    {
        return true;
    }

    public function getConfirmationMessage(): ?string
    {
        return $this->confirmationMessage ?? t('Are you sure you want to delete the selected {type} for this site?', [
            'type' => $this->elementType::pluralLowerDisplayName(),
        ]);
    }

    #[\Override]
    public function performAction(ElementQueryInterface $query): bool
    {
        // Ignore any elements the user doesn’t have permission to delete, or that only exist in this site
        $elements = array_filter(
            $query->all(),
            fn (ElementInterface $element) => (
                Gate::check('view', $element) &&
                Gate::check('deleteForSite', $element) &&
                ElementHelper::isMultiSite($element)
            ),
        );

        Elements::deleteElementsForSite($elements);

        if (isset($this->successMessage)) {
            $this->setMessage($this->successMessage);
        } else {
            $this->setMessage(t('{type} deleted for site.', [
                'type' => $this->elementType::pluralDisplayName(),
            ]));
        }

        return true;
    }
}
