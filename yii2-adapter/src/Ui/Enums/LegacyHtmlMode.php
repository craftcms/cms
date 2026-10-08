<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Ui\Enums;

use CraftCms\Cms\Ui\Enums\ControlMode;

enum LegacyHtmlMode
{
    case Editable;
    case Static;
    case ReadOnly;
    case Disabled;

    public function controlMode(): ControlMode
    {
        return match ($this) {
            self::Editable => ControlMode::Editable,
            self::Static, self::ReadOnly => ControlMode::ReadOnly,
            self::Disabled => ControlMode::Disabled,
        };
    }
}
