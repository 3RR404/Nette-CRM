<?php

declare(strict_types=1);

namespace App\UI\Presenters;

use App\Model\Contact\ContactManager;
use App\Model\Company\CompanyManager;
use App\Model\Deal\DealManager;
use App\Model\Note\NoteManager;
use App\Model\Task\TaskManager;
use App\Model\Tag\TagManager;
use App\Model\User\UserManager;
use Nette\Application\UI\Form;
use Nette\Application\BadRequestException;

final class ContactPresenter extends BasePresenter
{
	public function __construct(
		private readonly ContactManager $contactManager,
		private readonly CompanyManager $companyManager,
		private readonly DealManager    $dealManager,
		private readonly NoteManager    $noteManager,
		private readonly TaskManager    $taskManager,
		private readonly TagManager     $tagManager,
		private readonly UserManager    $userManager,
	) {
		parent::__construct();
	}

	public function renderDefault(string $q = '', string $status = '', int $page = 1): void
	{
		$contacts = $this->contactManager->search(
			$q,
			$status !== '' ? $status : null,
		);

		$paginator = new \Nette\Utils\Paginator;
		$paginator->setItemCount($contacts->count('*'));
		$paginator->setItemsPerPage(20);
		$paginator->setPage($page);

		$this->template->contacts   = $contacts->limit($paginator->getLength(), $paginator->getOffset());
		$this->template->paginator  = $paginator;
		$this->template->q          = $q;
		$this->template->status     = $status;
	}

	public function renderDetail(int $id): void
	{
		$contact = $this->contactManager->getById($id);
		if (!$contact) {
			throw new BadRequestException('Contact not found.');
		}

		$this->template->contact  = $contact;
		$this->template->notes    = $this->noteManager->getRelated('contact', $id);
		$this->template->tasks    = $this->taskManager->getRelated('contact', $id);
		$this->template->deals    = $this->dealManager->getByContact($id);
		$this->template->tags     = $this->tagManager->getEntityTags('contact', $id);
		$this->template->activity = $this->activityLogger->getForEntity('contact', $id);
	}

	public function renderCreate(): void
	{
		$this->template->companies = $this->companyManager->getForSelect();
		$this->template->users     = $this->userManager->getActive()->fetchPairs('id', 'email');
	}

	public function renderEdit(int $id): void
	{
		$contact = $this->contactManager->getById($id);
		if (!$contact) {
			throw new BadRequestException('Contact not found.');
		}

		/** @var Form $form */
		$form = $this->getComponent('contactForm');
		$form->setDefaults($contact->toArray());

		$this->template->contact   = $contact;
		$this->template->companies = $this->companyManager->getForSelect();
		$this->template->users     = $this->userManager->getActive()->fetchPairs('id', 'email');
	}

	public function actionDelete(int $id): void
	{
		$contact = $this->contactManager->getById($id);
		if (!$contact) {
			throw new BadRequestException('Contact not found.');
		}

		$this->activityLogger->log(
			$this->user->getId(),
			'deleted',
			'contact',
			$id,
			"Deleted contact: {$contact->first_name} {$contact->last_name}"
		);

		$this->contactManager->delete($id);
		$this->flashMessage('Contact has been deleted.', 'success');
		$this->redirect('default');
	}

	public function handleAddNote(int $contactId, string $content): void
	{
		$this->noteManager->create('contact', $contactId, $content, $this->user->getId());
		$this->activityLogger->log($this->user->getId(), 'note_added', 'contact', $contactId);

		if ($this->isAjax()) {
			$this->redrawControl('notes');
		} else {
			$this->redirect('detail', $contactId);
		}
	}

	protected function createComponentContactForm(): Form
	{
		$form = new Form;

		$form->addText('first_name', 'First Name')
			->setRequired()
			->setHtmlAttribute('placeholder', 'First name');

		$form->addText('last_name', 'Last Name')
			->setRequired()
			->setHtmlAttribute('placeholder', 'Last name');

		$form->addEmail('email', 'Email')
			->setHtmlAttribute('placeholder', 'email@example.com');

		$form->addText('phone', 'Phone')
			->setHtmlAttribute('placeholder', '+1-555-0100');

		$form->addText('mobile', 'Mobile')
			->setHtmlAttribute('placeholder', '+1-555-0200');

		$form->addText('job_title', 'Job Title')
			->setHtmlAttribute('placeholder', 'CEO, Manager...');

		$form->addSelect('company_id', 'Company', ['' => '— Select company —'] + $this->companyManager->getForSelect())
			->setPrompt('— Select company —');

		$form->addSelect('status', 'Status', [
			'lead'     => 'Lead',
			'prospect' => 'Prospect',
			'customer' => 'Customer',
			'churned'  => 'Churned',
			'other'    => 'Other',
		])->setRequired();

		$form->addSelect('source', 'Source', [
			''         => '— Unknown —',
			'web'      => 'Website',
			'phone'    => 'Phone',
			'email'    => 'Email',
			'referral' => 'Referral',
			'social'   => 'Social Media',
			'event'    => 'Event',
			'other'    => 'Other',
		]);

		$form->addSelect('owner_id', 'Owner', $this->userManager->getActive()->fetchPairs('id', 'email'));

		$form->addText('city', 'City');
		$form->addText('country', 'Country');
		$form->addTextArea('description', 'Description')->setHtmlAttribute('rows', 3);

		$form->addSubmit('send', 'Save Contact');
		$form->onSuccess[] = $this->contactFormSucceeded(...);

		return $form;
	}

	private function contactFormSucceeded(Form $form, \stdClass $values): void
	{
		$data = (array) $values;

		if ($this->action === 'edit') {
			$id = (int) $this->getParameter('id');
			$this->contactManager->update($id, $data);
			$this->activityLogger->log($this->user->getId(), 'updated', 'contact', $id, 'Contact updated.');
			$this->flashMessage('Contact has been updated.', 'success');
			$this->redirect('detail', $id);
		} else {
			$data['owner_id'] = $this->user->getId();
			$contact = $this->contactManager->create($data);
			$this->activityLogger->log($this->user->getId(), 'created', 'contact', $contact->id, 'Contact created.');
			$this->flashMessage('Contact has been created.', 'success');
			$this->redirect('detail', $contact->id);
		}
	}
}
