# L4shell: Shell Commands for Laravel 4 #

This laravel 4 package is a simple wrapper class for shell commands (exec). It allows you to add exec() commands to your application without losing the ability to write unit tests. The package comes with a Facade, so using Mockery for testing purposes is a breeze.

This package will **not** work on Windows systems.

## Installation ##

Simply use composer:

```$ php composer.phar require netson/l4shell:1.0.* ```

Or add the requirement to the composer.json file manually:


```
"require": {
     "netson/l4shell": "1.0.x"
}
```

## Usage ##

This package escapes both the entire command (using escapeshellcmd()) and each individual argument (using escapdeshellarg()). Almost all methods allow object chaining for easy setup.

**Initializing a new command:**

This can be done via the shortcut registered in the Service Provider:

```php
$command = L4shell::get();
```
or by creating a new object manually:
```php
$command = new \Netson\L4shell\Command("command", array("arg1", "arg2"));
```
Using the get() method will initialize an empty command object and requires you to use the setCommand() and setArguments() method (optional) before the command can be executed.

**Sample command: without arguments:**

```php
$command = L4shell::get();
$result = $command->setCommand('hostname')->execute();
```
If the command was executed successfully, the output of the command will be returned. If the command could not be executed, an exception will be thrown, including the error message from the command (except when using the sendToDevNull() method; see below).

**Sample command: with arguments:**

When adding arguments, make sure you add the correct number of placeholder (sprintf-format), otherwise an Exception will be thrown.

```php
$command = L4shell::get();
$result = $command->setCommand('hostname %s')->setArguments(array("-s"))->execute();
```

**Sample command: send output to /dev/null**

The package has an easy way of sending output from commands to /dev/null since quite often you may only be interested in the exit status, and not the output text. Sending output to /dev/null will render any output messages useless, but the exit code will off course still be available and exceptions will be thrown when errors occur.

```php
$command = L4shell::get();
$result = $command->setCommand("hostname")->sendToDevNull()->execute(); // will return exit code (0), but no output message
```

**Sample command: enable logging of all commands**

L4shell allows you to easily log all calls to shell commands to the default laravel log. By default, logging is **enabled**. Logging uses the default Laravel 4 logging package ([Monolog](http://laravel.com/docs/errors#logging "Monolog")). 

For each successful command, 3 or 4 log lines will appear, depending on whether arguments have been set:

* setting command ...
* setting arguments ... (optional)
* executing command ...
* command successfully executed

To disable logging, publish the package config file:

```$ php artisan config:publish netson/l4shell```

And then change the ```enable_logging``` option in the ```app/config/packages/netson/l4shell/config.php``` file.

**Alternatively**, you can change the logging settings at runtime:

```php
$command = L4shell::get();
$result = $command->setLogging(true)->setCommand("hostname")->execute();
```

## Unit testing ##

Unit testing your modules/packages when using L4shell is easy. Simply use Mockery style calls in your tests:

```php
public function testSomething ()
{
    L4shell::shouldReceive('get')->once();
    L4shell::shouldReceive('execute')->once()->andReturn("your message");
}
public function tearDown ()
{
    \Mockery::close();
}
```
For more information on unit testing with laravel 4, check out the following docs:
* [https://github.com/padraic/mockery](https://github.com/padraic/mockery "Mockery")
* [http://laravel.com/docs/testing](http://laravel.com/docs/testing "Laravel 4 Docs - Unit Testing") 