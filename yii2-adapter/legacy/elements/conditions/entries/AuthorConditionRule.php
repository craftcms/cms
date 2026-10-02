<?php

declare(strict_types=1);

namespace craft\elements\conditions\entries;

use CraftCms\Yii2Adapter\Form\Concerns\LegacyElementSelectConditionRule;

/** @deprecated 6.0.0 Use \CraftCms\Cms\Entry\Conditions\AuthorConditionRule instead. */
class AuthorConditionRule extends \CraftCms\Cms\Entry\Conditions\AuthorConditionRule
{
    use LegacyElementSelectConditionRule;
}
