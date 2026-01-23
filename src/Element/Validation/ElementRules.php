<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Validation;

use CraftCms\Cms\Component\Validation\Ruleset;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Validation\Rules\ElementUriRule;
use CraftCms\Cms\Element\Validation\Rules\SlugRule;
use CraftCms\Cms\Shared\Rules\DisallowMb4;
use CraftCms\Cms\Shared\Rules\SiteIdRule;
use CraftCms\Cms\Support\Str;
use Illuminate\Validation\Rule;
use Override;
use Throwable;

/**
 * @template T of Element
 *
 * @extends Ruleset<T>
 */
abstract class ElementRules extends Ruleset
{
    public const string SCENARIO_DEFAULT = 'default';

    public const string SCENARIO_ESSENTIALS = 'essentials';

    public const string SCENARIO_LIVE = 'live';

    public string $scenario = self::SCENARIO_DEFAULT;

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function scenarios(): array
    {
        return [
            self::SCENARIO_DEFAULT => null,
            self::SCENARIO_LIVE => null,
            self::SCENARIO_ESSENTIALS => null,
        ];
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
            'title' => ['string', 'max:255', new DisallowMb4, function ($attribute, $value) {
                if (! is_string($value)) {
                    return;
                }

                $this->component->$attribute = trim(Str::convertLineBreaks($value));
            }],
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

        $language = $this->component->getSite()->language;

        $rules['slug'] = [Rule::when($this->inScenarios(Element::SCENARIO_DEFAULT, Element::SCENARIO_LIVE, Element::SCENARIO_ESSENTIALS), [
            'string',
            'max:255',
            new SlugRule(
                element: $this->component,
                language: $language,
            ),
        ])];

        try {
            $uriFormat = $this->component->getUriFormat() ?? '';

            if ($this->inScenarios(Element::SCENARIO_DEFAULT, Element::SCENARIO_LIVE)
                && preg_match('/\bslug\b/', $uriFormat)
            ) {
                array_unshift($rules['slug'], 'required');
            } else {
                array_unshift($rules['slug'], 'nullable');
            }
        } catch (Throwable) {
            // Validation rules will catch this.
        }

        $rules['uri'] = Rule::when($this->inScenarios(Element::SCENARIO_DEFAULT, Element::SCENARIO_LIVE, Element::SCENARIO_ESSENTIALS), [
            new ElementUriRule($this->component),
        ]);

        return $rules;
    }
}
