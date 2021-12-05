<?php
declare(strict_types=1);

namespace ContainerInteropSymfonyConsole\CommandLoader;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;
use Symfony\Component\Console\Exception\CommandNotFoundException;

/**
 * Merges commands from several command loaders together
 */
class Merger implements CommandLoaderInterface
{
	/**
	 * @var CommandLoaderInterface[]
	 */
	protected $loaders = [];

	/**
	 * @var array<string, int|string>
	 */
	protected $names = [];

	/**
	 * Adds new command loader to merge
	 * @param CommandLoaderInterface $loader
	 */
	public function addLoader(CommandLoaderInterface $loader): void
	{
		$loaderIndex = \array_push($this->loaders, $loader) - 1;
		foreach ($loader->getNames() as $name)
		{
			$this->names[$name] = $loaderIndex;
		}
	}

	/**
	 * @inheritdoc
	 */
	public function get($name): Command
	{
		$loaderIndex = $this->names[$name] ?? null;
		if ($loaderIndex === null)
		{
			throw new CommandNotFoundException(\sprintf('Unknown command "%s"', $name));
		}
		return $this->loaders[$loaderIndex]->get($name);
	}

	/**
	 * @inheritdoc
	 */
	public function has($name): bool
	{
		return isset($this->names[$name]);
	}

	/**
	 * @inheritdoc
	 */
	public function getNames(): array
	{
		return \array_keys($this->names);
	}
}
