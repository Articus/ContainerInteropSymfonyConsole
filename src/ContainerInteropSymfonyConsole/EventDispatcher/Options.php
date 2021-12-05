<?php
declare(strict_types=1);

namespace ContainerInteropSymfonyConsole\EventDispatcher;

class Options
{
	/**
	 * Container service names of event subscribers that should be added to event dispatcher
	 * @var array<string>
	 */
	public $subscribers = [];

	public function __construct(iterable $options)
	{
		foreach ($options as $key => $value)
		{
			switch ($key)
			{
				case 'subscribers':
				case 'eventSubscribers':
				case 'event_subscribers':
					$this->subscribers = $value;
					break;
			}
		}
	}
}
