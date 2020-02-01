<?php
declare(strict_types=1);

namespace ContainerInteropSymfonyConsole;

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;
use Symfony\Component\Console\Helper\HelperInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
	 * @return mixed
	 * @throws \Psr\Container\ContainerExceptionInterface
	 * @throws \Psr\Container\NotFoundExceptionInterface
	 */
	public static function __callStatic(string $name, array $arguments)
	{
		if (!((\count($arguments) > 0) && ($arguments[0] instanceof ContainerInterface)))
		{
			throw new \InvalidArgumentException(\sprintf(
				'To invoke %s with custom configuration key statically first argument should be %s, not %s',
				static::class,
				ContainerInterface::class,
				\is_object($arguments[0]) ? \get_class($arguments[0]) : \gettype($arguments[0])
			));
		}
		return (new static($name))->__invoke($arguments[0]);
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

		$result = new Application($options->getName(), $options->getVersion());
		$result->setCatchExceptions($options->getCatchExceptions());
		$result->setAutoExit($options->getAutoExit());

		foreach ($options->getCommands() as $commandNameOrInstance)
		{
			$result->add($this->getValidServiceInstance($commandNameOrInstance, $container, Command::class));
		}

		if ($options->getCommandLoader() !== null)
		{
			$result->setCommandLoader(
				$this->getValidServiceInstance($options->getCommandLoader(), $container, CommandLoaderInterface::class)
			);
		}

		if ($options->getEventDispatcher() !== null)
		{
			$result->setDispatcher(
				$this->getValidServiceInstance($options->getEventDispatcher(), $container, EventDispatcherInterface::class)
			);
		}

		foreach ($options->getHelpers() as $key => $helperNameOrInstance)
		{
			$result->getHelperSet()->set(
				$this->getValidServiceInstance($helperNameOrInstance, $container, HelperInterface::class),
				\is_int($key) ? null : $key
			);
		}
		return $result;
	}

	/**
	 * @param $serviceNameOrInstance
	 * @param ContainerInterface $container
	 * @param string $serviceClassName
	 * @return mixed|null
	 * @throws \Psr\Container\ContainerExceptionInterface
	 * @throws \Psr\Container\NotFoundExceptionInterface
	 */
	protected function getValidServiceInstance($serviceNameOrInstance, ContainerInterface $container, string $serviceClassName)
	{
		$result = null;
		switch (true)
		{
			case ($serviceNameOrInstance instanceof $serviceClassName):
				$result = $serviceNameOrInstance;
				break;
			case (\is_string($serviceNameOrInstance) && $container->has($serviceNameOrInstance)):
				$result = $container->get($serviceNameOrInstance);
				if (!($result instanceof $serviceClassName))
				{
					throw new \InvalidArgumentException(\sprintf(
						'Service %s should be %s, not %s.',
						$serviceNameOrInstance,
						$serviceClassName,
						\is_object($result) ? \get_class($result) : \gettype($result)
					));
				}
				break;
			default:
				throw new \InvalidArgumentException(\sprintf(
					'Expecting either valid service name or instance of %s, not %s.',
					$serviceClassName,
					\is_object($serviceNameOrInstance) ? \get_class($serviceNameOrInstance) : \gettype($serviceNameOrInstance)
				));
		}
		return $result;
	}
}
