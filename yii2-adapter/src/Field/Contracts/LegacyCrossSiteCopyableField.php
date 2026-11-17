<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Field\Contracts;

/**
 * Stands in for `craft\base\CrossSiteCopyableFieldInterface`, which Craft 6 no longer uses, so field types that still
 * implement it keep loading. Copying a field's value from another site is done from the Compare screen now, for any
 * field.
 *
 * @deprecated in 6.0.0
 */
interface LegacyCrossSiteCopyableField
{
}
