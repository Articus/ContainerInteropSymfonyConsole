<?php
namespace Test\ContainerInteropSymfonyConsole;


use Symfony\Component\Console\Helper\Helper;

class TestHelper extends Helper
{
	public function getName()
	{
		return 'test_helper';
	}
}