<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\FieldLayout;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\FieldLayout\FieldLayout as CoreFieldLayout;
use InvalidArgumentException;

class FieldLayout extends CoreFieldLayout
{
    /** @param array<string, mixed> $config */
    public function createForm(?ElementInterface $element = null, bool $static = false, array $config = []): FieldLayoutForm
    {
        $namespace = $config['namespace'] ?? null;
        unset($config['namespace']);

        if ($config !== []) {
            throw new InvalidArgumentException(sprintf(
                'Legacy FieldLayout form configuration [%s] is incompatible with Form rendering.',
                implode(', ', array_keys($config)),
            ));
        }

        return FieldLayoutForm::fromLayout($this, $element, $static, $namespace ?? []);
    }
}
