<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry\Validation;

use Closure;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Section\Enums\SectionType;
use Illuminate\Validation\Rule;
use Override;

use function CraftCms\Cms\t;

/**
 * @extends ElementRules<Entry>
 *
 * @property Entry $component
 */
final class EntryRules extends ElementRules
{
    #[Override]
    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules['sectionId'] = ['nullable', 'integer', 'required_without:fieldId'];
        $rules['fieldId'] = ['nullable', 'integer', function (string $attribute, mixed $value, Closure $fail) {
            if ($this->component->sectionId && $this->component->fieldId) {
                $fail(t('`sectionId` and `fieldId` cannot both be set on an entry.'));
            }
        }];
        $rules['ownerId'] = ['nullable', 'integer'];
        $rules['primaryOwnerId'] = ['nullable', 'integer'];
        $rules['sortOrder'] = ['nullable', 'integer'];
        $rules['placeInStructure'] = ['bool'];
        $rules['postDate'] = ['nullable', 'date', Rule::when($this->component->inScenarios(Entry::SCENARIO_LIVE) && ! is_null($this->component->expiryDate), ['before:expiryDate'])];
        $rules['expiryDate'] = ['nullable', 'date'];
        $rules['typeId'] = [
            'required',
            'integer',
            function (string $attribute, int $value, Closure $fail) {
                $typeId = $this->component->getType()->id;

                if (array_any($this->component->getAvailableEntryTypes(), fn ($entryType) => $entryType->id === $typeId)) {
                    return;
                }

                $fail(t('{attribute} is invalid.', [
                    'attribute' => $this->component->getAttributeLabel($attribute),
                ]));
            },
            Rule::when($this->component->inScenarios(Entry::SCENARIO_LIVE), [
                function (string $attribute, int $value, Closure $fail) {
                    if (! $this->component->getIsCanonical()) {
                        return;
                    }

                    if ($this->component->isEntryTypeAllowed()) {
                        return;
                    }

                    $fail(t('{type} entries are no longer allowed in this section. Please choose a different entry type.', [
                        'type' => $this->component->getType()->getUiLabel(),
                    ]));
                },
            ]),
        ];
        $rules['authorIds'] = [
            'nullable',
            'array',
            Rule::when(function () {
                $section = $this->component->getSection();

                return
                    $section &&
                    $section->type !== SectionType::Single &&
                    isset($section->maxAuthors) &&
                    $section->maxAuthors !== 0;
            }, [
                function (string $attribute, array $value, Closure $fail) {
                    $section = $this->component->getSection();

                    if (is_null($section)) {
                        return;
                    }

                    if (count($value) > $section->maxAuthors) {
                        $fail(t('{num, plural, =1{Only one author is} other{Up to {num, number} authors are}} allowed.', [
                            'num' => $section->maxAuthors,
                        ]));
                    }

                    $authors = $this->component->getAuthors();
                    if ($this->component->getOldAuthorIds() !== null) {
                        foreach ($authors as $author) {
                            if (
                                ! in_array($author->id, $this->component->getOldAuthorIds()) &&
                                ! $author->can(sprintf('viewEntries:%s', $this->component->getSection()->uid))
                            ) {
                                $fail(t('This user doesn’t have permission to author entries in this section.'));
                                break;
                            }
                        }
                    }
                },
            ]),
            Rule::requiredIf(function () {
                if (! $this->component->inScenarios(Entry::SCENARIO_LIVE)) {
                    return false;
                }

                $section = $this->component->getSection();

                if (! $section) {
                    return false;
                }

                if ($section->type === SectionType::Single) {
                    return false;
                }

                if ($section->maxAuthors === 0) {
                    return false;
                }

                return true;
            }),
        ];
        $rules['authorIds.*'] = ['integer'];

        return $rules;
    }
}
