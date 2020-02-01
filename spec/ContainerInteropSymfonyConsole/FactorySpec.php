<?php
declare(strict_types=1);

namespace spec\ContainerInteropSymfonyConsole;

use spec\Example;
use ContainerInteropSymfonyConsole\Factory;
use PhpSpec\ObjectBehavior;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;
use Symfony\Component\Console\Helper\HelperInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class FactorySpec extends ObjectBehavior
{
	public function getMatchers(): array
	{
		return [
			//Seems to be the only way to check correct initialization inside factory :(
			'haveProperty' => function ($subject, $propertyName, $propertyValue)
			{
				$classReflection = new \ReflectionClass($subject);
				$propertyReflection = $classReflection->getProperty($propertyName);
				$propertyReflection->setAccessible(true);
				return $propertyReflection->getValue($subject) === $propertyValue;
			}
		];
	}

	public function it_throws_if_config_service_is_missing(ContainerInterface $container)
	{
		$error = new Example\TestException('No config service');
		$container->get('config')->shouldBeCalledOnce()->willThrow($error);

		$this->shouldThrow($error)->during('__invoke', [$container]);
	}

	public function it_gets_configuration_from_default_config_key(ContainerInterface $container, \ArrayAccess $config)
	{
		$configKey = Application::class;
		$container->get('config')->shouldBeCalledOnce()->willReturn($config);
		$config->offsetExists($configKey)->shouldBeCalledOnce()->willReturn(true);
		$config->offsetGet($configKey)->shouldBeCalledOnce();

		$app = $this->__invoke($container);
		$app->shouldBeAnInstanceOf(Application::class);
	}

	public function it_gets_configuration_from_custom_config_key(ContainerInterface $container, \ArrayAccess $config)
	{
		$configKey = 'test_config_key';
		$container->get('config')->shouldBeCalledOnce()->willReturn($config);
		$config->offsetExists($configKey)->shouldBeCalledOnce()->willReturn(true);
		$config->offsetGet($configKey)->shouldBeCalledOnce();

		$this->beConstructedWith($configKey);
		$app = $this->__invoke($container);
		$app->shouldBeAnInstanceOf(Application::class);
	}

	public function it_constructs_itself_and_gets_configuration_from_custom_config_key(ContainerInterface $container, \ArrayAccess $config)
	{
		$configKey = 'test_config_key';
		$container->get('config')->shouldBeCalledOnce()->willReturn($config);
		$config->offsetExists($configKey)->shouldBeCalledOnce()->willReturn(true);
		$config->offsetGet($configKey)->shouldBeCalledOnce();

		$app = $this::__callStatic($configKey, [$container]);
		$app->shouldBeAnInstanceOf(Application::class);
	}

	public function it_throws_on_invalid_container_instance_during_self_construct()
	{
		$configKey = 'test_config_key';
		$error = new \InvalidArgumentException(\sprintf(
			'To invoke %s with custom configuration key statically first argument should be %s, not %s',
			Factory::class,
			ContainerInterface::class,
			'stdClass'
		));

		$this::shouldThrow($error)->during('__callStatic', [$configKey, [new \stdClass()]]);
	}

	public function it_creates_service_with_empty_configuration(ContainerInterface $container)
	{
		$container->get('config')->shouldBeCalledOnce()->willReturn([]);

		$app = $this->__invoke($container);
		$app->shouldBeAnInstanceOf(Application::class);
		$app->getName()->shouldBe('Application');
		$app->getVersion()->shouldBe('1.0.0');
		$app->areExceptionsCaught()->shouldBe(true);
		$app->isAutoExitEnabled()->shouldBe(true);
	}

	public function it_creates_service_with_custom_scalar_parameters(ContainerInterface $container)
	{
		$options = [
			'name' => 'Test Name',
			'version' => '1.2.3',
			'catch_exceptions' => false,
			'auto_exit' => false,
		];
		$container->get('config')->shouldBeCalledOnce()->willReturn([Application::class => $options]);

		$app = $this->__invoke($container);
		$app->shouldBeAnInstanceOf(Application::class);
		$app->getName()->shouldBe($options['name']);
		$app->getVersion()->shouldBe($options['version']);
		$app->areExceptionsCaught()->shouldBe($options['catch_exceptions']);
		$app->isAutoExitEnabled()->shouldBe($options['auto_exit']);
	}

	public function it_creates_service_with_command_name(ContainerInterface $container)
	{
		$commandName = 'test_command';
		$command = new Example\TestCommand($commandName);
		$options = ['commands' => [$commandName]];
		$container->get('config')->shouldBeCalledOnce()->willReturn([Application::class => $options]);
		$container->has($commandName)->shouldBeCalledOnce()->willReturn(true);
		$container->get($commandName)->shouldBeCalledOnce()->willReturn($command);

		$app = $this->__invoke($container);
		$app->shouldBeAnInstanceOf(Application::class);
		$app->get($commandName)->shouldBe($command);
	}

	public function it_creates_service_with_command_instance(ContainerInterface $container)
	{
		$commandName = 'test_command';
		$command = new Example\TestCommand($commandName);
		$options = ['commands' => [$command]];
		$container->get('config')->shouldBeCalledOnce()->willReturn([Application::class => $options]);

		$app = $this->__invoke($container);
		$app->shouldBeAnInstanceOf(Application::class);
		$app->get($commandName)->shouldBe($command);
	}

	public function it_throws_on_unknown_command_name(ContainerInterface $container)
	{
		$commandName = 'test_command';
		$options = ['commands' => [$commandName]];
		$error = new \InvalidArgumentException(\sprintf(
			'Expecting either valid service name or instance of %s, not %s.',
			Command::class,
			'string'
		));
		$container->get('config')->shouldBeCalledOnce()->willReturn([Application::class => $options]);
		$container->has($commandName)->shouldBeCalledOnce()->willReturn(false);

		$this->shouldThrow($error)->during('__invoke', [$container]);
	}

	public function it_throws_on_invalid_command_instance_in_container(ContainerInterface $container)
	{
		$commandName = 'test_command';
		$options = ['commands' => [$commandName]];
		$error = new \InvalidArgumentException(\sprintf(
			'Service %s should be %s, not %s.',
			$commandName,
			Command::class,
			\stdClass::class
		));
		$container->get('config')->shouldBeCalledOnce()->willReturn([Application::class => $options]);
		$container->has($commandName)->shouldBeCalledOnce()->willReturn(true);
		$container->get($commandName)->shouldBeCalledOnce()->willReturn(new \stdClass());

		$this->shouldThrow($error)->during('__invoke', [$container]);
	}

	public function it_throws_on_invalid_command_instance_in_config(ContainerInterface $container)
	{
		$options = ['commands' => [new \stdClass()]];
		$error = new \InvalidArgumentException(\sprintf(
			'Expecting either valid service name or instance of %s, not %s.',
			Command::class,
			'stdClass'
		));
		$container->get('config')->shouldBeCalledOnce()->willReturn([Application::class => $options]);

		$this->shouldThrow($error)->during('__invoke', [$container]);
	}

	public function it_creates_service_with_command_loader_name(ContainerInterface $container, CommandLoaderInterface $commandLoader)
	{
		$commandName = 'test_command';
		$command = new Example\TestCommand($commandName);
		$commandLoaderName = 'test_command_loader';
		$options = ['command_loader' => $commandLoaderName];
		$container->get('config')->shouldBeCalledOnce()->willReturn([Application::class => $options]);
		$container->has($commandLoaderName)->shouldBeCalledOnce()->willReturn(true);
		$container->get($commandLoaderName)->shouldBeCalledOnce()->willReturn($commandLoader);
		$commandLoader->has($commandName)->shouldBeCalledOnce()->willReturn(true);
		$commandLoader->get($commandName)->shouldBeCalledOnce()->willReturn($command);

		$app = $this->__invoke($container);
		$app->shouldBeAnInstanceOf(Application::class);
		$app->shouldHaveProperty('commandLoader', $commandLoader);
		$app->get($commandName)->shouldBe($command);
	}

	public function it_creates_service_with_command_loader_instance(ContainerInterface $container, CommandLoaderInterface $commandLoader)
	{
		$commandName = 'test_command';
		$command = new Example\TestCommand($commandName);
		$options = ['command_loader' => $commandLoader];
		$container->get('config')->shouldBeCalledOnce()->willReturn([Application::class => $options]);
		$commandLoader->has($commandName)->shouldBeCalledOnce()->willReturn(true);
		$commandLoader->get($commandName)->shouldBeCalledOnce()->willReturn($command);

		$app = $this->__invoke($container);
		$app->shouldBeAnInstanceOf(Application::class);
		$app->shouldHaveProperty('commandLoader', $commandLoader);
		$app->get($commandName)->shouldBe($command);
	}

	public function it_throws_on_unknown_command_loader_name(ContainerInterface $container)
	{
		$commandLoaderName = 'test_command_loader';
		$options = ['command_loader' => $commandLoaderName];
		$error = new \InvalidArgumentException(\sprintf(
			'Expecting either valid service name or instance of %s, not %s.',
			CommandLoaderInterface::class,
			'string'
		));
		$container->get('config')->shouldBeCalledOnce()->willReturn([Application::class => $options]);
		$container->has($commandLoaderName)->shouldBeCalledOnce()->willReturn(false);

		$this->shouldThrow($error)->during('__invoke', [$container]);
	}

	public function it_throws_on_invalid_command_loader_instance_in_container(ContainerInterface $container)
	{
		$commandLoaderName = 'test_command_loader';
		$options = ['command_loader' => $commandLoaderName];
		$error = new \InvalidArgumentException(\sprintf(
			'Service %s should be %s, not %s.',
			$commandLoaderName,
			CommandLoaderInterface::class,
			'stdClass'
		));
		$container->get('config')->shouldBeCalledOnce()->willReturn([Application::class => $options]);
		$container->has($commandLoaderName)->shouldBeCalledOnce()->willReturn(true);
		$container->get($commandLoaderName)->shouldBeCalledOnce()->willReturn(new \stdClass());

		$this->shouldThrow($error)->during('__invoke', [$container]);
	}

	public function it_throws_on_invalid_command_loader_instance_in_config(ContainerInterface $container)
	{
		$options = ['command_loader' => new \stdClass()];
		$error = new \InvalidArgumentException(\sprintf(
			'Expecting either valid service name or instance of %s, not %s.',
			CommandLoaderInterface::class,
			'stdClass'
		));
		$container->get('config')->shouldBeCalledOnce()->willReturn([Application::class => $options]);

		$this->shouldThrow($error)->during('__invoke', [$container]);
	}

	public function it_creates_service_with_event_dispatcher_name(ContainerInterface $container, EventDispatcherInterface $eventDispatcher)
	{
		$eventDispatcherName = 'test_event_dispatcher';
		$options = ['event_dispatcher' => $eventDispatcherName];

		$container->get('config')->shouldBeCalledOnce()->willReturn([Application::class => $options]);
		$container->has($eventDispatcherName)->shouldBeCalledOnce()->willReturn(true);
		$container->get($eventDispatcherName)->shouldBeCalledOnce()->willReturn($eventDispatcher);

		$app = $this->__invoke($container);
		$app->shouldBeAnInstanceOf(Application::class);
		$app->shouldHaveProperty('dispatcher', $eventDispatcher);
	}

	public function it_creates_service_with_event_dispatcher_instance(ContainerInterface $container, EventDispatcherInterface $eventDispatcher)
	{
		$options = ['event_dispatcher' => $eventDispatcher];

		$container->get('config')->shouldBeCalledOnce()->willReturn([Application::class => $options]);

		$app = $this->__invoke($container);
		$app->shouldBeAnInstanceOf(Application::class);
		$app->shouldHaveProperty('dispatcher', $eventDispatcher);
	}

	public function it_throws_on_unknown_event_dispatcher_name(ContainerInterface $container)
	{
		$eventDispatcherName = 'test_event_dispatcher';
		$options = ['event_dispatcher' => $eventDispatcherName];
		$error = new \InvalidArgumentException(\sprintf(
			'Expecting either valid service name or instance of %s, not %s.',
			EventDispatcherInterface::class,
			'string'
		));
		$container->get('config')->shouldBeCalledOnce()->willReturn([Application::class => $options]);
		$container->has($eventDispatcherName)->shouldBeCalledOnce()->willReturn(false);

		$this->shouldThrow($error)->during('__invoke', [$container]);
	}

	public function it_throws_on_invalid_event_dispatcher_instance_in_container(ContainerInterface $container)
	{
		$eventDispatcherName = 'test_event_dispatcher';
		$options = ['event_dispatcher' => $eventDispatcherName];
		$error = new \InvalidArgumentException(\sprintf(
			'Service %s should be %s, not %s.',
			$eventDispatcherName,
			EventDispatcherInterface::class,
			'stdClass'
		));
		$container->get('config')->shouldBeCalledOnce()->willReturn([Application::class => $options]);
		$container->has($eventDispatcherName)->shouldBeCalledOnce()->willReturn(true);
		$container->get($eventDispatcherName)->shouldBeCalledOnce()->willReturn(new \stdClass());

		$this->shouldThrow($error)->during('__invoke', [$container]);
	}

	public function it_throws_on_invalid_event_dispatcher_instance_in_config(ContainerInterface $container)
	{
		$options = ['event_dispatcher' => new \stdClass()];
		$error = new \InvalidArgumentException(\sprintf(
			'Expecting either valid service name or instance of %s, not %s.',
			EventDispatcherInterface::class,
			'stdClass'
		));
		$container->get('config')->shouldBeCalledOnce()->willReturn([Application::class => $options]);

		$this->shouldThrow($error)->during('__invoke', [$container]);
	}

	public function it_creates_service_with_helper_name(ContainerInterface $container)
	{
		$helperName = 'test_helper';
		$helper = new Example\TestHelper();
		$options = ['helpers' => [$helperName]];
		$container->get('config')->shouldBeCalledOnce()->willReturn([Application::class => $options]);
		$container->has($helperName)->shouldBeCalledOnce()->willReturn(true);
		$container->get($helperName)->shouldBeCalledOnce()->willReturn($helper);

		$app = $this->__invoke($container);
		$app->shouldBeAnInstanceOf(Application::class);
		$app->getHelperSet()->get($helper->getName())->shouldBe($helper);
	}

	public function it_creates_service_with_aliased_helper_name(ContainerInterface $container)
	{
		$helperName = 'test_helper';
		$helperAlias = 'test_alias';
		$helper = new Example\TestHelper();
		$options = ['helpers' => [$helperAlias => $helperName]];
		$container->get('config')->shouldBeCalledOnce()->willReturn([Application::class => $options]);
		$container->has($helperName)->shouldBeCalledOnce()->willReturn(true);
		$container->get($helperName)->shouldBeCalledOnce()->willReturn($helper);

		$app = $this->__invoke($container);
		$app->shouldBeAnInstanceOf(Application::class);
		$app->getHelperSet()->get($helper->getName())->shouldBe($helper);
		$app->getHelperSet()->get($helperAlias)->shouldBe($helper);
	}

	public function it_creates_service_with_helper_instance(ContainerInterface $container)
	{
		$helper = new Example\TestHelper();
		$options = ['helpers' => [$helper]];
		$container->get('config')->shouldBeCalledOnce()->willReturn([Application::class => $options]);

		$app = $this->__invoke($container);
		$app->shouldBeAnInstanceOf(Application::class);
		$app->getHelperSet()->get($helper->getName())->shouldBe($helper);
	}

	public function it_creates_service_with_aliased_helper_instance(ContainerInterface $container)
	{
		$helperAlias = 'test_alias';
		$helper = new Example\TestHelper();
		$options = ['helpers' => [$helperAlias => $helper]];
		$container->get('config')->shouldBeCalledOnce()->willReturn([Application::class => $options]);

		$app = $this->__invoke($container);
		$app->shouldBeAnInstanceOf(Application::class);
		$app->getHelperSet()->get($helper->getName())->shouldBe($helper);
		$app->getHelperSet()->get($helperAlias)->shouldBe($helper);
	}

	public function it_throws_on_unknown_helper_name(ContainerInterface $container)
	{
		$helperName = 'test_helper';
		$options = ['helpers' => [$helperName]];
		$error = new \InvalidArgumentException(\sprintf(
			'Expecting either valid service name or instance of %s, not %s.',
			HelperInterface::class,
			'string'
		));
		$container->get('config')->shouldBeCalledOnce()->willReturn([Application::class => $options]);
		$container->has($helperName)->shouldBeCalledOnce()->willReturn(false);

		$this->shouldThrow($error)->during('__invoke', [$container]);
	}

	public function it_throws_on_invalid_helper_instance_in_container(ContainerInterface $container)
	{
		$helperName = 'test_helper';
		$options = ['helpers' => [$helperName]];
		$error = new \InvalidArgumentException(\sprintf(
			'Service %s should be %s, not %s.',
			$helperName,
			HelperInterface::class,
			'stdClass'
		));
		$container->get('config')->shouldBeCalledOnce()->willReturn([Application::class => $options]);
		$container->has($helperName)->shouldBeCalledOnce()->willReturn(true);
		$container->get($helperName)->shouldBeCalledOnce()->willReturn(new \stdClass());

		$this->shouldThrow($error)->during('__invoke', [$container]);
	}

	public function it_throws_on_invalid_helper_instance_in_config(ContainerInterface $container)
	{
		$options = ['helpers' => [new \stdClass()]];
		$error = new \InvalidArgumentException(\sprintf(
			'Expecting either valid service name or instance of %s, not %s.',
			HelperInterface::class,
			'stdClass'
		));
		$container->get('config')->shouldBeCalledOnce()->willReturn([Application::class => $options]);

		$this->shouldThrow($error)->during('__invoke', [$container]);
	}
}
