<?php
namespace Netson\L4shell;

use Config;
use Log;

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
    public function setArguments (array $arguments)
    {
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
     * method to execute the set command
     *
     * @return string
     * @throws InvalidUsageException
     * @throws CommandNotFoundException
     * @throws NonExecutableCommandException
     * @throws UnknownException
     */
    public function execute ()
    {
        // get command
        $command = $this->getCommand();

        // log
        if ($this->logging)
            Log::info("Executing command: " . $command, array("context" => "l4shell"));

        // execute command
        @exec($command, $this->result, $this->exit_status);

        // implode result
        $result = implode("\n", $this->result);

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
                Log::error("The given command was used incorrectly (exit status 2)", array("l4shell"));
                throw new InvalidUsageException("The given command was used incorrectly (exit status 2)");
                break;
            case 127:
                Log::error("The given command could not be found (exit status 127)", array("l4shell"));
                throw new CommandNotFoundException("The given command could not be found (exit status 127)");
                break;
            case 126:
                Log::error("The given command is not executable (exit status 126)", array("l4shell"));
                throw new NonExecutableCommandException("The given command is not executable (exit status 126)");
                break;
            default:
                Log::error("The given command could not be executed (exit status {$this->exit_status})", array("l4shell"));
                throw new UnknownException("The given command could not be executed (exit status {$this->exit_status})");
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
            $this->devnull = "> /dev/null 2>&1";
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
        $argument_count = count($this->arguments);
        $command_count = substr_count($this->command, "%s");

        // sanity check
        if ($command_count !== $argument_count)
            throw new InvalidNumberOfArgumentsException("The given number of arguments [$argument_count] is not equal to the number of arguments in the command [$command_count]");

        // sanity check
        if (is_null($this->command))
            throw new CommandNotSetException("A valid command has not been set; please set a oommand using the setCommand() method");

        // replace argument placeholder with escaped argument
        return vsprintf($this->command, $this->arguments) . $this->devnull;

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

}

?>