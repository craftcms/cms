<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Expressions\FixedOrderExpression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

afterEach(function () {
    Schema::dropIfExists('values');
});

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

    $values = DB::table('values')->orderBy(new FixedOrderExpression('name', [
        'four',
        'two',
        'three',
        'one',
    ]))->pluck('name')->all();

    expect($values)->toBe(['four', 'two', 'three', 'one']);
});
