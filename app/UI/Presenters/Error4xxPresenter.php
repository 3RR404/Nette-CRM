<?php

declare(strict_types=1);

namespace App\UI\Presenters;

use Nette\Application\BadRequestException;
use Nette\Application\UI\Presenter;

final class Error4xxPresenter extends Presenter
{
	public function renderDefault(BadRequestException $exception): void
	{
		$code = $exception->getCode();
		$this->template->code    = $code;
		$this->template->message = $exception->getMessage();
		$this->setView((string) $code);
	}
}
