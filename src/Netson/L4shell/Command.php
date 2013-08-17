<?php
namespace Netson\L4shell;

use Config;
use Log;
use File;

class Command {

    /**
     * variable to enable/diable logging
     *
     * @var boolean
     */
    protected $logging;

    /**
     * variable which holds the (escaped) shell command to be executed
     * can be set via constructor or via setCommand method
     *
     * @var string
     */
    protected $command;

    /**
     * variable which holds the arguments for the command, if any
     * can be set via constructor or via setArguments method
     *
     * @var array
     */
    protected $arguments = array();

    /**
     * variable that will contain the exit status of the executed command, after executing
     *
     * @var integer
     */
    protected $exit_status = 0;

    /**
     * array which holds the result of the executed command
     *
     * @var array
     */
    protected $result = array();

    /**
     * variable used to send output to /dev/null
     *
     * @var string
     */
    protected $devnull = "";

    /**
     * array that holds allowed characters which will NOT be escaped
     *
     * @var array
     */
    protected $allowed_characters = array();

    /**
     * variable that contains the path which will be cd'd to before executing the command
     * this variable is static so it remains set across command calls
     *
     * @var string
     */
    protected static $execution_path = null;

    /**
     * variable that contains the path where the executable command is supposed to be located
     * this variable is static so it remains set across command calls
     *
     * @var string
     */
    protected static $executable_path = null;

    /**
     * variable that holds the current working directory, before changing to the execution path
     *
     * @var string
     */
    protected $cwd;

    /**
     * constructor method
     * accepts an optional $locale, otherwise the default will be used
     *
     * @param string $locale
     */
    public function __construct ($command = null, array $arguments = array())
    {
        // set logging from config file
        $this->setLogging(Config::get("l4shell::config.enable_logging"));

        // set command
        $this->setCommand($command);

        // set arguments
        $this->setArguments($arguments);

        // return object to allow chaining
        return $this;

    }

    /**
     * method returns the command object
     *
     * @return \Netson\L4shell\Command
     */
    public function get ()
    {
        // return
        return $this;

    }

    /**
     * method to set and escape the command to be executed
     * you should set any arguments you have as %s (sprintf) variables in the string
     *
     * @param string $command
     */
    public function setCommand ($command)
    {
        // sanity check
        if (!is_null($command))
        {
            // set command
            $this->command = escapeshellcmd((string) $command);

            // add to log
            if ($this->logging)
                Log::info("Command set to: " . $this->command, array("context" => "l4shell"));
        }

        // return object to allow chaining
        return $this;

    }

    /**
     * method to set and escape all arguments
     *
     * @param array $arguments
     */
    public function setArguments (array $arguments, $keep_existing_arguments = false)
    {
        // check to clear existing arguments
        if (!$keep_existing_arguments)
            $this->clearArguments();

        // sniaty check
        if (count($arguments) > 0)
        {
            // loop through all arguments
            foreach ($arguments as $arg)
                $this->arguments[] = escapeshellarg((string) $arg);

            // log
            if ($this->logging)
                Log::info("Arguments for command set to: " . implode(" | ", $this->arguments), array("context" => "l4shell"));
        }
        // return object to allow chaining
        return $this;

    }

    /**
     * method to clear any existing arguments
     *
     * @return \Netson\L4shell\Command
     */
    public function clearArguments ()
    {
        // clear existing arguments
        $this->arguments = array();

        // return object to allow chaining
        return $this;

    }

    /**
     * method to execute the set command
     *
     * @return string
     * @throws InvalidUsageException
     * @throws CommandNotFoundException
     * @throws NonExecutableCommandException
     * @throws UnknownException
     * @throws NoExecFunctionException
     */
    public function execute ()
    {
        // get command
        $command = $this->getCommand();

        // sanity check
        if (!function_exists('exec'))
            throw new NoExecFunctionException("the command cannot be executed because the exec() function is not available.\nYou can attempt to execute the command manually:\n\n$command");

        // check if execution folder should be changed
        if (!is_null($this->getExecutionPath()))
            $this->setCwd();

        // log
        if ($this->logging)
            Log::info("Executing command: " . $command, array("context" => "l4shell"));

        // execute command
        @exec($command, $this->result, $this->exit_status);

        // implode result
        $result = implode("\n", $this->result);

        // check if execution folder should be reverted
        if (!is_null($this->getExecutionPath()))
            $this->revertCwd();

        // check result
        if ($this->exit_status === 0)
        {
            // log success
            Log::info("Command [{$command}] successfully executed", array("l4shell"));

            // return
            return $result;
        }

        // error handling
        switch ($this->exit_status)
        {
            case 2:
                Log::error("The given command was used incorrectly (exit status 2) - [{$command}]", array("l4shell"));
                throw new InvalidUsageException("The given command was used incorrectly (exit status 2) - [{$command}]");
                break;
            case 127:
                Log::error("The given command could not be found (exit status 127) - [{$command}]", array("l4shell"));
                throw new CommandNotFoundException("The given command could not be found (exit status 127) - [{$command}]");
                break;
            case 126:
                Log::error("The given command is not executable (exit status 126) - [{$command}]", array("l4shell"));
                throw new NonExecutableCommandException("The given command is not executable (exit status 126) - [{$command}]");
                break;
            default:
                Log::error("The given command could not be executed (exit status {$this->exit_status}) - [{$command}]", array("l4shell"));
                throw new UnknownException("The given command could not be executed (exit status {$this->exit_status}) - [{$command}]");
                break;
        }

    }

    /**
     * method to enable/disable logging
     *
     * @param boolean $enable
     */
    public function setLogging ($enable)
    {
        $this->logging = (bool) $enable;

        // return object to allow chaining
        return $this;

    }

    /**
     * method to allow sending output to /dev/null
     *
     * @param boolean $enable
     */
    public function sendToDevNull ($enable = true)
    {
        if ($enable === true)
            $this->devnull = " > /dev/null 2>&1";
        else
            $this->devnull = "";

        // allow object chaining
        return $this;

    }

    /**
     * method to return the command as a string
     *
     * @return string
     */
    public function getCommand ()
    {
        // counters
        $argument_count = count($this->getArguments());
        $command_count = substr_count($this->command, "%s");

        // sanity check
        if ($command_count !== $argument_count)
            throw new InvalidNumberOfArgumentsException("The given number of arguments [$argument_count] is not equal to the number of arguments in the command [$command_count]");

        // sanity check
        if (is_null($this->command))
            throw new CommandNotSetException("A valid command has not been set; please set a oommand using the setCommand() method");

        // check for executable path
        if (!is_null($this->getExecutablePath()))
            $this->command = trim($this->getExecutablePath()) . trim($this->command);

        // replace argument placeholder with escaped argument
        $command = vsprintf($this->command, $this->getArguments()) . $this->devnull;

        // check for allowed characters
        return $this->unescapeAllowedCharacters($command);

    }

    /**
     * method to fetch the set arguments
     * 
     * @return array
     */
    public function getArguments ()
    {
        // return
        return $this->arguments;

    }

    /**
     * method to dump the current command to string
     *
     * @return string
     */
    public function __toString ()
    {
        return (string) $this->getCommand();

    }

    /**
     * method to set allowed characters; these characters will NOT be escaped
     * this can be useful when you, for example want to pass an asterisk as an argument without it being escaped
     * USE WITH CUATION!
     *
     * @param array $characters
     * @return \Netson\L4shell\Command
     */
    public function setAllowedCharacters (array $characters = array())
    {
        // log this activity and print to screen
        Log::info("Setting allowed characters: " . implode(" - ", $characters), array("l4shell"));

        // set characters
        $this->allowed_characters = $characters;

        // return object to allow chaining
        return $this;

    }

    /**
     * method to allow certain characters; these will NOT be escaped
     * USE WITH CAUTION!!
     *
     * @param string $command
     * @return string
     */
    protected function unescapeAllowedCharacters ($command)
    {
        // loop through all allowed characters to unescape them
        foreach ($this->allowed_characters as $char)
        {
            // set escaped character
            $esc = "\\" . $char;

            // replace escaped version with unescaped version
            $command = str_replace($esc, $char, $command);
        }

        // return command
        return $command;

    }

    /**
     * method to set a static variable of the path where the command will be executed (cd)
     *
     * @param type $path
     * @return \Netson\L4shell\Command
     * @throws ExecutionPathNotFoundException
     */
    public function setExecutionPath ($path = null)
    {
        // sanity check
        if (!is_null($path) && !File::isDirectory($path))
            throw new ExecutionPathNotFoundException("The given execution path [$path] does not exist");

        // set execution path
        self::$execution_path = $path;

        // log
        if ($this->logging)
            Log::info("Execution path set to: " . $path, array("context" => "l4shell"));

        // return object to allow chaining
        return $this;

    }

    /**
     * method to fetch the execution path
     *
     * @return string|null
     */
    public function getExecutionPath ()
    {
        return self::$execution_path;

    }

    /**
     * method to set a static variable of the path where the executable(s) is/are located
     *
     * @param string $path
     * @return \Netson\L4shell\Command
     * @throws ExecutablePathNotFoundException
     */
    public function setExecutablePath ($path = null)
    {
        // sanity check
        if (!is_null($path) && !File::isDirectory($path))
            throw new ExecutablePathNotFoundException("The given executable path [$path] does not exist");

        // set executable path
        self::$executable_path = $path;

        // log
        if ($this->logging)
            Log::info("Executable path set to: " . $path, array("context" => "l4shell"));

        // return object to allow chaining
        return $this;

    }

    /**
     * method to return executable path
     *
     * @return string|null
     */
    public function getExecutablePath ()
    {
        return self::$executable_path;

    }

    /**
     * method to retreive the current working directory
     *
     * @return string|boolean
     */
    public function getCwd ()
    {
        return getcwd();

    }

    /**
     * method to change the current working directory
     *
     * @return string|boolean
     * @throws WorkingDirectoryCouldNotBeSetException
     */
    protected function setCwd ()
    {
        // set current working directory
        $this->cwd = $this->getCwd();

        // path
        $path = $this->getExecutionPath();

        // change directory
        if (!chdir($path))
            throw new WorkingDirectoryCouldNotBeSetException("The working directory [$path] could not be set");

        // log
        if ($this->logging)
            Log::info("Changing working directory / execution path to: " . $path, array("context" => "l4shell"));

        // return
        return $this->getCwd();

    }

    /**
     * method to change the working directory back to what it was before executing the command
     *
     * @return string|boolean
     * @throws WorkingDirectoryCouldNotBeSetException
     */
    protected function revertCwd ()
    {
        // change directory
        if (!chdir($this->cwd))
            throw new WorkingDirectoryCouldNotBeSetException("The working directory could not be set to the original [$this->cwd]");

        // log
        if ($this->logging)
            Log::info("Reverting working directory / execution path to: " . $this->cwd, array("context" => "l4shell"));

        // return
        return getcwd();

    }

}

?>