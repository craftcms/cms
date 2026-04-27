<?php

declare(strict_types=1);

use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Component\ComponentHelper;
use CraftCms\Cms\Component\Contracts\ComponentInterface;
use CraftCms\Cms\Component\Exceptions\MissingComponentException;
use CraftCms\Cms\Plugin\Plugins;

class StubComponent extends Component
{
    public ?string $foo = null;

    public int $bar = 0;

    #[Override]
    public static function displayName(): string
    {
        return 'Stub Component';
    }
}

class StubChildComponent extends StubComponent
{
    public ?string $baz = null;
}

class DependencyHeavyStubComponent extends Component
{
    public mixed $dependency1 = null;

    public mixed $dependency2 = null;

    public mixed $settingsDependency = null;

    #[Override]
    public static function displayName(): string
    {
        return 'Dependency Heavy Stub';
    }
}

class DatetimeStubComponent extends Component
{
    public DateTime $startsAt;

    public ?DateTime $endsAt = null;

    public string $title = '';

    public DateTimeImmutable $immutableAt;

    public DateTime|int $unionAt;

    public static ?DateTime $staticAt = null;

    protected ?DateTime $hiddenAt = null;

    #[Override]
    public static function displayName(): string
    {
        return 'Datetime Stub';
    }
}

class DatetimeChildStubComponent extends DatetimeStubComponent
{
    public DateTime $publishedAt;
}

class NonComponent {}

abstract class AbstractStubComponent extends Component
{
    #[Override]
    public static function displayName(): string
    {
        return 'Abstract Stub';
    }
}

describe('validateComponentClass', function () {
    test('returns true for a valid component class', function () {
        expect(ComponentHelper::validateComponentClass(StubComponent::class))->toBeTrue();
    });

    test('returns true for a child component class', function () {
        expect(ComponentHelper::validateComponentClass(StubChildComponent::class))->toBeTrue();
    });

    test('returns false for a nonexistent class', function () {
        expect(ComponentHelper::validateComponentClass('App\NonExistent\FakeClass'))->toBeFalse();
    });

    test('throws MissingComponentException for a nonexistent class when throwException is true', function () {
        ComponentHelper::validateComponentClass('App\NonExistent\FakeClass', throwException: true);
    })->throws(MissingComponentException::class, "Unable to find component class 'App\\NonExistent\\FakeClass'.");

    test('returns false for a class that does not implement ComponentInterface', function () {
        expect(ComponentHelper::validateComponentClass(NonComponent::class))->toBeFalse();
    });

    test('throws RuntimeException for a class that does not implement ComponentInterface when throwException is true', function () {
        ComponentHelper::validateComponentClass(NonComponent::class, throwException: true);
    })->throws(RuntimeException::class, 'does not implement ComponentInterface');

    test('returns true when instanceOf matches', function () {
        expect(ComponentHelper::validateComponentClass(StubChildComponent::class, StubComponent::class))->toBeTrue();
    });

    test('returns false when instanceOf does not match', function () {
        expect(ComponentHelper::validateComponentClass(StubComponent::class, StubChildComponent::class))->toBeFalse();
    });

    test('throws RuntimeException when instanceOf does not match and throwException is true', function () {
        ComponentHelper::validateComponentClass(StubComponent::class, StubChildComponent::class, throwException: true);
    })->throws(RuntimeException::class, 'is not an instance of');

    test('returns false when class belongs to a disabled plugin', function () {
        $pluginsMock = Mockery::mock(Plugins::class);
        $pluginsMock->shouldReceive('getPluginHandleByClass')->andReturn('my-plugin');
        $pluginsMock->shouldReceive('isPluginEnabled')->with('my-plugin')->andReturn(false);
        app()->instance(Plugins::class, $pluginsMock);

        expect(ComponentHelper::validateComponentClass(StubComponent::class))->toBeFalse();
    });

    test('throws MissingComponentException for a disabled plugin class when throwException is true', function () {
        $pluginsMock = Mockery::mock(Plugins::class);
        $pluginsMock->shouldReceive('getPluginHandleByClass')->andReturn('my-plugin');
        $pluginsMock->shouldReceive('isPluginEnabled')->with('my-plugin')->andReturn(false);
        $pluginsMock->shouldReceive('isPluginInstalled')->with('my-plugin')->andReturn(true);
        $pluginsMock->shouldReceive('getComposerPluginInfo')->with('my-plugin')->andReturn(['name' => 'My Plugin']);
        app()->instance(Plugins::class, $pluginsMock);

        ComponentHelper::validateComponentClass(StubComponent::class, throwException: true);
    })->throws(MissingComponentException::class, 'belongs to a disabled plugin (My Plugin)');

    test('throws MissingComponentException for an uninstalled plugin class when throwException is true', function () {
        $pluginsMock = Mockery::mock(Plugins::class);
        $pluginsMock->shouldReceive('getPluginHandleByClass')->andReturn('my-plugin');
        $pluginsMock->shouldReceive('isPluginEnabled')->with('my-plugin')->andReturn(false);
        $pluginsMock->shouldReceive('isPluginInstalled')->with('my-plugin')->andReturn(false);
        $pluginsMock->shouldReceive('getComposerPluginInfo')->with('my-plugin')->andReturn(['name' => 'My Plugin']);
        app()->instance(Plugins::class, $pluginsMock);

        ComponentHelper::validateComponentClass(StubComponent::class, throwException: true);
    })->throws(MissingComponentException::class, 'belongs to an uninstalled plugin (My Plugin)');

    test('uses plugin handle as fallback name when composer info has no name', function () {
        $pluginsMock = Mockery::mock(Plugins::class);
        $pluginsMock->shouldReceive('getPluginHandleByClass')->andReturn('my-plugin');
        $pluginsMock->shouldReceive('isPluginEnabled')->with('my-plugin')->andReturn(false);
        $pluginsMock->shouldReceive('isPluginInstalled')->with('my-plugin')->andReturn(false);
        $pluginsMock->shouldReceive('getComposerPluginInfo')->with('my-plugin')->andReturn([]);
        app()->instance(Plugins::class, $pluginsMock);

        expect(fn () => ComponentHelper::validateComponentClass(StubComponent::class, throwException: true))
            ->toThrow(MissingComponentException::class, 'belongs to an uninstalled plugin (my-plugin)');
    });

    test('returns false when instanceOf class does not exist', function () {
        expect(ComponentHelper::validateComponentClass(StubComponent::class, 'Non\\Existent\\ParentClass'))->toBeFalse();
    });

    test('throws RuntimeException when instanceOf class does not exist and throwException is true', function () {
        ComponentHelper::validateComponentClass(StubComponent::class, 'Non\\Existent\\ParentClass', throwException: true);
    })->throws(RuntimeException::class, 'is not an instance of');

    test('returns true when class belongs to an enabled plugin', function () {
        $pluginsMock = Mockery::mock(Plugins::class);
        $pluginsMock->shouldReceive('getPluginHandleByClass')->andReturn('my-plugin');
        $pluginsMock->shouldReceive('isPluginEnabled')->with('my-plugin')->andReturn(true);
        app()->instance(Plugins::class, $pluginsMock);

        expect(ComponentHelper::validateComponentClass(StubComponent::class))->toBeTrue();
    });

    test('returns true when class does not belong to any plugin', function () {
        $pluginsMock = Mockery::mock(Plugins::class);
        $pluginsMock->shouldReceive('getPluginHandleByClass')->andReturn(null);
        app()->instance(Plugins::class, $pluginsMock);

        expect(ComponentHelper::validateComponentClass(StubComponent::class))->toBeTrue();
    });
});

describe('createComponent', function () {
    test('creates a component from a class string', function () {
        $component = ComponentHelper::createComponent(StubComponent::class);

        expect($component)->toBeInstanceOf(StubComponent::class)
            ->and($component->foo)->toBeNull()
            ->and($component->bar)->toBe(0);
    });

    test('creates a component from a config array with type', function () {
        $component = ComponentHelper::createComponent([
            'type' => StubComponent::class,
            'foo' => 'hello',
            'bar' => 42,
        ]);

        expect($component)->toBeInstanceOf(StubComponent::class)
            ->and($component->foo)->toBe('hello')
            ->and($component->bar)->toBe(42);
    });

    test('strips __class from config', function () {
        $component = ComponentHelper::createComponent([
            'type' => StubComponent::class,
            '__class' => 'some-legacy-class',
            'foo' => 'test',
        ]);

        expect($component)->toBeInstanceOf(StubComponent::class)
            ->and($component->foo)->toBe('test');
    });

    test('throws RuntimeException when config array is missing type', function () {
        ComponentHelper::createComponent(['foo' => 'bar']);
    })->throws(RuntimeException::class, 'did not specify a class');

    test('throws RuntimeException when config array has empty type', function () {
        ComponentHelper::createComponent(['type' => '', 'foo' => 'bar']);
    })->throws(RuntimeException::class, 'did not specify a class');

    test('throws MissingComponentException for a nonexistent class', function () {
        ComponentHelper::createComponent('App\NonExistent\FakeClass');
    })->throws(MissingComponentException::class);

    test('throws RuntimeException for a non-component class', function () {
        ComponentHelper::createComponent(NonComponent::class);
    })->throws(RuntimeException::class, 'does not implement ComponentInterface');

    test('validates instanceOf constraint', function () {
        $component = ComponentHelper::createComponent(StubChildComponent::class, StubComponent::class);

        expect($component)->toBeInstanceOf(StubChildComponent::class);
    });

    test('throws RuntimeException when instanceOf constraint fails', function () {
        ComponentHelper::createComponent(StubComponent::class, StubChildComponent::class);
    })->throws(RuntimeException::class, 'is not an instance of');

    test('merges settings into component config', function () {
        $component = ComponentHelper::createComponent([
            'type' => StubComponent::class,
            'settings' => [
                'foo' => 'from-settings',
                'bar' => 99,
            ],
        ]);

        expect($component->foo)->toBe('from-settings')
            ->and($component->bar)->toBe(99);
    });

    test('merges JSON-encoded settings into component config', function () {
        $component = ComponentHelper::createComponent([
            'type' => StubComponent::class,
            'settings' => json_encode(['foo' => 'json-value', 'bar' => 7]),
        ]);

        expect($component->foo)->toBe('json-value')
            ->and($component->bar)->toBe(7);
    });

    test('top-level config overrides settings values', function () {
        $component = ComponentHelper::createComponent([
            'type' => StubComponent::class,
            'foo' => 'top-level',
            'settings' => [
                'foo' => 'from-settings',
            ],
        ]);

        expect($component->foo)->toBe('from-settings');
    });

    test('typecasts properties during creation', function () {
        $component = ComponentHelper::createComponent([
            'type' => StubComponent::class,
            'bar' => '123',
        ]);

        expect($component->bar)->toBe(123);
    });

    test('creates child component with parent instanceOf', function () {
        $component = ComponentHelper::createComponent([
            'type' => StubChildComponent::class,
            'foo' => 'parent-prop',
            'baz' => 'child-prop',
        ], StubComponent::class);

        expect($component)->toBeInstanceOf(StubChildComponent::class)
            ->and($component->foo)->toBe('parent-prop')
            ->and($component->baz)->toBe('child-prop');
    });

    test('creates a dependency-heavy component with properties and settings', function () {
        $component = ComponentHelper::createComponent([
            'type' => DependencyHeavyStubComponent::class,
            'dependency1' => 'value1',
            'dependency2' => 'value2',
            'settings' => [
                'settingsDependency' => 'from-settings',
            ],
        ]);

        expect($component)->toBeInstanceOf(DependencyHeavyStubComponent::class)
            ->and($component->dependency1)->toBe('value1')
            ->and($component->dependency2)->toBe('value2')
            ->and($component->settingsDependency)->toBe('from-settings');
    });

    test('throws RuntimeException when instanceOf is a nonexistent class', function () {
        ComponentHelper::createComponent(
            ['type' => StubChildComponent::class],
            'Non\\Existent\\ParentClass',
        );
    })->throws(RuntimeException::class, 'is not an instance of');

    test('created component implements ComponentInterface', function () {
        $component = ComponentHelper::createComponent(StubComponent::class);

        expect($component)->toBeInstanceOf(ComponentInterface::class);
    });
});

describe('mergeSettings', function () {
    test('returns config as-is when no settings key exists', function () {
        $config = ['foo' => 'bar', 'baz' => 42];

        expect(ComponentHelper::mergeSettings($config))->toBe(['foo' => 'bar', 'baz' => 42]);
    });

    test('merges array settings into config', function () {
        $config = [
            'foo' => 'bar',
            'settings' => ['baz' => 'qux', 'num' => 1],
        ];

        $result = ComponentHelper::mergeSettings($config);

        expect($result)->toBe(['foo' => 'bar', 'baz' => 'qux', 'num' => 1])
            ->and($result)->not->toHaveKey('settings');
    });

    test('merges JSON string settings into config', function () {
        $config = [
            'foo' => 'bar',
            'settings' => json_encode(['baz' => 'qux', 'num' => 1]),
        ];

        $result = ComponentHelper::mergeSettings($config);

        expect($result)->toBe(['foo' => 'bar', 'baz' => 'qux', 'num' => 1])
            ->and($result)->not->toHaveKey('settings');
    });

    test('returns config without settings key when JSON settings decode to non-array', function () {
        $config = [
            'foo' => 'bar',
            'settings' => json_encode('just a string'),
        ];

        $result = ComponentHelper::mergeSettings($config);

        expect($result)->toBe(['foo' => 'bar'])
            ->and($result)->not->toHaveKey('settings');
    });

    test('settings values override existing config values via array_merge', function () {
        $config = [
            'foo' => 'original',
            'settings' => ['foo' => 'overridden'],
        ];

        $result = ComponentHelper::mergeSettings($config);

        expect($result['foo'])->toBe('overridden');
    });

    test('handles null settings value', function () {
        $config = [
            'foo' => 'bar',
            'settings' => null,
        ];

        $result = ComponentHelper::mergeSettings($config);

        expect($result)->toBe(['foo' => 'bar']);
    });

    test('removes settings key from result', function () {
        $config = [
            'settings' => ['foo' => 'bar'],
        ];

        $result = ComponentHelper::mergeSettings($config);

        expect($result)->not->toHaveKey('settings')
            ->and($result)->toHaveKey('foo');
    });

    test('handles empty array settings', function () {
        $config = [
            'foo' => 'bar',
            'settings' => [],
        ];

        $result = ComponentHelper::mergeSettings($config);

        expect($result)->toBe(['foo' => 'bar']);
    });

    test('handles empty JSON object settings', function () {
        $config = [
            'foo' => 'bar',
            'settings' => '{}',
        ];

        $result = ComponentHelper::mergeSettings($config);

        expect($result)->toBe(['foo' => 'bar']);
    });

    test('handles nested array settings', function () {
        $config = [
            'settings' => ['nested' => ['key' => 'value']],
        ];

        $result = ComponentHelper::mergeSettings($config);

        expect($result)->toBe(['nested' => ['key' => 'value']]);
    });

    test('handles empty config with no settings', function () {
        expect(ComponentHelper::mergeSettings([]))->toBe([]);
    });

    test('does not process settings inside nested arrays', function () {
        $config = [
            [
                'name' => 'Component',
                'settings' => ['setting1' => 'stuff'],
            ],
        ];

        $result = ComponentHelper::mergeSettings($config);

        expect($result)->toBe($config);
    });

    test('does not affect indexed arrays containing the string settings', function () {
        $config = ['settings'];

        expect(ComponentHelper::mergeSettings($config))->toBe(['settings']);
    });
});

describe('datetimeAttributes', function () {
    test('returns all public datetime attributes including inherited ones', function () {
        expect(ComponentHelper::datetimeAttributes(new DatetimeChildStubComponent))
            ->toEqualCanonicalizing(['startsAt', 'endsAt', 'publishedAt']);
    });

    test('excludes non-datetime, union, static, and non-public properties', function () {
        expect(ComponentHelper::datetimeAttributes(new DatetimeStubComponent))
            ->not->toContain('title')
            ->not->toContain('immutableAt')
            ->not->toContain('unionAt')
            ->not->toContain('staticAt')
            ->not->toContain('hiddenAt');
    });
});
