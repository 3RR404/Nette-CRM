<?php

declare(strict_types=1);

namespace App\Model\Company;

use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

final class CompanyManager
{
	public function __construct(
		private readonly Explorer $db,
	) {
	}

	public function getAll(): Selection
	{
		return $this->db->table('companies')->order('name');
	}

	public function getById(int $id): ?ActiveRow
	{
		return $this->db->table('companies')->get($id) ?: null;
	}

	public function search(string $query, ?int $ownerId = null): Selection
	{
		$sel = $this->db->table('companies');

		if ($query !== '') {
			$sel->where('name LIKE ? OR email LIKE ? OR city LIKE ?', "%$query%", "%$query%", "%$query%");
		}

		if ($ownerId !== null) {
			$sel->where('owner_id', $ownerId);
		}

		return $sel->order('name');
	}

	public function create(array $data): ActiveRow
	{
		$data['created_at'] = new \Nette\Utils\DateTime;
		$data['updated_at'] = new \Nette\Utils\DateTime;
		return $this->db->table('companies')->insert($data);
	}

	public function update(int $id, array $data): void
	{
		$data['updated_at'] = new \Nette\Utils\DateTime;
		$this->db->table('companies')->where('id', $id)->update($data);
	}

	public function delete(int $id): void
	{
		$this->db->table('companies')->where('id', $id)->delete();
	}

	public function getForSelect(): array
	{
		return $this->db->table('companies')->order('name')->fetchPairs('id', 'name');
	}

	public function getTotalCount(): int
	{
		return $this->db->table('companies')->count('*');
	}

	public function getRecentlyAdded(int $limit = 5): Selection
	{
		return $this->db->table('companies')->order('created_at DESC')->limit($limit);
	}
}
