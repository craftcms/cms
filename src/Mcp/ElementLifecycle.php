<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Contracts\NestedElementInterface;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\Exceptions\InvalidElementException;
use CraftCms\Cms\Element\Validation\ElementRules;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Mcp\Exception\ToolCallException;

/**
 * @since 6.0.0
 */
readonly class ElementLifecycle
{
    public function __construct(
        private Elements $elements,
        private McpActor $actor,
    ) {}

    /**
     * @template T of ElementInterface
     *
     * @param  class-string<T>  $type
     * @return T
     */
    public function find(
        string $type,
        ?int $id = null,
        ?string $uid = null,
        ?int $siteId = null,
        bool $includeTrashed = false,
        string $ability = 'view',
    ): ElementInterface {
        if (($id === null) === ($uid === null)) {
            throw new ToolCallException('Provide exactly one of: id, uid.');
        }

        $criteria = [
            'trashed' => $includeTrashed ? null : false,
            'provisionalDrafts' => false,
        ];

        $element = $id !== null
            ? $this->elements->getElementById($id, $type, $siteId, $criteria)
            : $this->elements->getElementByUid($uid, $type, $siteId, $criteria);

        if (! $element || $element->getIsRevision() || ! Gate::forUser($this->actor->user())->allows($ability, $element)) {
            throw new ToolCallException($type::displayName().' not found.');
        }

        return $element;
    }

    /** @return array{restored: bool} */
    public function restore(ElementInterface $element): array
    {
        if (! $element->trashed) {
            return ['restored' => false];
        }

        if (! $this->elements->restoreElement($element)) {
            throw new ToolCallException(implode("\n", $element->errors()->all()) ?: 'Element could not be restored.');
        }

        return ['restored' => true];
    }

    /** @return array{valid: bool, scenario: string, errors: array<string, list<string>>} */
    public function validate(ElementInterface $element): array
    {
        $element->ruleset->useScenario(ElementRules::SCENARIO_LIVE);
        $valid = $element->validate();

        return [
            'valid' => $valid,
            'scenario' => ElementRules::SCENARIO_LIVE,
            'errors' => $element->errors()->getMessages(),
        ];
    }

    public function duplicate(ElementInterface $element, bool $asUnpublishedDraft = false): ElementInterface
    {
        $ability = $asUnpublishedDraft ? 'duplicateAsDraft' : 'duplicate';

        if (($asUnpublishedDraft && ! $element::hasDrafts()) || ! Gate::forUser($this->actor->user())->allows($ability, $element)) {
            throw new ToolCallException('You are not authorized to duplicate this element.');
        }

        $attributes = [
            'isProvisionalDraft' => false,
            'draftId' => null,
        ];

        if ($asUnpublishedDraft && $element->getIsCanonical()) {
            $attributes['slug'] = null;
        }

        if ($element instanceof NestedElementInterface) {
            $attributes += [
                'primaryOwnerId' => $element->getOwnerId(),
                'ownerId' => $element->getOwnerId(),
                'sortOrder' => null,
            ];
        }

        try {
            $duplicate = DB::transaction(
                fn (): ElementInterface => $this->elements->duplicateElement($element, $attributes, asUnpublishedDraft: $asUnpublishedDraft),
            );
        } catch (InvalidElementException $exception) {
            throw new ToolCallException(
                implode("\n", $exception->element->errors()->all()) ?: 'Element could not be duplicated.',
                previous: $exception,
            );
        }

        return $this->elements->getElementById($duplicate->id, $duplicate::class, $duplicate->siteId)
            ?? throw new ToolCallException('Duplicated element could not be loaded.');
    }
}
