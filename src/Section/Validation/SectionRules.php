<?php

declare(strict_types=1);

namespace CraftCms\Cms\Section\Validation;

use Closure;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Enums\PropagationMethod;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Section\Enums\DefaultPlacement;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Validation\Rules\HandleRule;
use CraftCms\Cms\Validation\Ruleset;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

use function CraftCms\Cms\t;

/** @extends Ruleset<Section> */
class SectionRules extends Ruleset
{
    /** @return array<string, array<int, string|Closure|object>> */
    public function rules(): array
    {
        return [
            'id' => ['nullable', 'integer'],
            'structureId' => ['nullable', 'integer'],
            'maxLevels' => ['nullable', 'integer', 'min:0', 'max:32767'],
            'minAuthors' => ['nullable', 'integer', 'min:0', 'max:32767'],
            'maxAuthors' => ['nullable', 'integer', 'min:0', 'gte:minAuthors', 'max:32767'],
            'name' => ['required', 'string', 'max:255'],
            'handle' => [
                'required',
                'string',
                'max:255',
                new HandleRule(['id', 'dateCreated', 'dateUpdated', 'uid', 'title']),
                Rule::unique(Table::SECTIONS)->ignore($this->subject->id)->withoutTrashed('dateDeleted'),
            ],
            'entryTypes' => ['required'],
            'type' => ['required', Rule::enum(SectionType::class)],
            'defaultPlacement' => ['nullable', Rule::enum(DefaultPlacement::class)],
            'propagationMethod' => ['required', Rule::enum(PropagationMethod::class)],
            'previewTargets' => [
                'nullable',
                'array',
                function (string $attribute, array $value, Closure $fail) {
                    $this->validatePreviewTargets($value, $fail);
                },
            ],
            'siteSettings' => [
                'required',
                'array',
                function (string $attribute, array $value, Closure $fail) {
                    $this->validateSiteSettings($fail);
                },
            ],
        ];
    }

    private function validateSiteSettings(Closure $fail): void
    {
        // If this is an existing section, make sure they aren't moving it to a
        // completely different set of sites in one fell swoop
        if ($this->subject->id) {
            $currentSiteIds = DB::table(Table::SECTIONS_SITES)
                ->where('sectionId', $this->subject->id)
                ->pluck('siteId')
                ->all();

            if (empty(array_intersect($currentSiteIds, array_keys($this->subject->getSiteSettings())))) {
                $fail(t('At least one currently-enabled site must remain enabled.'));
            }
        }

        foreach ($this->subject->getSiteSettings() as $i => $siteSettings) {
            if ($siteSettings->validate()) {
                continue;
            }

            foreach ($siteSettings->errors()->getMessages() as $a => $errors) {
                foreach ($errors as $error) {
                    $this->subject->errors()->add("siteSettings[$i].$a", $error);
                }
            }
        }
    }

    /** @param array<int, array{label: string, urlFormat: string}> $value */
    private function validatePreviewTargets(array $value, Closure $fail): void
    {
        $hasErrors = false;

        foreach ($value as &$target) {
            $target['label'] = trim((string) $target['label']);
            $target['urlFormat'] = trim((string) $target['urlFormat']);

            if ($target['label'] === '') {
                $target['label'] = ['value' => $target['label'], 'hasErrors' => true];
                $hasErrors = true;
            }
        }

        unset($target);

        if ($hasErrors) {
            $fail(t('All targets must have a label.'));
        }
    }
}
