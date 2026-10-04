<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Contracts\NestedElementInterface;
use CraftCms\Cms\Element\Data\UserInitiatedElementSaveResult;
use CraftCms\Cms\Element\Enums\ElementActivityType;
use CraftCms\Cms\Element\Exceptions\InvalidElementException;
use CraftCms\Cms\Element\Exceptions\UnsupportedSiteException;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\User\Contracts\CraftUser;
use CraftCms\Cms\Workflow\Workflows;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;

/**
 * @since 6.0.0
 */
readonly class UserInitiatedElementSave
{
    public function __construct(
        private Drafts $drafts,
        private ElementActivity $elementActivity,
        private Elements $elements,
        private Workflows $workflows,
    ) {}

    /**
     * The caller must authorize the element before applying user input and authorize the mutated state before calling this method.
     */
    public function save(
        ElementInterface $element,
        CraftUser $actor,
        bool $crossSiteValidate = false,
    ): UserInitiatedElementSaveResult {
        if ($element->enabled && $element->getEnabledForSite()) {
            $element->ruleset->useScenario(ElementRules::SCENARIO_LIVE);
        }

        $isNotNew = (bool) $element->id;
        $requiresApproval = $this->workflows->requiresApproval($element);
        $mutex = null;

        if ($isNotNew) {
            $mutex = Cache::lock("element:{$element->id}", 15);

            if (! $mutex->get()) {
                throw new LockTimeoutException("Could not acquire a lock to save the element: {$element->id}.");
            }
        }

        if ($element instanceof NestedElementInterface && property_exists($element, 'updateSearchIndexForOwner')) {
            $element->updateSearchIndexForOwner = true;
        }

        try {
            if ($requiresApproval && $isNotNew) {
                $element = $this->drafts->createDraft($element, $actor->getCraftUserId());
                $successful = true;
            } elseif ($requiresApproval) {
                $successful = $this->drafts->saveElementAsDraft($element, $actor->getCraftUserId());
            } else {
                $successful = $this->elements->saveElement(
                    $element,
                    crossSiteValidate: $crossSiteValidate,
                );
            }
        } catch (InvalidElementException $exception) {
            $element = $exception->element;
            $successful = false;
        } catch (UnsupportedSiteException $exception) {
            $element->errors()->add('siteId', $exception->getMessage());
            $successful = false;
        } finally {
            $mutex?->release();
        }

        if (! $successful) {
            return new UserInitiatedElementSaveResult($element, false);
        }

        $this->elementActivity->trackActivity($element, ElementActivityType::Save, $actor->asElement());

        if (! $requiresApproval) {
            $provisional = $element::find()
                ->provisionalDrafts()
                ->draftOf($element->id)
                ->draftCreator($actor->getCraftUserId())
                ->siteId($element->siteId)
                ->status(null)
                ->one();

            if ($provisional) {
                $this->elements->deleteElement($provisional, true);
            }
        }

        return new UserInitiatedElementSaveResult($element, true);
    }
}
