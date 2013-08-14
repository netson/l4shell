<?php namespace Netson\L4shell;

// include exception namespace
use Exception;

/**
 * thrown when command was used improperly
 */
class InvalidUsageException extends Exception {};

/**
 * thrown when command could not be found on system
 */
class CommandNotFoundException extends Exception {};

/**
 * thrown when the command is not executable
 */
class NonExecutableCommandException extends Exception {};

/**
 * thrown when the exit code is not 0, 2, 126 or 127
 */
class UnknownException extends Exception {};

/**
 * thrown when the number of provided arguments does not match the number of arguments set in the command
 */
class InvalidNumberOfArgumentsException extends Exception {};

/**
 * thrown when getCommand() is called without setting the command first
 */
class CommandNotSetException extends Exception {};

/**
 * thrown when the exec() function is unavailable
 */
class NoExecFunctionException extends Exception {};
?>