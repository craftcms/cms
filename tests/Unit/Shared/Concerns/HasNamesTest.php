<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Shared\Concerns\HasNames;

beforeEach(function () {
    $this->class = new class
    {
        use HasNames;

        public function save(): void
        {
            $this->prepareNamesForSave();
        }
    };
});

test('names', function (array $config, array $expected, array $suffixes = [], array $salutations = [], array $lastNamePrefixes = []) {
    foreach ($config as $attr => $val) {
        $this->class->$attr = $val;
    }

    Cms::config()
        ->extraNameSuffixes($suffixes)
        ->extraNameSalutations($salutations)
        ->extraLastNamePrefixes($lastNamePrefixes);

    $this->class->save();

    foreach ($expected as $attr => $val) {
        expect($this->class->$attr)->toBe($val);
    }
})->with([
    'onlyFullName' => [
        ['fullName' => 'Dr. Emmett Brown'],
        ['fullName' => 'Dr. Emmett Brown', 'firstName' => 'Emmett', 'lastName' => 'Brown'],
    ],
    'onlyFirstName' => [
        ['fullName' => 'Emmett'],
        ['fullName' => 'Emmett', 'firstName' => 'Emmett', 'lastName' => null],
    ],
    'lastNamePrefix' => [
        ['fullName' => 'Emmett von Brown'],
        ['fullName' => 'Emmett von Brown', 'firstName' => 'Emmett', 'lastName' => 'von Brown'],
    ],
    'lastNamePrefixWithWordingChange' => [
        ['fullName' => 'Emmett von Brown'],
        ['fullName' => 'Emmett von Brown', 'firstName' => 'Emmett', 'lastName' => 'Vonilla Brown'],
        [],
        [],
        ['von' => 'Vonilla'],
    ],
    'joinedFirstAndLast' => [
        ['firstName' => 'Emmett', 'lastName' => 'Brown'],
        ['fullName' => 'Emmett Brown', 'firstName' => 'Emmett', 'lastName' => 'Brown'],
    ],

    'expectedWrongLastName' => [
        ['fullName' => 'Emmett Prefix Brown'],
        ['fullName' => 'Emmett Prefix Brown', 'firstName' => 'Emmett', 'lastName' => 'Brown'],
        // The following test solves this case
    ],
    'suffixFromConfig' => [
        ['fullName' => 'Emmett Brown Suffix'],
        ['fullName' => 'Emmett Brown Suffix', 'firstName' => 'Emmett', 'lastName' => 'Brown'],
        ['Suffix'],
    ],
    'salutationFromConfig' => [
        ['fullName' => 'Salutation Emmett Brown'],
        ['fullName' => 'Salutation Emmett Brown', 'firstName' => 'Emmett', 'lastName' => 'Brown'],
        [],
        ['Salutation'],
    ],
    'prefixFromConfig' => [
        ['fullName' => 'Emmett Prefix Brown'],
        ['fullName' => 'Emmett Prefix Brown', 'firstName' => 'Emmett', 'lastName' => 'Prefix Brown'],
        [],
        [],
        ['Prefix'],
    ],
    // https://github.com/craftcms/cms/issues/14723
    'maintainCasing1' => [
        ['fullName' => 'Eddie Van Halen'],
        ['fullName' => 'Eddie Van Halen', 'firstName' => 'Eddie', 'lastName' => 'Van Halen'],
    ],
    'maintainCasing2' => [
        ['fullName' => 'Vincent van Gogh'],
        ['fullName' => 'Vincent van Gogh', 'firstName' => 'Vincent', 'lastName' => 'van Gogh'],
    ],
]);
