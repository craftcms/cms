<?php

namespace craft\models;

use craft\base\LegacyEventConstants;
use CraftCms\Cms\Element\Enums\PropagationMethod;
use CraftCms\Cms\Section\Enums\DefaultPlacement;
use CraftCms\Cms\Section\Enums\SectionType;

/**
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Section\Data\Section} instead.
 */
class Section extends \CraftCms\Cms\Section\Data\Section
{
    use LegacyEventConstants;

    public const TYPE_SINGLE = SectionType::Single->value;
    public const TYPE_CHANNEL = SectionType::Channel->value;
    public const TYPE_STRUCTURE = SectionType::Structure->value;

    public const PROPAGATION_METHOD_NONE = PropagationMethod::None->value;
    public const PROPAGATION_METHOD_SITE_GROUP = PropagationMethod::SiteGroup->value;
    public const PROPAGATION_METHOD_LANGUAGE = PropagationMethod::Language->value;
    public const PROPAGATION_METHOD_ALL = PropagationMethod::All->value;
    /** @since 3.5.0 */
    public const PROPAGATION_METHOD_CUSTOM = PropagationMethod::Custom->value;

    /** @since 3.7.0 */
    public const DEFAULT_PLACEMENT_BEGINNING = DefaultPlacement::Beginning->value;
    /** @since 3.7.0 */
    public const DEFAULT_PLACEMENT_END = DefaultPlacement::End->value;
}
