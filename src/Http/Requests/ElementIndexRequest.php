<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Requests;

use Closure;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionInterface;
use CraftCms\Cms\Element\Conditions\ElementCondition;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Element\Validation\Rules\ElementTypeRule;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Support\Facades\Conditions;
use CraftCms\Cms\Support\Facades\Elements;
use Illuminate\Foundation\Http\FormRequest;

use function CraftCms\Cms\t;

class ElementIndexRequest extends FormRequest
{
    /**
     * Returns the posted element type class.
     *
     * @return class-string<ElementInterface>
     */
    public function elementType(): string
    {
        $this->validate([
            'elementType' => ['required', 'string', new ElementTypeRule],
        ]);

        return $this->input('elementType');
    }

    /**
     * Returns the context that this controller is being called in.
     */
    public function context(): string
    {
        return $this->input('context', ElementSources::CONTEXT_INDEX);
    }

    public function isAdministrative(?string $context = null): bool
    {
        return in_array($context ?? $this->context(), [ElementSources::CONTEXT_INDEX, ElementSources::CONTEXT_EMBEDDED_INDEX]);
    }

    /**
     * Returns the condition that should be applied to the element query.
     */
    public function condition(): ?ElementConditionInterface
    {
        $this->validate([
            'condition' => ['nullable', function (string $attribute, mixed $value, Closure $fail): void {
                if (! is_array($value) && ! is_string($value)) {
                    $fail(t('The {attribute} field must be a string or array.', ['attribute' => $attribute]));

                    return;
                }

                if (is_array($value)) {
                    $class = $value['class'] ?? null;

                    if (! is_string($class) || trim($class) === '') {
                        $fail(t('The {attribute} field must contain a `class` value.', ['attribute' => $attribute]));
                    }
                }
            }],
            'referenceElementId' => ['nullable', 'integer'],
            'referenceElementOwnerId' => ['nullable', 'integer'],
            'referenceElementSiteId' => ['nullable', 'integer'],
        ]);

        /** @var array{class:class-string<ElementConditionInterface>}|null $conditionConfig */
        $conditionConfig = $this->input('condition');

        if (! $conditionConfig) {
            return null;
        }

        $condition = Conditions::createCondition($conditionConfig);

        if ($condition instanceof ElementCondition) {
            $referenceElementId = $this->input('referenceElementId');

            if ($referenceElementId) {
                $ownerId = $this->input('referenceElementOwnerId');
                $siteId = $this->input('referenceElementSiteId');
                $criteria = [];

                if ($ownerId) {
                    $criteria['ownerId'] = $ownerId;
                }

                $condition->referenceElement = Elements::getElementById(
                    (int) $referenceElementId,
                    siteId: $siteId,
                    criteria: $criteria,
                );
            }
        }

        if (! $condition instanceof ElementConditionInterface) {
            return null;
        }

        return $condition;
    }

    /**
     * Returns the posted view state, normalized to always carry a mode.
     */
    /** @return array<string, mixed> */
    public function viewState(): array
    {
        $viewState = $this->input('viewState', []);

        if (! is_array($viewState)) {
            $viewState = [];
        }

        if (empty($viewState['mode'])) {
            $viewState['mode'] = 'table';
        }

        return $viewState;
    }

    /**
     * Returns the posted field layouts, hydrated from their configs.
     *
     * @return FieldLayout[]|null
     */
    public function fieldLayouts(): ?array
    {
        $fieldLayouts = $this->input('fieldLayouts');

        if (empty($fieldLayouts) || ! is_array($fieldLayouts)) {
            return null;
        }

        return array_map(FieldLayout::createFromConfig(...), $fieldLayouts);
    }

    /**
     * Returns the posted return URL, with `?` replaced by the `:QS:` token
     * (see https://github.com/craftcms/cms/issues/18923).
     */
    public function returnUrl(): ?string
    {
        $returnUrl = $this->input('returnUrl');

        return $returnUrl ? str_replace('?', ':QS:', $returnUrl) : null;
    }

    /** @return array<string, mixed> */
    public function criteria(): array
    {
        return $this->array('criteria');
    }

    /** @return array<string, mixed> */
    public function baseCriteria(): array
    {
        return $this->array('baseCriteria');
    }

    /** @return list<int> */
    public function collapsedElementIds(): array
    {
        return $this->array('collapsedElementIds');
    }

    /**
     * Returns the filter condition config, posted either as a `filterConfig`
     * array or as a `filters` query string.
     */
    /** @return array<string, mixed>|null */
    public function filterConditionConfig(): ?array
    {
        $config = $this->input('filterConfig');

        if (! $config && ($serialized = $this->input('filters'))) {
            parse_str((string) $serialized, $parsed);
            $config = $parsed['condition'] ?? null;
        }

        return is_array($config) ? $config : null;
    }
}
