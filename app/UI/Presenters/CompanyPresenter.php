<?php

declare(strict_types=1);

namespace App\UI\Presenters;

use App\Model\Company\CompanyManager;
use App\Model\Contact\ContactManager;
use App\Model\Deal\DealManager;
use App\Model\Note\NoteManager;
use App\Model\Task\TaskManager;
use App\Model\Tag\TagManager;
use App\Model\User\UserManager;
use Nette\Application\UI\Form;
use Nette\Application\BadRequestException;

final class CompanyPresenter extends BasePresenter
{
	public function __construct(
		private readonly CompanyManager $companyManager,
		private readonly ContactManager $contactManager,
		private readonly DealManager    $dealManager,
		private readonly NoteManager    $noteManager,
		private readonly TaskManager    $taskManager,
		private readonly TagManager     $tagManager,
		private readonly UserManager    $userManager,
	) {
		parent::__construct();
	}

	public function renderDefault(string $q = '', int $page = 1): void
	{
		$companies = $this->companyManager->search($q);

		$paginator = new \Nette\Utils\Paginator;
		$paginator->setItemCount($companies->count('*'));
		$paginator->setItemsPerPage(20);
		$paginator->setPage($page);

		$this->template->companies = $companies->limit($paginator->getLength(), $paginator->getOffset());
		$this->template->paginator = $paginator;
		$this->template->q         = $q;
	}

	public function renderDetail(int $id): void
	{
		$company = $this->companyManager->getById($id);
		if (!$company) {
			throw new BadRequestException('Company not found.');
		}

		$this->template->company  = $company;
		$this->template->contacts = $this->contactManager->getByCompany($id);
		$this->template->deals    = $this->dealManager->getByCompany($id);
		$this->template->notes    = $this->noteManager->getRelated('company', $id);
		$this->template->tasks    = $this->taskManager->getRelated('company', $id);
		$this->template->tags     = $this->tagManager->getEntityTags('company', $id);
		$this->template->activity = $this->activityLogger->getForEntity('company', $id);
	}

	public function renderCreate(): void
	{
		$this->template->users = $this->userManager->getActive()->fetchPairs('id', 'email');
	}

	public function renderEdit(int $id): void
	{
		$company = $this->companyManager->getById($id);
		if (!$company) {
			throw new BadRequestException('Company not found.');
		}

		/** @var Form $form */
		$form = $this->getComponent('companyForm');
		$form->setDefaults($company->toArray());

		$this->template->company = $company;
		$this->template->users   = $this->userManager->getActive()->fetchPairs('id', 'email');
	}

	public function actionDelete(int $id): void
	{
		$company = $this->companyManager->getById($id);
		if (!$company) {
			throw new BadRequestException('Company not found.');
		}

		$this->activityLogger->log($this->user->getId(), 'deleted', 'company', $id, "Deleted: {$company->name}");
		$this->companyManager->delete($id);
		$this->flashMessage('Company has been deleted.', 'success');
		$this->redirect('default');
	}

	public function handleAddNote(int $companyId, string $content): void
	{
		$this->noteManager->create('company', $companyId, $content, $this->user->getId());
		$this->activityLogger->log($this->user->getId(), 'note_added', 'company', $companyId);

		if ($this->isAjax()) {
			$this->redrawControl('notes');
		} else {
			$this->redirect('detail', $companyId);
		}
	}

	protected function createComponentCompanyForm(): Form
	{
		$form = new Form;

		$form->addText('name', 'Company Name')->setRequired();
		$form->addEmail('email', 'Email');
		$form->addText('phone', 'Phone');
		$form->addText('website', 'Website');
		$form->addText('industry', 'Industry');
		$form->addInteger('employees', 'Employees');
		$form->addText('city', 'City');
		$form->addText('country', 'Country');
		$form->addTextArea('description', 'Description')->setHtmlAttribute('rows', 3);
		$form->addSelect('owner_id', 'Owner', $this->userManager->getActive()->fetchPairs('id', 'email'));
		$form->addSubmit('send', 'Save Company');
		$form->onSuccess[] = $this->companyFormSucceeded(...);

		return $form;
	}

	private function companyFormSucceeded(Form $form, \stdClass $values): void
	{
		$data = (array) $values;

		if ($this->action === 'edit') {
			$id = (int) $this->getParameter('id');
			$this->companyManager->update($id, $data);
			$this->activityLogger->log($this->user->getId(), 'updated', 'company', $id);
			$this->flashMessage('Company has been updated.', 'success');
			$this->redirect('detail', $id);
		} else {
			$data['owner_id'] = $this->user->getId();
			$company = $this->companyManager->create($data);
			$this->activityLogger->log($this->user->getId(), 'created', 'company', $company->id);
			$this->flashMessage('Company has been created.', 'success');
			$this->redirect('detail', $company->id);
		}
	}
}
