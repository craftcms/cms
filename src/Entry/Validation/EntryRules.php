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
 * @property Entry $subject
 */
class EntryRules extends ElementRules
{
    /** @return array<string, array<int, string|Closure|object>> */
    #[Override]
    public function rules(): array
    {
        $section = $this->subject->getSection();

        $rules = parent::rules();

        $rules['sectionId'] = ['nullable', 'integer', 'required_without:fieldId'];
        $rules['fieldId'] = ['nullable', 'integer', function (string $attribute, mixed $value, Closure $fail) {
            if ($this->subject->sectionId && $this->subject->fieldId) {
                $fail(t('`sectionId` and `fieldId` cannot both be set on an entry.'));
            }
        }];
        $rules['ownerId'] = ['nullable', 'integer'];
        $rules['primaryOwnerId'] = ['nullable', 'integer'];
        $rules['sortOrder'] = ['nullable', 'integer'];
        $rules['placeInStructure'] = ['bool'];
        $rules['postDate'] = ['nullable', 'date', Rule::when($this->inScenarios(self::SCENARIO_LIVE) && ! is_null($this->subject->expiryDate), ['before:expiryDate'])];
        $rules['expiryDate'] = ['nullable', 'date'];
        $rules['typeId'] = [
            'required',
            'integer',
            Rule::when($this->inScenarios(self::SCENARIO_DEFAULT, self::SCENARIO_LIVE), [
                function (string $attribute, int $value, Closure $fail) {
                    $typeId = $this->subject->getType()->id;

                    if (array_any($this->subject->getAvailableEntryTypes(), fn ($entryType) => $entryType->id === $typeId)) {
                        return;
                    }

                    $fail(t('{attribute} is invalid.', [
                        'attribute' => $this->subject->getAttributeLabel($attribute),
                    ]));
                },
            ]),
            Rule::when($this->inScenarios(self::SCENARIO_LIVE), [
                function (string $attribute, int $value, Closure $fail) {
                    if (! $this->subject->getIsCanonical()) {
                        return;
                    }

                    if ($this->subject->isEntryTypeAllowed()) {
                        return;
                    }

                    if (isset($this->subject->sectionId)) {
                        $fail(t('{type} entries are no longer allowed in this section. Please choose a different entry type.', [
                            'type' => $this->subject->getType()->getUiLabel(),
                        ]));

                        return;
                    }

                    $fail(t('{type} entries are no longer allowed in this field. Please choose a different entry type.', [
                        'type' => $this->subject->getType()->getUiLabel(),
                    ]));
                },
            ]),
        ];
        $rules['authorIds'] = [
            'nullable',
            'array',
            Rule::when(function () {
                if (! $this->inScenarios(self::SCENARIO_DEFAULT, self::SCENARIO_LIVE)) {
                    return false;
                }

                $section = $this->subject->getSection();

                return
                    $section &&
                    $section->type !== SectionType::Single &&
                    isset($section->maxAuthors) &&
                    $section->maxAuthors !== 0;
            }, [
                function (string $attribute, array $value, Closure $fail) {
                    $section = $this->subject->getSection();

                    if (is_null($section)) {
                        return;
                    }

                    if (count($value) > $section->maxAuthors) {
                        $fail(t('{num, plural, =1{Only one author is} other{Up to {num, number} authors are}} allowed.', [
                            'num' => $section->maxAuthors,
                        ]));
                    }

                    $authors = $this->subject->getAuthors();
                    if ($this->subject->getOldAuthorIds() !== null) {
                        foreach ($authors as $author) {
                            if (
                                ! in_array($author->id, $this->subject->getOldAuthorIds()) &&
                                ! $author->can('author', $this->subject)
                            ) {
                                $fail(t('This user doesn’t have permission to author entries in this section.'));
                                break;
                            }
                        }
                    }
                },
            ]),
        ];

        if ($this->inScenarios(self::SCENARIO_LIVE) &&
            $section &&
            $section->type !== SectionType::Single
        ) {
            if ($section->minAuthors > 0) {
                $rules['authorIds'] = array_merge($rules['authorIds'], [
                    'required',
                ]);
            }

            $rules['authorIds'] = array_merge($rules['authorIds'], [
                'min:'.$section->minAuthors,
            ]);

            if (isset($section->maxAuthors) && $section->maxAuthors !== 0) {
                $rules['authorIds'] = array_merge($rules['authorIds'], [
                    'max:'.$section->maxAuthors,
                ]);
            }
        }

        $rules['authorIds.*'] = ['nullable', 'integer'];

        return $rules;
    }
}
