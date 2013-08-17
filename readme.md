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

After the package has been successfully loaded, you have to add it to the Service Providers array:
```php
// app/config/app.php
'providers' => array(
	...
	'Netson\L4shell\L4shellServiceProvider',
);
```

An alias is automatically registered by the Service Provider, but in case you're wondering, this is it:
```php
'L4shell' => 'Netson\L4shell\Facades\Command'
```

## Usage ##

This package escapes both the entire command (using escapeshellcmd()) and each individual argument (using escapdeshellarg()). Almost all methods allow object chaining for easy setup.

### Initializing a new command: ###

This can be done via the shortcut registered in the Service Provider:

```php
$command = L4shell::get();
```
or by creating a new object manually:
```php
$command = new \Netson\L4shell\Command("command", array("arg1", "arg2"));
```
Using the get() method will initialize an empty command object and requires you to use the setCommand() and setArguments() method (optional) before the command can be executed.

### Sample command: without arguments ###

```php
$command = L4shell::get();
$result = $command->setCommand('hostname')->execute();
```
If the command was executed successfully, the output of the command will be returned. If the command could not be executed, an exception will be thrown, including the error message from the command (except when using the sendToDevNull() method; see below).

### Sample command: with arguments ###

When adding arguments, make sure you add the correct number of placeholder (sprintf-format), otherwise an Exception will be thrown.

```php
$command = L4shell::get();
$result = $command->setCommand('hostname %s')->setArguments(array("-s"))->execute();
```

### Sample command: send output to /dev/null ###

The package has an easy way of sending output from commands to /dev/null since quite often you may only be interested in the exit status, and not the output text. Sending output to /dev/null will render any output messages useless, but the exit code will off course still be available and exceptions will be thrown when errors occur.

```php
$command = L4shell::get();
$result = $command->setCommand("hostname")->sendToDevNull()->execute(); // will return exit code (0), but no output message
```

### Sample command: enable logging of all commands ###

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

### Setting and unsetting the execution path ###

In case you wish to execute your command from within a particular directory, you can use the following method:

```php
$command = L4shell::get();
$result = $command->setExecutionPath("/path/to/your/folder")->setCommand("ls")->execute();
```

The execution path is a static variable, which in this case means that the execution path, if set only once, will affect **ALL** commands executed thereafter.

**NOTE:** *The execution path is changed right before executing the command, and is changed back to the original setting right after the command has been executed. This way you don't have to remember to revert the working directory and it won't mess up any other scripts running after this one.*

If you wish to unset the execution path, simply call the method without any parameters:

```php
$command = L4shell::get();
$result = $command->setExecutionPath()->setCommand("ls")->execute();
```

### Setting and unsetting the executable path ###

In case the folder containing your executables is not in the path of the user executing the command, you can use the following method:

```php
$command = L4shell::get();
$result = $command->setExecutablePath("/usr/bin")->setCommand("ls")->execute();
```
This will effectively change your command from simply ```$ ls``` to ```$ /usr/bin/ls```.
This is also a static variable, meaning that the setting will persist across commands.

If you wish to unset the executable path (for example to run a command from a local directory), simply call the method without any parameters:

```php
$command = L4shell::get();
$result = $command->setExecutablePath()->setCommand("ls")->execute();
```

### Prevent certain characters from being escaped ###

When you have a specific shell command which requires the use of characters that would normally be escaped by the ``` escapeshellcmd()``` or ```escapeshellarg()``` functions, you can use the ```setAllowedCharacters()``` method. This method accepts an array of characters that will not be escaped by L4shell:

```php
$command = L4shell::setCommand('find ./ -maxdepth 1 -name %s')->setArguments(array("*.txt"))->setAllowedCharacters(array("*"));
// returned with allowed characters: find ./ -maxdepth 1 -name '*.txt'
// returned without allowed characters: find ./ -maxdepth 1 -name '\*.txt'
```

**SINCE THIS METHOD COMPLETELY CIRCUMVENTS THE ESCAPING OF POTENTIALLY DANGEROUS CHARACTERS, USE THIS METHOD WITH THE UTMOST CAUTION!!**

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