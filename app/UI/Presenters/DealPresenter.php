<?php

declare(strict_types=1);

namespace App\UI\Presenters;

use App\Model\Deal\DealManager;
use App\Model\Contact\ContactManager;
use App\Model\Company\CompanyManager;
use App\Model\Note\NoteManager;
use App\Model\Task\TaskManager;
use App\Model\User\UserManager;
use Nette\Application\UI\Form;
use Nette\Application\BadRequestException;

final class DealPresenter extends BasePresenter
{
	public function __construct(
		private readonly DealManager    $dealManager,
		private readonly ContactManager $contactManager,
		private readonly CompanyManager $companyManager,
		private readonly NoteManager    $noteManager,
		private readonly TaskManager    $taskManager,
		private readonly UserManager    $userManager,
	) {
		parent::__construct();
	}

	public function renderDefault(string $q = '', string $stage = '', int $page = 1): void
	{
		$deals = $this->dealManager->search(
			$q,
			$stage !== '' ? $stage : null,
		);

		$paginator = new \Nette\Utils\Paginator;
		$paginator->setItemCount($deals->count('*'));
		$paginator->setItemsPerPage(20);
		$paginator->setPage($page);

		$this->template->deals     = $deals->limit($paginator->getLength(), $paginator->getOffset());
		$this->template->paginator = $paginator;
		$this->template->q         = $q;
		$this->template->stage     = $stage;
		$this->template->stages    = DealManager::STAGES;
		$this->template->stats     = $this->dealManager->getStats();
	}

	public function renderPipeline(): void
	{
		$this->template->pipeline = $this->dealManager->getPipelineGrouped();
		$this->template->stages   = DealManager::STAGES;
		$this->template->stats    = $this->dealManager->getStats();
	}

	public function renderDetail(int $id): void
	{
		$deal = $this->dealManager->getById($id);
		if (!$deal) {
			throw new BadRequestException('Deal not found.');
		}

		$this->template->deal     = $deal;
		$this->template->notes    = $this->noteManager->getRelated('deal', $id);
		$this->template->tasks    = $this->taskManager->getRelated('deal', $id);
		$this->template->activity = $this->activityLogger->getForEntity('deal', $id);
		$this->template->stages   = DealManager::STAGES;
	}

	public function renderCreate(): void
	{
		$this->template->contacts  = $this->contactManager->getAll()->fetchPairs('id', 'email');
		$this->template->companies = $this->companyManager->getForSelect();
		$this->template->users     = $this->userManager->getActive()->fetchPairs('id', 'email');
	}

	public function renderEdit(int $id): void
	{
		$deal = $this->dealManager->getById($id);
		if (!$deal) {
			throw new BadRequestException('Deal not found.');
		}

		/** @var Form $form */
		$form = $this->getComponent('dealForm');
		$form->setDefaults($deal->toArray());

		$this->template->deal      = $deal;
		$this->template->contacts  = $this->contactManager->getAll()->fetchPairs('id', 'email');
		$this->template->companies = $this->companyManager->getForSelect();
		$this->template->users     = $this->userManager->getActive()->fetchPairs('id', 'email');
	}

	public function actionDelete(int $id): void
	{
		$deal = $this->dealManager->getById($id);
		if (!$deal) {
			throw new BadRequestException('Deal not found.');
		}

		$this->activityLogger->log($this->user->getId(), 'deleted', 'deal', $id, "Deleted deal: {$deal->title}");
		$this->dealManager->delete($id);
		$this->flashMessage('Deal has been deleted.', 'success');
		$this->redirect('default');
	}

	public function handleMoveStage(int $id, string $stage): void
	{
		if (!in_array($stage, DealManager::STAGES, true)) {
			$this->error('Invalid stage.');
		}

		$deal = $this->dealManager->getById($id);
		if (!$deal) {
			$this->error('Deal not found.');
		}

		$this->dealManager->moveStage($id, $stage);
		$this->activityLogger->log(
			$this->user->getId(), 'stage_changed', 'deal', $id,
			"Stage changed from {$deal->stage} to {$stage}"
		);

		if ($this->isAjax()) {
			$this->sendJson(['status' => 'ok', 'stage' => $stage]);
		} else {
			$this->redirect('detail', $id);
		}
	}

	public function handleAddNote(int $dealId, string $content): void
	{
		$this->noteManager->create('deal', $dealId, $content, $this->user->getId());

		if ($this->isAjax()) {
			$this->redrawControl('notes');
		} else {
			$this->redirect('detail', $dealId);
		}
	}

	protected function createComponentDealForm(): Form
	{
		$form = new Form;

		$form->addText('title', 'Deal Title')->setRequired();

		$form->addSelect('contact_id', 'Contact',
			['' => '— No contact —'] + $this->contactManager->getAll()->fetchPairs('id', 'email')
		)->setPrompt('— No contact —');

		$form->addSelect('company_id', 'Company',
			['' => '— No company —'] + $this->companyManager->getForSelect()
		)->setPrompt('— No company —');

		$form->addSelect('stage', 'Stage', array_combine(DealManager::STAGES, array_map('ucfirst', DealManager::STAGES)))
			->setRequired();

		$form->addText('value', 'Value')->setHtmlAttribute('type', 'number')->setHtmlAttribute('step', '0.01');
		$form->addSelect('currency', 'Currency', ['USD' => 'USD', 'EUR' => 'EUR', 'GBP' => 'GBP', 'CZK' => 'CZK']);
		$form->addInteger('probability', 'Probability (%)')->setHtmlAttribute('min', 0)->setHtmlAttribute('max', 100);
		$form->addText('expected_close_date', 'Expected Close')->setHtmlAttribute('type', 'date');
		$form->addSelect('owner_id', 'Owner', $this->userManager->getActive()->fetchPairs('id', 'email'));
		$form->addTextArea('description', 'Description')->setHtmlAttribute('rows', 3);
		$form->addSubmit('send', 'Save Deal');
		$form->onSuccess[] = $this->dealFormSucceeded(...);

		return $form;
	}

	private function dealFormSucceeded(Form $form, \stdClass $values): void
	{
		$data = (array) $values;
		$data['value'] = $data['value'] !== '' ? (float) $data['value'] : null;
		if (empty($data['expected_close_date'])) {
			$data['expected_close_date'] = null;
		}

		if ($this->action === 'edit') {
			$id = (int) $this->getParameter('id');
			$this->dealManager->update($id, $data);
			$this->activityLogger->log($this->user->getId(), 'updated', 'deal', $id);
			$this->flashMessage('Deal has been updated.', 'success');
			$this->redirect('detail', $id);
		} else {
			$data['owner_id'] = $this->user->getId();
			$deal = $this->dealManager->create($data);
			$this->activityLogger->log($this->user->getId(), 'created', 'deal', $deal->id);
			$this->flashMessage('Deal has been created.', 'success');
			$this->redirect('detail', $deal->id);
		}
	}
}
