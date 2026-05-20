<?php

declare(strict_types=1);

namespace App\Model\Task;

use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

final class TaskManager
{
	public function __construct(
		private readonly Explorer $db,
	) {
	}

	public function getAll(): Selection
	{
		return $this->db->table('tasks')->order('due_date ASC, priority DESC');
	}

	public function getById(int $id): ?ActiveRow
	{
		return $this->db->table('tasks')->get($id) ?: null;
	}

	public function getForUser(int $userId): Selection
	{
		return $this->db->table('tasks')
			->where('assigned_to', $userId)
			->where('status NOT IN ?', ['completed', 'cancelled'])
			->order('due_date ASC');
	}

	public function getOverdue(int $userId): Selection
	{
		return $this->db->table('tasks')
			->where('assigned_to', $userId)
			->where('status NOT IN ?', ['completed', 'cancelled'])
			->where('due_date < ?', (new \Nette\Utils\DateTime)->format('Y-m-d'))
			->order('due_date ASC');
	}

	public function getDueToday(int $userId): Selection
	{
		return $this->db->table('tasks')
			->where('assigned_to', $userId)
			->where('status NOT IN ?', ['completed', 'cancelled'])
			->where('due_date', (new \Nette\Utils\DateTime)->format('Y-m-d'))
			->order('due_time ASC');
	}

	public function getRelated(string $type, int $id): Selection
	{
		return $this->db->table('tasks')
			->where('related_type', $type)
			->where('related_id', $id)
			->order('due_date ASC, priority DESC');
	}

	public function create(array $data): ActiveRow
	{
		$data['created_at'] = new \Nette\Utils\DateTime;
		$data['updated_at'] = new \Nette\Utils\DateTime;
		return $this->db->table('tasks')->insert($data);
	}

	public function update(int $id, array $data): void
	{
		$data['updated_at'] = new \Nette\Utils\DateTime;

		if (isset($data['status']) && $data['status'] === 'completed') {
			$data['completed_at'] = new \Nette\Utils\DateTime;
		}

		$this->db->table('tasks')->where('id', $id)->update($data);
	}

	public function complete(int $id): void
	{
		$this->update($id, ['status' => 'completed']);
	}

	public function delete(int $id): void
	{
		$this->db->table('tasks')->where('id', $id)->delete();
	}

	public function countOpen(int $userId): int
	{
		return $this->db->table('tasks')
			->where('assigned_to', $userId)
			->where('status', 'open')
			->count('*');
	}

	public function getUpcoming(int $userId, int $days = 7): Selection
	{
		$from = (new \Nette\Utils\DateTime)->format('Y-m-d');
		$to   = (new \Nette\Utils\DateTime("+{$days} days"))->format('Y-m-d');

		return $this->db->table('tasks')
			->where('assigned_to', $userId)
			->where('status NOT IN ?', ['completed', 'cancelled'])
			->where('due_date BETWEEN ? AND ?', $from, $to)
			->order('due_date ASC');
	}
}
