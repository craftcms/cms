<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use craft\base\Model;

/**
 * Stores the info for an update release.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class UpdateRelease extends Model
{
    // Properties
    // =========================================================================

    /**
     * @var string Version
     */
    public $version;

    /**
     * @var \DateTime|null Date
     */
    public $date;

    /**
     * @var bool Critical
     */
    public $critical = false;

    /**
     * @var string|null Notes
     */
    public $notes;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function datetimeAttributes(): array
    {
        $attributes = parent::datetimeAttributes();
        $attributes[] = 'date';
        return $attributes;
    }
}
