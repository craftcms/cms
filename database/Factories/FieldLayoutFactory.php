<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Factories;

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Field;
use CraftCms\Cms\Field\Models\Field as FieldModel;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

class FieldLayoutFactory extends Factory
{
    #[\Override]
    protected $model = FieldLayout::class;

    public function definition(): array
    {
        return [
            'type' => Entry::class,
        ];
    }

    public function withContentTab(array $elements = [], string $name = 'Content'): self
    {
        return $this->state(fn () => [
            'config' => [
                'tabs' => [
                    [
                        'uid' => Str::uuid()->toString(),
                        'name' => $name,
                        'elements' => $elements,
                    ],
                ],
            ],
        ]);
    }

    public function forField(Field|FieldModel $field, bool $required = false): self
    {
        return $this->withContentTab([
            [
                'uid' => Str::uuid()->toString(),
                'type' => CustomField::class,
                'fieldUid' => $field->uid,
                'required' => $required,
            ],
        ]);
    }
}
