<?php

use craft\records\Session;
use Faker\Factory;

$faker = Factory::create();

$fm->define(Session::class)->setDefinitions([
    'userId' => '1',
    'token' => $faker->text(36),
]);
