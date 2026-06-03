<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Policies;

use BadMethodCallException;
use CraftCms\Cms\Auth\Events\ElementAuthorizing;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Contracts\NestedElementInterface;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Field\Contracts\ElementContainerFieldInterface;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Contracts\CraftUser;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Gate;
use ReflectionMethod;

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

    private const array ELEMENT_AUTHORIZATION_METHODS = [
        'view' => 'canView',
        'save' => 'canSave',
        'delete' => 'canDelete',
        'duplicate' => 'canDuplicate',
        'copy' => 'canCopy',
        'createDrafts' => 'canCreateDrafts',
        'deleteForSite' => 'canDeleteForSite',
        'duplicateAsDraft' => 'canDuplicateAsDraft',
    ];

    /**
     * Runs before all ability checks.
     * Returns true/false to short-circuit, or null to continue.
     */
    public function before(CraftUser $user, string $ability, mixed $element): ?bool
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

        event($event = new ElementAuthorizing($user, $element, $ability));

        return $event->authorized;
    }

    public function saveCanonical(CraftUser $user, ElementInterface $element): bool
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
            [$user, $element] = $arguments + [null, null];

            if ($user instanceof CraftUser && $element instanceof ElementInterface && $this->hasCustomElementAuthorizationMethod($element, $method)) {
                return $element->{self::ELEMENT_AUTHORIZATION_METHODS[$method]}($user->asElement());
            }

            return false;
        }

        throw new BadMethodCallException("Method {$method} does not exist.");
    }

    private function hasCustomElementAuthorizationMethod(ElementInterface $element, string $ability): bool
    {
        $method = self::ELEMENT_AUTHORIZATION_METHODS[$ability] ?? null;

        if (! $method || ! method_exists($element, $method)) {
            return false;
        }

        return new ReflectionMethod($element, $method)
            ->getDeclaringClass()
            ->getName() !== Element::class;
    }

    private function checkSiteAuthorization(CraftUser $user, ElementInterface $element): ?bool
    {
        if (! $siteId = $element->siteId) {
            return null;
        }

        if (! $site = Sites::getSiteById($siteId)) {
            return false;
        }

        return $user->can("editSite:$site->uid");
    }

    private function checkNestedElementAuthorization(
        CraftUser $user,
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

        $userElement = $user->asElement();

        return match ($ability) {
            'view' => $field->canViewElement($element, $userElement),
            'save' => $this->checkNestedSaveAuthorization($element, $userElement, $field),
            'delete' => $field->canDeleteElement($element, $userElement),
            'duplicate', 'duplicateAsDraft' => $field->canDuplicateElement($element, $userElement),
            'deleteForSite' => $field->canDeleteElementForSite($element, $userElement),
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
