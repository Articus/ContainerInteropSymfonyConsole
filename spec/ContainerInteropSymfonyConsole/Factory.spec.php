<?php
declare(strict_types=1);

namespace spec\ContainerInteropSymfonyConsole;

use spec\Example;
use ContainerInteropSymfonyConsole as CISC;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

\describe(CISC\Factory::class, function ()
{
	\afterEach(function ()
	{
		\Mockery::close();
	});
	\it('gets configuration from default config key', function ()
	{
		$configKey = Application::class;
		$container = \mock(ContainerInterface::class);
		$config = \mock(\ArrayAccess::class);

		$container->shouldReceive('get')->with('config')->andReturn($config)->once();
		$config->shouldReceive('offsetExists')->with($configKey)->andReturn(true)->once();
		$config->shouldReceive('offsetGet')->with($configKey)->andReturn([])->once();

		$factory = new CISC\Factory();
		\expect($factory($container))->toBeAnInstanceOf(Application::class);
	});
	\it('gets configuration from custom config key', function ()
	{
		$configKey = 'test_config_key';
		$container = \mock(ContainerInterface::class);
		$config = \mock(\ArrayAccess::class);

		$container->shouldReceive('get')->with('config')->andReturn($config)->once();
		$config->shouldReceive('offsetExists')->with($configKey)->andReturn(true)->once();
		$config->shouldReceive('offsetGet')->with($configKey)->andReturn([])->once();

		\expect(CISC\Factory::{$configKey}($container))->toBeAnInstanceOf(Application::class);
	});
	\it('creates service with empty configuration', function ()
	{
		$container = \mock(ContainerInterface::class);

		$container->shouldReceive('get')->with('config')->andReturn([])->once();

		$factory = new CISC\Factory();
		$app = $factory($container);
 		\expect($app->getName())->toBe('Application');
		\expect($app->getVersion())->toBe('1.0.0');
		\expect($app->areExceptionsCaught())->toBe(true);
		\expect($app->isAutoExitEnabled())->toBe(true);
	});
	\it('creates service with custom scalar parameters', function ()
	{
		$options = [
			'name' => 'Test Name',
			'version' => '1.2.3',
			'catch_exceptions' => false,
			'auto_exit' => false,
		];
		$container = \mock(ContainerInterface::class);

		$container->shouldReceive('get')->with('config')->andReturn([Application::class => $options])->once();

		$factory = new CISC\Factory();
		$app = $factory($container);
		\expect($app->getName())->toBe($options['name']);
		\expect($app->getVersion())->toBe($options['version']);
		\expect($app->areExceptionsCaught())->toBe($options['catch_exceptions']);
		\expect($app->isAutoExitEnabled())->toBe($options['auto_exit']);
	});
	\it('creates service with command service name', function ()
	{
		$commandServiceName = 'test_service_name';
		$commandName = 'test_command_name';
		$options = [
			'commands' => [$commandServiceName],
		];
		$container = \mock(ContainerInterface::class);
		$command = new Example\TestCommand($commandName);

		$container->shouldReceive('get')->with('config')->andReturn([Application::class => $options])->once();
		$container->shouldReceive('get')->with($commandServiceName)->andReturn($command)->once();

		$factory = new CISC\Factory();
		$app = $factory($container);
		\expect($app->get($commandName))->toBe($command);
	});
	\it('creates service with command loader service name', function ()
	{
		$commandLoaderServiceName = 'test_service_name';
		$options = [
			'command_loader' => $commandLoaderServiceName,
		];
		$container = \mock(ContainerInterface::class);
		$commandLoader = \mock(CommandLoaderInterface::class);

		$container->shouldReceive('get')->with('config')->andReturn([Application::class => $options])->once();
		$container->shouldReceive('get')->with($commandLoaderServiceName)->andReturn($commandLoader)->once();

		$factory = new CISC\Factory();
		$app = $factory($container);
		\expect(\propertyByPath($app, ['commandLoader']))->toBe($commandLoader);
	});
	\it('creates service with merger command loader configuration', function ()
	{
		$commandLoaderServiceName = 'test_service_name';
		$options = [
			'command_loader' => [
				'loaders' => [$commandLoaderServiceName],
			],
		];
		$container = \mock(ContainerInterface::class);
		$commandLoader = \mock(CommandLoaderInterface::class);

		$container->shouldReceive('get')->with('config')->andReturn([Application::class => $options])->once();
		$container->shouldReceive('get')->with($commandLoaderServiceName)->andReturn($commandLoader)->once();
		$commandLoader->shouldReceive('getNames')->andReturn(['test_command'])->once();

		$factory = new CISC\Factory();
		$app = $factory($container);
		\expect(\propertyByPath($app, ['commandLoader']))->toBeAnInstanceOf(CISC\CommandLoader\Merger::class);
		\expect(\propertyByPath($app, ['commandLoader', 'loaders']))->toBe([$commandLoader]);
	});
	\it('creates service with event dispatcher service name', function ()
	{
		$eventDispatcherServiceName = 'test_service_name';
		$options = [
			'event_dispatcher' => $eventDispatcherServiceName,
		];
		$container = \mock(ContainerInterface::class);
		$eventDispatcher = \mock(EventDispatcherInterface::class);

		$container->shouldReceive('get')->with('config')->andReturn([Application::class => $options])->once();
		$container->shouldReceive('get')->with($eventDispatcherServiceName)->andReturn($eventDispatcher)->once();

		$factory = new CISC\Factory();
		$app = $factory($container);
		\expect(\propertyByPath($app, ['dispatcher']))->toBe($eventDispatcher);
	});
	\it('creates service with event dispatcher configuration', function ()
	{
		$subscriberServiceName = 'test_service_name';
		$eventName = 'test_event';
		$eventMethod = 'test_method';
		$options = [
			'event_dispatcher' => [
				'subscribers' => [$subscriberServiceName],
			],
		];
		$container = \mock(ContainerInterface::class);
		$subscriber = \mock(EventSubscriberInterface::class);

		$container->shouldReceive('get')->with('config')->andReturn([Application::class => $options])->once();
		$container->shouldReceive('get')->with($subscriberServiceName)->andReturn($subscriber)->once();
		$subscriber->shouldReceive('getSubscribedEvents')->andReturn([$eventName => $eventMethod])->once();

		$factory = new CISC\Factory();
		$app = $factory($container);
		\expect(\propertyByPath($app, ['dispatcher']))->toBeAnInstanceOf(EventDispatcher::class);
		\expect(\propertyByPath($app, ['dispatcher', 'listeners']))->toBe([$eventName => [0 => [[$subscriber, $eventMethod]]]]);
	});
	\it('creates service with indexed helper service name', function ()
	{
		$helperServiceName = 'test_service_name';
		$options = [
			'helpers' => [123 => $helperServiceName],
		];
		$container = \mock(ContainerInterface::class);
		$helper = new Example\TestHelper();

		$container->shouldReceive('get')->with('config')->andReturn([Application::class => $options])->once();
		$container->shouldReceive('get')->with($helperServiceName)->andReturn($helper)->once();

		$factory = new CISC\Factory();
		$app = $factory($container);
		\expect($app->getHelperSet()->get($helper->getName()))->toBe($helper);
	});
	\it('creates service with aliased helper service name', function ()
	{
		$helperServiceName = 'test_service_name';
		$helperAlias = 'test_alias';
		$options = [
			'helpers' => [$helperAlias => $helperServiceName],
		];
		$container = \mock(ContainerInterface::class);
		$helper = new Example\TestHelper();

		$container->shouldReceive('get')->with('config')->andReturn([Application::class => $options])->once();
		$container->shouldReceive('get')->with($helperServiceName)->andReturn($helper)->once();

		$factory = new CISC\Factory();
		$app = $factory($container);
		\expect($app->getHelperSet()->get($helper->getName()))->toBe($helper);
		\expect($app->getHelperSet()->get($helperAlias))->toBe($helper);
	});
});
