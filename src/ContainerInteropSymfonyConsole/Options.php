<?php
declare(strict_types=1);

namespace ContainerInteropSymfonyConsole;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;
use Symfony\Component\Console\Helper\HelperInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Options
{
	/**
	 * Console application name
	 * @var string
	 */
	public $name = 'Application';

	/**
	 * Console application version
	 * @var string
	 */
	public $version = '1.0.0';

	/**
	 * List of service names or service instances that should be added as console application commands
	 * @var string[]|Command[]
	 */
	public $commands = [];

	/**
	 * Flag if console application should catch exceptions thrown during application run
	 * @var bool
	 */
	public $catchExceptions = true;

	/**
	 * Flag if console application run method should call 'exit()' with corresponding exit code on completion
	 * or simply return exit code as method result
	 * @see http://php.net/manual/en/function.exit.php
	 * @var bool
	 */
	public $autoExit = true;

	/**
	 * Name in container or instance of command loader service for console application
	 * @var string|CommandLoaderInterface|null
	 */
	public $commandLoader = null;

	/**
	 * Name in container or instance of event dispatcher service for console application
	 * @var string|EventDispatcherInterface|null
	 */
	public $eventDispatcher = null;

	/**
	 * Map of service names or service instances that should be added to console application helper set with specified keys
	 * @var string[]|HelperInterface[] - Map<string, string|HelperInterface>
	 */
	public $helpers = [];

	public function __construct(iterable $options)
	{
		foreach ($options as $key => $value)
		{
			switch ($key)
			{
				case 'name':
					$this->name = $value;
					break;
				case 'version':
					$this->version = $value;
					break;
				case 'commands':
					$this->commands = $value;
					break;
				case 'catchExceptions':
				case 'catch_exceptions':
					$this->catchExceptions = $value;
					break;
				case 'autoExit':
				case 'auto_exit':
					$this->autoExit = $value;
					break;
				case 'commandLoader':
				case 'command_loader':
					$this->commandLoader = $value;
					break;
				case 'eventDispatcher':
				case 'event_dispatcher':
					$this->eventDispatcher = $value;
					break;
				case 'helpers':
					$this->helpers = $value;
					break;
			}
		}
	}
}
