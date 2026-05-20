<?php

declare(strict_types=1);

namespace App\Model\Deal;

use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

final class DealManager
{
	public const STAGES = ['new', 'qualified', 'proposal', 'negotiation', 'won', 'lost'];

	public function __construct(
		private readonly Explorer $db,
	) {
	}

	public function getAll(): Selection
	{
		return $this->db->table('deals')->order('created_at DESC');
	}

	public function getById(int $id): ?ActiveRow
	{
		return $this->db->table('deals')->get($id) ?: null;
	}

	public function search(string $query, ?string $stage = null, ?int $ownerId = null): Selection
	{
		$sel = $this->db->table('deals');

		if ($query !== '') {
			$sel->where('title LIKE ?', "%$query%");
		}

		if ($stage !== null) {
			$sel->where('stage', $stage);
		}

		if ($ownerId !== null) {
			$sel->where('owner_id', $ownerId);
		}

		return $sel->order('updated_at DESC');
	}

	public function create(array $data): ActiveRow
	{
		$data['created_at'] = new \Nette\Utils\DateTime;
		$data['updated_at'] = new \Nette\Utils\DateTime;
		return $this->db->table('deals')->insert($data);
	}

	public function update(int $id, array $data): void
	{
		$data['updated_at'] = new \Nette\Utils\DateTime;

		if (isset($data['stage'])) {
			if ($data['stage'] === 'won' && empty($data['won_at'])) {
				$data['won_at'] = new \Nette\Utils\DateTime;
			} elseif ($data['stage'] === 'lost' && empty($data['lost_at'])) {
				$data['lost_at'] = new \Nette\Utils\DateTime;
			}
		}

		$this->db->table('deals')->where('id', $id)->update($data);
	}

	public function delete(int $id): void
	{
		$this->db->table('deals')->where('id', $id)->delete();
	}

	public function moveStage(int $id, string $stage): void
	{
		$this->update($id, ['stage' => $stage]);
	}

	/** @return array<string, array<ActiveRow>> */
	public function getPipelineGrouped(): array
	{
		$pipeline = array_fill_keys(self::STAGES, []);
		foreach ($this->db->table('deals')->where('stage NOT IN ?', ['won', 'lost'])->order('updated_at DESC') as $deal) {
			$pipeline[$deal->stage][] = $deal;
		}
		return $pipeline;
	}

	public function getStats(): array
	{
		$rows = $this->db->query('
			SELECT
				stage,
				COUNT(*) as count,
				COALESCE(SUM(value), 0) as total_value
			FROM deals
			GROUP BY stage
		')->fetchAll();

		$stats = [];
		foreach ($rows as $row) {
			$stats[$row->stage] = ['count' => $row->count, 'value' => (float) $row->total_value];
		}
		return $stats;
	}

	public function getTotalPipelineValue(): float
	{
		return (float) $this->db->query(
			"SELECT COALESCE(SUM(value), 0) FROM deals WHERE stage NOT IN ('won','lost')"
		)->fetchField();
	}

	public function getRecentlyUpdated(int $limit = 5): Selection
	{
		return $this->db->table('deals')->order('updated_at DESC')->limit($limit);
	}

	public function getByContact(int $contactId): Selection
	{
		return $this->db->table('deals')->where('contact_id', $contactId)->order('created_at DESC');
	}

	public function getByCompany(int $companyId): Selection
	{
		return $this->db->table('deals')->where('company_id', $companyId)->order('created_at DESC');
	}
}
