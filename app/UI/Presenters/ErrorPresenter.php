<?php

declare(strict_types=1);

namespace App\UI\Presenters;

use Nette\Application\BadRequestException;
use Nette\Application\IPresenter;
use Nette\Application\Request;
use Nette\Application\Response;
use Nette\Application\Responses\CallbackResponse;
use Nette\Application\Responses\ForwardResponse;
use Nette\Http;
use Tracy\ILogger;

final class ErrorPresenter implements IPresenter
{
	public function __construct(
		private readonly ILogger $logger,
	) {
	}

	public function run(Request $request): Response
	{
		$exception = $request->getParameter('exception');

		if ($exception instanceof BadRequestException) {
			[$module, , $sep] = \Nette\Application\Helpers::splitName($request->getPresenterName());
			$errorPresenter = $module . $sep . 'Error4xx';
			return new ForwardResponse($request->withPresenterName($errorPresenter));
		}

		$this->logger->log($exception, ILogger::EXCEPTION);
		return new CallbackResponse(function (Http\IRequest $httpRequest, Http\IResponse $httpResponse): void {
			require __DIR__ . '/../templates/Error/500.phtml';
		});
	}
}
