<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Field;

use CraftCms\Cms\Field\Field as CoreField;
use CraftCms\Yii2Adapter\Field\Concerns\LegacyFieldControl;
use CraftCms\Yii2Adapter\Form\Concerns\LegacySettingsForm;

abstract class Field extends CoreField
{
    use LegacyFieldControl;
    use LegacySettingsForm;
}
