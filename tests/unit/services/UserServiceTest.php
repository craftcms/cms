<?php

namespace craftunit\services;

use craft\records\User;
use craftunit\fixtures\TestDataFixture;

/**
 * Created by PhpStorm.
 * User: Giel Tettelaar PC
 * Date: 9/30/2018
 * Time: 7:12 PM
 */

class UserServiceTest extends \Codeception\Test\Unit
{
    public function _fixtures()
    {
        return [
            'profiles' =>  'craftunit\\fixtures\\TestDataFixture',
        ];
    }

    public function testSaveuser()
    {
        $this->tester->haveFixtures(['profiles' =>'craftunit\\fixtures\\TestDataFixture' ]);
        $this->tester->grabFixture('profiles')->getData();
        die(print_r($this->tester->grabFixture('profiles')));
        foreach ($this->tester->grabFixture('profiles')->getData() as $fixture) {
           die(print_r($fixture));
       }

    }
}