<?php

declare(strict_types=1);

namespace App\UI\Presenters;

use App\Model\User\UserManager;
use Nette\Application\UI\Form;
use Nette\Application\BadRequestException;

final class UserPresenter extends BasePresenter
{
	public function __construct(
		private readonly UserManager $userManager,
	) {
		parent::__construct();
	}

	public function startup(): void
	{
		parent::startup();
		$this->requireRole('admin');
	}

	public function renderDefault(): void
	{
		$this->template->users = $this->userManager->getAll();
	}

	public function renderCreate(): void
	{
	}

	public function renderEdit(int $id): void
	{
		$user = $this->userManager->getById($id);
		if (!$user) {
			throw new BadRequestException('User not found.');
		}

		/** @var Form $form */
		$form = $this->getComponent('userForm');
		$defaults = $user->toArray();
		unset($defaults['password_hash']);
		$form->setDefaults($defaults);

		$this->template->editedUser = $user;
	}

	public function actionToggle(int $id): void
	{
		if ($id === $this->user->getId()) {
			$this->flashMessage('You cannot deactivate your own account.', 'warning');
			$this->redirect('default');
		}

		$this->userManager->toggleActive($id);
		$this->flashMessage('User status has been changed.', 'success');
		$this->redirect('default');
	}

	public function actionDelete(int $id): void
	{
		if ($id === $this->user->getId()) {
			$this->flashMessage('You cannot delete your own account.', 'warning');
			$this->redirect('default');
		}

		$this->userManager->delete($id);
		$this->activityLogger->log($this->user->getId(), 'user_deleted', 'user', $id);
		$this->flashMessage('User has been deleted.', 'success');
		$this->redirect('default');
	}

	protected function createComponentUserForm(): Form
	{
		$form = new Form;

		$form->addText('first_name', 'First Name')->setRequired();
		$form->addText('last_name', 'Last Name')->setRequired();
		$form->addEmail('email', 'Email')->setRequired();
		$form->addText('phone', 'Phone');

		$form->addSelect('role', 'Role', [
			'admin'   => 'Admin',
			'manager' => 'Manager',
			'agent'   => 'Agent',
		])->setRequired();

		$form->addCheckbox('active', 'Active')->setDefaultValue(true);

		$isEdit = $this->action === 'edit';

		$form->addPassword('password', $isEdit ? 'New Password (leave blank to keep)' : 'Password')
			->setRequired(!$isEdit);

		$form->addPassword('password_confirm', 'Confirm Password')
			->addRule(Form::Equal, 'Passwords do not match.', $form['password'])
			->setRequired(!$isEdit);

		$form->addSubmit('send', 'Save User');
		$form->onSuccess[] = $this->userFormSucceeded(...);

		return $form;
	}

	private function userFormSucceeded(Form $form, \stdClass $values): void
	{
		$data = (array) $values;

		if ($this->action === 'edit') {
			$id = (int) $this->getParameter('id');
			$this->userManager->update($id, $data);
			$this->activityLogger->log($this->user->getId(), 'user_updated', 'user', $id);
			$this->flashMessage('User has been updated.', 'success');
		} else {
			$user = $this->userManager->create($data);
			$this->activityLogger->log($this->user->getId(), 'user_created', 'user', $user->id);
			$this->flashMessage('User has been created.', 'success');
		}

		$this->redirect('default');
	}
}
