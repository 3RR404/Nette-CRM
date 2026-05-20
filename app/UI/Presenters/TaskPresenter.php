<?php

declare(strict_types=1);

namespace App\UI\Presenters;

use App\Model\Task\TaskManager;
use App\Model\User\UserManager;
use App\Model\Contact\ContactManager;
use App\Model\Company\CompanyManager;
use App\Model\Deal\DealManager;
use Nette\Application\UI\Form;
use Nette\Application\BadRequestException;

final class TaskPresenter extends BasePresenter
{
	public function __construct(
		private readonly TaskManager    $taskManager,
		private readonly UserManager    $userManager,
		private readonly ContactManager $contactManager,
		private readonly CompanyManager $companyManager,
		private readonly DealManager    $dealManager,
	) {
		parent::__construct();
	}

	public function renderDefault(string $filter = 'all', int $page = 1): void
	{
		$userId = $this->user->getId();

		$tasks = match ($filter) {
			'mine'    => $this->taskManager->getForUser($userId),
			'overdue' => $this->taskManager->getOverdue($userId),
			'today'   => $this->taskManager->getDueToday($userId),
			default   => $this->taskManager->getAll(),
		};

		$paginator = new \Nette\Utils\Paginator;
		$paginator->setItemCount($tasks->count('*'));
		$paginator->setItemsPerPage(25);
		$paginator->setPage($page);

		$this->template->tasks     = $tasks->limit($paginator->getLength(), $paginator->getOffset());
		$this->template->paginator = $paginator;
		$this->template->filter    = $filter;
		$this->template->openCount = $this->taskManager->countOpen($userId);
	}

	public function renderCreate(?string $relatedType = null, ?int $relatedId = null): void
	{
		$this->template->relatedType = $relatedType;
		$this->template->relatedId   = $relatedId;
		$this->template->users       = $this->userManager->getActive()->fetchPairs('id', 'email');
	}

	public function renderEdit(int $id): void
	{
		$task = $this->taskManager->getById($id);
		if (!$task) {
			throw new BadRequestException('Task not found.');
		}

		/** @var Form $form */
		$form = $this->getComponent('taskForm');
		$form->setDefaults($task->toArray());

		$this->template->task  = $task;
		$this->template->users = $this->userManager->getActive()->fetchPairs('id', 'email');
	}

	public function actionComplete(int $id): void
	{
		$task = $this->taskManager->getById($id);
		if (!$task) {
			throw new BadRequestException('Task not found.');
		}

		$this->taskManager->complete($id);
		$this->activityLogger->log($this->user->getId(), 'task_completed', 'task', $id, "Task completed: {$task->title}");
		$this->flashMessage('Task marked as completed.', 'success');
		$this->redirect('default');
	}

	public function actionDelete(int $id): void
	{
		$task = $this->taskManager->getById($id);
		if (!$task) {
			throw new BadRequestException('Task not found.');
		}

		$this->taskManager->delete($id);
		$this->flashMessage('Task deleted.', 'success');
		$this->redirect('default');
	}

	protected function createComponentTaskForm(): Form
	{
		$form = new Form;

		$form->addText('title', 'Title')->setRequired();

		$form->addSelect('type', 'Type', [
			'task'     => 'Task',
			'call'     => 'Call',
			'meeting'  => 'Meeting',
			'email'    => 'Email',
			'deadline' => 'Deadline',
		])->setRequired();

		$form->addSelect('priority', 'Priority', [
			'low'    => 'Low',
			'medium' => 'Medium',
			'high'   => 'High',
			'urgent' => 'Urgent',
		])->setDefaultValue('medium');

		$form->addSelect('status', 'Status', [
			'open'        => 'Open',
			'in_progress' => 'In Progress',
			'completed'   => 'Completed',
			'cancelled'   => 'Cancelled',
		])->setDefaultValue('open');

		$form->addSelect('assigned_to', 'Assign to', $this->userManager->getActive()->fetchPairs('id', 'email'));
		$form->addText('due_date', 'Due Date')->setHtmlAttribute('type', 'date');
		$form->addText('due_time', 'Due Time')->setHtmlAttribute('type', 'time');
		$form->addTextArea('description', 'Description')->setHtmlAttribute('rows', 3);

		$form->addSelect('related_type', 'Related to', [
			''        => '— None —',
			'contact' => 'Contact',
			'company' => 'Company',
			'deal'    => 'Deal',
		]);
		$form->addInteger('related_id', 'Related ID');

		$form->addSubmit('send', 'Save Task');
		$form->onSuccess[] = $this->taskFormSucceeded(...);

		return $form;
	}

	private function taskFormSucceeded(Form $form, \stdClass $values): void
	{
		$data = (array) $values;
		if (empty($data['due_date'])) {
			$data['due_date'] = null;
		}
		if (empty($data['due_time'])) {
			$data['due_time'] = null;
		}
		if (empty($data['related_type'])) {
			$data['related_type'] = null;
			$data['related_id']   = null;
		}

		if ($this->action === 'edit') {
			$id = (int) $this->getParameter('id');
			$this->taskManager->update($id, $data);
			$this->flashMessage('Task updated.', 'success');
			$this->redirect('default');
		} else {
			$data['created_by'] = $this->user->getId();
			if (empty($data['assigned_to'])) {
				$data['assigned_to'] = $this->user->getId();
			}
			$task = $this->taskManager->create($data);
			$this->activityLogger->log($this->user->getId(), 'task_created', 'task', $task->id);
			$this->flashMessage('Task created.', 'success');
			$this->redirect('default');
		}
	}
}
