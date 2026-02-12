<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\enums;

/**
 * PropagationMethod defines all possible site propagation methods for element values.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
enum PropagationMethod: string
{
    case None = 'none';
    case SiteGroup = 'siteGroup';
    case Language = 'language';
    case Custom = 'custom';
    case All = 'all';
}
