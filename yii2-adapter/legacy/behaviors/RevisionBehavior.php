<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\behaviors;

use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Database\Table;
use Illuminate\Support\Facades\DB;
use function CraftCms\Cms\t;

/**
 * RevisionBehavior is applied to element revisions.
 *
 * @property-read string $revisionLabel The revision label
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.2.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Concerns\Revisionable} instead.
 */
class RevisionBehavior extends BaseRevisionBehavior
{
    /**
     * @var int The revision number
     */
    public int $revisionNum {
        get => $this->owner->revisionNum;
        set(int $value) => $this->owner->revisionNum = $value;
    }

    /**
     * @var string|null The revision notes
     */
    public ?string $revisionNotes {
        get => $this->owner->revisionNotes;
        set(?string $value) => $this->owner->revisionNotes = $value;
    }

    /**
     * Returns the revision label.
     *
     * @return string
     */
    public function getRevisionLabel(): string
    {
        return $this->owner->getRevisionLabel();
    }
}
