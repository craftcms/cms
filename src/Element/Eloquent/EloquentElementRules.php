<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Eloquent;

use CraftCms\Cms\Element\Validation\ElementRules;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Override;

/**
 * @extends ElementRules<EloquentElement<Model>>
 *
 * @property EloquentElement<Model> $subject
 *
 * @since 6.0.0
 */
class EloquentElementRules extends ElementRules
{
    #[Override]
    public function rules(): array
    {
        $rules = parent::rules();
        $isLive = $this->inScenarios(self::SCENARIO_DEFAULT, self::SCENARIO_LIVE);

        foreach ($this->subject->modelRules() as $attribute => $attributeRules) {
            $rules[$attribute] = Rule::when($isLive, $attributeRules);
        }

        return $rules;
    }
}
