<?php

declare(strict_types=1);

use craft\console\User as ConsoleUser;
use craft\elements\User as UserElement;
use craft\services\Users as LegacyUsers;
use CraftCms\Cms\Cms;
use CraftCms\Yii2Adapter\IdentityWrapper;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\GenericUser;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Password;
use yii\web\IdentityInterface;

beforeEach(function() {
    Cms::config()->authGuard = 'craft';
    Cms::config()->authPasswordBroker = 'craft';

    $this->legacyUser = app('Craft')->getUser();
    $this->authUser = new GenericUser(['id' => 42, 'password' => '']);
});

it('ignores authentication events from other guards', function() {
    $identity = Mockery::mock(IdentityInterface::class);
    $this->legacyUser->setIdentity($identity);

    Event::dispatch(new Authenticated('host', $this->authUser));
    Event::dispatch(new Login('host', $this->authUser, false));
    Event::dispatch(new Logout('host', $this->authUser));

    expect($this->legacyUser->getIdentity())->toBe($identity);
});

it('synchronizes authentication events from the Craft guard', function() {
    Event::dispatch(new Authenticated('craft', $this->authUser));

    expect($this->legacyUser->getIdentity())->toBeInstanceOf(IdentityWrapper::class);

    $this->legacyUser->setIdentity(null);
    Event::dispatch(new Login('craft', $this->authUser, false));

    expect($this->legacyUser->getIdentity())->toBeInstanceOf(IdentityWrapper::class);

    Event::dispatch(new Logout('craft', $this->authUser));

    expect($this->legacyUser->getIdentity())->toBeNull();
});

it('reads the current identity from the Craft guard', function() {
    $guard = Mockery::mock(StatefulGuard::class);
    $guard->shouldReceive('user')->once()->andReturnNull();
    Auth::shouldReceive('guard')->once()->with('craft')->andReturn($guard);

    expect($this->legacyUser->getIdentity())->toBeNull();
});

it('switches identity through the Craft guard', function() {
    $identity = Mockery::mock(IdentityInterface::class);
    $identity->shouldReceive('getId')->once()->andReturn(42);

    $guard = Mockery::mock(StatefulGuard::class);
    $guard->shouldReceive('loginUsingId')->once()->with(42, false);
    $guard->shouldReceive('logout')->once();
    Auth::shouldReceive('guard')->twice()->with('craft')->andReturn($guard);

    $this->legacyUser->switchIdentity($identity);
    $this->legacyUser->switchIdentity(null);
});

it('clears the console identity through the Craft guard', function() {
    $guard = Mockery::mock(StatefulGuard::class);
    $guard->shouldReceive('logout')->once();
    Auth::shouldReceive('guard')->once()->with('craft')->andReturn($guard);

    new ConsoleUser()->setIdentity();
});

it('sets the console identity on the Craft guard without logging in', function() {
    $identity = Mockery::mock(UserElement::class);
    $identity->shouldReceive('getAuthIdentifier')->andReturn(42);

    $guard = Mockery::mock(StatefulGuard::class);
    $guard->shouldReceive('id')->once()->andReturnNull();
    $guard->shouldReceive('setUser')->once()->with($identity);
    $guard->shouldNotReceive('login');
    Auth::shouldReceive('guard')->with('craft')->andReturn($guard);

    new ConsoleUser()->setIdentity($identity);
});

// Setting the guard's user fires `Authenticated`, which sets the console identity again.
it('leaves the console identity alone when the guard already has that user', function() {
    $identity = Mockery::mock(UserElement::class);
    $identity->shouldReceive('getAuthIdentifier')->andReturn(42);

    $guard = Mockery::mock(StatefulGuard::class);
    $guard->shouldReceive('id')->once()->andReturn(42);
    $guard->shouldNotReceive('setUser');
    $guard->shouldNotReceive('login');
    Auth::shouldReceive('guard')->with('craft')->andReturn($guard);

    new ConsoleUser()->setIdentity($identity);
});

it('checks legacy verification codes through the Craft password broker', function() {
    $user = Mockery::mock(UserElement::class);
    $broker = Mockery::mock(PasswordBroker::class);
    $broker->shouldReceive('tokenExists')->once()->with($user, 'code')->andReturnTrue();
    Password::shouldReceive('broker')->once()->with('craft')->andReturn($broker);

    expect(new LegacyUsers()->isVerificationCodeValidForUser($user, 'code'))->toBeTrue();
});
