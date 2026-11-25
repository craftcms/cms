<?php

declare(strict_types=1);

use craft\events\DefineGqlArgumentsEvent;
use craft\events\ExecuteGqlQueryEvent;
use craft\events\RegisterGqlArgumentHandlersEvent;
use craft\events\RegisterGqlDirectivesEvent;
use craft\events\RegisterGqlMutationsEvent;
use craft\events\RegisterGqlQueriesEvent;
use craft\events\RegisterGqlTypesEvent;
use craft\gql\ArgumentManager as LegacyArgumentManager;
use craft\gql\base\ElementArguments as LegacyElementArguments;
use craft\gql\base\StructureElementArguments as LegacyStructureElementArguments;
use craft\gql\TypeLoader as LegacyTypeLoader;
use craft\helpers\Gql as LegacyGqlHelper;
use craft\models\GqlSchema;
use craft\models\GqlToken as LegacyGqlToken;
use craft\services\Gql as LegacyGql;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Gql\ArgumentManager;
use CraftCms\Cms\Gql\Contracts\ArgumentHandlerInterface;
use CraftCms\Cms\Gql\Data\GqlToken;
use CraftCms\Cms\Gql\Directives\ParseRefs;
use CraftCms\Cms\Gql\Gql;
use CraftCms\Cms\Gql\GqlArguments;
use CraftCms\Cms\Gql\GqlDirectives;
use CraftCms\Cms\Gql\GqlEntityRegistry;
use CraftCms\Cms\Gql\GqlHelper;
use CraftCms\Cms\Http\Controllers\Gql\SchemasController;
use CraftCms\Cms\Tests\TestClasses\Gql\MockDirective;
use CraftCms\Cms\Tests\TestClasses\Gql\MockType;
use CraftCms\Cms\User\Models\User;
use CraftCms\Yii2Adapter\Tests\DatabaseTestCase;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Facades\Session;
use Inertia\Testing\AssertableInertia;
use yii\base\Event;

uses(DatabaseTestCase::class);

beforeEach(function() {
    app(Gql::class)->flushCaches();
    app(Gql::class)->setActiveSchema(new GqlSchema());
    Cms::config()->enableGraphqlCaching = false;
});

it('bridges legacy query registration listeners', function() {
    $handler = function(RegisterGqlQueriesEvent $event) {
        $event->queries['legacyMockQuery'] = [
            'type' => Type::string(),
            'args' => [],
            'resolve' => static fn() => 'legacy',
        ];
    };

    Event::on(LegacyGql::class, LegacyGql::EVENT_REGISTER_GQL_QUERIES, $handler);

    try {
        $queries = app(Gql::class)->getSchemaDef()->getQueryType()->getFields();

        expect($queries)->toHaveKey('legacyMockQuery');
    } finally {
        Event::off(LegacyGql::class, LegacyGql::EVENT_REGISTER_GQL_QUERIES, $handler);
    }
});

it('bridges legacy mutation registration listeners', function() {
    $handler = function(RegisterGqlMutationsEvent $event) {
        $event->mutations['legacyMockMutation'] = [
            'type' => Type::string(),
            'args' => [],
            'resolve' => static fn() => 'legacy',
        ];
    };

    Event::on(LegacyGql::class, LegacyGql::EVENT_REGISTER_GQL_MUTATIONS, $handler);

    try {
        expect(app(Gql::class)->getSchemaDef()->getMutationType()->getFields())
            ->toHaveKey('legacyMockMutation');
    } finally {
        Event::off(LegacyGql::class, LegacyGql::EVENT_REGISTER_GQL_MUTATIONS, $handler);
    }
});

it('bridges legacy type registration listeners', function() {
    $handler = function(RegisterGqlTypesEvent $event) {
        $event->types[] = MockType::class;
    };

    Event::on(LegacyGql::class, LegacyGql::EVENT_REGISTER_GQL_TYPES, $handler);

    try {
        expect(app(Gql::class)->getSchemaDef(null, true)->getType(MockType::getName()))->not()->toBeNull();
    } finally {
        Event::off(LegacyGql::class, LegacyGql::EVENT_REGISTER_GQL_TYPES, $handler);
    }
});

it('applies legacy directive listeners to each schema without changing the registry', function() {
    app(GqlDirectives::class)->register(AdapterGqlDirective::class);

    $seenDirectives = [];
    $handler = function(RegisterGqlDirectivesEvent $event) use (&$seenDirectives) {
        $seenDirectives[] = $event->directives;
        $event->directives = [];
    };

    Event::on(LegacyGql::class, LegacyGql::EVENT_REGISTER_GQL_DIRECTIVES, $handler);

    try {
        $gql = app(Gql::class);
        $gql->getSchemaDef(new GqlSchema(), true);
        $gql->flushCaches();
        $gql->getSchemaDef(new GqlSchema([
            'scope' => ['directive:parseRefs'],
        ]), true);

        expect($seenDirectives)->toHaveCount(2)
            ->and($seenDirectives[0])->toContain(AdapterGqlDirective::class)
            ->not()->toContain(ParseRefs::class)
            ->and($seenDirectives[1])->toContain(AdapterGqlDirective::class, ParseRefs::class)
            ->and(app(GqlDirectives::class)->types())->toContain(AdapterGqlDirective::class);
    } finally {
        Event::off(LegacyGql::class, LegacyGql::EVENT_REGISTER_GQL_DIRECTIVES, $handler);
    }
});

it('uses configured legacy handler instances independently for each manager', function() {
    app(GqlArguments::class)->register('adapter', AdapterArgumentHandler::class);

    $registeredHandlerWasVisible = false;
    $handler = function(RegisterGqlArgumentHandlersEvent $event) use (&$registeredHandlerWasVisible) {
        $registeredHandlerWasVisible = $event->handlers['adapter'] === AdapterArgumentHandler::class;
        $event->handlers['adapter'] = new LegacyReplacementArgumentHandler(configuration: 'configured');
    };

    Event::on(LegacyArgumentManager::class, LegacyArgumentManager::EVENT_DEFINE_GQL_ARGUMENT_HANDLERS, $handler);

    try {
        $firstResult = new LegacyArgumentManager()->prepareArguments(['adapter' => true]);
        $secondResult = new LegacyArgumentManager()->prepareArguments(['adapter' => true]);

        expect($firstResult)->toMatchArray([
            'handledBy' => 'legacy',
            'configuration' => 'configured',
            'calls' => 1,
            'managerBound' => true,
        ])
            ->and($secondResult)->toMatchArray([
                'handledBy' => 'legacy',
                'configuration' => 'configured',
                'calls' => 1,
                'managerBound' => true,
            ])
            ->and($registeredHandlerWasVisible)->toBeTrue();
    } finally {
        Event::off(LegacyArgumentManager::class, LegacyArgumentManager::EVENT_DEFINE_GQL_ARGUMENT_HANDLERS, $handler);
    }
});

it('bridges legacy before-execute listeners', function() {
    $schema = app(Gql::class)->getPublicSchema();
    $handler = function(ExecuteGqlQueryEvent $event) {
        $event->result = ['data' => 'legacy override'];
    };

    Event::on(LegacyGql::class, LegacyGql::EVENT_BEFORE_EXECUTE_GQL_QUERY, $handler);

    try {
        expect(app(Gql::class)->executeQuery($schema, '{ping}'))->toBe(['data' => 'legacy override']);
    } finally {
        Event::off(LegacyGql::class, LegacyGql::EVENT_BEFORE_EXECUTE_GQL_QUERY, $handler);
    }
});

it('keeps the legacy gql helper working against the new service', function() {
    app(Gql::class)->setActiveSchema(new GqlSchema([
        'scope' => ['sections.news:read'],
    ]));

    expect(LegacyGqlHelper::canSchema('sections.news'))->toBeTrue()
        ->and(LegacyGqlHelper::isSchemaAwareOf('sections.news'))->toBeTrue();
});

it('returns gql token aliases from the legacy gql service', function() {
    $modernToken = app(Gql::class)->getPublicToken();
    $legacyToken = Craft::$app->getGql()->getPublicToken();

    expect($modernToken)->toBeInstanceOf(GqlToken::class)
        ->and($modernToken)->toBeInstanceOf(LegacyGqlToken::class)
        ->and($legacyToken)->toBeInstanceOf(LegacyGqlToken::class)
        ->and($legacyToken?->getSchema())->toBeInstanceOf(GqlSchema::class);
});

it('shares registry and loader state across modern and legacy namespaces', function() {
    GqlEntityRegistry::createEntity('SharedType', new ObjectType([
        'name' => 'SharedType',
        'fields' => [],
    ]));

    expect(LegacyTypeLoader::loadType('SharedType'))->toBeInstanceOf(ObjectType::class);
});

it('gives legacy element argument classes their Craft 5 draft, revision, and structure arguments', function() {
    app(Gql::class)->setActiveSchema(GqlHelper::createFullAccessSchema());
    $structureArguments = LegacyGqlCompatibilityStructureArguments::getArguments();
    $elementArguments = LegacyGqlCompatibilityElementArguments::getArguments();

    expect($structureArguments)->toHaveKeys(['level', 'ancestorOf', 'positionedAfter', 'status', 'revisions'])
        ->and($structureArguments)->not()->toHaveKey('drafts')
        ->and($elementArguments)->toHaveKeys(['drafts', 'draftOf', 'revisions', 'revisionOf', 'status', 'title'])
        ->and($elementArguments)->not()->toHaveKey('level');
});

it('lets legacy define-arguments listeners change draft and revision arguments', function() {
    app(Gql::class)->setActiveSchema(GqlHelper::createFullAccessSchema());

    $handler = function(DefineGqlArgumentsEvent $event) {
        unset($event->arguments['drafts']);
        $event->arguments['revisions']['description'] = 'Changed by a listener.';
    };

    Event::on(LegacyElementArguments::class, LegacyElementArguments::EVENT_DEFINE_ARGUMENTS, $handler);

    try {
        $arguments = LegacyGqlCompatibilityElementArguments::getArguments();

        expect($arguments)->not()->toHaveKey('drafts')
            ->and($arguments)->toHaveKey('draftOf')
            ->and($arguments['revisions']['description'])->toBe('Changed by a listener.');
    } finally {
        Event::off(LegacyElementArguments::class, LegacyElementArguments::EVENT_DEFINE_ARGUMENTS, $handler);
    }
});

it('offers the @transform directive permission on the schema screen when the legacy setting disables it', function() {
    Cms::config()->disableGraphqlTransformDirective = true;
    $this->actingAs(User::factory()->admin()->create());
    Session::passwordConfirmed();

    $this->get(action([SchemasController::class, 'create']))
        ->assertInertia(fn(AssertableInertia $page) => $page
            ->where('permissions', fn($permissions) => collect($permissions)
                ->pluck('permissions')
                ->contains(fn(array $groupPermissions) => array_key_exists('directive:transform', $groupPermissions))));
});

class AdapterGqlDirective extends MockDirective
{
    public static function name(): string
    {
        return 'adapterRegistry';
    }
}

class AdapterArgumentHandler implements ArgumentHandlerInterface
{
    public function handleArgumentCollection(array $argumentList = []): array
    {
        $argumentList['handledBy'] = 'modern';

        return $argumentList;
    }

    public function setArgumentManager(ArgumentManager $argumentManager): void
    {
    }
}

class LegacyReplacementArgumentHandler extends AdapterArgumentHandler
{
    public ?ArgumentManager $argumentManager = null;

    private int $calls = 0;

    public function __construct(
        public string $configuration = '',
    ) {
    }

    public function handleArgumentCollection(array $argumentList = []): array
    {
        $argumentList['handledBy'] = 'legacy';
        $argumentList['configuration'] = $this->configuration;
        $argumentList['calls'] = ++$this->calls;
        $argumentList['managerBound'] = $this->argumentManager !== null;

        return $argumentList;
    }

    public function setArgumentManager(ArgumentManager $argumentManager): void
    {
        $this->argumentManager = $argumentManager;
    }
}

class LegacyGqlCompatibilityElementArguments extends LegacyElementArguments
{
}

class LegacyGqlCompatibilityStructureArguments extends LegacyStructureElementArguments
{
    public static function getDraftArguments(): array
    {
        return [];
    }
}
