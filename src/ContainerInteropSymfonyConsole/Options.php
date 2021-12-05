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
	 * Container service names of commands that should be added to console application
	 * @see Command
	 * @var string[]
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
	 * Container service names of command loader where console application should look for commands
	 * or options to construct command loader merger
	 * @see CommandLoaderInterface
	 * @see CommandLoader\Merger
	 * @var string|CommandLoader\Options\Merger|null
	 */
	public $commandLoader = null;

	/**
	 * Container service name of event dispatcher that console application should use
	 * or options to construct such event dispatcher
	 * @see EventDispatcherInterface
	 * @var string|EventDispatcher\Options|null
	 */
	public $eventDispatcher = null;

	/**
	 * Container service names of helpers that should be added to console application helper set.
	 * If map key is not numeric it is used as helper alias.
	 * @see HelperInterface
	 * @var array<string|int, string> map "optional helper alias" -> "helper service name"
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
					$this->commandLoader = \is_string($value) ? $value : new CommandLoader\Options\Merger($value);
					break;
				case 'eventDispatcher':
				case 'event_dispatcher':
					$this->eventDispatcher = \is_string($value) ? $value : new EventDispatcher\Options($value);
					break;
				case 'helpers':
					$this->helpers = $value;
					break;
			}
		}
	}
}
