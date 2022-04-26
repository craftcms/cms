<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\Html;
use craft\helpers\Queue;
use craft\queue\jobs\Announcement;
use DateTime;
use yii\base\Component;
use yii\helpers\Markdown;

/**
 * Announcements service.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getAnnouncements()|`Craft::$app->announcements`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.7.0
 */
class Announcements extends Component
{
    /**
     * Pushes a new announcement out to all control panel users.
     *
     * @param string|callable $heading The announcement heading. Set to a callable if the heading text should be translated with `Craft::t()`.
     * @param string|callable $body The announcement body. Set to a callable if the heading text should be translated with `Craft::t()`.
     * @param string|null $pluginHandle The plugin handle, if this announcement belongs to a plugin
     * @return void
     */
    public function push($heading, $body, ?string $pluginHandle = null): void
    {
        if (is_callable($heading) || is_callable($body)) {
            $t9nHeading = [];
            $t9nBody = [];
            // Translate the announcement into each of the supported languages
            foreach (Craft::$app->getI18n()->getAppLocaleIds() as $language) {
                $t9nHeading[$language] = (string)$heading($language);
                $t9nBody[$language] = (string)$body($language);
            }
            $heading = $t9nHeading;
            $body = $t9nBody;
        }

        Queue::push(new Announcement([
            'heading' => $heading,
            'body' => $body,
            'pluginHandle' => $pluginHandle,
        ]));
    }

    /**
     * Returns any announcements for the logged-in user.
     *
     * @return array
     * @since 3.7.0
     */
    public function get(): array
    {
        $userId = Craft::$app->getUser()->getId();
        if (!$userId) {
            return [];
        }

        $query = (new Query())
            ->select(['a.id', 'a.heading', 'a.body', 'a.unread', 'a.dateCreated'])
            ->from(['a' => Table::ANNOUNCEMENTS])
            ->orderBy(['a.dateCreated' => SORT_DESC])
            ->where(['userId' => $userId])
            ->andWhere([
                'or',
                ['a.unread' => true],
                ['>', 'a.dateRead', Db::prepareDateForDb(new DateTime('7 days ago'))],
            ]);

        // Any enabled plugins?
        $pluginsService = Craft::$app->getPlugins();
        $enabledPluginHandles = ArrayHelper::getColumn($pluginsService->getAllPlugins(), 'id');
        if (!empty($enabledPluginHandles)) {
            $query
                ->leftJoin(['p' => Table::PLUGINS], '[[p.id]] = [[a.pluginId]]')
                ->andWhere([
                    'or',
                    ['p.id' => null],
                    ['p.handle' => $enabledPluginHandles],
                ]);
        } else {
            $query->andWhere(['a.pluginId' => null]);
        }

        $formatter = Craft::$app->getFormatter();

        return array_map(function(array $result) use ($formatter, $pluginsService) {
            return [
                'id' => (int)$result['id'],
                'heading' => Html::widont(Html::encode($result['heading'])),
                'body' => Html::widont(Markdown::processParagraph(Html::encode($result['body']))),
                'timestamp' => $formatter->asTimestamp(DateTimeHelper::toDateTime($result['dateCreated'])->format('Y-m-d')),
                'unread' => (bool)$result['unread'],
            ];
        }, $query->all());
    }

    /**
     * Marks the user’s announcements as read.
     *
     * @param int[] $ids
     * @return void
     */
    public function markAsRead(array $ids): void
    {
        if (empty($ids)) {
            return;
        }

        $userId = Craft::$app->getUser()->getId();
        if (!$userId) {
            return;
        }

        Craft::$app->getDb()->createCommand()
            ->update(Table::ANNOUNCEMENTS, [
                'unread' => false,
                'dateRead' => Db::prepareDateForDb(new DateTime()),
            ], ['id' => $ids, 'userId' => $userId], [], false)
            ->execute();
    }
}
