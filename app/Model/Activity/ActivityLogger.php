<?php

declare(strict_types=1);

namespace App\Model\Activity;

use Nette\Database\Explorer;
use Nette\Database\Table\Selection;
use Nette\Http\Request;

final class ActivityLogger
{
	public function __construct(
		private readonly Explorer $db,
		private readonly Request  $httpRequest,
	) {
	}

	public function log(
		?int $userId,
		string $action,
		?string $entityType = null,
		?int $entityId = null,
		?string $description = null,
		?array $oldValues = null,
		?array $newValues = null,
	): void {
		$this->db->table('activity_log')->insert([
			'user_id'     => $userId,
			'action'      => $action,
			'entity_type' => $entityType,
			'entity_id'   => $entityId,
			'description' => $description,
			'old_values'  => $oldValues !== null ? json_encode($oldValues) : null,
			'new_values'  => $newValues !== null ? json_encode($newValues) : null,
			'ip_address'  => $this->httpRequest->getRemoteAddress(),
			'created_at'  => new \Nette\Utils\DateTime,
		]);
	}

	public function getRecent(int $limit = 20): Selection
	{
		return $this->db->table('activity_log')->order('created_at DESC')->limit($limit);
	}

	public function getForEntity(string $entityType, int $entityId): Selection
	{
		return $this->db->table('activity_log')
			->where('entity_type', $entityType)
			->where('entity_id', $entityId)
			->order('created_at DESC');
	}

	public function getForUser(int $userId, int $limit = 50): Selection
	{
		return $this->db->table('activity_log')
			->where('user_id', $userId)
			->order('created_at DESC')
			->limit($limit);
	}
}
