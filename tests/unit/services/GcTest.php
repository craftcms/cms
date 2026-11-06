<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\services;

use Codeception\Test\Unit;
use Craft;
use craft\db\Command;
use craft\db\Query;
use craft\db\Table;
use craft\elements\Entry;
use craft\elements\User;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\records\User as UserRecord;
use craft\services\Gc;
use craft\test\TestCase;
use crafttests\fixtures\EntryFixture;
use crafttests\fixtures\EntryTypeFixture;
use crafttests\fixtures\SectionsFixture;
use crafttests\fixtures\SessionsFixture;
use crafttests\fixtures\UserFixture;
use crafttests\fixtures\VolumesFixture;
use DateInterval;
use DateTime;
use DateTimeZone;
use Exception;
use yii\base\InvalidArgumentException;
use yii\db\Exception as DbException;

/**
 * Unit tests for the garbage collector service.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class GcTest extends TestCase
{
    /**
     * @var Gc
     */
    protected Gc $gc;

    /**
     * @return array
     */
    public function _fixtures(): array
    {
        return [
            'sessions' => [
                'class' => SessionsFixture::class,
            ],
            'entry-types' => [
                'class' => EntryTypeFixture::class,
            ],
            'entries' => [
                'class' => EntryFixture::class,
            ],
            'users' => [
                'class' => UserFixture::class,
            ],
            'sections' => [
                'class' => SectionsFixture::class,
            ],
            'volumes' => [
                'class' => VolumesFixture::class,
            ],
        ];
    }

    /**
     *
     */
    public function testRunForDeletedEntriesWithDefaultDuration(): void
    {
        $this->_doEntryTest(1, [
            'Deleted 40 days ago',
        ]);
    }

    /**
     *
     */
    public function testRunForDeletedEntriesWithCustomDuration(): void
    {
        // 5 Days
        Craft::$app->getConfig()->getGeneral()->softDeleteDuration = 432000;

        $this->_doEntryTest(2, [
            'Deleted 40 days ago',
            'Deleted 25 days ago',
        ]);
    }

    /**
     *
     */
    public function testRunDeleteAllTrashed(): void
    {
        $this->gc->deleteAllTrashed = true;

        $this->_doEntryTest(3, [
            'Deleted 40 days ago',
            'Deleted 25 days ago',
            'Deleted today',
        ]);
    }

    /**
     * @dataProvider gcDataProvider
     * @param int $remainingCount
     * @param string $leftoverId
     * @param string $table
     * @param array $ids
     */
    public function testGc(int $remainingCount, string $leftoverId, string $table, array $ids): void
    {
        $this->gc->run(true);

        $items = (new Query())
            ->from([$table])
            ->where(['id' => $ids])
            ->all();

        self::assertCount($remainingCount, $items);
        self::assertSame((string)ArrayHelper::firstValue($items)['id'], $leftoverId);
    }

    /**
     *
     */
    public function testRunForExpiringUsers(): void
    {
        // 2 days
        Craft::$app->getConfig()->getGeneral()->purgePendingUsersDuration = 60 * 60 * 24 * 2;

        $count = User::find()
            ->username(['user1', 'user2', 'user3', 'user4'])
            ->status(null)
            ->count();

        // Make sure all 4 users are in there
        self::assertEquals(4, $count);

        // Create them with 3 days
        $this->_createExpiringPendingUsers();

        $this->gc->run(true);

        $count = User::find()
            ->username(['user1', 'user2', 'user3', 'user4'])
            ->status(null)
            ->count();

        // Should only be 2 users now
        self::assertEquals(2, $count);
    }

    public function testRunDeletesStaleOAuthData(): void
    {
        $userId = (int)(new Query())->select('id')->from(Table::USERS)->scalar();
        $date = fn(string $time) => Db::prepareDateForDb(new DateTime($time));

        // id => [provider, revoked, created, last authorized]
        $clients = [
            'active' => [null, false, '3 days ago', '2 days ago'],
            'authorized' => [null, false, '3 days ago', '2 days ago'],
            'authorized-same-second' => [null, false, '2 days ago', '2 days ago'],
            'new' => [null, false, '1 hour ago', null],
            'unauthorized' => [null, false, '2 days ago', null],
            'revoked' => [null, true, '3 days ago', '2 days ago'],
            'revoked-live' => [null, true, '3 days ago', '2 days ago'],
            'personal' => ['craft', false, '2 days ago', '2 days ago'],
        ];
        foreach ($clients as $id => [$provider, $revoked, $created, $updated]) {
            Db::insert(Table::OAUTH_CLIENTS, [
                'id' => $id,
                'name' => $id,
                'provider' => $provider,
                'redirect_uris' => '[]',
                'grant_types' => '[]',
                'revoked' => $revoked,
                'created_at' => $date($created),
                'updated_at' => $updated ? $date($updated) : null,
            ]);
        }

        // id => [client, revoked, expires]
        $authCodes = [
            'code-expired' => ['active', false, '1 minute ago'],
            'code-revoked' => ['active', true, '+5 minutes'],
            'code-live' => ['active', false, '+5 minutes'],
        ];
        foreach ($authCodes as $id => [$client, $revoked, $expires]) {
            Db::insert(Table::OAUTH_AUTH_CODES, [
                'id' => $id,
                'user_id' => $userId,
                'client_id' => $client,
                'revoked' => $revoked,
                'expires_at' => $date($expires),
            ]);
        }

        // id => [client, revoked, expires, refresh token: [revoked, expires]]
        $accessTokens = [
            'access-expired' => ['active', false, '1 hour ago', null],
            'access-expired-refreshable' => ['active', false, '1 hour ago', [false, '+29 days']],
            'access-expired-refresh-expired' => ['active', false, '31 days ago', [false, '1 day ago']],
            'access-rotated' => ['active', true, '+30 minutes', [true, '+29 days']],
            'access-live' => ['active', false, '+30 minutes', null],
            'access-revoked-client' => ['revoked-live', false, '+30 minutes', null],
        ];
        foreach ($accessTokens as $id => [$client, $revoked, $expires, $refresh]) {
            Db::insert(Table::OAUTH_ACCESS_TOKENS, [
                'id' => $id,
                'user_id' => $userId,
                'client_id' => $client,
                'revoked' => $revoked,
                'created_at' => $date('2 days ago'),
                'updated_at' => $date('2 days ago'),
                'expires_at' => $date($expires),
            ]);
            if ($refresh) {
                Db::insert(Table::OAUTH_REFRESH_TOKENS, [
                    'id' => "$id-refresh",
                    'access_token_id' => $id,
                    'revoked' => $refresh[0],
                    'expires_at' => $date($refresh[1]),
                ]);
            }
        }

        $this->gc->run(true);

        // PostgreSQL pads fixed-length IDs with spaces
        $remaining = fn(string $table, array $ids) => array_map('rtrim', (new Query())->select('id')->from($table)->where(['id' => $ids])->orderBy('id')->column());

        self::assertSame(['code-live'], $remaining(Table::OAUTH_AUTH_CODES, array_keys($authCodes)));
        self::assertSame(
            ['access-expired-refreshable', 'access-live', 'access-revoked-client'],
            $remaining(Table::OAUTH_ACCESS_TOKENS, array_keys($accessTokens)),
        );
        self::assertSame(
            ['access-expired-refreshable-refresh'],
            $remaining(Table::OAUTH_REFRESH_TOKENS, array_map(fn($id) => "$id-refresh", array_keys($accessTokens))),
        );
        self::assertSame(
            ['active', 'authorized', 'authorized-same-second', 'new', 'personal', 'revoked-live'],
            $remaining(Table::OAUTH_CLIENTS, array_keys($clients)),
        );
    }

    /**
     * Foreign key checks must be back on when deleting orphaned FK rows fails partway through.
     *
     * @see https://github.com/craftcms/cms/issues/20386
     */
    public function testFkChecksRestoredWhenOrphanedFkRowDeletionFails(): void
    {
        $db = Craft::$app->getDb();
        $commandClass = $db->commandClass;
        $db->commandClass = get_class(new class() extends Command {
            public function execute()
            {
                // only fail the orphaned FK row deletions
                if (preg_match('/^DELETE (t\.\* )?FROM \S+ t\s/', $this->getSql())) {
                    throw new DbException('Simulated failure');
                }
                return parent::execute();
            }
        });

        try {
            $this->gc->run(true);
            self::fail('Garbage collection should have failed.');
        } catch (DbException $e) {
            self::assertSame('Simulated failure', $e->getMessage());
        } finally {
            $db->commandClass = $commandClass;
        }

        if ($db->getIsMysql()) {
            self::assertSame(1, (int)$db->createCommand('SELECT @@foreign_key_checks')->queryScalar());
        } else {
            self::assertSame(0, (int)$db->createCommand("SELECT count(*) FROM pg_trigger WHERE tgenabled = 'D'")->queryScalar());
        }
    }

    /**
     * @return array
     * @todo Other GC tables
     */
    public static function gcDataProvider(): array
    {
        return [
            [1, '1005', Table::SESSIONS, ['1003', '1004', '1005']],
            [1, '1000', Table::SECTIONS, ['1000', '1001', '1002']],
            [1, '1000', Table::ENTRYTYPES, ['1000', '1001', '1002']],
            [1, '1000', Table::VOLUMES, ['1000', '1001', '1002']],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function _before(): void
    {
        parent::_before();

        $this->gc = Craft::$app->getGc();
    }

    /**
     * Helper method to check entries are removed. You can pass $expectedRemove
     * to indicate how many entries should be deleted and a $notAllowedTitles to
     * indicate what titles are not allowed to be present.
     *
     * @param int $expectedRemoval
     * @param array $notAllowedTitles
     */
    private function _doEntryTest(int $expectedRemoval, array $notAllowedTitles = [])
    {
        $totalEntries = Entry::find()->trashed()->count();
        $this->gc->run(true);
        /** @var Entry[] $entries */
        $entries = Entry::find()
            ->trashed()
            ->asArray()
            ->all();

        self::assertCount($totalEntries - $expectedRemoval, $entries);

        // Check any non allowed titles. Fail if an entry exists with a title that isn't allowed.
        foreach ($notAllowedTitles as $notAllowedTitle) {
            $doesEntryExistWithThisTitle = ArrayHelper::where($entries, 'title', $notAllowedTitle);
            if ($doesEntryExistWithThisTitle) {
                self::fail("Entries were deleted but an entry with title ($notAllowedTitle) still exists");
            }
        }
    }

    /**
     * @throws Exception
     */
    private function _createExpiringPendingUsers()
    {
        $date = (new DateTime('now', new DateTimeZone('UTC')))->sub(new DateInterval('P3D'))->format('Y-m-d H:i:s');

        /** @var UserRecord[] $userRecords */
        $userRecords = UserRecord::find()
            ->where(['username' => ['user1', 'user2']])
            ->all();

        foreach ($userRecords as $userRecord) {
            $userRecord->verificationCodeIssuedDate = $date;
            $userRecord->pending = true;

            if (!$userRecord->save()) {
                throw new InvalidArgumentException('Unable to update user');
            }
        }
    }
}
