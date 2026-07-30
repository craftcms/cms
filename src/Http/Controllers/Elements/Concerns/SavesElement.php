<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements\Concerns;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\Http\Requests\ElementRequest;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Contracts\CraftUser;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Gate;

trait SavesElement
{
    protected readonly ElementRequest $request;

    protected function canSave(ElementInterface $element, CraftUser $user): bool
    {
        if ($element->getIsRevision()) {
            return false;
        }

        if ($element->isProvisionalDraft) {
            $element = $element->getCanonical(true);
        }

        return $user->can('save', $element);
    }

    protected function applyParamsToElement(ElementInterface $element): void
    {
        $fieldsLocation = $this->request->input('fieldsLocation', 'fields');
        $applyParams = $this->request->boolean('applyParams', true) || ! $this->request->isMethod('POST');

        if (! $applyParams) {
            return;
        }

        $enabled = $this->request->boolean('setEnabled', true) ? true : null;

        if (! is_null($enabledForSite = $this->request->input('enabledForSite'))) {
            if (is_array($enabledForSite)) {
                // Make sure they are allowed to edit all of the posted site IDs
                $editableSiteIds = Sites::getEditableSiteIds()->all();

                if (array_diff(array_keys($enabledForSite), $editableSiteIds)) {
                    abort(403, 'User not authorized to edit element statuses for all the submitted site IDs.');
                }

                // Set the global status to true if it's enabled for *any* sites, or if already enabled.
                $element->enabled = in_array(true, $enabledForSite) || $element->enabled;
            }

            $element->setEnabledForSite($enabledForSite);
        } elseif (isset($enabled)) {
            $element->enabled = $enabled;
        }

        if ($this->request->boolean('fresh')) {
            $element->setIsFresh();

            if ($element->getIsUnpublishedDraft()) {
                $element->propagateAll = true;
            }
        }

        if ($element->getIsDraft()) {
            /** @var ElementInterface $element */
            if ($this->request->has('draftName')) {
                $element->draftName = $this->request->input('draftName');
            }
            if ($this->request->has('notes')) {
                $element->draftNotes = $this->request->input('notes');
            }
        } elseif ($this->request->has('notes')) {
            $element->setRevisionNotes($this->request->input('notes'));
        }

        if ($this->request->has('updateSearchIndexImmediately')) {
            $element->updateSearchIndexImmediately = $this->request->boolean('updateSearchIndexImmediately');
        }

        $element->ruleset->withScenario(
            ElementRules::SCENARIO_LIVE,
            function () use ($element) {
                $values = $this->request->validated() + array_filter(['fieldId' => $this->request->input('fieldId')]);

                if ($element instanceof User) {
                    $values = Arr::except($values, $this->sensitiveAttributes($element));
                }

                $element->setAttributesFromRequest($values);

                if ($this->request->has('slug')) {
                    $element->slug = $this->request->input('slug');
                }
            },
        );

        // Now that the element is fully configured, make sure the user can actually view it
        Gate::authorize('view', $element);

        // Set the custom field values
        $element->setFieldValuesFromRequest($fieldsLocation);
    }

    /** @return list<string> */
    private function sensitiveAttributes(User $element): array
    {
        if ($element->getIsDraft()) {
            return array_diff(User::SENSITIVE_ATTRIBUTES, ['email']);
        }

        return User::SENSITIVE_ATTRIBUTES;
    }
}
