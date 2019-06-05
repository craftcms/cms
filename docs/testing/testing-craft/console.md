# Console commands
Console commands are a regular feature in many Craft modules and plugins as well as the 
Craft core itself. 
They are an extremely useful developer tool to cut straight through to application functionality, 
without the fuss of a UI. They are of course for this reason fundamentally different to 
test than web applications. 
Craft, however, offers native support for testing your console commands. 

::: tip
The way you can test your console commands with Craft is inspired by the excellent work 
done over at [Laravel](https://laravel.com/docs/5.8/console-tests) by [@themsaid](https://github.com/laravel/framework/pull/25270). 
If you are familiar with testing Laravel applications this will feel quite familiar.  
:::

## How it works
You can test console controllers by creating a unit test and setting it up in a specific way
([see the guide below for a practical example](#step-1-extend-a-specific-class)). Within this unit test Craft makes
available the following method `$this->consoleCommand()`.
This method creates a `craft\test\console\CommandTest` object which is set up with
a [fluid interface](https://en.wikipedia.org/wiki/Fluent_interface#PHP). This allows you
to build out your console command's user based outputs  
methods (I.E what `stdOut`, `stderr`, `prompt`, `confirm` e.t.c.) 
must be called when triggering your console command and according to what specification. 

::: tip
Underneath, Craft executes your console command exactly like you would when calling it through
command line.
So any resulting actions to the database, filesystem e.t.c. can be tested like you would in any
other unit test. 
:::

### Step 1: Extend a specific class
Your unit test needs to extend `craft\test\console\ConsoleTest`.
```php
<?php

namespace crafttests\unit\console;

use \craft\test\console\ConsoleTest;

class MyConsoleTest extends ConsoleTest
{
}

```

### Step 2: Create a test
Create a test like you would in any other unit test.
```php
<?php

namespace crafttests\unit\console;

use \craft\test\console\ConsoleTest;

class MyConsoleTest extends ConsoleTest
{
    public function testSomething()
    {
        
    }
}

```

### Step 3: Invoke the `consoleCommand` method
Invoke the `consoleCommand` method as follows. 
```php

public function testSomething()
{
    $this->consoleCommand('test-controller/test-action');
}


```

### Step 4: Add steps and run the command
Because the `consoleCommand` returns a [fluid interface](https://en.wikipedia.org/wiki/Fluent_interface#PHP)
you can add as many methods ([see options below](#methods)) in order to 
specify what 'user journey' your console command will follow. 

```php
public function testSomething()
{
    $this->consoleCommand('test-controller/test-action')
        ->stdOut('This output must be given')
        ->stdOut('Followed by this one')
        ->prompt('The user must then input something', 'This will be returned in the controller action (your console command)', 'the $default value')
        ->exitCode(ExitCode::OK)
        ->run();
}
```

You must end the chain with a `->exitCode($value)` call to specific what exit code 
must be returned. 
This call must then be followed by a `->run()` call, which runs the command. 


The commands will be checked in the order you define them. 
So if your console command is structured as follows: 
```php
public function actionSomething() {
    $this->stdOut('first');
    $this->stdOut('second');
}
```

Dont setup your method call as follows: 

```php
$this->consoleCommand('test-controller/test-action')
        ->stdOut('second')
        ->stdOut('first')
        ->exitCode(ExitCode::OK)
        ->run();
```
As this **will** fail. 

::: tip
If you want to ignore all `stdOut` calls you can pass `false` as the third parameter of the `consoleCommand()` 
call. You will then not have to define your `stdOut` calls when calling `$this->consoleCommand()` and Craft 
will ignore then when checking what your console command returns to the user. 
:::

## Methods
### `stdOut`

 - **string $desiredOutput:** The string that should be output by your console command

If your console command calls `$this->stdOut()` you should test that this method is correctly
called using the `stdOut` method. The value you pass in will be checked against what your 
console command passes in when calling `$this->stdOut()`

### `stderr`

- **string $desiredOutput:** The error string that should be output by your console command

Exactly the same principal as `stdOut` above - except for the `$this->stderr()` method.

### `prompt`

- **string $prompt:** What prompt your console command should invoke for the user
- **$returnValue:** What value should be returned by `$this->prompt()` 
when it is called in your console command. 
- **array $options = []:** The options your console command should pass into the `$this->prompt()` call. 

If your console command calls `$this->prompt()` this method ensures that you can test 
how this method is called and what user input is returned (as there is no *actual* user in testing). 

### `confirm`

- **string $message:** The message that your console command should ask the user to confirm
- **$returnValue:** The value that is returned to your console command
- **bool $default = false:** The $default value that is passed into the `$this->confirm()` method
by your console command

If your console command calls `$this->confirm()` this method ensures that you can test 
how this method is called and what user input is returned (as there is no *actual* user in testing). 

### `select`

- **string $prompt:** The prompt that should be asked of your user
by your console command when calling `$this->select()`
- **$returnValue:** The value that is returned to your console command by `$this->select()`
- **$options = []:** The options passed into `$this->select()` by your console command

### `outputCommand`

- **string $command:** The command to output when calling `$this->outputCommand()`
- **bool $withScriptName = true:** What value should be passed as second argument when your console
command calls `$this->outputCommand()`

If your console command calls `$this->outputCommand()` this method ensures that you can test 
how this method is called and what is output to the user. 

::: warning
Please ensure you call `$this->outputCommand()` in your console commands and not `craft\helpers\Console::outputCommand()`. 
This static method will not be taken into account as it is currently not possible to mock static methods - 
something required for the `CommandTest` class to work. 
:::
