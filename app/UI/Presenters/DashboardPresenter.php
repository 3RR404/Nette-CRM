<?php

declare(strict_types=1);

namespace App\UI\Presenters;

use App\Model\Contact\ContactManager;
use App\Model\Company\CompanyManager;
use App\Model\Deal\DealManager;
use App\Model\Task\TaskManager;

final class DashboardPresenter extends BasePresenter
{
	public function __construct(
		private readonly ContactManager $contactManager,
		private readonly CompanyManager $companyManager,
		private readonly DealManager    $dealManager,
		private readonly TaskManager    $taskManager,
	) {
		parent::__construct();
	}

	public function renderDefault(): void
	{
		$userId = $this->user->getId();

		$this->template->stats = [
			'contacts'      => $this->contactManager->getTotalCount(),
			'companies'     => $this->companyManager->getTotalCount(),
			'pipeline'      => $this->dealManager->getTotalPipelineValue(),
			'openTasks'     => $this->taskManager->countOpen($userId),
		];

		$this->template->dealStats        = $this->dealManager->getStats();
		$this->template->recentContacts   = $this->contactManager->getRecentlyAdded(5);
		$this->template->recentDeals      = $this->dealManager->getRecentlyUpdated(5);
		$this->template->overdueTasks     = $this->taskManager->getOverdue($userId);
		$this->template->todayTasks       = $this->taskManager->getDueToday($userId);
		$this->template->contactsByStatus = $this->contactManager->countByStatus();
	}
}
