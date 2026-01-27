<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Validation;

use craft\base\ElementInterface;
use craft\helpers\ElementHelper;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Component\Validation\Ruleset;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Validation\Rules\ElementUriRule;
use CraftCms\Cms\Shared\Rules\DisallowMb4;
use CraftCms\Cms\Shared\Rules\SiteIdRule;
use CraftCms\Cms\Support\Str;
use Illuminate\Validation\Rule;
use Override;
use Throwable;

/**
 * @template T of Element
 *
 * @property Element $component
 *
 * @extends Ruleset<T>
 */
abstract class ElementRules extends Ruleset
{
    public string $scenario = Element::SCENARIO_DEFAULT;

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function scenarios(): array
    {
        return [
            Element::SCENARIO_DEFAULT => null,
            Element::SCENARIO_LIVE => null,
            Element::SCENARIO_ESSENTIALS => null,
        ];
    }

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
        $int = Rule::when($this->inScenarios(Element::SCENARIO_DEFAULT, Element::SCENARIO_LIVE), ['nullable', 'integer']);

        $rules = [
            'id' => $int,
            'parentId' => $int,
            'root' => $int,
            'lft' => $int,
            'rgt' => $int,
            'level' => Rule::when($this->inScenarios(Element::SCENARIO_DEFAULT, Element::SCENARIO_LIVE, Element::SCENARIO_ESSENTIALS), ['nullable', 'integer']),
            'title' => ['string', 'max:255', new DisallowMb4],
            'siteId' => Rule::when(
                $this->inScenarios(Element::SCENARIO_DEFAULT, Element::SCENARIO_LIVE, Element::SCENARIO_ESSENTIALS),
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
        if ($this->component->hasTitles() && $this->inScenarios(Element::SCENARIO_DEFAULT, Element::SCENARIO_LIVE)) {
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

        $rules['slug'] = [Rule::when($this->inScenarios(Element::SCENARIO_DEFAULT, Element::SCENARIO_LIVE, Element::SCENARIO_ESSENTIALS), [
            'string',
            'max:255',
        ])];

        try {
            $uriFormat = $this->component->getUriFormat() ?? '';

            if ($this->inScenarios(Element::SCENARIO_DEFAULT, Element::SCENARIO_LIVE)
                && preg_match('/\bslug\b/', $uriFormat)
            ) {
                $rules['slug'][] = 'required';
            } else {
                $rules['slug'][] = 'nullable';
            }
        } catch (Throwable) {
            // Validation rules will catch this.
        }

        $rules['uri'] = Rule::when($this->inScenarios(Element::SCENARIO_DEFAULT, Element::SCENARIO_LIVE, Element::SCENARIO_ESSENTIALS), [
            new ElementUriRule($this->component),
        ]);

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
}
