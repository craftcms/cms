<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Field\Concerns;

use CraftCms\Cms\Field\BaseRelationField;

use function CraftCms\Cms\template;

/**
 * @internal
 * @phpstan-require-extends BaseRelationField
 */
trait LegacyRelationFieldInput
{
    /**
     * @var string Template to render the editable input with. Without one, the field renders the core element select.
     */
    protected string $inputTemplate;

    /**
     * @param array<string, mixed> $variables
     */
    protected function elementSelectHtml(array $variables): string
    {
        if (!isset($this->inputTemplate)) {
            return parent::elementSelectHtml($variables);
        }

        return template($this->inputTemplate, $variables);
    }
}
