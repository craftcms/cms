<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\gql;

use Craft;
use craft\behaviors\DraftBehavior;
use craft\elements\Entry;
use craft\elements\User;
use craft\enums\CmsEdition;
use craft\helpers\StringHelper;
use craft\models\GqlSchema;
use craft\test\TestCase;
use crafttests\fixtures\EntryFixture;

/**
 * Tests schemas for GraphQL requests authenticated as a user.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.12.0
 */
class AuthenticatedSchemaTest extends TestCase
{
    private const OWN_ONLY_SECTION = 'section-1000---------------------uid';
    private const PEER_SECTION = 'section-1003---------------------uid';
    private const HIDDEN_SECTION = 'section-1004---------------------uid';

    private User $user;
    private Entry $ownEntry;
    private bool $lazyGqlTypes;

    public function _fixtures(): array
    {
        return [
            'entries' => [
                'class' => EntryFixture::class,
            ],
        ];
    }

    protected function _before(): void
    {
        Craft::$app->edition = CmsEdition::Pro;

        // Prebuilt schemas can only be built once per process
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $this->lazyGqlTypes = $generalConfig->lazyGqlTypes;
        $generalConfig->lazyGqlTypes = true;

        $user = new User([
            'username' => 'gql-' . StringHelper::randomString(6),
            'email' => 'gql-' . StringHelper::randomString(6) . '@example.com',
            'active' => true,
        ]);
        Craft::$app->getElements()->saveElement($user, false);
        Craft::$app->getUserPermissions()->saveUserPermissions($user->id, [
            'editSite:' . Craft::$app->getSites()->getPrimarySite()->uid,
            'viewEntries:' . self::OWN_ONLY_SECTION,
            'deleteEntries:' . self::OWN_ONLY_SECTION,
            'viewEntries:' . self::PEER_SECTION,
            'viewPeerEntries:' . self::PEER_SECTION,
        ]);
        $this->user = Craft::$app->getUsers()->getUserById($user->id);

        $this->ownEntry = new Entry(['sectionId' => 1000, 'typeId' => 1000, 'title' => 'Mine']);
        $this->ownEntry->setAuthorIds([$this->user->id]);
        Craft::$app->getElements()->saveElement($this->ownEntry, false);

        // The GraphQL controller sets the authenticated user as the identity
        Craft::$app->getUser()->setIdentity($this->user);
    }

    protected function _after(): void
    {
        Craft::$app->getUser()->setIdentity(null);
        Craft::$app->getConfig()->getGeneral()->lazyGqlTypes = $this->lazyGqlTypes;
        Craft::$app->getGql()->flushCaches();
    }

    public function testSchemaIsBasedOnUserPermissions(): void
    {
        $schema = Craft::$app->getGql()->getSchemaForUser($this->user);
        self::assertSame($this->user, $schema->user);
        self::assertContains('sections.' . self::OWN_ONLY_SECTION . ':read', $schema->scope);
        self::assertContains('sections.' . self::OWN_ONLY_SECTION . ':delete', $schema->scope);
        self::assertNotContains('sections.' . self::OWN_ONLY_SECTION . ':save', $schema->scope);
        self::assertContains('sections.' . self::PEER_SECTION . ':read', $schema->scope);
        self::assertNotContains('sections.' . self::HIDDEN_SECTION . ':read', $schema->scope);

        $schema = Craft::$app->getGql()->getSchemaForUser($this->user, false);
        self::assertNotContains('sections.' . self::OWN_ONLY_SECTION . ':delete', $schema->scope);

        // Cached scopes reflect permission changes
        Craft::$app->getUserPermissions()->saveUserPermissions($this->user->id, [
            'editSite:' . Craft::$app->getSites()->getPrimarySite()->uid,
            'viewEntries:' . self::OWN_ONLY_SECTION,
            'saveEntries:' . self::OWN_ONLY_SECTION,
        ]);
        $schema = Craft::$app->getGql()->getSchemaForUser($this->user);
        self::assertContains('sections.' . self::OWN_ONLY_SECTION . ':save', $schema->scope);
        self::assertNotContains('sections.' . self::OWN_ONLY_SECTION . ':delete', $schema->scope);
        self::assertNotContains('sections.' . self::PEER_SECTION . ':read', $schema->scope);
    }

    public function testCurrentUser(): void
    {
        $query = '{ currentUser { id email } }';
        $expected = ['id' => (string)$this->user->id, 'email' => $this->user->email];

        // Available without “Query for users”
        $schema = Craft::$app->getGql()->getSchemaForUser($this->user);
        self::assertNotContains('usergroups.everyone:read', $schema->scope);
        self::assertSame($expected, $this->execute($schema, $query)['data']['currentUser']);

        // Same data as the `user` query
        Craft::$app->getUserPermissions()->saveUserPermissions($this->user->id, ['viewUsers']);
        $schema = Craft::$app->getGql()->getSchemaForUser($this->user);
        $result = $this->execute($schema, '{ currentUser { id email fullName } user(id: ' . $this->user->id . ') { id email fullName } }');
        self::assertSame($result['data']['user'], $result['data']['currentUser']);
        self::assertSame($expected['email'], $result['data']['currentUser']['email']);

        // Schema tokens have no current user
        $result = $this->execute(new GqlSchema(['name' => 'Token', 'uid' => StringHelper::UUID(), 'scope' => ['usergroups.everyone:read']]), $query);
        self::assertArrayNotHasKey('currentUser', $result['data']);
    }

    public function testQueriesOnlyReturnEntriesTheUserCanView(): void
    {
        $result = $this->execute(Craft::$app->getGql()->getSchemaForUser($this->user), '{ entries(limit: 100) { title } }');

        $titles = array_column($result['data']['entries'], 'title');
        sort($titles);
        self::assertSame(['Mine', 'Pending 1', 'Pending 2', 'With URL 1', 'With URL 2'], $titles);
    }

    public function testReadOnlySchemasCannotMutate(): void
    {
        $result = $this->execute(Craft::$app->getGql()->getSchemaForUser($this->user, false), "mutation { deleteEntry(id: {$this->ownEntry->id}) }");

        self::assertArrayNotHasKey('deleteEntry', $result['data']);
        self::assertNotNull(Entry::find()->id($this->ownEntry->id)->one());
    }

    public function testMutationsAreLimitedToUserPermissions(): void
    {
        $schema = Craft::$app->getGql()->getSchemaForUser($this->user);
        $peerEntry = Entry::find()->sectionId(1000)->title('Theories of life')->one();

        // The schema can delete entries in the section, but the user can't delete other authors' entries
        $result = $this->execute($schema, "mutation { deleteEntry(id: $peerEntry->id) }");
        self::assertSame('Unable to perform the action.', $result['errors'][0]['message']);
        self::assertNotNull(Entry::find()->id($peerEntry->id)->one());

        $result = $this->execute($schema, "mutation { deleteEntry(id: {$this->ownEntry->id}) }");
        self::assertTrue($result['data']['deleteEntry']);
        self::assertNull(Entry::find()->id($this->ownEntry->id)->one());
    }

    public function testDraftsAreLimitedToUserPermissions(): void
    {
        Craft::$app->getUserPermissions()->saveUserPermissions($this->user->id, [
            'accessCp',
            'editSite:' . Craft::$app->getSites()->getPrimarySite()->uid,
            'viewEntries:' . self::OWN_ONLY_SECTION,
            'saveEntries:' . self::OWN_ONLY_SECTION,
            'viewEntries:' . self::PEER_SECTION,
            'viewPeerEntries:' . self::PEER_SECTION,
        ]);
        $admin = User::find()->admin()->one();
        $drafts = Craft::$app->getDrafts();
        $drafts->createDraft($this->ownEntry, $this->user->id, 'My draft');
        $peerEntry = Entry::find()->section('withUri1')->title('With URL 1')->one();
        $drafts->createDraft($peerEntry, $admin->id, 'Peer draft');
        $schema = Craft::$app->getGql()->getSchemaForUser($this->user);

        $result = $this->execute($schema, '{ entries(drafts: true, limit: 100) { draftName } }');
        self::assertSame(['My draft'], array_column($result['data']['entries'], 'draftName'));

        $result = $this->execute($schema, '{ entries(section: "withUri1", title: "With URL 1") { drafts { draftName } } }');
        self::assertSame([], $result['data']['entries'][0]['drafts']);

        // Drafts are created for the authenticated user, regardless of creatorId
        $result = $this->execute($schema, "mutation { createDraft(id: {$this->ownEntry->id}, name: \"New\", creatorId: $admin->id) }");
        $draft = Entry::find()->draftId($result['data']['createDraft'])->status(null)->one();
        self::assertInstanceOf(Entry::class, $draft);
        /** @var DraftBehavior $behavior */
        $behavior = $draft->getBehavior('draft');
        self::assertSame($this->user->id, $behavior->creatorId);
    }

    public function testCachedResultsAreLimitedToTheUsersPermissions(): void
    {
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $enableGraphqlCaching = $generalConfig->enableGraphqlCaching;
        $generalConfig->enableGraphqlCaching = true;
        $query = '{ entries(section: "test1", limit: 100) { title } }';

        try {
            $gql = Craft::$app->getGql();
            $titles = fn(array $result) => array_column($result['data']['entries'], 'title');

            self::assertSame(['Mine'], $titles($gql->executeQuery($gql->getSchemaForUser($this->user), $query)));

            // Another user with peer access to the section gets their own results, not the cached ones
            $peer = new User(['username' => 'gql-' . StringHelper::randomString(6), 'email' => StringHelper::randomString(6) . '@example.com', 'active' => true]);
            Craft::$app->getElements()->saveElement($peer, false);
            Craft::$app->getUserPermissions()->saveUserPermissions($peer->id, [
                'editSite:' . Craft::$app->getSites()->getPrimarySite()->uid,
                'viewEntries:' . self::OWN_ONLY_SECTION,
                'viewPeerEntries:' . self::OWN_ONLY_SECTION,
            ]);
            $peer = Craft::$app->getUsers()->getUserById($peer->id);
            Craft::$app->getUser()->setIdentity($peer);
            self::assertContains('Theories of life', $titles($gql->executeQuery($gql->getSchemaForUser($peer), $query)));

            // Losing peer access isn't masked by the cached result
            Craft::$app->getUserPermissions()->saveUserPermissions($peer->id, [
                'editSite:' . Craft::$app->getSites()->getPrimarySite()->uid,
                'viewEntries:' . self::OWN_ONLY_SECTION,
            ]);
            self::assertSame([], $titles($gql->executeQuery($gql->getSchemaForUser($peer), $query)));
        } finally {
            $generalConfig->enableGraphqlCaching = $enableGraphqlCaching;
        }
    }

    private function execute(GqlSchema $schema, string $query): array
    {
        $gql = Craft::$app->getGql();
        $gql->flushCaches();
        return $gql->executeQuery($schema, $query);
    }
}
