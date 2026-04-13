<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements;

use Closure;
use CraftCms\Cms\Component\ComponentHelper;
use CraftCms\Cms\Condition\Conditions;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionInterface;
use CraftCms\Cms\Element\Conditions\ElementCondition;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Element\Exceptions\InvalidTypeException;
use Illuminate\Http\Request;

use function CraftCms\Cms\t;

abstract readonly class BaseElementsController
{
    public function __construct(
        protected Elements $elements,
        protected Conditions $conditions,
        protected Request $request,
    ) {}

    /**
     * Returns the posted element type class.
     *
     * @return class-string<ElementInterface>
     */
    protected function elementType(): string
    {
        $this->request->validate([
            'elementType' => ['required', 'string', function (string $attribute, mixed $value, Closure $fail): void {
                if (! ComponentHelper::validateComponentClass($value, ElementInterface::class)) {
                    $fail(new InvalidTypeException((string) $value, ElementInterface::class)->getMessage());
                }
            }],
        ]);

        return $this->request->input('elementType');
    }

    /**
     * Returns the context that this controller is being called in.
     */
    protected function context(): string
    {
        return $this->request->input('context', ElementSources::CONTEXT_INDEX);
    }

    /**
     * Returns the condition that should be applied to the element query.
     */
    protected function condition(): ?ElementConditionInterface
    {
        $this->request->validate([
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
        $conditionConfig = $this->request->input('condition');

        if (! $conditionConfig) {
            return null;
        }

        $condition = $this->conditions->createCondition($conditionConfig);

        if ($condition instanceof ElementCondition) {
            $referenceElementId = $this->request->input('referenceElementId');

            if ($referenceElementId) {
                $ownerId = $this->request->input('referenceElementOwnerId');
                $siteId = $this->request->input('referenceElementSiteId');
                $criteria = [];

                if ($ownerId) {
                    $criteria['ownerId'] = $ownerId;
                }

                $condition->referenceElement = $this->elements->getElementById(
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
}
