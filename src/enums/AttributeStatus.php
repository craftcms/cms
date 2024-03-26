<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\enums;

/**
 * AttributeStatus defines all possible attribute/field statuses for elements.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
enum AttributeStatus: string
{
    case Modified = 'modified';
    case Outdated = 'outdated';
}
