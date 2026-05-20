<?php

declare(strict_types=1);

namespace App\UI\Presenters;

use App\Model\Contact\ContactManager;
use App\Model\Company\CompanyManager;
use App\Model\Deal\DealManager;
use App\Model\Task\TaskManager;
use App\Model\User\UserManager;

final class ReportPresenter extends BasePresenter
{
	public function __construct(
		private readonly ContactManager $contactManager,
		private readonly CompanyManager $companyManager,
		private readonly DealManager    $dealManager,
		private readonly TaskManager    $taskManager,
		private readonly UserManager    $userManager,
	) {
		parent::__construct();
	}

	public function renderDefault(): void
	{
		$this->template->dealStats        = $this->dealManager->getStats();
		$this->template->contactsByStatus = $this->contactManager->countByStatus();
		$this->template->usersByRole      = $this->userManager->countByRole();
		$this->template->pipelineValue    = $this->dealManager->getTotalPipelineValue();
		$this->template->totalContacts    = $this->contactManager->getTotalCount();
		$this->template->totalCompanies   = $this->companyManager->getTotalCount();
	}

	public function renderSales(): void
	{
		$this->template->dealStats     = $this->dealManager->getStats();
		$this->template->pipelineValue = $this->dealManager->getTotalPipelineValue();
		$this->template->recentDeals   = $this->dealManager->getRecentlyUpdated(20);
	}

	public function renderContacts(): void
	{
		$this->template->byStatus = $this->contactManager->countByStatus();
		$this->template->total    = $this->contactManager->getTotalCount();
		$this->template->recent   = $this->contactManager->getRecentlyAdded(10);
	}
}
