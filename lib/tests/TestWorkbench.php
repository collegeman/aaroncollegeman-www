<?php
namespace FatPanda\WordPress\Tests;

use PHPUnit\Framework\TestCase;
use FatPanda\WordPress\Workbench;

class TestWorkbench extends TestCase {

	protected $workbench;

	function testPostCreateProject()
	{
		Workbench::postCreateProject();
	}

}