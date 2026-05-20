<?php

declare(strict_types=1);

namespace App\Model\Contact;

use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

final class ContactManager
{
	public function __construct(
		private readonly Explorer $db,
	) {
	}

	public function getAll(): Selection
	{
		return $this->db->table('contacts')->order('last_name, first_name');
	}

	public function getById(int $id): ?ActiveRow
	{
		return $this->db->table('contacts')->get($id) ?: null;
	}

	public function search(string $query, ?string $status = null, ?int $ownerId = null): Selection
	{
		$sel = $this->db->table('contacts');

		if ($query !== '') {
			$sel->where(
				'first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ?',
				"%$query%", "%$query%", "%$query%", "%$query%"
			);
		}

		if ($status !== null) {
			$sel->where('status', $status);
		}

		if ($ownerId !== null) {
			$sel->where('owner_id', $ownerId);
		}

		return $sel->order('last_name, first_name');
	}

	public function create(array $data): ActiveRow
	{
		$data['created_at'] = new \Nette\Utils\DateTime;
		$data['updated_at'] = new \Nette\Utils\DateTime;
		return $this->db->table('contacts')->insert($data);
	}

	public function update(int $id, array $data): void
	{
		$data['updated_at'] = new \Nette\Utils\DateTime;
		$this->db->table('contacts')->where('id', $id)->update($data);
	}

	public function delete(int $id): void
	{
		$this->db->table('contacts')->where('id', $id)->delete();
	}

	public function countByStatus(): array
	{
		return $this->db->query(
			'SELECT status, COUNT(*) as count FROM contacts GROUP BY status'
		)->fetchPairs('status', 'count');
	}

	public function getRecentlyAdded(int $limit = 5): Selection
	{
		return $this->db->table('contacts')->order('created_at DESC')->limit($limit);
	}

	public function getByCompany(int $companyId): Selection
	{
		return $this->db->table('contacts')->where('company_id', $companyId)->order('last_name, first_name');
	}

	public function getTotalCount(): int
	{
		return $this->db->table('contacts')->count('*');
	}
}
