<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\gql;

use Craft;
use craft\base\FieldLayoutComponent;
use craft\elements\Asset;
use craft\elements\conditions\assets\FilenameConditionRule;
use craft\elements\conditions\assets\FileTypeConditionRule;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\conditions\entries\TypeConditionRule;
use craft\elements\conditions\TitleConditionRule;
use craft\elements\conditions\users\AdminConditionRule;
use craft\elements\Entry;
use craft\elements\User;
use craft\enums\CmsEdition;
use craft\events\DefineShowFieldLayoutComponentInFormEvent;
use craft\fieldlayoutelements\CustomField;
use craft\gql\base\ElementMutationResolver;
use craft\gql\resolvers\mutations\Asset as AssetMutationResolver;
use craft\helpers\StringHelper;
use craft\models\GqlSchema;
use craft\test\TestCase;
use crafttests\fixtures\EntryFixture;
use crafttests\fixtures\VolumesFixture;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use ReflectionMethod;

/**
 * Tests that schemas for GraphQL requests authenticated as a user respect field layout conditions.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.12.1
 */
class AuthenticatedFieldConditionsTest extends TestCase
{
    private const SECTION = 'section-1003---------------------uid';
    private const ENTRY_TYPE_ID = 1003;
    private const NESTED_ENTRY_TYPE_ID = 1008;
    private const VOLUME = 'volume-1000----------------------uid';

    /**
     * A 1×1 PNG
     */
    private const PNG = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    private User $user;
    private bool $lazyGqlTypes;

    /**
     * @var CustomField[]
     */
    private array $conditionalLayoutElements = [];

    public function _fixtures(): array
    {
        return [
            'entries' => [
                'class' => EntryFixture::class,
            ],
            'volumes' => [
                'class' => VolumesFixture::class,
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
            'viewEntries:' . self::SECTION,
            'viewPeerEntries:' . self::SECTION,
            'saveEntries:' . self::SECTION,
            'savePeerEntries:' . self::SECTION,
            'viewAssets:' . self::VOLUME,
            'saveAssets:' . self::VOLUME,
        ]);
        $this->user = Craft::$app->getUsers()->getUserById($user->id);

        foreach (Entry::find()->section('withUri1')->status(null)->all() as $entry) {
            $entry->setFieldValue('plainTextField2', "Secret: $entry->title");
            Craft::$app->getElements()->saveElement($entry, false);
        }

        // The GraphQL controller sets the authenticated user as the identity
        Craft::$app->getUser()->setIdentity($this->user);
    }

    protected function _after(): void
    {
        foreach ($this->conditionalLayoutElements as $layoutElement) {
            $layoutElement->setUserCondition(null);
            $layoutElement->setElementCondition(null);
            $layoutElement->setEditCondition(null);
            $layoutElement->setElementEditCondition(null);
            $layoutElement->off(FieldLayoutComponent::EVENT_DEFINE_SHOW_IN_FORM);
        }

        Craft::$app->getUser()->setIdentity(null);
        Craft::$app->getGql()->setActiveSchema(null);
        Craft::$app->getConfig()->getGeneral()->lazyGqlTypes = $this->lazyGqlTypes;
        Craft::$app->getGql()->flushCaches();
    }

    public function testFieldsHiddenByUserConditionsAreExcludedFromTheSchema(): void
    {
        $query = '{ entries(section: "withUri1", title: "With URL 1") { ... on withUrls_Entry { plainTextField2 } } }';

        $result = $this->execute($this->userSchema(), $query);
        self::assertSame('Secret: With URL 1', $result['data']['entries'][0]['plainTextField2']);

        $this->layoutElement('plainTextField2')->setUserCondition($this->adminCondition());

        $result = $this->execute($this->userSchema(), $query);
        self::assertArrayNotHasKey('plainTextField2', (array)$result['data']['entries'][0]);
        self::assertFalse($this->entryType()->hasField('plainTextField2'));

        // Admins meet the condition
        $this->user->admin = true;
        $result = $this->execute($this->userSchema(), $query);
        self::assertSame('Secret: With URL 1', $result['data']['entries'][0]['plainTextField2']);
        $this->user->admin = false;

        // Schema tokens aren't affected
        $tokenSchema = new GqlSchema([
            'name' => 'Token',
            'uid' => StringHelper::UUID(),
            'scope' => ['sections.' . self::SECTION . ':read'],
        ]);
        $result = $this->execute($tokenSchema, $query);
        self::assertSame('Secret: With URL 1', $result['data']['entries'][0]['plainTextField2']);
    }

    public function testFieldsHiddenByElementConditionsResolveToNull(): void
    {
        $this->layoutElement('plainTextField2')->setElementCondition($this->titleCondition('With URL 1'));

        $result = $this->execute($this->userSchema(), '{ entries(section: "withUri1", title: ["With URL 1", "With URL 2"], orderBy: "title") { title ... on withUrls_Entry { plainTextField2 } } }');
        self::assertSame([
            ['title' => 'With URL 1', 'plainTextField2' => 'Secret: With URL 1'],
            ['title' => 'With URL 2', 'plainTextField2' => null],
        ], $result['data']['entries']);
    }

    public function testHiddenNonNullFieldsResolveToNull(): void
    {
        // Matrix fields have a non-null output type
        $this->layoutElement('matrixSecond')->setElementCondition($this->titleCondition('With URL 1'));

        $result = $this->execute($this->userSchema(), '{ entries(section: "withUri1", title: ["With URL 1", "With URL 2"], orderBy: "title") { title ... on withUrls_Entry { matrixSecond { id } } } }');
        self::assertArrayNotHasKey('errors', $result);
        self::assertSame('With URL 1', $result['data']['entries'][0]['title']);
        self::assertIsArray($result['data']['entries'][0]['matrixSecond']);
        self::assertSame(['title' => 'With URL 2', 'matrixSecond' => null], $result['data']['entries'][1]);
    }

    public function testVisibilityEventHandlersOnLayoutElementsAreRespected(): void
    {
        // Instance-level handlers aren't found by class-level handler checks
        $this->layoutElement('plainTextField2')->on(
            FieldLayoutComponent::EVENT_DEFINE_SHOW_IN_FORM,
            function(DefineShowFieldLayoutComponentInFormEvent $event) {
                // Handlers may assume there's an element
                $event->showInForm = $event->element?->title === 'With URL 1';
            },
        );

        $result = $this->execute($this->userSchema(), '{ entries(section: "withUri1", title: ["With URL 1", "With URL 2"], orderBy: "title") { title ... on withUrls_Entry { plainTextField2 } } }');
        self::assertSame([
            ['title' => 'With URL 1', 'plainTextField2' => 'Secret: With URL 1'],
            ['title' => 'With URL 2', 'plainTextField2' => null],
        ], $result['data']['entries']);

        // The field can still be set where the handler allows it, and is rejected elsewhere
        $mutation = 'mutation { save_withUri1_withUrls_Entry(id: %s, plainTextField2: "Changed") { id } }';
        $allowed = Entry::find()->section('withUri1')->title('With URL 1')->one();
        $result = $this->execute($this->userSchema(), sprintf($mutation, $allowed->id));
        self::assertArrayNotHasKey('errors', $result);
        self::assertSame('Changed', $this->fieldValue($allowed->id));

        $hidden = Entry::find()->section('withUri1')->title('With URL 2')->one();
        $result = $this->execute($this->userSchema(), sprintf($mutation, $hidden->id));
        self::assertSame('You don’t have permission to edit the following fields: plainTextField2', $result['errors'][0]['message']);
    }

    public function testFieldsLockedByEditConditionsAreExcludedFromMutations(): void
    {
        $entry = Entry::find()->section('withUri1')->title('With URL 1')->one();
        $this->layoutElement('plainTextField2')->setEditCondition($this->adminCondition());
        $schema = $this->userSchema();

        // Still queryable
        $result = $this->execute($schema, "{ entry(id: $entry->id) { ... on withUrls_Entry { plainTextField2 } } }");
        self::assertSame('Secret: With URL 1', $result['data']['entry']['plainTextField2']);

        $result = $this->execute($schema, "mutation { save_withUri1_withUrls_Entry(id: $entry->id, plainTextField2: \"Changed\") { id } }");
        self::assertStringContainsString('Unknown argument "plainTextField2"', $result['errors'][0]['message']);
        self::assertSame('Secret: With URL 1', $this->fieldValue($entry->id));

        // Other fields can still be saved
        $result = $this->execute($schema, "mutation { save_withUri1_withUrls_Entry(id: $entry->id, title: \"Renamed\") { title } }");
        self::assertSame('Renamed', $result['data']['save_withUri1_withUrls_Entry']['title']);
    }

    public function testFieldsLockedByElementEditConditionsAreRejectedWhenMutating(): void
    {
        $this->layoutElement('plainTextField2')->setElementEditCondition($this->titleCondition('With URL 1'));
        $schema = $this->userSchema();
        $mutation = 'mutation { save_withUri1_withUrls_Entry(id: %s, plainTextField2: "Changed") { id } }';

        $locked = Entry::find()->section('withUri1')->title('With URL 2')->one();
        $result = $this->execute($schema, sprintf($mutation, $locked->id));
        self::assertSame('You don’t have permission to edit the following fields: plainTextField2', $result['errors'][0]['message']);
        self::assertSame('Secret: With URL 2', $this->fieldValue($locked->id));

        $editable = Entry::find()->section('withUri1')->title('With URL 1')->one();
        $result = $this->execute($schema, sprintf($mutation, $editable->id));
        self::assertArrayNotHasKey('errors', $result);
        self::assertSame('Changed', $this->fieldValue($editable->id));
    }

    public function testNestedEntryFieldsRespectEditConditions(): void
    {
        $entry = Entry::find()->section('withUri1')->title('With URL 1')->one();
        $nestedLayoutElement = $this->layoutElement('secondSubfield', self::NESTED_ENTRY_TYPE_ID);
        $mutation = "mutation { save_withUri1_withUrls_Entry(id: $entry->id, matrixSecond: { sortOrder: [\"new:1\"], entries: [{ matrixLayout2: { id: \"new:1\", secondSubfield: \"Nested\" } }] }) { id } }";

        // Excluded from the input type when the user condition isn't met
        $nestedLayoutElement->setEditCondition($this->adminCondition());
        $result = $this->execute($this->userSchema(), $mutation);
        self::assertStringContainsString('"secondSubfield"', $result['errors'][0]['message']);

        // Rejected when the element condition isn't met
        $nestedLayoutElement->setEditCondition(null);
        $condition = Entry::createCondition();
        $condition->addConditionRule(new TypeConditionRule(['values' => ['entry-type-1009------------------uid']]));
        $nestedLayoutElement->setElementEditCondition($condition);
        $result = $this->execute($this->userSchema(), $mutation);
        self::assertSame('You don’t have permission to edit the following fields: secondSubfield', $result['errors'][0]['message']);
    }

    private function entryType(): ObjectType
    {
        $type = Craft::$app->getGql()->getSchemaDef()->getType('withUrls_Entry');
        self::assertInstanceOf(ObjectType::class, $type);
        return $type;
    }

    private function fieldValue(int $entryId): mixed
    {
        $entry = Entry::find()->id($entryId)->one();
        self::assertInstanceOf(Entry::class, $entry);
        return $entry->getFieldValue('plainTextField2');
    }

    public function testAssetFieldConditionsCanDependOnTheUploadedFile(): void
    {
        // Filename conditions can only be evaluated once the uploaded file has been handled
        $this->setAssetEditCondition(new FilenameConditionRule(['operator' => '=', 'value' => 'upload.txt']));

        $asset = new Asset(['volumeId' => 1000]);
        $this->populateAsset($asset, 'data:text/plain;base64,' . base64_encode('Hello'), 'upload.txt');

        self::assertSame('upload.txt', $asset->getFilename());
        self::assertSame('Described', $asset->getFieldValue('imageDescription'));
    }

    public function testAssetFieldConditionsUseTheUploadedFilesInfo(): void
    {
        $this->setAssetEditCondition(new FileTypeConditionRule(['values' => [Asset::KIND_IMAGE]]));

        $asset = new Asset(['volumeId' => 1000]);
        $this->populateAsset($asset, self::PNG, 'upload.png');

        self::assertSame(Asset::KIND_IMAGE, $asset->kind);
        self::assertSame(1, $asset->getWidth());
        self::assertSame(1, $asset->getHeight());
        self::assertGreaterThan(0, $asset->size);
        self::assertSame('Described', $asset->getFieldValue('imageDescription'));
    }

    public function testAssetFieldConditionsUseTheReplacementFilesInfo(): void
    {
        $this->setAssetEditCondition(new FileTypeConditionRule(['values' => [Asset::KIND_IMAGE]]));

        // An existing text file being replaced with an image
        $asset = new Asset([
            'id' => 999999,
            'volumeId' => 1000,
            'uploaderId' => $this->user->id,
            'filename' => 'old.txt',
            'kind' => Asset::KIND_TEXT,
            'size' => 5,
        ]);
        $this->populateAsset($asset, self::PNG, 'upload.png');

        self::assertSame('old.txt', $asset->getFilename());
        self::assertSame('upload.png', $asset->newFilename);
        self::assertSame(Asset::KIND_IMAGE, $asset->kind);
        self::assertSame('Described', $asset->getFieldValue('imageDescription'));
    }

    private function setAssetEditCondition(ElementConditionRuleInterface $rule): void
    {
        $condition = Asset::createCondition();
        $condition->addConditionRule($rule);
        $layoutElement = Craft::$app->getVolumes()->getVolumeById(1000)
            ->getFieldLayout()
            ->getFieldByHandle('imageDescription')
            ->layoutElement;
        $layoutElement->setElementEditCondition($condition);
        $this->conditionalLayoutElements[] = $layoutElement;
    }

    private function populateAsset(Asset $asset, string $fileData, string $filename): void
    {
        Craft::$app->getGql()->setActiveSchema($this->userSchema());
        $resolver = Craft::createObject(AssetMutationResolver::class);
        $resolver->setResolutionData('volume', Craft::$app->getVolumes()->getVolumeById(1000));
        $resolver->setResolutionData(ElementMutationResolver::CONTENT_FIELD_KEY, ['imageDescription' => Type::string()]);

        $populate = new ReflectionMethod($resolver, 'populateElementWithData');
        $populate->invoke($resolver, $asset, [
            '_file' => ['fileData' => $fileData, 'filename' => $filename],
            'imageDescription' => 'Described',
        ]);
    }

    private function userSchema(): GqlSchema
    {
        return Craft::$app->getGql()->getSchemaForUser($this->user);
    }

    private function layoutElement(string $handle, int $entryTypeId = self::ENTRY_TYPE_ID): CustomField
    {
        $layoutElement = Craft::$app->getEntries()->getEntryTypeById($entryTypeId)
            ->getFieldLayout()
            ->getFieldByHandle($handle)
            ->layoutElement;
        $this->conditionalLayoutElements[] = $layoutElement;
        return $layoutElement;
    }

    private function adminCondition(): mixed
    {
        $condition = User::createCondition();
        $condition->addConditionRule(new AdminConditionRule(['value' => true]));
        return $condition;
    }

    private function titleCondition(string $title): mixed
    {
        $condition = Entry::createCondition();
        $condition->addConditionRule(new TitleConditionRule(['operator' => '=', 'value' => $title]));
        return $condition;
    }

    private function execute(GqlSchema $schema, string $query): array
    {
        $gql = Craft::$app->getGql();
        $gql->flushCaches();
        return $gql->executeQuery($schema, $query);
    }
}
