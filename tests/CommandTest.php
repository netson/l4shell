<?php

class CommandTest extends Orchestra\Testbench\TestCase {

    protected function getPackageProviders ()
    {
        return array(
            'Netson\L4shell\L4shellServiceProvider',
        );

    }

    protected function getPackageAliases ()
    {
        return array(
            'L4shell' => 'Netson\L4shell\Facades\L4shell',
        );

    }

    public function setUp ()
    {
        parent::setUp();
        L4shell::setLogging(true);

    }

    public function testConstructorReturnsClassObject ()
    {
        Log::shouldReceive('info', 'error')->never();

        $command = new \Netson\L4shell\Command();
        $this->assertInstanceOf('Netson\L4shell\Command', $command);

    }

    public function testGetReturnsClassObject ()
    {
        Log::shouldReceive('info', 'error')->never();

        $command = L4shell::get();
        $this->assertInstanceOf('Netson\L4shell\Command', $command);

    }

    public function testGetCommandThrowsExceptionWhenCommandNotSet ()
    {
        $this->setExpectedException('Netson\L4shell\CommandNotSetException');
        Log::shouldReceive('info', 'error')->never();

        $command = L4shell::get();
        $command->getCommand();

    }

    public function testGetCommandThrowsExceptionWhenInvalidArgumentCount ()
    {
        $this->setExpectedException('Netson\L4shell\InvalidNumberOfArgumentsException');
        Log::shouldReceive('info')->twice();

        $command = L4shell::get();
        $command->setCommand('hostname')
                ->setArguments(array("-v"));

        $command->getCommand();

    }

    public function testGetCommandReturnsString ()
    {
        Log::shouldReceive('info')->twice();

        $command = L4shell::get();
        $command->setCommand('hostname %s')
                ->setArguments(array("-s"));

        $this->assertEquals("hostname '-s'", $command->getCommand());

    }

    public function testSetLoggingReturnsObject ()
    {
        $command = L4shell::get();
        $this->assertInstanceOf('Netson\L4shell\Command', $command->setLogging(false));

    }

    public function testSetCommandReturnsObjectWhenLoggingDisabled ()
    {
        Log::shouldReceive('info')->never();

        $command = L4shell::get();
        $command->setLogging(false)
                ->setCommand('hostname');

        $this->assertInstanceOf('Netson\L4shell\Command', $command);

    }

    public function testSetCommandReturnsObjectWhenLoggingEnabled ()
    {
        Log::shouldReceive('info')->once();

        $command = L4shell::get();
        $command->setLogging(true)
                ->setCommand('hostname');

        $this->assertInstanceOf('Netson\L4shell\Command', $command);

    }

    public function testSetCommandReturnsObjectWhenLoggingDisabledAndCommandIsNull ()
    {
        Log::shouldReceive('info')->never();

        $command = L4shell::get();
        $command->setLogging(false)
                ->setCommand(null);

        $this->assertInstanceOf('Netson\L4shell\Command', $command);

    }

    public function testSetCommandReturnsObjectWhenLoggingEnabledAndCommandIsNull ()
    {
        Log::shouldReceive('info')->never();

        $command = L4shell::get();
        $command->setLogging(true)
                ->setCommand(null);

        $this->assertInstanceOf('Netson\L4shell\Command', $command);

    }

    public function testSetArgumentsReturnsObjectWhenLoggingDisabled ()
    {
        Log::shouldReceive('info')->never();

        $command = L4shell::get();
        $command->setLogging(false)
                ->setArguments(array('-v'));

        $this->assertInstanceOf('Netson\L4shell\Command', $command);

    }

    public function testSetArgumentsReturnsObjectWhenLoggingEnabled ()
    {
        Log::shouldReceive('info')->once();

        $command = L4shell::get();
        $command->setLogging(true)
                ->setArguments(array('-v'));

        $this->assertInstanceOf('Netson\L4shell\Command', $command);

    }

    public function testSetArgumentsReturnsObjectWhenLoggingDisabledAndCommandIsNull ()
    {
        Log::shouldReceive('info')->never();

        $command = L4shell::get();
        $command->setLogging(false)
                ->setArguments(array());

        $this->assertInstanceOf('Netson\L4shell\Command', $command);

    }

    public function testSetArgumentsReturnsObjectWhenLoggingEnabledAndCommandIsNull ()
    {
        Log::shouldReceive('info')->never();

        $command = L4shell::get();
        $command->setLogging(true)
                ->setArguments(array());

        $this->assertInstanceOf('Netson\L4shell\Command', $command);

    }

    public function testExecuteIsSuccessful ()
    {
        // when setting, executing and completing the command
        Log::shouldReceive('info')->times(3);

        $reference = str_replace("\n", "", shell_exec("hostname"));

        $command = L4shell::get();
        $command->setCommand('hostname');

        $this->assertEquals($reference, $command->execute());

    }

    public function testExecuteWithArgumentsIsSuccessful ()
    {
        // when setting (command and arguments), executing and completing the command
        Log::shouldReceive('info')->times(4);

        $reference = str_replace("\n", "", shell_exec("hostname -s"));

        $command = L4shell::get();
        $command->setCommand('hostname %s')
                ->setArguments(array("-s"));

        $this->assertEquals($reference, $command->execute());

    }

    public function testExecuteInvalidUsageThrowsException ()
    {
        $this->setExpectedException('Netson\L4shell\InvalidUsageException');
        Log::shouldReceive('info')->times(3);
        Log::shouldReceive('error')->once();

        $command = L4shell::get();
        $command->setCommand('ls %s')
                ->sendToDevNull(true)
                ->setArguments(array("-z"));

        $command->execute();

    }

    public function testExecuteCommandNotFoundThrowsException ()
    {
        $this->setExpectedException('Netson\L4shell\CommandNotFoundException');
        Log::shouldReceive('info')->times(2);
        Log::shouldReceive('error')->once();

        $command = L4shell::get();
        $command->setCommand('commanddoesnotexist')
                ->sendToDevNull(true);

        $command->execute();

    }

    public function testExecuteCommandNotExecutableThrowsException ()
    {
        $this->setExpectedException('Netson\L4shell\NonExecutableCommandException');
        Log::shouldReceive('info')->times(2);
        Log::shouldReceive('error')->once();

        $command = L4shell::get();
        $command->setCommand('./composer.json')
                ->sendToDevNull(true);

        $command->execute();

    }

    public function testSendToDevNullReturnsObject ()
    {
        $command = L4shell::get();

        $this->assertInstanceOf('Netson\L4shell\Command', $command->sendToDevNull());

    }

    public function testAllowUnescapedCharacters ()
    {
        Log::shouldReceive('info')->times(3);
        Log::shouldReceive('error')->never();
        $command = L4shell::setCommand('find ./ -maxdepth 1 -name %s')->setArguments(array("*.txt"))->setAllowedCharacters(array("*"));
        $expected = "find ./ -maxdepth 1 -name '*.txt'";

        $this->assertEquals($expected, $command->getCommand());

    }

    public function testSetExecutionPathThrowsException ()
    {
        $this->setExpectedException('Netson\L4shell\ExecutionPathNotFoundException');
        File::shouldReceive('isDirectory')->once()->andReturn(false);

        L4shell::setExecutionPath('/thisdirectorydoesnotexist');

    }

    public function testSetExecutionPathIsSuccessful ()
    {
        File::shouldReceive('isDirectory')->once()->andReturn(true);
        Log::shouldReceive('info')->once();

        $command = L4shell::setExecutionPath('/');
        $expected = "/";

        $this->assertEquals($expected, $command->getExecutionPath());

    }

    public function testExecutionPathIsReverted ()
    {
        File::shouldReceive('isDirectory')->once()->andReturn(true);
        Log::shouldReceive('info')->times(6);

        $command = L4shell::setCommand("ls");
        $expected = $command->getCwd();
        $command->setExecutionPath("/")->execute();

        $this->assertEquals($expected, $command->getCwd());

    }

    public function testExecutablePathThrowException ()
    {
        $this->setExpectedException('Netson\L4shell\ExecutablePathNotFoundException');
        File::shouldReceive('isDirectory')->once()->andReturn(false);

        L4shell::setExecutablePath('/thisdirectorydoesnotexist');

    }

    public function testSetExecutablePathIsSuccessful ()
    {
        File::shouldReceive('isDirectory')->once()->andReturn(true);
        Log::shouldReceive('info')->once();

        $command = L4shell::setExecutablePath('/');
        $expected = "/";

        $this->assertEquals($expected, $command->getExecutablePath());

    }

    public function testExecutablePathIsAddedToCommand ()
    {
        File::shouldReceive('isDirectory')->once()->andReturn(true);
        Log::shouldReceive('info')->twice();

        $command = L4shell::setCommand("ls");
        $command->setExecutablePath("/");
        $expected = "/ls";

        $this->assertEquals($expected, $command->getCommand());

    }

    public function testExecutionPathIsClearedWhenPassedNull ()
    {
        Log::shouldReceive('info')->once();

        $command = L4shell::setExecutionPath();

        $expected = null;
        $this->assertEquals($expected, $command->getExecutionPath());

    }

    public function testExecutablePathIsClearedWhenPassedNull ()
    {
        Log::shouldReceive('info')->once();

        $command = L4shell::setExecutablePath();

        $expected = null;
        $this->assertEquals($expected, $command->getExecutablePath());

    }

    public function testSettingMultipleArgumentsInSequenceReturnsObject ()
    {
        Log::shouldReceive('info')->never();

        $command = L4shell::get();
        $command->setLogging(false)
                ->setArguments(array("arg1"))
                ->setArguments(array("arg2"), true);

        $this->assertInstanceOf('Netson\L4shell\Command', $command);

    }

    public function testSettingMultipleArgumentsInSequenceIsSuccessful ()
    {
        Log::shouldReceive('info')->never();

        $command = L4shell::get();
        $command->setLogging(false)
                ->setCommand("ls %s %s")
                ->setArguments(array("-a"))
                ->setArguments(array("-l"), true);

        $expected = "ls '-a' '-l'";
        $this->assertEquals($expected, $command->getCommand());
        $this->assertCount(2, $command->getArguments());

    }

    public function testSettingSingleArgumentsInSequenceIsSuccessful ()
    {
        Log::shouldReceive('info')->never();

        $command = L4shell::get();
        $command->setLogging(false)
                ->setCommand("ls %s %s")
                ->setArguments(array("-a", "-l"))
                ->setArguments(array("-l", "-a"));

        $expected = "ls '-l' '-a'";
        $this->assertEquals($expected, $command->getCommand());
        $this->assertCount(2, $command->getArguments());

    }

    public function testGetArgumentsReturnsArray ()
    {
        Log::shouldReceive('info')->never();

        $command = L4shell::get();
        $command->setLogging(false)
                ->setArguments(array("-a", "-l"));

        $this->assertInternalType('array', $command->getArguments());

    }

    public function tearDown ()
    {
        \Mockery::close();

    }

}

?>