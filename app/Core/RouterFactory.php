<?php

declare(strict_types=1);

namespace App\Core;

use Nette\Application\Routers\RouteList;

final class RouterFactory
{
	public function createRouter(): RouteList
	{
		$router = new RouteList;

		$router->addRoute('sign/<action>', 'Sign:Sign:default');
		$router->addRoute('sign/<action>[/<id>]', 'Sign:Sign:default');

		$router->addRoute('<presenter>/<action>[/<id>]', 'Dashboard:default');

		return $router;
	}
}
