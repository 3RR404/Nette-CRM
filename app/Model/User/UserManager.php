<?php

declare(strict_types=1);

namespace App\Model\User;

use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;
use Nette\Utils\Paginator;

final class UserManager
{
	public function __construct(
		private readonly Explorer $db,
	) {
	}

	public function getAll(): Selection
	{
		return $this->db->table('users')->order('last_name, first_name');
	}

	public function getById(int $id): ?ActiveRow
	{
		return $this->db->table('users')->get($id) ?: null;
	}

	public function getByEmail(string $email): ?ActiveRow
	{
		return $this->db->table('users')->where('email', $email)->fetch() ?: null;
	}

	public function getActive(): Selection
	{
		return $this->db->table('users')->where('active', 1)->order('last_name, first_name');
	}

	public function create(array $data): ActiveRow
	{
		$data['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
		unset($data['password'], $data['password_confirm']);
		return $this->db->table('users')->insert($data);
	}

	public function update(int $id, array $data): void
	{
		if (!empty($data['password'])) {
			$data['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
		}
		unset($data['password'], $data['password_confirm']);
		$this->db->table('users')->where('id', $id)->update($data);
	}

	public function updateLastLogin(int $id): void
	{
		$this->db->table('users')->where('id', $id)->update(['last_login_at' => new \Nette\Utils\DateTime]);
	}

	public function delete(int $id): void
	{
		$this->db->table('users')->where('id', $id)->delete();
	}

	public function toggleActive(int $id): void
	{
		$user = $this->getById($id);
		if ($user) {
			$this->db->table('users')->where('id', $id)->update(['active' => !$user->active]);
		}
	}

	public function countByRole(): array
	{
		return $this->db->query('SELECT role, COUNT(*) as count FROM users GROUP BY role')->fetchPairs('role', 'count');
	}
}
