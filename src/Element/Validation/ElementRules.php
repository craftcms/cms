<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Validation;

use Closure;
use Craft;
use craft\base\ElementInterface;
use craft\helpers\ElementHelper;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Component\Validation\Ruleset;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Validation\Rules\ElementUriRule;
use CraftCms\Cms\Shared\Exceptions\OperationAbortedException;
use CraftCms\Cms\Shared\Rules\DisallowMb4;
use CraftCms\Cms\Shared\Rules\SiteIdRule;
use CraftCms\Cms\Support\Str;
use Illuminate\Validation\Rule;
use Override;
use Throwable;

use function CraftCms\Cms\t;

/**
 * @template T of Element
 *
 * @property Element $component
 *
 * @extends Ruleset<T>
 */
abstract class ElementRules extends Ruleset
{
    private ?string $uriPreparationError = null;

    #[Override]
    public function prepareForValidation(?array $attributeNames = null): void
    {
        $shouldPrepare = fn (string $attribute) => is_null($attributeNames) || in_array($attribute, $attributeNames, true);

        if ($shouldPrepare('title')) {
            $this->prepareTitle();
        }

        if ($shouldPrepare('slug') && $this->component->hasUris()) {
            $this->prepareSlug($this->component->getSite()->language);
        }

        if ($shouldPrepare('uri') && $this->component->hasUris()) {
            $this->prepareUri();
        }
    }

    /**
     * Define validation rules in Laravel format.
     *
     * Override this method in subclasses to define rules.
     *
     * @return array<string, array>
     */
    #[Override]
    protected function defineRules(): array
    {
        $int = Rule::when($this->component->inScenarios(Element::SCENARIO_DEFAULT, Element::SCENARIO_LIVE), ['nullable', 'integer']);

        $rules = [
            'id' => $int,
            'parentId' => $int,
            'root' => $int,
            'lft' => $int,
            'rgt' => $int,
            'level' => Rule::when($this->component->inScenarios(Element::SCENARIO_DEFAULT, Element::SCENARIO_LIVE, Element::SCENARIO_ESSENTIALS), ['nullable', 'integer']),
            'title' => ['string', 'max:255', new DisallowMb4],
            'siteId' => Rule::when(
                $this->component->inScenarios(Element::SCENARIO_DEFAULT, Element::SCENARIO_LIVE, Element::SCENARIO_ESSENTIALS),
                [
                    'nullable',
                    new SiteIdRule(allowDisabled: true),
                ],
            ),
            'dateCreated' => ['nullable', 'date'],
            'dateUpdated' => ['nullable', 'date'],
            'isFresh' => ['nullable', 'boolean'],
        ];

        $rules = $this->addTitleRules($rules);

        return $this->addUriRules($rules);
    }

    private function addTitleRules(array $rules): array
    {
        if ($this->component->hasTitles() && $this->component->inScenarios(Element::SCENARIO_DEFAULT, Element::SCENARIO_LIVE)) {
            array_unshift($rules['title'], 'required');
        } else {
            array_unshift($rules['title'], 'nullable');
        }

        return $rules;
    }

    private function addUriRules(array $rules): array
    {
        if (! $this->component->hasUris()) {
            return $rules;
        }

        $rules['slug'] = [Rule::when($this->component->inScenarios(Element::SCENARIO_DEFAULT, Element::SCENARIO_LIVE, Element::SCENARIO_ESSENTIALS), [
            'string',
            'max:255',
        ])];

        try {
            $uriFormat = $this->component->getUriFormat() ?? '';

            if ($this->component->inScenarios(Element::SCENARIO_DEFAULT, Element::SCENARIO_LIVE)
                && preg_match('/\bslug\b/', $uriFormat)
            ) {
                $rules['slug'][] = 'required';
            } else {
                $rules['slug'][] = 'nullable';
            }
        } catch (Throwable) {
            // Validation rules will catch this.
        }

        $rules['uri'] = Rule::when(
            $this->component->inScenarios(Element::SCENARIO_DEFAULT, Element::SCENARIO_LIVE, Element::SCENARIO_ESSENTIALS),
            [
                'bail',
                // Fail if we have an uriPreparationError
                function (?string $attribute, mixed $value, Closure $fail) {
                    if (is_null($this->uriPreparationError)) {
                        return;
                    }

                    $fail($this->uriPreparationError);
                },
                new ElementUriRule,
            ],
        );

        return $rules;
    }

    private function prepareTitle(): void
    {
        $title = $this->component->title;

        if (! is_string($title)) {
            return;
        }

        $this->component->title = trim(Str::convertLineBreaks($title));
    }

    private function prepareSlug(string $language): void
    {
        $slug = (string) $this->component->slug;
        $isTemp = ElementHelper::isTempSlug($slug);

        $element = $this->component;
        $isDraft = $element instanceof ElementInterface && $element->getIsDraft();

        if ($isDraft && ! in_array($element->getScenario(), [Element::SCENARIO_LIVE, 'default'], true)) {
            if ($isTemp) {
                return;
            }

            if ($slug === '') {
                $this->setSlugOnElement($element, ElementHelper::tempSlug());

                return;
            }
        }

        $limitToAscii = $this->limitAutoSlugsToAscii ?? Cms::config()->limitAutoSlugsToAscii;

        if (($slug === '' || $isTemp) && $element !== null) {
            $sourceValue = $element->title ?? '';
            $slug = ElementHelper::generateSlug($sourceValue, $limitToAscii, $language);
        } else {
            $slug = ElementHelper::normalizeSlug($slug);
        }

        if ($slug !== '') {
            $this->setSlugOnElement($element, $slug);
        }
    }

    private function setSlugOnElement(?ElementInterface $element, string $slug): void
    {
        if ($element !== null) {
            $element->slug = $slug;
        }
    }

    private function prepareUri(): void
    {
        if ($this->component->getIsRevision()) {
            return;
        }

        if ($this->component->getIsDraft() && ! $this->component->getIsUnpublishedDraft()) {
            if (! $this->component->inScenarios(Element::SCENARIO_LIVE)) {
                return;
            }

            $canonical = $this->component->getCanonical();

            if (
                $canonical !== $this->component &&
                $this->component->uri === $canonical->uri &&
                $canonical->enabled &&
                $canonical->getEnabledForSite()
            ) {
                return;
            }
        }

        try {
            Craft::$app->getElements()->setElementUri($this->component);
        } catch (OperationAbortedException) {
            if (
                $this->component->enabled &&
                $this->component->getEnabledForSite() &&
                (! $this->component->getIsUnpublishedDraft() || $this->component->inScenarios(Element::SCENARIO_LIVE))
            ) {
                $this->uriPreparationError = t('Could not generate a unique URI based on the URI format.');
            }
        }
    }
}
