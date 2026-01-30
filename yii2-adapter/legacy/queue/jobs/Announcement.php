<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\queue\jobs;

use craft\queue\BaseJob;
use CraftCms\Cms\Announcement\Jobs\SendAnnouncement;
use CraftCms\Cms\Support\Facades\I18N;
use yii\base\Exception;

/**
 * Announcement job
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 3.7.0
 * @deprecated in Craft 6.0.0. Use {@see \CraftCms\Cms\Announcement\Jobs\SendAnnouncement} instead.
 */
class Announcement extends BaseJob
{
    /**
     * @var string The announcement heading
     */
    public string $heading;

    /**
     * @var string The announcement body
     */
    public string $body;

    /**
     * @var string|null The plugin handle
     */
    public ?string $pluginHandle = null;

    /**
     * @var bool Whether only admins should receive the announcement.
     *
     * @since 4.5.6
     */
    public bool $adminsOnly = false;

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function execute($queue): void
    {
        new SendAnnouncement($this->heading, $this->body, $this->pluginHandle, $this->adminsOnly)->handle();
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultDescription(): ?string
    {
        return I18N::prep('Pushing announcement to control panel users');
    }
}
