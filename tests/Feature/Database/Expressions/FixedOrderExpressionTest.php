<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Expressions\FixedOrderExpression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('can order by a fixed order', function () {
    Schema::create('values', function (Blueprint $table) {
        $table->id();
        $table->string('name');
    });

    DB::table('values')->insert([
        ['name' => 'one'],
        ['name' => 'three'],
        ['name' => 'two'],
        ['name' => 'four'],
    ]);

    $values = DB::table('values')->get();
    expect($values[0]->name)->toBe('one');
    expect($values[1]->name)->toBe('three');
    expect($values[2]->name)->toBe('two');
    expect($values[3]->name)->toBe('four');

    $values = DB::table('values')->orderBy(new FixedOrderExpression('name', [
        'one',
        'two',
        'three',
        'four',
    ]))->get();

    expect($values[0]->name)->toBe('one');
    expect($values[1]->name)->toBe('two');
    expect($values[2]->name)->toBe('three');
    expect($values[3]->name)->toBe('four');

    Schema::drop('values');
});
