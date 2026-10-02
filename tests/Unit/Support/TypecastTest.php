<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Support\Typecast;
use Illuminate\Support\MessageBag;

test('scalar properties', function (string $class, string $property, mixed $expected, mixed $value) {
    $config = [
        $property => $value,
    ];

    Typecast::properties($class, $config);

    expect($config)->toBe([
        $property => $expected,
    ]);
})->with([
    [GeneralConfig::class, 'aliases', ['foo', 'bar'], 'foo,bar'],
    [GeneralConfig::class, 'allowAdminChanges', true, 'yes'],
    [GeneralConfig::class, 'allowAdminChanges', false, 'no'],
    [GeneralConfig::class, 'allowAdminChanges', true, 'on'],
    [GeneralConfig::class, 'allowAdminChanges', false, 'off'],
    [GeneralConfig::class, 'allowAdminChanges', true, '1'],
    [GeneralConfig::class, 'allowAdminChanges', false, '0'],
    [GeneralConfig::class, 'allowAdminChanges', true, 'true'],
    [GeneralConfig::class, 'allowAdminChanges', false, 'false'],
    [GeneralConfig::class, 'allowAdminChanges', false, ''],
    [GeneralConfig::class, 'allowAdminChanges', false, 'whatever'],
    [GeneralConfig::class, 'baseCpUrl', null, ''],
    [GeneralConfig::class, 'blowfishHashCost', 123, 123],
    [GeneralConfig::class, 'maxUploadFileSize', 123, '123'],
    [GeneralConfig::class, 'maxUploadFileSize', '123abc', '123abc'],
]);

test('datetime properties', function () {
    $now = now();

    $config = [
        'postDate' => $now->format(DateTime::ATOM),
        'expiryDate' => '',
    ];

    Typecast::properties(Entry::class, $config);

    expect($config['postDate'])->toBeInstanceOf(DateTime::class);
    expect($config['postDate']->getTimestamp())->toBe($now->getTimestamp());
    expect($config['expiryDate'])->toBeNull();
});

test('can identify datetime properties', function () {
    expect(Typecast::isDateTimeProperty(Entry::class, 'postDate'))->toBeTrue();
    expect(Typecast::isDateTimeProperty(Entry::class, 'expiryDate'))->toBeTrue();
    expect(Typecast::isDateTimeProperty(Entry::class, 'title'))->toBeFalse();
    expect(Typecast::isDateTimeProperty(Entry::class, 'doesNotExist'))->toBeFalse();
});

test('enum properties', function () {
    enum Suit: string
    {
        case Hearts = 'H';
        case Diamonds = 'D';
        case Clubs = 'C';
        case Spades = 'S';
    }

    class EnumModel
    {
        public Suit $suit;

        public Suit $anotherSuit;

        public ?Suit $nullableSuit = null;
    }

    $config = [
        'suit' => 'H',
        'anotherSuit' => Suit::Hearts,
        'nullableSuit' => '',
    ];

    Typecast::properties(EnumModel::class, $config);

    expect($config)->toBe([
        'suit' => Suit::Hearts,
        'anotherSuit' => Suit::Hearts,
        'nullableSuit' => null,
    ]);
});

test('configure skips properties without public setters', function () {
    class AsymmetricSetterModel
    {
        public string $public = 'original';

        public private(set) string $privateSet = 'original';

        public protected(set) string $protectedSet = 'original';

        public string $readOnly {
            get => 'original';
        }
    }

    $model = Typecast::configure(new AsymmetricSetterModel, [
        'public' => 'updated',
        'privateSet' => 'updated',
        'protectedSet' => 'updated',
        'readOnly' => 'updated',
    ]);

    expect($model)
        ->public->toBe('updated')
        ->privateSet->toBe('original')
        ->protectedSet->toBe('original')
        ->readOnly->toBe('original');
});

test('configure skips component validation errors', function () {
    class TypecastComponent extends Component {}

    $component = new TypecastComponent([
        'errors' => new MessageBag(['title' => ['Invalid title.']]),
    ]);

    expect($component->errors()->isEmpty())->toBeTrue();
});

test('typed setters take precedence over private backing properties', function () {
    class SetterAcceptsBroaderInputModel extends Component
    {
        public array|string|null $receivedFocalPoint = null;

        private ?array $_focalPoint = null;

        public function getFocalPoint(): ?array
        {
            return $this->_focalPoint;
        }

        public function setFocalPoint(array|string|null $value): void
        {
            $this->receivedFocalPoint = $value;
            $this->_focalPoint = is_string($value) ? ['x' => 0.5, 'y' => 0.5] : $value;
        }
    }

    $model = Typecast::configure(new SetterAcceptsBroaderInputModel, [
        'focalPoint' => '0.5000;0.5000',
    ]);

    expect($model->receivedFocalPoint)->toBe('0.5000;0.5000')
        ->and($model->focalPoint)->toBe(['x' => 0.5, 'y' => 0.5]);
});

test('public properties take precedence over setters', function () {
    class PublicPropertyWithMixedSetterModel extends Component
    {
        public int $count = 0;

        public function setCount(mixed $count): void
        {
            $this->count = $count;
        }
    }

    $model = Typecast::configure(new PublicPropertyWithMixedSetterModel, [
        'count' => '42',
    ]);

    expect($model->count)->toBe(42);
});

test('asset focal point strings are preserved for the setter', function () {
    $config = [
        'focalPoint' => '0.5000;0.5000',
    ];

    Typecast::properties(Asset::class, $config);

    expect($config)->toBe([
        'focalPoint' => '0.5000;0.5000',
    ]);
});

test('untyped setters can use private backing property types', function () {
    class UntypedSetterPrivateBackingModel extends Component
    {
        private ?int $_count = null;

        public function getCount(): ?int
        {
            return $this->_count;
        }

        public function setCount($value): void
        {
            $this->_count = $value;
        }
    }

    $model = Typecast::configure(new UntypedSetterPrivateBackingModel, [
        'count' => '42',
    ]);

    expect($model->count)->toBe(42);
});

test('setters are used for private backing properties with the same name', function () {
    class SameNamePrivateBackingModel extends Component
    {
        private ?int $count = null;

        public function getCount(): ?int
        {
            return $this->count;
        }

        public function setCount(?int $value): void
        {
            $this->count = $value;
        }
    }

    $model = Typecast::configure(new SameNamePrivateBackingModel, [
        'count' => '42',
    ]);

    expect($model->count)->toBe(42);
});

test('isIntOrFloat', function (bool $expected, mixed $value) {
    expect(Typecast::isIntOrFloat($value))->toBe($expected);
})->with([
    [true, 0],
    [true, 0.5],
    [true, 10],
    [true, 10.5],
    [true, '0'],
    [true, '0.5'],
    [true, '0.50'],
    [true, '10'],
    [true, '10.5'],
    [false, '00'],
    [false, ' 0'],
    [false, '00.5'],
    [false, ' 0.5'],
    [false, ' '],
    [false, 'y'],
    [false, true],
    [false, []],
]);
