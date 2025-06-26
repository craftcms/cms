<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use yii\base\Component;

/**
 * Announcements service.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getAnnouncements()|`Craft::$app->getAnnouncements()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.7.0
 * @deprecated in 6.0.0. [[\Craft\Cms\Announcement\Announcements]] should be used instead.
 */
class Announcements extends Component
{
    /**
     * Pushes a new announcement out to all control panel users.
     *
     * ::: tip
     * Run the heading and body through [[\craft\i18n\Translation::prep()]] rather than [[\yii\BaseYii::t()|Craft::t()]]
     * so they can be lazy-translated for users’ preferred languages rather that the current app language.
     * :::
     *
     * @param string $heading The announcement heading.
     * @param string $body The announcement body.
     * @param string|null $pluginHandle The plugin handle, if this announcement belongs to a plugin
     * @param bool $adminsOnly Whether only admin users should receive the announcement
     */
    public function push(string $heading, string $body, ?string $pluginHandle = null, bool $adminsOnly = false): void
    {
        Craft::$app->getAnnouncements()->push($heading, $body, $pluginHandle, $adminsOnly);
    }

    /**
     * Returns any announcements for the logged-in user.
     *
     * @return array
     * @since 3.7.0
     */
    public function get(): array
    {
        return Craft::$app->getAnnouncements()->get();
    }

    /**
     * Marks the user’s announcements as read.
     *
     * @param int[] $ids
     */
    public function markAsRead(array $ids): void
    {
        Craft::$app->getAnnouncements()->markAsRead($ids);
    }
}
