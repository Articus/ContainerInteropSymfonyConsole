<?php
namespace Test\ContainerInteropSymfonyConsole;

use ContainerInteropSymfonyConsole\Factory;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;
use Symfony\Component\Console\Helper\HelperInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @method ObjectProphecy prophesize (string $className)
 * @method void verifyMockObjects ()
 */
class FactoryTest extends \Codeception\Test\Unit
{
    /**
     * @var \Test\ContainerInteropSymfonyConsole\UnitTester
     */
    protected $tester;
    
    protected function _before()
    {
    }

    protected function _after()
    {
    	$this->verifyMockObjects();
    }

    public function provideFactories()
	{
		return [
			'constructor' => [Application::class, new Factory()],
			'constructor with custom parameters' => ['custom_config', new Factory('custom_config')],
			'static constructor' => ['static_custom_config', [Factory::class, 'static_custom_config']],
		];
	}
	/**
	 * @dataProvider provideFactories
	 */
    public function testErrorIfConfigServiceIsMissing(string $configRoot, callable $factory)
    {
		$exception = new TestException('No config service');

		$containerProphecy = $this->prophesize(ContainerInterface::class);
		$containerProphecy->get('config')->willThrow($exception)->shouldBeCalledTimes(1);
		$container = $containerProphecy->reveal();

		$this->tester->expectException($exception, function() use ($factory, $container)
		{
			$factory($container);
		});
    }

	/**
	 * @dataProvider provideFactories
	 */
    public function testCreationWithEmptyConfiguration(string $configRoot, callable $factory)
	{
		$tester = $this->tester;

		$containerProphecy = $this->prophesize(ContainerInterface::class);
		$containerProphecy->get('config')->willReturn([])->shouldBeCalledTimes(1);
		$container = $containerProphecy->reveal();

		/** @var Application $app */
		$app = $factory($container);

		$tester->assertInstanceOf(Application::class, $app);
		$tester->assertEquals('Application', $app->getName());
		$tester->assertEquals('1.0.0', $app->getVersion());
		$tester->assertEquals(true, $app->areExceptionsCaught());
		$tester->assertEquals(true, $app->isAutoExitEnabled());
	}

	/**
	 * @dataProvider provideFactories
	 */
	public function testCreationWithCustomScalarParameters(string $configRoot, callable $factory)
	{
		$tester = $this->tester;

		$options = [
			'name' => 'Test Name',
			'version' => '1.2.3',
			'catch_exceptions' => false,
			'auto_exit' => false,
		];
		$config = [$configRoot => $options];

		$containerProphecy = $this->prophesize(ContainerInterface::class);
		$containerProphecy->get('config')->willReturn($config)->shouldBeCalledTimes(1);
		$container = $containerProphecy->reveal();

		/** @var Application $app */
		$app = $factory($container);

		$tester->assertInstanceOf(Application::class, $app);
		$tester->assertEquals($options['name'], $app->getName());
		$tester->assertEquals($options['version'], $app->getVersion());
		$tester->assertEquals($options['catch_exceptions'], $app->areExceptionsCaught());
		$tester->assertEquals($options['auto_exit'], $app->isAutoExitEnabled());
	}

	/**
	 * @dataProvider provideFactories
	 */
	public function testCreationWithCommandName(string $configRoot, callable $factory)
	{
		$tester = $this->tester;

		$options = ['commands' => ['test_command']];
		$config = [$configRoot => $options];

		$command = new TestCommand('test_command');

		$containerProphecy = $this->prophesize(ContainerInterface::class);
		$containerProphecy->get('config')->willReturn($config)->shouldBeCalledTimes(1);
		$containerProphecy->has('test_command')->willReturn(true)->shouldBeCalledTimes(1);
		$containerProphecy->get('test_command')->willReturn($command)->shouldBeCalledTimes(1);
		$container = $containerProphecy->reveal();

		/** @var Application $app */
		$app = $factory($container);

		$tester->assertInstanceOf(Application::class, $app);
		$tester->assertSame($command, $app->get('test_command'));
	}

	/**
	 * @dataProvider provideFactories
	 */
	public function testCreationWithCommandInstance(string $configRoot, callable $factory)
	{
		$tester = $this->tester;

		$command = new TestCommand('test_command');

		$options = ['commands' => [$command]];
		$config = [$configRoot => $options];

		$containerProphecy = $this->prophesize(ContainerInterface::class);
		$containerProphecy->get('config')->willReturn($config)->shouldBeCalledTimes(1);
		$container = $containerProphecy->reveal();

		/** @var Application $app */
		$app = $factory($container);

		$tester->assertInstanceOf(Application::class, $app);
		$tester->assertSame($command, $app->get('test_command'));
	}

	/**
	 * @dataProvider provideFactories
	 */
	public function testErrorWithUnknownCommandName(string $configRoot, callable $factory)
	{
		$tester = $this->tester;

		$exception = new \InvalidArgumentException(sprintf(
			'Expecting either valid service name or instance of %s, not %s.',
			Command::class,
			'string'
		));

		$options = ['commands' => ['test_command']];
		$config = [$configRoot => $options];

		$containerProphecy = $this->prophesize(ContainerInterface::class);
		$containerProphecy->get('config')->willReturn($config)->shouldBeCalledTimes(1);
		$containerProphecy->has('test_command')->willReturn(false)->shouldBeCalledTimes(1);
		$container = $containerProphecy->reveal();

		$tester->expectException($exception, function() use ($factory, $container)
		{
			/** @var Application $app */
			$app = $factory($container);
		});
	}

	/**
	 * @dataProvider provideFactories
	 */
	public function testErrorWithInvalidCommandName(string $configRoot, callable $factory)
	{
		$tester = $this->tester;

		$exception = new \InvalidArgumentException(sprintf(
			'Service %s should be %s, not %s.',
			'test_command',
			Command::class,
			'stdClass'
		));

		$options = ['commands' => ['test_command']];
		$config = [$configRoot => $options];

		$containerProphecy = $this->prophesize(ContainerInterface::class);
		$containerProphecy->get('config')->willReturn($config)->shouldBeCalledTimes(1);
		$containerProphecy->has('test_command')->willReturn(true)->shouldBeCalledTimes(1);
		$containerProphecy->get('test_command')->willReturn(new \stdClass())->shouldBeCalledTimes(1);
		$container = $containerProphecy->reveal();

		$tester->expectException($exception, function() use ($factory, $container)
		{
			/** @var Application $app */
			$app = $factory($container);
		});
	}

	/**
	 * @dataProvider provideFactories
	 */
	public function testErrorWithInvalidCommandInstance(string $configRoot, callable $factory)
	{
		$tester = $this->tester;

		$exception = new \InvalidArgumentException(sprintf(
			'Expecting either valid service name or instance of %s, not %s.',
			Command::class,
			'stdClass'
		));

		$options = ['commands' => [new \stdClass()]];
		$config = [$configRoot => $options];

		$containerProphecy = $this->prophesize(ContainerInterface::class);
		$containerProphecy->get('config')->willReturn($config)->shouldBeCalledTimes(1);
		$container = $containerProphecy->reveal();

		$tester->expectException($exception, function() use ($factory, $container)
		{
			/** @var Application $app */
			$app = $factory($container);
		});
	}

	/**
	 * @dataProvider provideFactories
	 */
	public function testCreationWithCommandLoaderName(string $configRoot, callable $factory)
	{
		$tester = $this->tester;

		$command = new TestCommand('test_command');

		$commandLoaderProphecy = $this->prophesize(CommandLoaderInterface::class);
		$commandLoaderProphecy->has('test_command')->willReturn(true)->shouldBeCalledTimes(1);
		$commandLoaderProphecy->get('test_command')->willReturn($command)->shouldBeCalledTimes(1);
		$commandLoader = $commandLoaderProphecy->reveal();

		$options = ['command_loader' => 'test_command_loader'];
		$config = [$configRoot => $options];

		$containerProphecy = $this->prophesize(ContainerInterface::class);
		$containerProphecy->get('config')->willReturn($config)->shouldBeCalledTimes(1);
		$containerProphecy->has('test_command_loader')->willReturn(true)->shouldBeCalledTimes(1);
		$containerProphecy->get('test_command_loader')->willReturn($commandLoader)->shouldBeCalledTimes(1);
		$container = $containerProphecy->reveal();

		/** @var Application $app */
		$app = $factory($container);

		$tester->assertInstanceOf(Application::class, $app);
		$tester->assertSame($command, $app->get('test_command'));
	}

	/**
	 * @dataProvider provideFactories
	 */
	public function testCreationWithCommandLoaderInstance(string $configRoot, callable $factory)
	{
		$tester = $this->tester;

		$command = new TestCommand('test_command');

		$commandLoaderProphecy = $this->prophesize(CommandLoaderInterface::class);
		$commandLoaderProphecy->has('test_command')->willReturn(true)->shouldBeCalledTimes(1);
		$commandLoaderProphecy->get('test_command')->willReturn($command)->shouldBeCalledTimes(1);
		$commandLoader = $commandLoaderProphecy->reveal();

		$options = ['command_loader' => $commandLoader];
		$config = [$configRoot => $options];

		$containerProphecy = $this->prophesize(ContainerInterface::class);
		$containerProphecy->get('config')->willReturn($config)->shouldBeCalledTimes(1);
		$container = $containerProphecy->reveal();

		/** @var Application $app */
		$app = $factory($container);

		$tester->assertInstanceOf(Application::class, $app);
		$tester->assertSame($command, $app->get('test_command'));
	}

	/**
	 * @dataProvider provideFactories
	 */
	public function testErrorWithUnknownCommandLoaderName(string $configRoot, callable $factory)
	{
		$tester = $this->tester;

		$exception = new \InvalidArgumentException(sprintf(
			'Expecting either valid service name or instance of %s, not %s.',
			CommandLoaderInterface::class,
			'string'
		));

		$options = ['command_loader' => 'test_command_loader'];
		$config = [$configRoot => $options];

		$containerProphecy = $this->prophesize(ContainerInterface::class);
		$containerProphecy->get('config')->willReturn($config)->shouldBeCalledTimes(1);
		$containerProphecy->has('test_command_loader')->willReturn(false)->shouldBeCalledTimes(1);
		$container = $containerProphecy->reveal();

		$tester->expectException($exception, function() use ($factory, $container)
		{
			/** @var Application $app */
			$app = $factory($container);
		});
	}

	/**
	 * @dataProvider provideFactories
	 */
	public function testErrorWithInvalidCommandLoaderName(string $configRoot, callable $factory)
	{
		$tester = $this->tester;

		$exception = new \InvalidArgumentException(sprintf(
			'Service %s should be %s, not %s.',
			'test_command_loader',
			CommandLoaderInterface::class,
			'stdClass'
		));

		$options = ['command_loader' => 'test_command_loader'];
		$config = [$configRoot => $options];

		$containerProphecy = $this->prophesize(ContainerInterface::class);
		$containerProphecy->get('config')->willReturn($config)->shouldBeCalledTimes(1);
		$containerProphecy->has('test_command_loader')->willReturn(true)->shouldBeCalledTimes(1);
		$containerProphecy->get('test_command_loader')->willReturn(new \stdClass())->shouldBeCalledTimes(1);
		$container = $containerProphecy->reveal();

		$tester->expectException($exception, function() use ($factory, $container)
		{
			/** @var Application $app */
			$app = $factory($container);
		});
	}

	/**
	 * @dataProvider provideFactories
	 */
	public function testErrorWithInvalidCommandLoaderInstance(string $configRoot, callable $factory)
	{
		$tester = $this->tester;

		$exception = new \InvalidArgumentException(sprintf(
			'Expecting either valid service name or instance of %s, not %s.',
			CommandLoaderInterface::class,
			'stdClass'
		));

		$options = ['command_loader' => new\stdClass()];
		$config = [$configRoot => $options];

		$containerProphecy = $this->prophesize(ContainerInterface::class);
		$containerProphecy->get('config')->willReturn($config)->shouldBeCalledTimes(1);
		$container = $containerProphecy->reveal();

		$tester->expectException($exception, function() use ($factory, $container)
		{
			/** @var Application $app */
			$app = $factory($container);
		});
	}

	/**
	 * @dataProvider provideFactories
	 */
	public function testCreationWithEventDispatcherName(string $configRoot, callable $factory)
	{
		$tester = $this->tester;

		$eventDispatcherProphecy = $this->prophesize(EventDispatcherInterface::class);
		$eventDispatcher = $eventDispatcherProphecy->reveal();

		$options = ['event_dispatcher' => 'test_event_dispatcher'];
		$config = [$configRoot => $options];

		$containerProphecy = $this->prophesize(ContainerInterface::class);
		$containerProphecy->get('config')->willReturn($config)->shouldBeCalledTimes(1);
		$containerProphecy->has('test_event_dispatcher')->willReturn(true)->shouldBeCalledTimes(1);
		$containerProphecy->get('test_event_dispatcher')->willReturn($eventDispatcher)->shouldBeCalledTimes(1);
		$container = $containerProphecy->reveal();

		/** @var Application $app */
		$app = $factory($container);

		$tester->assertInstanceOf(Application::class, $app);
		//TODO how to test that event dispatcher actually set?
	}

	/**
	 * @dataProvider provideFactories
	 */
	public function testCreationWithEventDispatcherInstance(string $configRoot, callable $factory)
	{
		$tester = $this->tester;

		$eventDispatcherProphecy = $this->prophesize(EventDispatcherInterface::class);
		$eventDispatcher = $eventDispatcherProphecy->reveal();

		$options = ['event_dispatcher' => $eventDispatcher];
		$config = [$configRoot => $options];

		$containerProphecy = $this->prophesize(ContainerInterface::class);
		$containerProphecy->get('config')->willReturn($config)->shouldBeCalledTimes(1);
		$container = $containerProphecy->reveal();

		/** @var Application $app */
		$app = $factory($container);

		$tester->assertInstanceOf(Application::class, $app);
		//TODO how to test that event dispatcher actually set?
	}

	/**
	 * @dataProvider provideFactories
	 */
	public function testErrorWithUnknownEventDispatcherName(string $configRoot, callable $factory)
	{
		$tester = $this->tester;

		$exception = new \InvalidArgumentException(sprintf(
			'Expecting either valid service name or instance of %s, not %s.',
			EventDispatcherInterface::class,
			'string'
		));

		$options = ['event_dispatcher' => 'test_event_dispatcher'];
		$config = [$configRoot => $options];

		$containerProphecy = $this->prophesize(ContainerInterface::class);
		$containerProphecy->get('config')->willReturn($config)->shouldBeCalledTimes(1);
		$containerProphecy->has('test_event_dispatcher')->willReturn(false)->shouldBeCalledTimes(1);
		$container = $containerProphecy->reveal();

		$tester->expectException($exception, function() use ($factory, $container)
		{
			/** @var Application $app */
			$app = $factory($container);
		});
	}

	/**
	 * @dataProvider provideFactories
	 */
	public function testErrorWithInvalidEventDispatcherName(string $configRoot, callable $factory)
	{
		$tester = $this->tester;

		$exception = new \InvalidArgumentException(sprintf(
			'Service %s should be %s, not %s.',
			'test_event_dispatcher',
			EventDispatcherInterface::class,
			'stdClass'
		));

		$options = ['event_dispatcher' => 'test_event_dispatcher'];
		$config = [$configRoot => $options];

		$containerProphecy = $this->prophesize(ContainerInterface::class);
		$containerProphecy->get('config')->willReturn($config)->shouldBeCalledTimes(1);
		$containerProphecy->has('test_event_dispatcher')->willReturn(true)->shouldBeCalledTimes(1);
		$containerProphecy->get('test_event_dispatcher')->willReturn(new \stdClass())->shouldBeCalledTimes(1);
		$container = $containerProphecy->reveal();

		$tester->expectException($exception, function() use ($factory, $container)
		{
			/** @var Application $app */
			$app = $factory($container);
		});
	}

	/**
	 * @dataProvider provideFactories
	 */
	public function testErrorWithInvalidEventDispatcherInstance(string $configRoot, callable $factory)
	{
		$tester = $this->tester;

		$exception = new \InvalidArgumentException(sprintf(
			'Expecting either valid service name or instance of %s, not %s.',
			EventDispatcherInterface::class,
			'stdClass'
		));

		$options = ['event_dispatcher' => new\stdClass()];
		$config = [$configRoot => $options];

		$containerProphecy = $this->prophesize(ContainerInterface::class);
		$containerProphecy->get('config')->willReturn($config)->shouldBeCalledTimes(1);
		$container = $containerProphecy->reveal();

		$tester->expectException($exception, function() use ($factory, $container)
		{
			/** @var Application $app */
			$app = $factory($container);
		});
	}

	/**
	 * @dataProvider provideFactories
	 */
	public function testCreationWithHelperName(string $configRoot, callable $factory)
	{
		$tester = $this->tester;

		$helper = new TestHelper();

		$options = ['helpers' => ['test_helper']];
		$config = [$configRoot => $options];

		$containerProphecy = $this->prophesize(ContainerInterface::class);
		$containerProphecy->get('config')->willReturn($config)->shouldBeCalledTimes(1);
		$containerProphecy->has('test_helper')->willReturn(true)->shouldBeCalledTimes(1);
		$containerProphecy->get('test_helper')->willReturn($helper)->shouldBeCalledTimes(1);
		$container = $containerProphecy->reveal();

		/** @var Application $app */
		$app = $factory($container);

		$tester->assertInstanceOf(Application::class, $app);
		$tester->assertSame($helper, $app->getHelperSet()->get('test_helper'));
	}

	/**
	 * @dataProvider provideFactories
	 */
	public function testCreationWithAliasedHelperName(string $configRoot, callable $factory)
	{
		$tester = $this->tester;

		$helper = new TestHelper();

		$options = ['helpers' => ['th' => 'test_helper']];
		$config = [$configRoot => $options];

		$containerProphecy = $this->prophesize(ContainerInterface::class);
		$containerProphecy->get('config')->willReturn($config)->shouldBeCalledTimes(1);
		$containerProphecy->has('test_helper')->willReturn(true)->shouldBeCalledTimes(1);
		$containerProphecy->get('test_helper')->willReturn($helper)->shouldBeCalledTimes(1);
		$container = $containerProphecy->reveal();

		/** @var Application $app */
		$app = $factory($container);

		$tester->assertInstanceOf(Application::class, $app);
		$tester->assertSame($helper, $app->getHelperSet()->get('test_helper'));
		$tester->assertSame($helper, $app->getHelperSet()->get('th'));
	}

	/**
	 * @dataProvider provideFactories
	 */
	public function testCreationWithHelperInstance(string $configRoot, callable $factory)
	{
		$tester = $this->tester;

		$helper = new TestHelper();

		$options = ['helpers' => [$helper]];
		$config = [$configRoot => $options];

		$containerProphecy = $this->prophesize(ContainerInterface::class);
		$containerProphecy->get('config')->willReturn($config)->shouldBeCalledTimes(1);
		$container = $containerProphecy->reveal();

		/** @var Application $app */
		$app = $factory($container);

		$tester->assertInstanceOf(Application::class, $app);
		$tester->assertSame($helper, $app->getHelperSet()->get('test_helper'));
	}

	/**
	 * @dataProvider provideFactories
	 */
	public function testCreationWithAliasedHelperInstance(string $configRoot, callable $factory)
	{
		$tester = $this->tester;

		$helper = new TestHelper();

		$options = ['helpers' => ['th' => $helper]];
		$config = [$configRoot => $options];

		$containerProphecy = $this->prophesize(ContainerInterface::class);
		$containerProphecy->get('config')->willReturn($config)->shouldBeCalledTimes(1);
		$container = $containerProphecy->reveal();

		/** @var Application $app */
		$app = $factory($container);

		$tester->assertInstanceOf(Application::class, $app);
		$tester->assertSame($helper, $app->getHelperSet()->get('test_helper'));
		$tester->assertSame($helper, $app->getHelperSet()->get('th'));
	}

	/**
	 * @dataProvider provideFactories
	 */
	public function testErrorWithUnknownHelperName(string $configRoot, callable $factory)
	{
		$tester = $this->tester;

		$exception = new \InvalidArgumentException(sprintf(
			'Expecting either valid service name or instance of %s, not %s.',
			HelperInterface::class,
			'string'
		));

		$options = ['helpers' => ['test_helper']];
		$config = [$configRoot => $options];

		$containerProphecy = $this->prophesize(ContainerInterface::class);
		$containerProphecy->get('config')->willReturn($config)->shouldBeCalledTimes(1);
		$containerProphecy->has('test_helper')->willReturn(false)->shouldBeCalledTimes(1);
		$container = $containerProphecy->reveal();

		$tester->expectException($exception, function() use ($factory, $container)
		{
			/** @var Application $app */
			$app = $factory($container);
		});
	}

	/**
	 * @dataProvider provideFactories
	 */
	public function testErrorWithInvalidHelperName(string $configRoot, callable $factory)
	{
		$tester = $this->tester;

		$exception = new \InvalidArgumentException(sprintf(
			'Service %s should be %s, not %s.',
			'test_helper',
			HelperInterface::class,
			'stdClass'
		));

		$options = ['helpers' => ['test_helper']];
		$config = [$configRoot => $options];

		$containerProphecy = $this->prophesize(ContainerInterface::class);
		$containerProphecy->get('config')->willReturn($config)->shouldBeCalledTimes(1);
		$containerProphecy->has('test_helper')->willReturn(true)->shouldBeCalledTimes(1);
		$containerProphecy->get('test_helper')->willReturn(new \stdClass())->shouldBeCalledTimes(1);
		$container = $containerProphecy->reveal();

		$tester->expectException($exception, function() use ($factory, $container)
		{
			/** @var Application $app */
			$app = $factory($container);
		});
	}

	/**
	 * @dataProvider provideFactories
	 */
	public function testErrorWithInvalidHelperInstance(string $configRoot, callable $factory)
	{
		$tester = $this->tester;

		$exception = new \InvalidArgumentException(sprintf(
			'Expecting either valid service name or instance of %s, not %s.',
			HelperInterface::class,
			'stdClass'
		));

		$options = ['helpers' => [new \stdClass()]];
		$config = [$configRoot => $options];

		$containerProphecy = $this->prophesize(ContainerInterface::class);
		$containerProphecy->get('config')->willReturn($config)->shouldBeCalledTimes(1);
		$container = $containerProphecy->reveal();

		$tester->expectException($exception, function() use ($factory, $container)
		{
			/** @var Application $app */
			$app = $factory($container);
		});
	}
}