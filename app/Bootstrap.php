<?php

declare(strict_types=1);

namespace App;

use Nette\Bootstrap\Configurator;

class Bootstrap
{
	public static function boot(): Configurator
	{
		$rootDir = dirname(__DIR__);

		$configurator = new Configurator;
		$configurator->setTempDirectory($rootDir . '/temp');

		$configurator->setDebugMode('secret@23.75.345.200');
		$configurator->enableTracy($rootDir . '/log');

		$configurator->addConfig($rootDir . '/app/config/common.neon');
		$configurator->addConfig($rootDir . '/app/config/local.neon');

		return $configurator;
	}
}
