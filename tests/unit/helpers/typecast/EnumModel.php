<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers\typecast;

/**
 * Unit tests for the Admin Table Helper class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class EnumModel
{
    public Suit $suit;

    public Suit $anotherSuit;

    public ?Suit $nullableSuit;
}
