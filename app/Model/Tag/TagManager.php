<?php

declare(strict_types=1);

namespace App\Model\Tag;

use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

final class TagManager
{
	public function __construct(
		private readonly Explorer $db,
	) {
	}

	public function getAll(): Selection
	{
		return $this->db->table('tags')->order('name');
	}

	public function getById(int $id): ?ActiveRow
	{
		return $this->db->table('tags')->get($id) ?: null;
	}

	public function create(string $name, string $color = '#6c757d'): ActiveRow
	{
		return $this->db->table('tags')->insert(['name' => $name, 'color' => $color]);
	}

	public function update(int $id, string $name, string $color): void
	{
		$this->db->table('tags')->where('id', $id)->update(['name' => $name, 'color' => $color]);
	}

	public function delete(int $id): void
	{
		$this->db->table('tags')->where('id', $id)->delete();
	}

	public function getForSelect(): array
	{
		return $this->db->table('tags')->order('name')->fetchPairs('id', 'name');
	}

	public function getEntityTags(string $entityType, int $entityId): array
	{
		return $this->db->query(
			'SELECT t.* FROM tags t
			 JOIN entity_tags et ON et.tag_id = t.id
			 WHERE et.entity_type = ? AND et.entity_id = ?
			 ORDER BY t.name',
			$entityType, $entityId
		)->fetchAll();
	}

	public function syncEntityTags(string $entityType, int $entityId, array $tagIds): void
	{
		$this->db->table('entity_tags')
			->where('entity_type', $entityType)
			->where('entity_id', $entityId)
			->delete();

		foreach ($tagIds as $tagId) {
			$this->db->table('entity_tags')->insert([
				'entity_type' => $entityType,
				'entity_id'   => $entityId,
				'tag_id'      => (int) $tagId,
			]);
		}
	}
}
