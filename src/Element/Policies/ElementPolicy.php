<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Policies;

use BadMethodCallException;
use craft\base\ElementInterface;
use craft\base\NestedElementInterface;
use CraftCms\Cms\Auth\Events\AuthorizingElement;
use CraftCms\Cms\Field\Contracts\ElementContainerFieldInterface;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Gate;

class ElementPolicy
{
    private const array ABILITIES = [
        'view',
        'save',
        'saveCanonical',
        'delete',
        'duplicate',
        'copy',
        'createDrafts',
        'deleteForSite',
        'duplicateAsDraft',
    ];

    /**
     * Runs before all ability checks.
     * Returns true/false to short-circuit, or null to continue.
     */
    public function before(User $user, string $ability, mixed $element): ?bool
    {
        if (! $element instanceof ElementInterface) {
            return null;
        }

        // Site authorization (for view and save)
        if (in_array($ability, ['view', 'save'], true)
            && $this->checkSiteAuthorization($user, $element) === false
        ) {
            return false;
        }

        // Nested element delegation
        $nestedResult = $this->checkNestedElementAuthorization($user, $ability, $element);
        if ($nestedResult !== null) {
            return $nestedResult;
        }

        event($event = new AuthorizingElement($user, $element, $ability));

        return $event->authorized;
    }

    public function saveCanonical(User $user, ElementInterface $element): bool
    {
        if ($element->getIsUnpublishedDraft()) {
            $fakeCanonical = clone $element;
            $fakeCanonical->draftId = null;

            return Gate::forUser($user)->check('save', $fakeCanonical);
        }

        return Gate::forUser($user)->check('save', $element->getCanonical(true));
    }

    /**
     * Default deny for all abilities not explicitly defined.
     *
     * @param  array<mixed>  $arguments
     */
    public function __call(string $method, array $arguments): bool
    {
        if (in_array($method, self::ABILITIES, true)) {
            return false;
        }

        throw new BadMethodCallException("Method {$method} does not exist.");
    }

    private function checkSiteAuthorization(User $user, ElementInterface $element): ?bool
    {
        if (! $siteId = $element->siteId) {
            return null;
        }

        if (! $site = Site::find($siteId)) {
            return false;
        }

        return $user->can("editSite:$site->uid");
    }

    private function checkNestedElementAuthorization(
        User $user,
        string $ability,
        ElementInterface $element,
    ): ?bool {
        if (! $element instanceof NestedElementInterface) {
            return null;
        }

        $field = $element->getField();
        if (! $field instanceof ElementContainerFieldInterface) {
            return null;
        }

        return match ($ability) {
            'view' => $field->canViewElement($element, $user),
            'save' => $this->checkNestedSaveAuthorization($element, $user, $field),
            'delete' => $field->canDeleteElement($element, $user),
            'duplicate' => $field->canDuplicateElement($element, $user),
            'deleteForSite' => $field->canDeleteElementForSite($element, $user),
            default => null,
        };
    }

    private function checkNestedSaveAuthorization(
        NestedElementInterface $element,
        User $user,
        ElementContainerFieldInterface $field,
    ): ?bool {
        if (! $authorized = $field->canSaveElement($element, $user)) {
            return $authorized;
        }

        if (! isset($field->layoutElement)) {
            return true;
        }

        if (! $owner = $element->getOwner()) {
            return false;
        }

        return $field->layoutElement->editable($owner);
    }
}
