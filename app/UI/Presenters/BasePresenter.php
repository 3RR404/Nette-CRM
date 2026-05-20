<?php

declare(strict_types=1);

namespace App\UI\Presenters;

use Nette\Application\UI\Presenter;
use App\Model\Activity\ActivityLogger;

abstract class BasePresenter extends Presenter
{
	#[\Nette\DI\Attributes\Inject]
	public ActivityLogger $activityLogger;

	public function startup(): void
	{
		parent::startup();

		if (!$this->user->isLoggedIn()) {
			$this->redirect('Sign:Sign:login');
		}

		$this->template->user = $this->user->getIdentity();
		$this->template->userRole = $this->user->getRoles()[0] ?? 'agent';
	}

	protected function requireRole(string ...$roles): void
	{
		foreach ($roles as $role) {
			if ($this->user->isInRole($role)) {
				return;
			}
		}
		$this->error('Forbidden', 403);
	}
}
