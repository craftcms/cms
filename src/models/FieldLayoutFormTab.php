<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use craft\base\Model;

/**
 * FieldLayoutFormTab model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class FieldLayoutFormTab extends Model
{
    /**
     * @var string The tab’s name.
     */
    public $name;

    /**
     * @var string The tab’s HTML ID.
     */
    public $id;

    /**
     * @var bool Whether the tab has any validation errors.
     */
    public $hasErrors = false;

    /**
     * @var string The tab’s HTML content.
     */
    public $content;
}
