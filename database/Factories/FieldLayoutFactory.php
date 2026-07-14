<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Factories;

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Field;
use CraftCms\Cms\Field\Models\Field as FieldModel;
use CraftCms\Cms\FieldLayout\FieldLayout as FieldLayoutConfig;
use CraftCms\Cms\FieldLayout\FieldLayoutElement;
use CraftCms\Cms\FieldLayout\FieldLayoutTab;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
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

    /** @param  FieldLayoutElement[]  $elements */
    public function withContentTab(array $elements = [], string $name = 'Content'): self
    {
        return $this->state(function (array $attributes) use ($elements, $name) {
            $layout = FieldLayoutConfig::create($attributes['type']);
            $layout->tab($name, fn (FieldLayoutTab $tab) => $tab->add(...$elements));

            return ['config' => $layout->getConfig()];
        });
    }

    public function forField(Field|FieldModel $field, bool $required = false): self
    {
        $element = $field instanceof FieldModel
            ? new CustomField(config: ['fieldUid' => $field->uid])
            : CustomField::for($field);

        return $this->withContentTab([$element->required($required)]);
    }
}
