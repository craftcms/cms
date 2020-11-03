<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\image;

use enshrined\svgSanitize\data\AllowedAttributes;
use enshrined\svgSanitize\data\AttributeInterface;

/**
 * SvgAllowedAttributes class is used for defining allowed SVG attributes during sanitization.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.4.22.1
 */
class SvgAllowedAttributes implements AttributeInterface
{
    /**
     * @inheritDoc
     */
    public static function getAttributes()
    {
        return array_merge(AllowedAttributes::getAttributes(), ['filterUnits']);
    }
}
