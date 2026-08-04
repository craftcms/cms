<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\queue\jobs;

use craft\queue\BaseJob;
use CraftCms\Cms\Support\Facades\I18N;

/**
 * LocalizeRelations job
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 3.0.0
 * @deprecated in Craft 6.0.0. Use {@see \CraftCms\Cms\Element\Jobs\LocalizeRelations} instead.
 */
class LocalizeRelations extends BaseJob
{
    /**
     * @var int|null The field ID whose data should be localized
     */
    public ?int $fieldId = null;

    /**
     * {@inheritdoc}
     */
    public function execute($queue): void
    {
        new \CraftCms\Cms\Element\Jobs\LocalizeRelations($this->fieldId)->handle();
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultDescription(): ?string
    {
        return I18N::prep('Localizing relations');
    }
}
