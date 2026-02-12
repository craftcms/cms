<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\enums;

/**
 * ElementIndexViewMode defines the element index view modes supported in core.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
enum ElementIndexViewMode: string
{
    case Cards = 'cards';
    case Structure = 'structure';
    case Table = 'table';
    case Thumbs = 'thumbs';
}
