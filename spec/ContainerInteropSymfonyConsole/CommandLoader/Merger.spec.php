<?php
declare(strict_types=1);

namespace spec\CommandLoader;

use ContainerInteropSymfonyConsole as CISC;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;
use Symfony\Component\Console\Exception\CommandNotFoundException;

\describe(CISC\CommandLoader\Merger::class, function ()
{
	\it('provides no commands without loaders', function ()
	{
		$unknownCommandName = 'test_command_name';
		$exception = new CommandNotFoundException(\sprintf('Unknown command "%s"', $unknownCommandName));

		$service = new CISC\CommandLoader\Merger();

		\expect($service->getNames())->toBe([]);
		\expect($service->has($unknownCommandName))->toBe(false);
		\expect(function () use ($service, $unknownCommandName)
		{
			$service->get($unknownCommandName);
		})->toThrow($exception);
	});
	\it('provides all commands from single loader', function ()
	{
		$commandName = 'test_command_name_1';
		$unknownCommandName = 'test_command_name_2';
		$exception = new CommandNotFoundException(\sprintf('Unknown command "%s"', $unknownCommandName));
		$loader = \mock(CommandLoaderInterface::class);
		$command = \mock(Command::class);

		$loader->shouldReceive('getNames')->andReturn([$commandName])->once();
		$loader->shouldReceive('get')->with($commandName)->andReturn($command)->once();

		$service = new CISC\CommandLoader\Merger();

		$service->addLoader($loader);
		\expect($service->getNames())->toBe([$commandName]);
		\expect($service->has($commandName))->toBe(true);
		\expect($service->has($unknownCommandName))->toBe(false);
		\expect($service->get($commandName))->toBe($command);
		\expect(function () use ($service, $unknownCommandName)
		{
			$service->get($unknownCommandName);
		})->toThrow($exception);
	});
	\it('provides all commands from multiple loaders preferring last added loader if command name is not unique', function ()
	{
		$commandName1 = 'test_command_name_1';
		$commandName2 = 'test_command_name_2';
		$unknownCommandName = 'test_command_name_3';
		$exception = new CommandNotFoundException(\sprintf('Unknown command "%s"', $unknownCommandName));
		$loader1 = \mock(CommandLoaderInterface::class);
		$loader2 = \mock(CommandLoaderInterface::class);
		$command1 = \mock(Command::class);
		$command2 = \mock(Command::class);

		$loader1->shouldReceive('getNames')->andReturn([$commandName1, $commandName2])->once();
		$loader2->shouldReceive('getNames')->andReturn([$commandName1])->once();
		$loader1->shouldReceive('get')->with($commandName2)->andReturn($command2)->once();
		$loader2->shouldReceive('get')->with($commandName1)->andReturn($command1)->once();

		$service = new CISC\CommandLoader\Merger();

		$service->addLoader($loader1);
		$service->addLoader($loader2);
		\expect($service->getNames())->toBe([$commandName1, $commandName2]);
		\expect($service->has($commandName1))->toBe(true);
		\expect($service->has($commandName2))->toBe(true);
		\expect($service->has($unknownCommandName))->toBe(false);
		\expect($service->get($commandName1))->toBe($command1);
		\expect($service->get($commandName2))->toBe($command2);
		\expect(function () use ($service, $unknownCommandName)
		{
			$service->get($unknownCommandName);
		})->toThrow($exception);
	});
});
