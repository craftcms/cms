<?php

declare(strict_types=1);

namespace craft\models;

/**
 * FieldLayoutForm model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\FieldLayout\FieldLayoutForm} instead.
 */
class FieldLayoutForm extends \CraftCms\Cms\FieldLayout\FieldLayoutForm
{
    use \craft\base\LegacyEventConstants;
}
