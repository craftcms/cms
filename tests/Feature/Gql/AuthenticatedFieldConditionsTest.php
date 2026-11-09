<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Conditions\FilenameConditionRule;
use CraftCms\Cms\Asset\Conditions\FileTypeConditionRule;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Enums\FileKind;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Conditions\TitleConditionRule;
use CraftCms\Cms\Entry\Conditions\TypeConditionRule;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\Events\FieldLayoutComponentShowInFormResolving;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Gql\Data\GqlSchema;
use CraftCms\Cms\Gql\Gql;
use CraftCms\Cms\Gql\GqlHelper;
use CraftCms\Cms\Gql\Resolvers\ElementMutationResolver;
use CraftCms\Cms\Gql\Resolvers\Mutations\Asset as AssetMutationResolver;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Volumes;
use CraftCms\Cms\User\Conditions\AdminConditionRule;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use CraftCms\Cms\User\UserPermissions;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\actingAs;

const AUTHENTICATED_ENTRIES_QUERY = '{ entries(section: "news", title: ["One", "Two"], orderBy: "title ASC") { title ... on article_Entry { secret } } }';

beforeEach(function (): void {
    Cms::config()->enableGraphqlCaching = false;

    $secret = Field::factory()->create(['handle' => 'secret', 'type' => PlainText::class]);
    $subfield = Field::factory()->create(['handle' => 'subfield', 'type' => PlainText::class]);

    $this->blockType = EntryType::factory()
        ->withFieldLayout(FieldLayout::factory()->withContentTab([new CustomField(config: ['fieldUid' => $subfield->uid])]))
        ->create(['handle' => 'block']);
    $this->otherBlockType = EntryType::factory()->create(['handle' => 'otherBlock']);

    $blocks = Field::factory()->create([
        'handle' => 'blocks',
        'type' => Matrix::class,
        'settings' => ['entryTypes' => [$this->blockType->id]],
    ]);

    $this->entryType = EntryType::factory()
        ->withFieldLayout(FieldLayout::factory()->withContentTab([
            new CustomField(config: ['fieldUid' => $secret->uid]),
            new CustomField(config: ['fieldUid' => $blocks->uid]),
        ]))
        ->create(['handle' => 'article']);
    $section = Section::factory()->withEntryTypes($this->entryType)->create(['handle' => 'news']);
    Fields::refreshFields();
    EntryTypes::refreshEntryTypes();

    $this->user = UserModel::factory()
        ->withPermissions([
            "viewEntries:$section->uid",
            "viewPeerEntries:$section->uid",
            "saveEntries:$section->uid",
            "savePeerEntries:$section->uid",
        ])
        ->createElement(['admin' => false]);

    foreach (['One', 'Two'] as $title) {
        $entry = EntryModel::factory()->forSection($section)->forEntryType($this->entryType)->createElement(['title' => $title]);
        $entry->setFieldValue('secret', "Secret $title");
        Elements::saveElement($entry);
        $this->{"entry$title"} = $entry;
    }

    actingAs(UserModel::findOrFail($this->user->id));
});

function conditionalLayoutElement(EntryType $entryType, string $handle): CustomField
{
    return EntryTypes::getEntryTypeById($entryType->id)->getFieldLayout()->getFieldByHandle($handle)->layoutElement;
}

function adminUserCondition(): mixed
{
    $condition = User::createCondition();
    $condition->addConditionRule(new AdminConditionRule(['value' => true]));

    return $condition;
}

function entryTitleCondition(string $title): mixed
{
    $condition = Entry::createCondition();
    $condition->addConditionRule(new TitleConditionRule(['operator' => '=', 'value' => $title]));

    return $condition;
}

/** @return array<string, mixed> */
function executeAuthenticated(User $user, string $query): array
{
    $gql = app(Gql::class);
    $gql->flushCaches();

    return $gql->executeQuery($gql->getSchemaForUser($user), $query);
}

function secretValue(Entry $entry): mixed
{
    return entryQuery()->id($entry->id)->status(null)->one()->getFieldValue('secret');
}

function saveSecretMutation(Entry $entry, string $value = 'Changed'): string
{
    return "mutation { save_news_article_Entry(id: $entry->id, secret: \"$value\") { id } }";
}

it('excludes fields hidden by user conditions from authenticated schemas', function (): void {
    expect(executeAuthenticated($this->user, AUTHENTICATED_ENTRIES_QUERY)['data']['entries'][0]['secret'])->toBe('Secret One');

    conditionalLayoutElement($this->entryType, 'secret')->setUserCondition(adminUserCondition());

    expect(executeAuthenticated($this->user, AUTHENTICATED_ENTRIES_QUERY)['data']['entries'][0])->not->toHaveKey('secret');
    $type = app(Gql::class)->getSchemaDef()->getType('article_Entry');
    expect($type)->toBeInstanceOf(ObjectType::class)
        ->and($type->hasField('secret'))->toBeFalse();

    $admin = UserModel::factory()->createElement(['admin' => true]);
    actingAs(UserModel::findOrFail($admin->id));
    expect(executeAuthenticated($admin, AUTHENTICATED_ENTRIES_QUERY)['data']['entries'][0]['secret'])->toBe('Secret One');

    $gql = app(Gql::class);
    $gql->flushCaches();
    $tokenSchema = new GqlSchema(['name' => 'Token', 'scope' => GqlHelper::createFullAccessSchema()->scope]);
    actingAs(UserModel::findOrFail($this->user->id));
    expect($gql->executeQuery($tokenSchema, AUTHENTICATED_ENTRIES_QUERY)['data']['entries'][0]['secret'])->toBe('Secret One');
});

it('resolves fields hidden by element conditions to null', function (): void {
    conditionalLayoutElement($this->entryType, 'secret')->setElementCondition(entryTitleCondition('One'));

    expect(executeAuthenticated($this->user, AUTHENTICATED_ENTRIES_QUERY)['data']['entries'])->toBe([
        ['title' => 'One', 'secret' => 'Secret One'],
        ['title' => 'Two', 'secret' => null],
    ]);
});

it('resolves hidden non-null fields to null', function (): void {
    conditionalLayoutElement($this->entryType, 'blocks')->setElementCondition(entryTitleCondition('One'));

    $result = executeAuthenticated($this->user, '{ entries(section: "news", title: ["One", "Two"], orderBy: "title ASC") { title ... on article_Entry { blocks { id } } } }');

    expect($result)->not->toHaveKey('errors')
        ->and($result['data']['entries'])->toBe([
            ['title' => 'One', 'blocks' => []],
            ['title' => 'Two', 'blocks' => null],
        ]);
});

it('respects showInForm listeners', function (): void {
    $layoutElement = conditionalLayoutElement($this->entryType, 'secret');
    Event::listen(function (FieldLayoutComponentShowInFormResolving $event) use ($layoutElement): void {
        if ($event->fieldLayoutComponent->uid === $layoutElement->uid) {
            $event->showInForm = $event->element?->title === 'One';
        }
    });

    expect(executeAuthenticated($this->user, AUTHENTICATED_ENTRIES_QUERY)['data']['entries'])->toBe([
        ['title' => 'One', 'secret' => 'Secret One'],
        ['title' => 'Two', 'secret' => null],
    ]);

    expect(executeAuthenticated($this->user, saveSecretMutation($this->entryOne)))->not->toHaveKey('errors')
        ->and(secretValue($this->entryOne))->toBe('Changed')
        ->and(executeAuthenticated($this->user, saveSecretMutation($this->entryTwo))['errors'][0]['message'])
        ->toBe('You don’t have permission to edit the following fields: secret')
        ->and(secretValue($this->entryTwo))->toBe('Secret Two');
});

it('excludes fields locked by edit conditions from mutation arguments', function (): void {
    conditionalLayoutElement($this->entryType, 'secret')->setEditCondition(adminUserCondition());

    expect(executeAuthenticated($this->user, "{ entry(id: {$this->entryOne->id}) { ... on article_Entry { secret } } }")['data']['entry']['secret'])
        ->toBe('Secret One')
        ->and(executeAuthenticated($this->user, saveSecretMutation($this->entryOne))['errors'][0]['message'])
        ->toContain('Unknown argument "secret"')
        ->and(secretValue($this->entryOne))->toBe('Secret One')
        ->and(executeAuthenticated($this->user, "mutation { save_news_article_Entry(id: {$this->entryOne->id}, title: \"Renamed\") { title } }")['data']['save_news_article_Entry']['title'])
        ->toBe('Renamed');
});

it('rejects fields locked by element edit conditions when mutating', function (): void {
    conditionalLayoutElement($this->entryType, 'secret')->setElementEditCondition(entryTitleCondition('One'));

    expect(executeAuthenticated($this->user, saveSecretMutation($this->entryTwo))['errors'][0]['message'])
        ->toBe('You don’t have permission to edit the following fields: secret')
        ->and(secretValue($this->entryTwo))->toBe('Secret Two')
        ->and(executeAuthenticated($this->user, saveSecretMutation($this->entryOne)))->not->toHaveKey('errors')
        ->and(secretValue($this->entryOne))->toBe('Changed');
});

it('respects edit conditions for nested entry fields', function (): void {
    $layoutElement = conditionalLayoutElement($this->blockType, 'subfield');
    $mutation = "mutation { save_news_article_Entry(id: {$this->entryOne->id}, blocks: { sortOrder: [\"new:1\"], entries: [{ block: { id: \"new:1\", title: \"Block\", subfield: \"Nested\" } }] }) { id } }";

    $layoutElement->setEditCondition(adminUserCondition());
    expect(executeAuthenticated($this->user, $mutation)['errors'][0]['message'])->toContain('"subfield"');

    $layoutElement->setEditCondition(null);
    $condition = Entry::createCondition();
    $condition->addConditionRule(new TypeConditionRule(['values' => [$this->otherBlockType->uid]]));
    $layoutElement->setElementEditCondition($condition);
    expect(executeAuthenticated($this->user, $mutation)['errors'][0]['message'])
        ->toBe('You don’t have permission to edit the following fields: subfield');

    $layoutElement->setElementEditCondition(null);
    expect(executeAuthenticated($this->user, $mutation))->not->toHaveKey('errors')
        ->and(entryQuery()->id($this->entryOne->id)->one()->getFieldValue('blocks')->one()->getFieldValue('subfield'))->toBe('Nested');
});

function populateAssetWithUpload(User $user, int $volumeId, Asset $asset, string $fileData, string $filename): void
{
    app(Gql::class)->setActiveSchema(app(Gql::class)->getSchemaForUser($user));
    $resolver = new AssetMutationResolver;
    $resolver->setResolutionData('volume', Volumes::getVolumeById($volumeId));
    $resolver->setResolutionData(ElementMutationResolver::CONTENT_FIELD_KEY, ['description' => Type::string()]);

    new ReflectionMethod($resolver, 'populateElementWithData')->invoke($resolver, $asset, [
        '_file' => ['fileData' => $fileData, 'filename' => $filename],
        'description' => 'Described',
    ]);
}

it('evaluates asset field conditions against the uploaded file', function (ElementConditionRuleInterface $rule, string $fileData, string $filename, ?string $existingFilename): void {
    $description = Field::factory()->create(['handle' => 'description', 'type' => PlainText::class]);
    $volume = Volume::factory()->create([
        'fieldLayoutId' => FieldLayout::factory()->withContentTab([new CustomField(config: ['fieldUid' => $description->uid])])->create(['type' => Asset::class])->id,
    ]);
    Fields::refreshFields();
    Volumes::reset();
    app(UserPermissions::class)->saveUserPermissions($this->user->id, ["viewAssets:$volume->uid", "saveAssets:$volume->uid", "replaceFiles:$volume->uid"]);
    $condition = Asset::createCondition();
    $condition->addConditionRule($rule);
    Volumes::getVolumeById($volume->id)->getFieldLayout()->getFieldByHandle('description')->layoutElement->setElementEditCondition($condition);

    $asset = new Asset(['volumeId' => $volume->id]);
    if ($existingFilename) {
        $asset->id = 999999;
        $asset->uploaderId = $this->user->id;
        $asset->setFilename($existingFilename);
        $asset->kind = FileKind::Text->value;
    }

    populateAssetWithUpload($this->user, $volume->id, $asset, $fileData, $filename);

    expect($asset->getFieldValue('description'))->toBe('Described');
})->with([
    'filename' => [fn () => new FilenameConditionRule(['operator' => '=', 'value' => 'upload.txt']), 'data:text/plain;base64,SGVsbG8=', 'upload.txt', null],
    'file kind' => [fn () => new FileTypeConditionRule(['values' => [FileKind::Image->value]]), 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==', 'upload.png', null],
    'replacement file kind' => [fn () => new FileTypeConditionRule(['values' => [FileKind::Image->value]]), 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==', 'upload.png', 'old.txt'],
]);
