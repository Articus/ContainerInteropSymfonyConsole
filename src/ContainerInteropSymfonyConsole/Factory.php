<?php
declare(strict_types=1);

namespace ContainerInteropSymfonyConsole;

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;
use Symfony\Component\Console\Helper\HelperInterface;
use Symfony\Component\EventDispatcher as SED;

class Factory
{
	/**
	 * Key inside Config service
	 * @var string
	 */
	protected $configKey;

	/**
	 * Factory constructor.
	 * @param string $configKey
	 */
	public function __construct(string $configKey = Application::class)
	{
		$this->configKey = $configKey;
	}

	/**
	 * "Static constructor" that simplifies creation of factory instances with custom configuration keys
	 * @param string $name
	 * @param array $arguments
	 * @return Application
	 * @throws \Psr\Container\ContainerExceptionInterface
	 * @throws \Psr\Container\NotFoundExceptionInterface
	 */
	public static function __callStatic(string $name, array $arguments)
	{
		return (new static($name))(...$arguments);
	}

	/**
	 * @param ContainerInterface $container
	 * @return Application
	 * @throws \Psr\Container\ContainerExceptionInterface
	 * @throws \Psr\Container\NotFoundExceptionInterface
	 */
	public function __invoke(ContainerInterface $container): Application
	{
		$config = $container->get('config');
		$options = new Options($config[$this->configKey] ?? []);

		$result = new Application($options->name, $options->version);
		$result->setCatchExceptions($options->catchExceptions);
		$result->setAutoExit($options->autoExit);

		foreach ($options->commands as $commandServiceName)
		{
			$result->add(self::getCommand($container, $commandServiceName));
		}

		if ($options->commandLoader !== null)
		{
			$commandLoader = null;
			if ($options->commandLoader instanceof CommandLoader\Options\Merger)
			{
				$commandLoader = new CommandLoader\Merger();
				foreach ($options->commandLoader->loaders as $loaderServiceName)
				{
					$commandLoader->addLoader(self::getLoader($container, $loaderServiceName));
				}
			}
			else
			{
				$commandLoader = self::getLoader($container, $options->commandLoader);
			}
			$result->setCommandLoader($commandLoader);
		}

		if ($options->eventDispatcher !== null)
		{
			$eventDispatcher = null;
			if ($options->eventDispatcher instanceof EventDispatcher\Options)
			{
				$eventDispatcher = new SED\EventDispatcher();
				foreach ($options->eventDispatcher->subscribers as $subscriberServiceName)
				{
					$eventDispatcher->addSubscriber(self::getSubscriber($container, $subscriberServiceName));
				}
			}
			else
			{
				$eventDispatcher = self::getDispatcher($container, $options->eventDispatcher);
			}
			$result->setDispatcher($eventDispatcher);
		}

		foreach ($options->helpers as $key => $helperServiceName)
		{
			$result->getHelperSet()->set(self::getHelper($container, $helperServiceName),  \is_int($key) ? null : $key);
		}
		return $result;
	}

	protected static function getCommand(ContainerInterface $container, string $serviceName): Command
	{
		return $container->get($serviceName);
	}

	protected static function getLoader(ContainerInterface $container, string $serviceName): CommandLoaderInterface
	{
		return $container->get($serviceName);
	}

	protected static function getSubscriber(ContainerInterface $container, string $serviceName): SED\EventSubscriberInterface
	{
		return $container->get($serviceName);
	}

	protected static function getDispatcher(ContainerInterface $container, string $serviceName): SED\EventDispatcherInterface
	{
		return $container->get($serviceName);
	}

	protected static function getHelper(ContainerInterface $container, string $serviceName): HelperInterface
	{
		return $container->get($serviceName);
	}
}
