<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Validation\Rules;

use Closure;
use Craft;
use craft\base\ElementInterface;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Shared\Exceptions\OperationAbortedException;
use Illuminate\Contracts\Validation\ValidationRule;

use function CraftCms\Cms\t;

/**
 * Validates and sets an element's URI.
 */
final readonly class ElementUriRule implements ValidationRule
{
    private const string URI_PATTERN = '/^\S+$/u';

    public function __construct(
        private ?ElementInterface $element = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($attribute !== 'uri') {
            return;
        }

        $element = $this->element;

        if (! $element instanceof ElementInterface) {
            return;
        }

        if ($element->getIsRevision()) {
            return;
        }

        if ($element->getIsDraft() && ! $element->getIsUnpublishedDraft()) {
            if ($element->getScenario() !== Element::SCENARIO_LIVE) {
                return;
            }

            $canonical = $element->getCanonical();

            if (
                $canonical !== $element &&
                $element->uri === $canonical->uri &&
                $canonical->enabled &&
                $canonical->getEnabledForSite()
            ) {
                return;
            }
        }

        try {
            Craft::$app->getElements()->setElementUri($element);
        } catch (OperationAbortedException) {
            if (
                $element->enabled &&
                $element->getEnabledForSite() &&
                (! $element->getIsUnpublishedDraft() || $element->getScenario() === Element::SCENARIO_LIVE)
            ) {
                $fail(t('Could not generate a unique URI based on the URI format.'));

                return;
            }
        }

        if ($element->uri === null || $element->uri === '') {
            return;
        }

        if (! preg_match(self::URI_PATTERN, $element->uri)) {
            $fail(t('{attribute} is not a valid URI', ['attribute' => $attribute]));
        }
    }
}
