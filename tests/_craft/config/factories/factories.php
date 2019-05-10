<?php
$faker = Faker\Factory::create();

$fm->define(\craft\records\Session::class)->setDefinitions([
    'userId' => '1',
    'token'  => $faker->text(36),
]);
