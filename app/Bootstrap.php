<?php

declare(strict_types=1);

namespace App;

use Dotenv\Dotenv;
use Nette\Bootstrap\Configurator;

class Bootstrap
{
	public static function boot(): Configurator
	{
		$rootDir = dirname(__DIR__);

        Dotenv::createImmutable(dirname(__DIR__))->load();

		$configurator = new Configurator;
		$configurator->setTempDirectory($rootDir . '/temp');

        $configurator->setTimeZone('Europe/Prague');
        $configurator->addDynamicParameters(['env' => $_ENV]);

		$configurator->setDebugMode(true);
		$configurator->enableTracy($rootDir . '/log');

		$configurator->addConfig($rootDir . '/app/config/local.neon');
		$configurator->addConfig($rootDir . '/app/config/common.neon');

		return $configurator;
	}
}
