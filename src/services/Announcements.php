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
use craft\helpers\Db;
use craft\helpers\Html;
use craft\helpers\Queue;
use craft\i18n\Translation;
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
     * ::: tip
     * Run the heading and body through [[\craft\i18n\Translation::prep()]] rather than [[\yii\BaseYii::t()|Craft::t()]]
     * so they can be lazy-translated for users’ preferred languages rather that the current app language.
     * :::
     *
     * @param string $heading The announcement heading.
     * @param string $body The announcement body.
     * @param string|null $pluginHandle The plugin handle, if this announcement belongs to a plugin
     */
    public function push(string $heading, string $body, ?string $pluginHandle = null): void
    {
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
            ->select(['a.id', 'a.heading', 'a.body', 'a.unread'])
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
                ->addSelect(['pluginHandle' => 'p.handle'])
                ->leftJoin(['p' => Table::PLUGINS], '[[p.id]] = [[a.pluginId]]')
                ->andWhere([
                    'or',
                    ['p.id' => null],
                    ['p.handle' => $enabledPluginHandles],
                ]);
        } else {
            $query->andWhere(['a.pluginId' => null]);
        }

        return array_map(function(array $result) use ($pluginsService) {
            $plugin = !empty($result['pluginHandle']) ? $pluginsService->getPlugin($result['pluginHandle']) : null;
            if ($plugin) {
                $icon = $pluginsService->getPluginIconSvg($plugin->getHandle());
                $label = $plugin->name;
            } else {
                $icon = file_get_contents(Craft::getAlias('@app/icons/craft-cms.svg'));
                $label = 'Craft CMS';
            }
            return [
                'id' => (int)$result['id'],
                'icon' => $icon,
                'label' => $label,
                'heading' => Html::widont(Html::encode(Translation::translate($result['heading']))),
                'body' => Html::widont(Markdown::processParagraph(Html::encode(Translation::translate($result['body'])))),
                'unread' => (bool)$result['unread'],
            ];
        }, $query->all());
    }

    /**
     * Marks the user’s announcements as read.
     *
     * @param int[] $ids
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
            ], ['id' => $ids, 'userId' => $userId])
            ->execute();
    }
}
