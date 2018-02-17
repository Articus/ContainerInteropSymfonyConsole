<?php
namespace ContainerInteropSymfonyConsole;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;
use Symfony\Component\Console\Helper\HelperInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Zend\Stdlib\AbstractOptions;

class Options extends AbstractOptions
{
	/**
	 * Console application name
	 * @var string
	 */
	protected $name = 'Application';

	/**
	 * Console application version
	 * @var string
	 */
	protected $version = '1.0.0';

	/**
	 * List of service names or service instances that should be added as console application commands
	 * @var string[]|Command[]
	 */
	protected $commands = [];

	/**
	 * Flag if console application should catch exceptions thrown during application run
	 * @var bool
	 */
	protected $catchExceptions = true;

	/**
	 * Flag if console application run method should call 'exit()' with corresponding exit code on completion
	 * or simply return exit code as method result
	 * @see http://php.net/manual/en/function.exit.php
	 * @var bool
	 */
	protected $autoExit = true;

	/**
	 * Name in container or instance of command loader service for console application
	 * @var string|CommandLoaderInterface|null
	 */
	protected $commandLoader = null;

	/**
	 * Name in container or instance of event dispatcher service for console application
	 * @var string|EventDispatcherInterface|null
	 */
	protected $eventDispatcher = null;

	/**
	 * Map of service names or service instances that should be added to console application helper set with specified keys
	 * @var string[]|HelperInterface[] - Map<string, string|HelperInterface>
	 */
	protected $helpers = [];

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * @param string $name
	 */
	public function setName(string $name): void
	{
		$this->name = $name;
	}

	/**
	 * @return string
	 */
	public function getVersion(): string
	{
		return $this->version;
	}

	/**
	 * @param string $version
	 */
	public function setVersion(string $version): void
	{
		$this->version = $version;
	}

	/**
	 * @return string[]|Command[]
	 */
	public function getCommands(): array
	{
		return $this->commands;
	}

	/**
	 * @param string[]|Command[] $commands
	 */
	public function setCommands(array $commands): void
	{
		$this->commands = $commands;
	}

	/**
	 * @return bool
	 */
	public function getCatchExceptions(): bool
	{
		return $this->catchExceptions;
	}

	/**
	 * @param bool $catchExceptions
	 */
	public function setCatchExceptions(bool $catchExceptions): void
	{
		$this->catchExceptions = $catchExceptions;
	}

	/**
	 * @return bool
	 */
	public function getAutoExit(): bool
	{
		return $this->autoExit;
	}

	/**
	 * @param bool $autoExit
	 */
	public function setAutoExit(bool $autoExit): void
	{
		$this->autoExit = $autoExit;
	}

	/**
	 * @return null|string|CommandLoaderInterface
	 */
	public function getCommandLoader()
	{
		return $this->commandLoader;
	}

	/**
	 * @param null|string|CommandLoaderInterface $commandLoader
	 */
	public function setCommandLoader($commandLoader): void
	{
		$this->commandLoader = $commandLoader;
	}

	/**
	 * @return null|string|EventDispatcherInterface
	 */
	public function getEventDispatcher()
	{
		return $this->eventDispatcher;
	}

	/**
	 * @param null|string|EventDispatcherInterface $eventDispatcher
	 */
	public function setEventDispatcher($eventDispatcher): void
	{
		$this->eventDispatcher = $eventDispatcher;
	}

	/**
	 * @return string[]|HelperInterface[]
	 */
	public function getHelpers(): array
	{
		return $this->helpers;
	}

	/**
	 * @param string[]|HelperInterface[] $helpers
	 */
	public function setHelpers(array $helpers): void
	{
		$this->helpers = $helpers;
	}
}