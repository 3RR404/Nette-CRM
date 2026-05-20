<?php

declare(strict_types=1);

namespace App\Model\Note;

use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

final class NoteManager
{
	public function __construct(
		private readonly Explorer $db,
	) {
	}

	public function getRelated(string $type, int $id): Selection
	{
		return $this->db->table('notes')
			->where('related_type', $type)
			->where('related_id', $id)
			->order('created_at DESC');
	}

	public function getById(int $id): ?ActiveRow
	{
		return $this->db->table('notes')->get($id) ?: null;
	}

	public function create(string $type, int $relatedId, string $content, int $createdBy): ActiveRow
	{
		return $this->db->table('notes')->insert([
			'content'      => $content,
			'related_type' => $type,
			'related_id'   => $relatedId,
			'created_by'   => $createdBy,
			'created_at'   => new \Nette\Utils\DateTime,
			'updated_at'   => new \Nette\Utils\DateTime,
		]);
	}

	public function update(int $id, string $content): void
	{
		$this->db->table('notes')->where('id', $id)->update([
			'content'    => $content,
			'updated_at' => new \Nette\Utils\DateTime,
		]);
	}

	public function delete(int $id): void
	{
		$this->db->table('notes')->where('id', $id)->delete();
	}
}
