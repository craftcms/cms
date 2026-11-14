<?php

namespace crafttests\unit\mcp;

use Closure;
use Craft;
use craft\behaviors\CustomFieldBehavior;
use craft\db\Query;
use craft\db\Table;
use craft\elements\Entry;
use craft\elements\User;
use craft\enums\CmsEdition;
use craft\fieldlayoutelements\CustomField;
use craft\fields\PlainText;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\mcp\ServerFactory;
use craft\models\EntryType;
use craft\models\FieldLayout;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use craft\models\Site;
use craft\test\TestCase;
use Mcp\Server\Transport\StdioTransport;

class StdioTransportTest extends TestCase
{
    private mixed $originalIdentity;

    protected function _before(): void
    {
        $this->originalIdentity = Craft::$app->getUser()->getIdentity();
    }

    protected function _after(): void
    {
        Craft::$app->getUser()->setIdentity($this->originalIdentity);
    }

    public function testProjectConfigChangesAreSavedBeforeTheServerStops(): void
    {
        Craft::$app->getUser()->setIdentity(User::find()->admin()->one());

        $input = tmpfile();
        foreach ([
            ['jsonrpc' => '2.0', 'id' => 'init', 'method' => 'initialize', 'params' => [
                'protocolVersion' => '2025-06-18',
                'capabilities' => (object)[],
                'clientInfo' => ['name' => 'stdio-test', 'version' => '1.0'],
            ]],
            ['jsonrpc' => '2.0', 'method' => 'notifications/initialized'],
            ['jsonrpc' => '2.0', 'id' => 'create', 'method' => 'tools/call', 'params' => [
                'name' => 'configuration_create',
                'arguments' => ['type' => 'site-groups', 'attributes' => ['name' => 'MCP Stdio Archive']],
            ]],
        ] as $message) {
            fwrite($input, json_encode($message) . "\n");
        }
        rewind($input);
        $outputPath = tempnam(sys_get_temp_dir(), 'craft-mcp-stdio-');
        $output = fopen($outputPath, 'w');

        Craft::$app->get('mcp')->getCapabilityContainer()->get(ServerFactory::class)->stdio()->run(new StdioTransport($input, $output));

        $response = file_get_contents($outputPath);
        unlink($outputPath);
        self::assertStringContainsString('"id":"create"', $response);
        self::assertStringNotContainsString('"isError":true', $response);

        // The console request hasn't ended, so only the stdio server could have saved it
        self::assertTrue((new Query())
            ->from(Table::PROJECTCONFIG)
            ->where(['like', 'path', 'siteGroups.%.name', false])
            ->andWhere(['value' => Json::encode('MCP Stdio Archive')])
            ->exists());
    }

    public function testProjectConfigChangesFromOtherProcessesAreSeenBetweenMessages(): void
    {
        Craft::$app->getUser()->setIdentity(User::find()->admin()->one());
        $field = new PlainText(['name' => 'MCP Tagline', 'handle' => 'mcpTagline']);
        Craft::$app->getFields()->saveField($field);
        Craft::$app->getProjectConfig()->flush();
        $uid = $field->uid;
        $identifier = ['type' => 'fields', 'identifier' => ['handle' => 'mcpTagline']];

        $responses = $this->serve([
            'read' => ['configuration_get', $identifier],
            'get' => ['configuration_get', $identifier],
            'update' => ['configuration_update', [...$identifier, 'attributes' => ['name' => 'MCP Tagline 2']]],
        ], [
            // Change the field the way another process, such as `craft project-config/set`, would
            'read' => function() use ($uid): void {
                $db = Craft::$app->getDb();
                $db->createCommand()->update(Table::PROJECTCONFIG, ['value' => Json::encode('Changed elsewhere')], ['path' => "fields.$uid.instructions"])->execute();
                $db->createCommand()->update(Table::FIELDS, ['instructions' => 'Changed elsewhere'], ['uid' => $uid])->execute();
                $this->bumpConfigVersion();
            },
        ]);

        self::assertSame('Changed elsewhere', $responses['get']['result']['structuredContent']['item']['instructions']);
        self::assertNotSame($responses['read']['result']['structuredContent']['revision'], $responses['get']['result']['structuredContent']['revision']);
        self::assertFalse($responses['update']['result']['isError']);
        self::assertEquals(
            ["fields.$uid.instructions" => 'Changed elsewhere', "fields.$uid.name" => 'MCP Tagline 2'],
            array_map([Json::class, 'decode'], (new Query())
                ->select(['value', 'path'])
                ->from(Table::PROJECTCONFIG)
                ->where(['path' => ["fields.$uid.name", "fields.$uid.instructions"]])
                ->orderBy(['path' => SORT_ASC])
                ->indexBy('path')
                ->column()),
        );
    }

    public function testPrimarySiteChangesFromOtherProcessesAreSeenBetweenMessages(): void
    {
        Craft::$app->getUser()->setIdentity(User::find()->admin()->one());
        $site = new Site([
            'groupId' => Craft::$app->getSites()->getPrimarySite()->groupId,
            'name' => 'MCP Second',
            'handle' => 'mcpSecond',
            'language' => 'en-US',
            'hasUrls' => true,
            'baseUrl' => 'https://second.test/',
        ]);
        Craft::$app->getSites()->saveSite($site);
        Craft::$app->getRoutes()->saveRoute(['mcp-second'], 'second', $site->uid);
        Craft::$app->getProjectConfig()->flush();

        $responses = $this->serve([
            'before' => ['configuration_list', ['type' => 'routes']],
            'after' => ['configuration_list', ['type' => 'routes']],
        ], [
            // Make the new site primary the way another process would
            'before' => function() use ($site): void {
                $db = Craft::$app->getDb();
                $db->createCommand()->update(Table::SITES, ['primary' => false])->execute();
                $db->createCommand()->update(Table::SITES, ['primary' => true], ['id' => $site->id])->execute();
                $this->bumpConfigVersion();
            },
        ]);

        $uris = fn(string $id): array => array_column($responses[$id]['result']['structuredContent']['items'], 'uri');
        self::assertNotContains('mcp-second', $uris('before'));
        self::assertContains('mcp-second', $uris('after'));
    }

    public function testUserPermissionChangesFromOtherProcessesAreSeenBetweenMessages(): void
    {
        $edition = Craft::$app->edition;
        Craft::$app->edition = CmsEdition::Pro;
        $user = User::find()->admin()->one();
        Craft::$app->getUser()->setIdentity($user);
        Craft::$app->getUserPermissions()->saveUserPermissions($user->id, []);

        try {
            $responses = $this->serve([
                'before' => ['user_permissions_user_get', ['userId' => $user->id]],
                'after' => ['user_permissions_user_get', ['userId' => $user->id]],
            ], [
                // Grant a permission the way another process would. Direct user permissions aren’t in the project config,
                // so the config version doesn’t change.
                'before' => function() use ($user): void {
                    $db = Craft::$app->getDb();
                    $db->createCommand()->insert(Table::USERPERMISSIONS, ['name' => 'mcpstdioprobe'])->execute();
                    $db->createCommand()->insert(Table::USERPERMISSIONS_USERS, [
                        'permissionId' => $db->getLastInsertID(Table::USERPERMISSIONS),
                        'userId' => $user->id,
                    ])->execute();
                },
            ]);
        } finally {
            Craft::$app->edition = $edition;
        }

        self::assertNotContains('mcpstdioprobe', $responses['before']['result']['structuredContent']['permissions']);
        self::assertContains('mcpstdioprobe', $responses['after']['result']['structuredContent']['permissions']);
    }

    public function testFieldsAddedByOtherProcessesCanBeReadBetweenMessages(): void
    {
        Craft::$app->getUser()->setIdentity(User::find()->admin()->one());
        $field = new PlainText(['name' => 'MCP Subtitle', 'handle' => 'mcpSubtitle']);
        Craft::$app->getFields()->saveField($field);
        $entryType = new EntryType(['name' => 'MCP Stdio', 'handle' => 'mcpStdio']);
        $entryType->setFieldLayout(new FieldLayout([
            'type' => Entry::class,
            'tabs' => [['name' => 'Content', 'elements' => [new CustomField($field)]]],
        ]));
        Craft::$app->getEntries()->saveEntryType($entryType);
        $section = new Section([
            'name' => 'MCP Stdio',
            'handle' => 'mcpStdio',
            'type' => Section::TYPE_CHANNEL,
            'siteSettings' => [new Section_SiteSettings([
                'siteId' => Craft::$app->getSites()->getPrimarySite()->id,
                'hasUrls' => false,
            ])],
        ]);
        $section->setEntryTypes([$entryType]);
        Craft::$app->getEntries()->saveSection($section);
        $entry = new Entry(['sectionId' => $section->id, 'typeId' => $entryType->id, 'title' => 'MCP Stdio']);
        $entry->setFieldValue('mcpSubtitle', 'Hello');
        Craft::$app->getElements()->saveElement($entry);
        Craft::$app->getProjectConfig()->flush();

        try {
            $responses = $this->serve([
                'before' => ['configuration_list', ['type' => 'sites']],
                'get' => ['elements_get', ['type' => 'entries', 'id' => $entry->id, 'fields' => ['mcpSubtitle']]],
            ], [
                // Leave the field’s handle unknown to this process, as if another process had added it
                'before' => function(): void {
                    unset(CustomFieldBehavior::$fieldHandles['mcpSubtitle']);
                    $this->bumpConfigVersion();
                },
            ]);
        } finally {
            CustomFieldBehavior::$fieldHandles['mcpSubtitle'] = true;
        }

        self::assertArrayNotHasKey('error', $responses['get'], json_encode($responses['get']));
        self::assertFalse($responses['get']['result']['isError'] ?? false, json_encode($responses['get']));
        self::assertStringContainsString('Hello', json_encode($responses['get']['result']['structuredContent']));
    }

    /**
     * Runs the stdio server with the given tool calls, running each callback after the response with its ID is written.
     *
     * @param array<string, array{string, array<string, mixed>}> $calls Tool names and arguments, by request ID
     * @param array<string, Closure> $after
     * @return array<string, array<string, mixed>> The responses, by request ID
     */
    private function serve(array $calls, array $after): array
    {
        $input = tmpfile();
        $messages = [
            ['jsonrpc' => '2.0', 'id' => 'init', 'method' => 'initialize', 'params' => [
                'protocolVersion' => '2025-06-18',
                'capabilities' => (object)[],
                'clientInfo' => ['name' => 'stdio-test', 'version' => '1.0'],
            ]],
            ['jsonrpc' => '2.0', 'method' => 'notifications/initialized'],
        ];
        foreach ($calls as $id => [$tool, $arguments]) {
            $messages[] = ['jsonrpc' => '2.0', 'id' => $id, 'method' => 'tools/call', 'params' => ['name' => $tool, 'arguments' => $arguments]];
        }
        foreach ($messages as $message) {
            fwrite($input, json_encode($message) . "\n");
        }
        rewind($input);

        StdioTestOutput::reset($after);
        stream_wrapper_register('stdio-test-output', StdioTestOutput::class);

        try {
            Craft::$app->get('mcp')->getCapabilityContainer()->get(ServerFactory::class)->stdio()->run(new StdioTransport($input, fopen('stdio-test-output://', 'w')));
        } finally {
            stream_wrapper_unregister('stdio-test-output');
        }

        return StdioTestOutput::$responses;
    }

    private function bumpConfigVersion(): void
    {
        Craft::$app->getDb()->createCommand()->update(Table::INFO, ['configVersion' => StringHelper::randomString(12)])->execute();
    }
}

/** Records each response as it's written, then runs the callback registered for it, before the next message is read. */
class StdioTestOutput
{
    /** @var array<string, array<string, mixed>> */
    public static array $responses = [];

    /** @var array<string, Closure> */
    public static array $after = [];

    /** @var resource|null */
    public $context;

    private string $buffer = '';

    /**
     * @param array<string, Closure> $after Callbacks to run after the response with the given ID is written
     */
    public static function reset(array $after): void
    {
        self::$responses = [];
        self::$after = $after;
    }

    public function stream_open(string $path, string $mode, int $options, ?string &$openedPath): bool
    {
        return true;
    }

    public function stream_write(string $data): int
    {
        $this->buffer .= $data;

        while (($end = strpos($this->buffer, "\n")) !== false) {
            $response = Json::decode(substr($this->buffer, 0, $end));
            $this->buffer = substr($this->buffer, $end + 1);
            $id = $response['id'] ?? '';
            self::$responses[$id] = $response;
            (self::$after[$id] ?? null)?->__invoke();
        }

        return strlen($data);
    }
}
