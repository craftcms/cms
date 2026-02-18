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
 * FindAndReplace job
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 3.0.0
 * @deprecated in Craft 6.0.0. Use {@see \CraftCms\Cms\Search\Jobs\FindAndReplace} instead.
 */
class FindAndReplace extends BaseJob
{
    /**
     * @var string|null The search text
     */
    public ?string $find = null;

    /**
     * @var string|null The replacement text
     */
    public ?string $replace = null;

    public function execute($queue): void
    {
        new \CraftCms\Cms\Search\Jobs\FindAndReplace(
            $this->find,
            $this->replace,
            $this->description,
        )->handle();
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultDescription(): ?string
    {
        return I18N::prep('Replacing “{find}” with “{replace}”', [
            'find' => $this->find,
            'replace' => $this->replace,
        ]);
    }
}
