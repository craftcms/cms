<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry\Validation;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Shared\Enums\Color;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Validation\Rules\HandleRule;
use CraftCms\Cms\Validation\Ruleset;
use Illuminate\Validation\Rule;

use function CraftCms\Cms\t;

/** @extends Ruleset<\CraftCms\Cms\Entry\Data\EntryType> */
final class EntryTypeRules extends Ruleset
{
    #[\Override]
    public function defineRules(): array
    {
        $rules = [
            'id' => ['nullable', 'integer'],
            'color' => [
                'nullable',
                Rule::enum(Color::class),
            ],
            'fieldLayoutId' => ['nullable', 'integer', function (string $attribute, int $value, $fail) {
                $fieldLayout = Fields::assembleLayoutFromPost();
                $fieldLayout->reservedFieldHandles = [
                    'author',
                    'authorId',
                    'authorIds',
                    'authors',
                    'section',
                    'sectionId',
                    'type',
                    'postDate',
                ];

                if (! $fieldLayout->validate()) {
                    $fail(t('The field layout is invalid.'));
                }
            }],
            'name' => ['required', 'string', 'max:255'],
            'handle' => [
                'required',
                'string',
                'max:255',
                new HandleRule(['id', 'dateCreated', 'dateUpdated', 'uid', 'title']),
            ],
        ];

        if ($this->component->validateHandleUniqueness) {
            $rules['handle'][] = Rule::unique(Table::ENTRYTYPES, 'handle')->ignore($this->component->id)->withoutTrashed('dateDeleted');
        }

        return $rules;
    }
}
