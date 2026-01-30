<?php

declare(strict_types=1);

use craft\fieldlayoutelements\users\FullNameField;
use craft\models\FieldLayout;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use CraftCms\Cms\User\Validation\UserRules;
use CraftCms\Cms\Validation\Attributes\Ruleset;
use Illuminate\Support\Facades\Hash;

#[Ruleset(UserRules::class)]
class TestUserWithFieldLayout extends User
{
    private $mockFieldLayout;

    public function setMockFieldLayout($fieldLayout): void
    {
        $this->mockFieldLayout = $fieldLayout;
    }

    #[Override]
    public function getFieldLayout(): ?FieldLayout
    {
        return $this->mockFieldLayout;
    }
}

beforeEach(function () {
    Edition::set(Edition::Pro);
});

describe('String trimming', function () {
    test('string fields are trimmed of whitespace', function (string $field, string $input, string $expected) {
        $user = UserModel::factory()->createElement();
        $user->{$field} = $input;

        $user->validate([$field]);

        expect($user->{$field})->toBe($expected);
    })->with([
        'username is trimmed' => ['username', '  testuser  ', 'testuser'],
        'email is trimmed' => ['email', '  test@example.com  ', 'test@example.com'],
        'unverifiedEmail is trimmed' => ['unverifiedEmail', '  new@example.com  ', 'new@example.com'],
        'fullName is trimmed' => ['fullName', '  John Doe  ', 'John Doe'],
        'firstName is trimmed' => ['firstName', '  John  ', 'John'],
        'lastName is trimmed' => ['lastName', '  Doe  ', 'Doe'],
    ]);

    test('trimming skips empty values', function () {
        $user = UserModel::factory()->createElement();
        $user->fullName = null;

        $user->validate(['fullName']);

        expect($user->fullName)->toBeNull();
    });
});

describe('Email format validation', function () {
    test('email validation', function (string $email, bool $expectError) {
        $user = UserModel::factory()->createElement();
        $user->email = $email;

        $user->validate(['email']);

        expect($user->errors()->has('email'))->toBe($expectError);
    })->with([
        'valid email is accepted' => ['valid@example.com', false],
        'invalid email is rejected' => ['not-an-email', true],
        'email without domain is rejected' => ['user@', true],
        'email without local part is rejected' => ['@example.com', true],
        'email with plus signs is accepted' => ['user+tag@example.com', false],
    ]);

    test('unverifiedEmail validation', function (string $email, bool $expectError) {
        $user = UserModel::factory()->createElement();
        $user->unverifiedEmail = $email;

        $user->validate(['unverifiedEmail']);

        expect($user->errors()->has('unverifiedEmail'))->toBe($expectError);
    })->with([
        'valid email is accepted' => ['newemail@example.com', false],
        'invalid email is rejected' => ['invalid-email', true],
    ]);
});

describe('String max length validation (255 chars)', function () {
    test('string fields validate max length', function (string $field, int $length, bool $expectError) {
        $user = UserModel::factory()->createElement();

        $user->{$field} = match ($field) {
            'email', 'unverifiedEmail' => str_repeat('a', 244).'@example.com',
            default => str_repeat('a', $length),
        };

        $user->validate([$field]);

        expect($user->errors()->has($field))->toBe($expectError);
    })->with([
        'email rejects 256+ chars' => ['email', 256, true],
        'username accepts 255 chars' => ['username', 255, false],
        'username rejects 256 chars' => ['username', 256, true],
        'fullName accepts 255 chars' => ['fullName', 255, false],
        'fullName rejects 256 chars' => ['fullName', 256, true],
        'firstName rejects 256 chars' => ['firstName', 256, true],
        'lastName rejects 256 chars' => ['lastName', 256, true],
        'password rejects 256 chars' => ['password', 256, true],
        'unverifiedEmail rejects 256+ chars' => ['unverifiedEmail', 256, true],
    ]);
});

describe('Email required validation', function () {
    test('email required validation', function (mixed $email, ?int $draftId, bool $expectError) {
        $user = UserModel::factory()->createElement();
        $user->email = $email;

        if ($draftId !== null) {
            $user->draftId = $draftId;
        }

        $user->validate(['email']);

        expect($user->errors()->has('email'))->toBe($expectError);
    })->with([
        'null email is required when not draft' => [null, null, true],
        'empty string email is required when not draft' => ['', null, true],
        'email is not required for drafts' => [null, 123, false],
    ]);
});

describe('IP address validation', function () {
    test('lastLoginAttemptIp validation', function (mixed $value, bool $expectError) {
        $user = UserModel::factory()->createElement();
        $user->lastLoginAttemptIp = $value;

        $user->validate(['lastLoginAttemptIp']);

        expect($user->errors()->has('lastLoginAttemptIp'))->toBe($expectError);
    })->with([
        'valid IPv4 is accepted' => ['192.168.1.1', false],
        'valid IPv6 is accepted' => ['2001:0db8:85a3:0000:0000:8a2e:0370:7334', false],
        '45 chars is accepted' => [str_repeat('a', 45), false],
        '46 chars is rejected' => [str_repeat('a', 46), true],
        'null is accepted' => [null, false],
    ]);
});

describe('Username validation', function () {
    beforeEach(function () {
        Cms::config()->useEmailAsUsername = false;
    });

    test('username whitespace validation', function (string $username, bool $expectError, ?string $errorContains = null) {
        $user = UserModel::factory()->createElement();
        $user->username = $username;

        $user->validate(['username']);

        expect($user->errors()->has('username'))->toBe($expectError);

        if ($errorContains !== null) {
            expect($user->errors()->first('username'))->toContain($errorContains);
        }
    })->with([
        'spaces are rejected' => ['user name', true, 'cannot contain spaces'],
        'tabs are rejected' => ["user\tname", true, null],
        'newlines are rejected' => ["user\nname", true, null],
        'valid username is accepted' => ['validusername', false, null],
        'underscores are accepted' => ['valid_user_name', false, null],
        'hyphens are accepted' => ['valid-user-name', false, null],
    ]);

    test('username is required for credentialed users', function (callable $factoryMethod, bool $expectError) {
        $user = $factoryMethod()->createElement();
        $user->username = null;

        $user->validate(['username']);

        expect($user->errors()->has('username'))->toBe($expectError);
    })->with([
        'active users require username' => [fn () => UserModel::factory()->active(), true],
        'pending users require username' => [fn () => UserModel::factory()->pending(), true],
    ]);
});

describe('Email uniqueness validation', function () {
    test('email must be unique among active users', function () {
        UserModel::factory()->active()->createElement(['email' => 'taken@example.com']);

        $newUser = UserModel::factory()->active()->createElement(['email' => 'different@example.com']);
        $newUser->email = 'taken@example.com';

        $newUser->validate(['email']);

        expect($newUser->errors()->has('email'))->toBeTrue();
        expect($newUser->errors()->first('email'))->toContain('has already been taken');
    });

    test('email must be unique among pending users', function () {
        UserModel::factory()->pending()->createElement(['email' => 'taken@example.com']);

        $newUser = UserModel::factory()->pending()->createElement(['email' => 'different@example.com']);
        $newUser->email = 'taken@example.com';

        $newUser->validate(['email']);

        expect($newUser->errors()->has('email'))->toBeTrue();
    });

    test('email uniqueness is case-insensitive', function () {
        UserModel::factory()->active()->createElement(['email' => 'Test@Example.com']);

        $newUser = UserModel::factory()->active()->createElement(['email' => 'different@example.com']);
        $newUser->email = 'test@example.com';

        $newUser->validate(['email']);

        expect($newUser->errors()->has('email'))->toBeTrue();
    });

    test('email can duplicate inactive user email', function () {
        UserModel::factory()->createElement([
            'email' => 'inactive@example.com',
            'active' => false,
            'pending' => false,
        ]);

        $newUser = UserModel::factory()->active()->createElement(['email' => 'inactive@example.com']);

        $newUser->validate(['email']);

        expect($newUser->errors()->has('email'))->toBeFalse();
    });

    test('same user can keep their email on update', function () {
        $user = UserModel::factory()->active()->createElement(['email' => 'user@example.com']);

        $user->validate(['email']);

        expect($user->errors()->has('email'))->toBeFalse();
    });

    test('email uniqueness not enforced for inactive users', function () {
        UserModel::factory()->active()->createElement(['email' => 'shared@example.com']);

        $inactiveUser = UserModel::factory()->createElement([
            'email' => 'different@example.com',
            'active' => false,
            'pending' => false,
        ]);
        $inactiveUser->email = 'shared@example.com';

        $inactiveUser->validate(['email']);

        expect($inactiveUser->errors()->has('email'))->toBeFalse();
    });
});

describe('Username uniqueness validation', function () {
    beforeEach(function () {
        Cms::config()->useEmailAsUsername = false;
    });

    test('username must be unique among active users', function () {
        UserModel::factory()->active()->createElement(['username' => 'takenuser']);

        $newUser = UserModel::factory()->active()->createElement(['username' => 'differentuser']);
        $newUser->username = 'takenuser';

        $newUser->validate(['username']);

        expect($newUser->errors()->has('username'))->toBeTrue();
    });

    test('username must be unique among pending users', function () {
        UserModel::factory()->pending()->createElement(['username' => 'takenuser']);

        $newUser = UserModel::factory()->pending()->createElement(['username' => 'differentuser']);
        $newUser->username = 'takenuser';

        $newUser->validate(['username']);

        expect($newUser->errors()->has('username'))->toBeTrue();
    });

    test('username uniqueness is case-insensitive', function () {
        UserModel::factory()->active()->createElement(['username' => 'TestUser']);

        $newUser = UserModel::factory()->active()->createElement(['username' => 'differentuser']);
        $newUser->username = 'testuser';

        $newUser->validate(['username']);

        expect($newUser->errors()->has('username'))->toBeTrue();
    });

    test('username can duplicate inactive user username', function () {
        UserModel::factory()->createElement([
            'username' => 'inactiveuser',
            'active' => false,
            'pending' => false,
        ]);

        $newUser = UserModel::factory()->active()->createElement(['username' => 'inactiveuser']);

        $newUser->validate(['username']);

        expect($newUser->errors()->has('username'))->toBeFalse();
    });

    test('same user can keep their username on update', function () {
        $user = UserModel::factory()->active()->createElement(['username' => 'myusername']);

        $user->validate(['username']);

        expect($user->errors()->has('username'))->toBeFalse();
    });
});

describe('Unverified email uniqueness validation', function () {
    test('unverifiedEmail must be unique among active users emails', function () {
        UserModel::factory()->active()->createElement(['email' => 'taken@example.com']);

        $user = UserModel::factory()->active()->createElement(['email' => 'different@example.com']);
        $user->unverifiedEmail = 'taken@example.com';

        $user->validate(['unverifiedEmail']);

        expect($user->errors()->has('unverifiedEmail'))->toBeTrue();
    });

    test('unverifiedEmail must be unique among pending users emails', function () {
        UserModel::factory()->pending()->createElement(['email' => 'taken@example.com']);

        $user = UserModel::factory()->active()->createElement(['email' => 'different@example.com']);
        $user->unverifiedEmail = 'taken@example.com';

        $user->validate(['unverifiedEmail']);

        expect($user->errors()->has('unverifiedEmail'))->toBeTrue();
    });

    test('unverifiedEmail uniqueness is case-insensitive', function () {
        UserModel::factory()->active()->createElement(['email' => 'Test@Example.com']);

        $user = UserModel::factory()->active()->createElement(['email' => 'different@example.com']);
        $user->unverifiedEmail = 'test@example.com';

        $user->validate(['unverifiedEmail']);

        expect($user->errors()->has('unverifiedEmail'))->toBeTrue();
    });

    test('unverifiedEmail can duplicate inactive user email', function () {
        UserModel::factory()->createElement([
            'email' => 'inactive@example.com',
            'active' => false,
            'pending' => false,
        ]);

        $user = UserModel::factory()->active()->createElement(['email' => 'different@example.com']);
        $user->unverifiedEmail = 'inactive@example.com';

        $user->validate(['unverifiedEmail']);

        expect($user->errors()->has('unverifiedEmail'))->toBeFalse();
    });

    test('unverifiedEmail can be the same as own current email', function () {
        $user = UserModel::factory()->active()->createElement(['email' => 'user@example.com']);
        $user->unverifiedEmail = 'user@example.com';

        $user->validate(['unverifiedEmail']);

        expect($user->errors()->has('unverifiedEmail'))->toBeFalse();
    });
});

describe('Password validation', function () {
    test('newPassword length validation', function (mixed $password, bool $expectError) {
        $user = UserModel::factory()->createElement();
        $user->newPassword = $password;

        $user->validate(['newPassword']);

        expect($user->errors()->has('newPassword'))->toBe($expectError);
    })->with([
        '5 chars is too short' => ['12345', true],
        '6 chars is valid' => ['123456', false],
        '160 chars is valid' => [str_repeat('a', 160), false],
        '161 chars is too long' => [str_repeat('a', 161), true],
        'null is valid' => [null, false],
    ]);

    test('newPassword must be different when passwordResetRequired is true', function () {
        $user = UserModel::factory()->active()->createElement();

        UserModel::findOrFail($user->id)->update([
            'password' => Hash::make('oldpassword'),
            'passwordResetRequired' => true,
        ]);

        $user = User::find()->id($user->id)->one();
        $user->passwordResetRequired = true;
        $user->newPassword = 'oldpassword';

        $user->validate(['newPassword']);

        expect($user->errors()->has('newPassword'))->toBeTrue();
        expect($user->errors()->first('newPassword'))->toContain('must be set to a new password');
    });
});

describe('URL injection prevention', function () {
    test('fields reject URL protocols', function (string $field, string $value, bool $expectError) {
        $user = UserModel::factory()->createElement();
        $user->{$field} = $value;

        $user->validate([$field]);

        expect($user->errors()->has($field))->toBe($expectError);
    })->with([
        'fullName rejects http://' => ['fullName', 'http://malicious.com', true],
        'fullName rejects https://' => ['fullName', 'https://malicious.com', true],
        'fullName rejects ftp://' => ['fullName', 'ftp://files.com', true],
        'firstName rejects http://' => ['firstName', 'http://malicious.com', true],
        'lastName rejects https://' => ['lastName', 'https://malicious.com', true],
        'username rejects http://' => ['username', 'http://malicious.com', true],
        'fullName with colon but no slashes is allowed' => ['fullName', 'John: A Developer', false],
        'fullName with single slash is allowed' => ['fullName', 'John/Jane Doe', false],
        'fullName containing :// anywhere is rejected' => ['fullName', 'Check out ://example', true],
    ]);
});

describe('Scenario validation', function () {
    test('SCENARIO_PASSWORD only validates newPassword', function () {
        $user = UserModel::factory()->createElement();
        $user->setScenario(User::SCENARIO_PASSWORD);
        $user->username = str_repeat('a', 256); // Invalid
        $user->newPassword = 'p'; // too short

        $user->validate(['username', 'newPassword']);

        expect($user->errors()->has('username'))->toBeFalse();
        expect($user->errors()->has('newPassword'))->toBeTrue();
    });

    test('SCENARIO_REGISTRATION validates username email and newPassword', function () {
        $user = UserModel::factory()->createElement();
        $user->setScenario(User::SCENARIO_REGISTRATION);
        $user->username = str_repeat('a', 256); // Invalid
        $user->email = 'invalid-email'; // Invalid
        $user->newPassword = 'p'; // too short
        $user->firstName = str_repeat('a', 256); // Invalid

        $user->validate();

        expect($user->errors()->has('username'))->toBeTrue();
        expect($user->errors()->has('email'))->toBeTrue();
        expect($user->errors()->has('newPassword'))->toBeTrue();
        expect($user->errors()->has('firstName'))->toBeFalse();
    });

    test('SCENARIO_ACTIVATION validates username and email', function () {
        $user = UserModel::factory()->createElement();
        $user->setScenario(User::SCENARIO_ACTIVATION);

        $user->username = str_repeat('a', 256); // Invalid
        $user->email = 'invalid-email'; // Invalid
        $user->newPassword = 'p'; // too short
        $user->firstName = str_repeat('a', 256); // Invalid

        $user->validate();

        expect($user->errors()->has('username'))->toBeTrue();
        expect($user->errors()->has('email'))->toBeTrue();
        expect($user->errors()->has('newPassword'))->toBeFalse();
        expect($user->errors()->has('firstName'))->toBeFalse();
    });

    test('treatAsActive returns correct value based on user status', function (callable $factoryMethod, bool $expected) {
        $user = $factoryMethod();

        expect($user->getIsCredentialed())->toBe($expected);
    })->with([
        'active users return true' => [fn () => UserModel::factory()->active()->createElement(), true],
        'pending users return true' => [fn () => UserModel::factory()->pending()->createElement(), true],
        'inactive users return false' => [fn () => UserModel::factory()->createElement(['active' => false, 'pending' => false]), false],
    ]);
});

describe('Name field validation with field layout', function () {
    test('firstName and lastName are required when showFirstAndLastNameFields is true and FullNameField is required in SCENARIO_LIVE', function () {
        Cms::config()->showFirstAndLastNameFields = true;

        $fullNameField = Mockery::mock(FullNameField::class);
        $fullNameField->required = true;

        $fieldLayout = Mockery::mock(FieldLayout::class);
        $fieldLayout->shouldReceive('getFirstVisibleElementByType')
            ->with(FullNameField::class, Mockery::any())
            ->andReturn($fullNameField);
        $fieldLayout->shouldReceive('getVisibleCustomFieldElements')->andReturn([]);
        $fieldLayout->shouldReceive('getTabs')->andReturn([]);

        $user = new TestUserWithFieldLayout;
        $user->setMockFieldLayout($fieldLayout);
        $user->setScenario(User::SCENARIO_LIVE);
        $user->email = 'test@example.com';
        $user->firstName = '';
        $user->lastName = '';

        expect($user->validate(['firstName']))->toBeFalse();
        expect($user->errors()->has('firstName'))->toBeTrue();

        expect($user->validate(['lastName']))->toBeFalse();
        expect($user->errors()->has('lastName'))->toBeTrue();
    });

    test('firstName and lastName are not required when showFirstAndLastNameFields is true but FullNameField is not required in SCENARIO_LIVE', function () {
        Cms::config()->showFirstAndLastNameFields = true;

        $fullNameField = Mockery::mock(FullNameField::class);
        $fullNameField->required = false;

        $fieldLayout = Mockery::mock(FieldLayout::class);
        $fieldLayout->shouldReceive('getFirstVisibleElementByType')
            ->with(FullNameField::class, Mockery::any())
            ->andReturn($fullNameField);
        $fieldLayout->shouldReceive('getVisibleCustomFieldElements')->andReturn([]);
        $fieldLayout->shouldReceive('getTabs')->andReturn([]);

        $user = new TestUserWithFieldLayout;
        $user->setMockFieldLayout($fieldLayout);
        $user->setScenario(User::SCENARIO_LIVE);
        $user->email = 'test@example.com';
        $user->firstName = '';
        $user->lastName = '';

        expect($user->validate(['firstName']))->toBeTrue();
        expect($user->validate(['lastName']))->toBeTrue();
    });

    test('fullName is required when showFirstAndLastNameFields is false and FullNameField is required in SCENARIO_LIVE', function () {
        Cms::config()->showFirstAndLastNameFields = false;

        $fullNameField = Mockery::mock(FullNameField::class);
        $fullNameField->required = true;

        $fieldLayout = Mockery::mock(FieldLayout::class);
        $fieldLayout->shouldReceive('getFirstVisibleElementByType')
            ->with(FullNameField::class, Mockery::any())
            ->andReturn($fullNameField);
        $fieldLayout->shouldReceive('getVisibleCustomFieldElements')->andReturn([]);
        $fieldLayout->shouldReceive('getTabs')->andReturn([]);

        $user = new TestUserWithFieldLayout;
        $user->setMockFieldLayout($fieldLayout);
        $user->setScenario(User::SCENARIO_LIVE);
        $user->email = 'test@example.com';
        $user->fullName = '';

        expect($user->validate(['fullName']))->toBeFalse();
        expect($user->errors()->has('fullName'))->toBeTrue();
    });

    test('fullName is not required when showFirstAndLastNameFields is false but FullNameField is not required in SCENARIO_LIVE', function () {
        Cms::config()->showFirstAndLastNameFields = false;

        $fullNameField = Mockery::mock(FullNameField::class);
        $fullNameField->required = false;

        $fieldLayout = Mockery::mock(FieldLayout::class);
        $fieldLayout->shouldReceive('getFirstVisibleElementByType')
            ->with(FullNameField::class, Mockery::any())
            ->andReturn($fullNameField);
        $fieldLayout->shouldReceive('getVisibleCustomFieldElements')->andReturn([]);
        $fieldLayout->shouldReceive('getTabs')->andReturn([]);

        $user = new TestUserWithFieldLayout;
        $user->setMockFieldLayout($fieldLayout);
        $user->setScenario(User::SCENARIO_LIVE);
        $user->email = 'test@example.com';
        $user->fullName = '';

        expect($user->validate(['fullName']))->toBeTrue();
    });

    test('name field required validation does not apply outside SCENARIO_LIVE', function () {
        Cms::config()->showFirstAndLastNameFields = true;

        $fullNameField = Mockery::mock(FullNameField::class);
        $fullNameField->required = true;

        $fieldLayout = Mockery::mock(FieldLayout::class);
        $fieldLayout->shouldReceive('getFirstVisibleElementByType')
            ->with(FullNameField::class, Mockery::any())
            ->andReturn($fullNameField);
        $fieldLayout->shouldReceive('getVisibleCustomFieldElements')->andReturn([]);
        $fieldLayout->shouldReceive('getTabs')->andReturn([]);

        $user = new TestUserWithFieldLayout;
        $user->setMockFieldLayout($fieldLayout);
        $user->setScenario(User::SCENARIO_DEFAULT);
        $user->email = 'test@example.com';
        $user->firstName = '';
        $user->lastName = '';

        expect($user->validate(['firstName']))->toBeTrue();
        expect($user->validate(['lastName']))->toBeTrue();
    });

    test('handles missing FullNameField gracefully using null coalescing', function () {
        Cms::config()->showFirstAndLastNameFields = true;

        $fieldLayout = Mockery::mock(FieldLayout::class);
        $fieldLayout->shouldReceive('getFirstVisibleElementByType')
            ->with(FullNameField::class, Mockery::any())
            ->andReturn(null);
        $fieldLayout->shouldReceive('getVisibleCustomFieldElements')->andReturn([]);
        $fieldLayout->shouldReceive('getTabs')->andReturn([]);

        $user = new TestUserWithFieldLayout;
        $user->setMockFieldLayout($fieldLayout);
        $user->setScenario(User::SCENARIO_LIVE);
        $user->email = 'test@example.com';
        $user->firstName = '';
        $user->lastName = '';

        expect($user->validate(['firstName']))->toBeTrue();
        expect($user->validate(['lastName']))->toBeTrue();
    });
});

describe('Edge cases', function () {
    test('unicode characters are handled in name fields', function () {
        $user = UserModel::factory()->createElement();
        $user->fullName = '日本語名前';
        $user->firstName = 'Müller';
        $user->lastName = "O'Brien";

        $user->validate(['fullName', 'firstName', 'lastName']);

        expect($user->errors()->has('fullName'))->toBeFalse();
        expect($user->errors()->has('firstName'))->toBeFalse();
        expect($user->errors()->has('lastName'))->toBeFalse();
    });

    test('special characters in username are allowed except whitespace', function () {
        Cms::config()->useEmailAsUsername = false;

        $user = UserModel::factory()->createElement();
        $user->username = 'user@name.test_123-abc';

        $user->validate(['username']);

        expect($user->errors()->has('username'))->toBeFalse();
    });

    test('validation runs for new unsaved users', function () {
        $user = new User;
        $user->email = 'test@example.com';
        $user->username = 'testuser';

        $user->validate(['email', 'username']);

        expect($user->errors()->has('email'))->toBeFalse();
        expect($user->errors()->has('username'))->toBeFalse();
    });

    test('multiple validation errors can be collected', function () {
        $user = UserModel::factory()->createElement();
        $user->email = 'invalid-email';
        $user->username = 'user name with spaces';
        $user->fullName = 'http://malicious.com';

        $user->validate(['email', 'username', 'fullName']);

        expect($user->errors()->has('email'))->toBeTrue();
        expect($user->errors()->has('username'))->toBeTrue();
        expect($user->errors()->has('fullName'))->toBeTrue();
    });

    test('errors reset between validate calls unless specified', function () {
        $user = UserModel::factory()->createElement();
        $user->email = 'invalid-email';

        $user->validate(['email']);

        expect($user->errors()->has('email'))->toBeTrue();

        $user->email = 'john@example.com';

        $user->validate(['email'], clearErrors: false);

        expect($user->errors()->has('email'))->toBeTrue();

        $user->validate(['email']);

        expect($user->errors()->has('email'))->toBeFalse();
    });
});
