<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry\Validation;

use Closure;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Shared\Enums\Color;
use CraftCms\Cms\Validation\Rules\HandleRule;
use CraftCms\Cms\Validation\Ruleset;
use Illuminate\Validation\Rule;

use function CraftCms\Cms\t;

/**
 * @extends Ruleset<EntryType>
 *
 * @since 6.0.0
 */
class EntryTypeRules extends Ruleset
{
    /** @return array<string, array<int, string|Closure|object>> */
    public function rules(): array
    {
        $rules = [
            'id' => ['nullable', 'integer'],
            'color' => [
                'nullable',
                Rule::enum(Color::class),
            ],
            'fieldLayoutId' => ['nullable', 'integer'],
            'fieldLayout' => [fn (string $attribute, mixed $value, Closure $fail) => $this->validateFieldLayout($value)],
            'name' => ['required', 'string', 'max:255'],
            'handle' => [
                'required',
                'string',
                'max:255',
                new HandleRule(['id', 'dateCreated', 'dateUpdated', 'uid', 'title']),
            ],
        ];

        if ($this->subject->validateHandleUniqueness) {
            $rules['handle'][] = Rule::unique(Table::ENTRYTYPES, 'handle')->ignore($this->subject->id)->withoutTrashed('dateDeleted');
        }

        return $rules;
    }

    private function validateFieldLayout(mixed $fieldLayout): void
    {
        if (! $fieldLayout instanceof FieldLayout) {
            $this->subject->errors()->add('fieldLayout', t('The field layout is invalid.'));

            return;
        }

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
            $this->subject->addModelErrors($fieldLayout, 'fieldLayout');
        }
    }
}
