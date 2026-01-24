<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Support\Sequence;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

test('next returns 1 on first call', function () {
    $result = Sequence::next('test-seq');

    expect($result)->toBe(1);
    expect(DB::table(Table::SEQUENCES)->where('name', 'test-seq')->value('next'))
        ->toBe(2);
});

test('next increments on subsequent calls', function () {
    expect(Sequence::next('test'))->toBe(1);
    expect(Sequence::next('test'))->toBe(2);
    expect(Sequence::next('test'))->toBe(3);

    expect(DB::table(Table::SEQUENCES)->where('name', 'test')->value('next'))
        ->toBe(4);
});

test('current returns value without incrementing', function () {
    Sequence::next('test');
    Sequence::next('test');

    expect(Sequence::current('test'))->toBe(2);
    expect(Sequence::current('test'))->toBe(2);

    expect(DB::table(Table::SEQUENCES)->where('name', 'test')->value('next'))
        ->toBe(3);
});

test('current returns 0 for non-existent sequence', function () {
    expect(Sequence::current('non-existent'))->toBe(0);

    expect(DB::table(Table::SEQUENCES)->where('name', 'non-existent')->exists())
        ->toBeFalse();
});

test('multiple sequences are independent', function () {
    expect(Sequence::next('seq-a'))->toBe(1);
    expect(Sequence::next('seq-a'))->toBe(2);

    expect(Sequence::next('seq-b'))->toBe(1);

    expect(Sequence::next('seq-c'))->toBe(1);
    expect(Sequence::next('seq-c'))->toBe(2);
    expect(Sequence::next('seq-c'))->toBe(3);

    expect(DB::table(Table::SEQUENCES)->where('name', 'seq-a')->value('next'))->toBe(3);
    expect(DB::table(Table::SEQUENCES)->where('name', 'seq-b')->value('next'))->toBe(2);
    expect(DB::table(Table::SEQUENCES)->where('name', 'seq-c')->value('next'))->toBe(4);
});

test('interleaved next and current calls', function () {
    expect(Sequence::next('test'))->toBe(1);
    expect(Sequence::current('test'))->toBe(1);
    expect(Sequence::next('test'))->toBe(2);
    expect(Sequence::current('test'))->toBe(2);
    expect(Sequence::next('test'))->toBe(3);
    expect(Sequence::current('test'))->toBe(3);
});

test('without length parameter returns int', function () {
    $result = Sequence::next('test');
    expect($result)->toBeInt();

    $current = Sequence::current('test');
    expect($current)->toBeInt();
});

test('with length parameter returns zero-padded string', function (int $num, int $length, string $expected) {
    // Seed sequence - current() returns next-1, so we need next = num + 1
    DB::table(Table::SEQUENCES)->insert(['name' => 'test', 'next' => $num + 1]);

    $result = Sequence::current('test', $length);

    expect($result)->toBe($expected);
    expect($result)->toBeString();
})->with([
    [1, 3, '001'],
    [1, 4, '0001'],
    [42, 5, '00042'],
    [999, 4, '0999'],
]);

test('length shorter than number does not truncate', function () {
    DB::table(Table::SEQUENCES)->insert(['name' => 'test', 'next' => 1001]);

    $result = Sequence::current('test', 2);

    expect($result)->toBe('1000');
    expect($result)->toBeString();
});

test('sequence names with forward slashes', function () {
    $result = Sequence::next('path/to/seq');

    expect($result)->toBe(1);
    expect(DB::table(Table::SEQUENCES)->where('name', 'path/to/seq')->exists())
        ->toBeTrue();
});

test('sequence names with backslashes', function () {
    $result = Sequence::next('namespace\\seq');

    expect($result)->toBe(1);
    expect(DB::table(Table::SEQUENCES)->where('name', 'namespace\\seq')->exists())
        ->toBeTrue();
});

test('sequence names with unicode characters', function (string $name) {
    $result = Sequence::next($name);

    expect($result)->toBe(1);
    expect(DB::table(Table::SEQUENCES)->where('name', $name)->exists())
        ->toBeTrue();
})->with([
    'emoji' => '🔥-sequence',
    'german' => 'sequenz-äöü',
    'greek' => 'κόσμε',
]);

test('first next call inserts row with next=2', function () {
    expect(DB::table(Table::SEQUENCES)->where('name', 'test')->exists())
        ->toBeFalse();

    $result = Sequence::next('test');

    expect($result)->toBe(1);

    $row = DB::table(Table::SEQUENCES)->where('name', 'test')->first();
    expect($row->name)->toBe('test');
    expect($row->next)->toBe(2);
});

test('subsequent next calls increment next column', function () {
    Sequence::next('test');

    expect(DB::table(Table::SEQUENCES)->where('name', 'test')->value('next'))
        ->toBe(2);

    Sequence::next('test');

    expect(DB::table(Table::SEQUENCES)->where('name', 'test')->value('next'))
        ->toBe(3);
});

test('current does not modify database', function () {
    Sequence::next('test');

    $before = DB::table(Table::SEQUENCES)->where('name', 'test')->first();

    Sequence::current('test');
    Sequence::current('test');

    $after = DB::table(Table::SEQUENCES)->where('name', 'test')->first();

    expect($after)->toEqual($before);
});

test('database state after complex sequence of operations', function () {
    Sequence::next('a');
    Sequence::next('b');
    Sequence::next('a');
    Sequence::current('a');
    Sequence::next('b');

    expect(DB::table(Table::SEQUENCES)->where('name', 'a')->value('next'))->toBe(3);
    expect(DB::table(Table::SEQUENCES)->where('name', 'b')->value('next'))->toBe(3);
});

test('next acquires cache lock with correct key', function (string $name, string $expectedLockKey) {
    Cache::spy();

    Sequence::next($name);

    Cache::shouldHaveReceived('lock')->once()->with($expectedLockKey);
})->with([
    ['test', 'seq--test'],
    ['test/path', 'seq--test-path'],
    ['test\\path', 'seq--test-path'],
    ['a/b\\c', 'seq--a-b-c'],
]);

test('handles large sequence numbers', function () {
    DB::table(Table::SEQUENCES)->insert(['name' => 'test', 'next' => 999999]);

    $result = Sequence::next('test');

    expect($result)->toBe(999999);
    expect(DB::table(Table::SEQUENCES)->where('name', 'test')->value('next'))
        ->toBe(1000000);
});

test('formatting works with large numbers', function () {
    DB::table(Table::SEQUENCES)->insert(['name' => 'test', 'next' => 1000001]);

    expect(Sequence::current('test', 10))->toBe('0001000000');
    expect(Sequence::current('test', 5))->toBe('1000000');
});
