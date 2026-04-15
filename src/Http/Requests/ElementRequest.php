<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Requests;

use Closure;
use CraftCms\Cms\Component\ComponentHelper;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionInterface;
use CraftCms\Cms\Element\Conditions\ElementCondition;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Element\Exceptions\InvalidTypeException;
use CraftCms\Cms\Support\Facades\Conditions;
use CraftCms\Cms\Support\Facades\Elements;
use Illuminate\Foundation\Http\FormRequest;

use function CraftCms\Cms\t;

class ElementRequest extends FormRequest
{
    /**
     * Returns the posted element type class.
     *
     * @return class-string<ElementInterface>
     */
    public function elementType(): string
    {
        $this->validate([
            'elementType' => ['required', 'string', function (string $attribute, mixed $value, Closure $fail): void {
                if (! ComponentHelper::validateComponentClass($value, ElementInterface::class)) {
                    $fail(new InvalidTypeException((string) $value, ElementInterface::class)->getMessage());
                }
            }],
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
}
