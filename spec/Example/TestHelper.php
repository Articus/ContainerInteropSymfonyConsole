<?php
declare(strict_types=1);

namespace spec\Example;

use Symfony\Component\Console\Helper\Helper;

class TestHelper extends Helper
{
	public function getName()
	{
		return 'test_name';
	}
}
