<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Validation\Rules;

use Closure;
use craft\base\ElementInterface;
use craft\helpers\ElementHelper;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Element;
use Illuminate\Contracts\Validation\ValidationRule;

use function CraftCms\Cms\t;

/**
 * Validates and normalizes an element's slug.
 */
final readonly class SlugRule implements ValidationRule
{
    public function __construct(
        private ?ElementInterface $element = null,
        private ?string $sourceAttribute = 'title',
        private ?bool $limitAutoSlugsToAscii = null,
        private ?string $language = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $slug = $originalSlug = (string) $value;
        $isTemp = ElementHelper::isTempSlug($slug);

        $element = $this->element;
        $isDraft = $element instanceof ElementInterface && $element->getIsDraft();

        if ($isDraft && ! in_array($element->getScenario(), [Element::SCENARIO_LIVE, 'default'], true)) {
            if ($isTemp) {
                return;
            }

            if ($slug === '') {
                $this->setSlugOnElement($element, $attribute, ElementHelper::tempSlug());

                return;
            }
        }

        $limitToAscii = $this->limitAutoSlugsToAscii ?? Cms::config()->limitAutoSlugsToAscii;

        if (($slug === '' || $isTemp) && $this->sourceAttribute !== null && $element !== null) {
            $sourceValue = $element->{$this->sourceAttribute} ?? '';
            $slug = ElementHelper::generateSlug((string) $sourceValue, $limitToAscii, $this->language);
        } else {
            $slug = ElementHelper::normalizeSlug($slug);
        }

        if ($slug !== '') {
            $this->setSlugOnElement($element, $attribute, $slug);
        } elseif (! $isTemp) {
            if ($originalSlug !== '') {
                $fail(t('{attribute} is invalid.'));
            }
        }
    }

    private function setSlugOnElement(?ElementInterface $element, string $attribute, string $slug): void
    {
        if ($element !== null && property_exists($element, $attribute)) {
            $element->{$attribute} = $slug;
        }
    }
}
