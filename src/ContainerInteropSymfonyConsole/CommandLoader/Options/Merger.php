<?php
declare(strict_types=1);

namespace ContainerInteropSymfonyConsole\CommandLoader\Options;

class Merger
{
	/**
	 * Container service names of command loaders that should be merged
	 * @var string[]
	 */
	public $loaders = [];

	public function __construct(iterable $options)
	{
		foreach ($options as $key => $value)
		{
			switch ($key)
			{
				case 'loaders':
				case 'commandLoaders':
				case 'command_loaders':
					$this->loaders = $value;
					break;
			}
		}
	}
}
