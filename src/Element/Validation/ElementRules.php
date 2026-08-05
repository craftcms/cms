<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Validation;

use Closure;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\Validation\Rules\ElementUriRule;
use CraftCms\Cms\Shared\Exceptions\OperationAbortedException;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Validation\Rules\DisallowMb4;
use CraftCms\Cms\Validation\Rules\SiteIdRule;
use CraftCms\Cms\Validation\Ruleset;
use Illuminate\Validation\Rule;
use Override;
use RuntimeException;
use Throwable;

use function CraftCms\Cms\t;

/**
 * @template T of Element
 *
 * @property Element $subject
 *
 * @extends Ruleset<T>
 */
class ElementRules extends Ruleset
{
    public const string SCENARIO_DEFAULT = 'default';

    public const string SCENARIO_ESSENTIALS = 'essentials';

    public const string SCENARIO_LIVE = 'live';

    private ?string $uriPreparationError = null;

    #[Override]
    public function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $shouldPrepare = fn (string $attribute) => is_null($this->validationAttributes) || in_array($attribute, $this->validationAttributes, true);

        if ($shouldPrepare('title')) {
            $this->prepareTitle();
        }

        if ($shouldPrepare('slug') && $this->subject->hasUris()) {
            $this->prepareSlug($this->subject->getSite()->language);
        }

        if ($shouldPrepare('uri') && $this->subject->hasUris()) {
            $this->prepareUri();
        }
    }

    /**
     * Define validation rules in Laravel format.
     *
     * Override this method in subclasses to define rules.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $int = Rule::when($this->inScenarios(self::SCENARIO_DEFAULT, self::SCENARIO_LIVE), ['nullable', 'integer']);

        $rules = [
            'id' => $int,
            'parentId' => $int,
            'root' => $int,
            'lft' => $int,
            'rgt' => $int,
            'level' => Rule::when($this->inScenarios(self::SCENARIO_DEFAULT, self::SCENARIO_LIVE, self::SCENARIO_ESSENTIALS), ['nullable', 'integer']),
            'title' => ['string', 'max:255', new DisallowMb4],
            'siteId' => Rule::when(
                $this->inScenarios(self::SCENARIO_DEFAULT, self::SCENARIO_LIVE, self::SCENARIO_ESSENTIALS),
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

    /**
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    private function addTitleRules(array $rules): array
    {
        try {
            if ($this->inScenarios(self::SCENARIO_DEFAULT, self::SCENARIO_LIVE) && $this->subject->shouldValidateTitle()) {
                array_unshift($rules['title'], 'required');
            } else {
                array_unshift($rules['title'], 'nullable');
            }
        } catch (RuntimeException) {
            // Related to sectionId, fieldId and ownerId being missing
            // Which will be caught by the other validation rules.
        }

        return $rules;
    }

    /**
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    private function addUriRules(array $rules): array
    {
        if (! $this->subject->hasUris()) {
            return $rules;
        }

        $rules['slug'] = [Rule::when($this->inScenarios(self::SCENARIO_DEFAULT, self::SCENARIO_LIVE, self::SCENARIO_ESSENTIALS), [
            'max:255',
        ])];

        try {
            $uriFormat = $this->subject->getUriFormat() ?? '';

            if ($this->inScenarios(self::SCENARIO_DEFAULT, self::SCENARIO_LIVE)
                && preg_match('/\bslug\b/', $uriFormat)
            ) {
                array_unshift($rules['slug'], 'required', 'string');
            } else {
                array_unshift($rules['slug'], 'nullable');
            }
        } catch (Throwable) {
            // Validation rules will catch this.
        }

        $rules['uri'] = Rule::when(
            $this->inScenarios(self::SCENARIO_DEFAULT, self::SCENARIO_LIVE, self::SCENARIO_ESSENTIALS),
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
        $title = $this->subject->title;

        if (! is_string($title)) {
            return;
        }

        $this->subject->title = trim(Str::convertLineBreaks($title));
    }

    private function prepareSlug(string $language): void
    {
        $slug = (string) $this->subject->slug;
        $isTemp = ElementHelper::isTempSlug($slug);

        $element = $this->subject;
        $isDraft = $element instanceof ElementInterface && $element->getIsDraft();

        if ($isDraft && ! in_array($element->ruleset->getScenario(), [self::SCENARIO_LIVE, self::SCENARIO_DEFAULT], true)) {
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
        if ($this->subject->getIsRevision()) {
            return;
        }

        if ($this->subject->getIsDraft() && ! $this->subject->getIsUnpublishedDraft()) {
            if (! $this->inScenarios(self::SCENARIO_LIVE)) {
                return;
            }

            $canonical = $this->subject->getCanonical();

            if (
                $canonical !== $this->subject &&
                $this->subject->uri === $canonical->uri &&
                $canonical->enabled &&
                $canonical->getEnabledForSite()
            ) {
                return;
            }
        }

        try {
            Elements::setElementUri($this->subject);
        } catch (OperationAbortedException) {
            if (
                $this->subject->enabled &&
                $this->subject->getEnabledForSite() &&
                (! $this->subject->getIsUnpublishedDraft() || $this->inScenarios(self::SCENARIO_LIVE))
            ) {
                $this->uriPreparationError = t('Could not generate a unique URI based on the URI format.');
            }
        }
    }
}
