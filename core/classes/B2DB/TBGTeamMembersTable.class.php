<?php

	/**
	 * Team members table
	 *
	 * @author Daniel Andre Eikeland <zegenie@zegeniestudios.net>
	 ** @version 3.0
	 * @license http://www.opensource.org/licenses/mozilla1.1.php Mozilla Public License 1.1 (MPL 1.1)
	 * @package thebuggenie
	 * @subpackage tables
	 */

	/**
	 * Team members table
	 *
	 * @package thebuggenie
	 * @subpackage tables
	 */
	class TBGTeamMembersTable extends TBGB2DBTable 
	{

		const B2DBNAME = 'teammembers';
		const ID = 'teammembers.id';
		const SCOPE = 'teammembers.scope';
		const UID = 'teammembers.uid';
		const TID = 'teammembers.tid';
		
		/**
		 * Return an instance of this table
		 *
		 * @return TBGTeamMembersTable
		 */
		public static function getTable()
		{
			return B2DB::getTable('TBGTeamMembersTable');
		}

		public function __construct()
		{
			parent::__construct(self::B2DBNAME, self::ID);
			
			parent::_addForeignKeyColumn(self::UID, TBGUsersTable::getTable(), TBGUsersTable::ID);
			parent::_addForeignKeyColumn(self::TID, B2DB::getTable('TBGTeamsTable'), TBGTeamsTable::ID);
			parent::_addForeignKeyColumn(self::SCOPE, TBGScopesTable::getTable(), TBGScopesTable::ID);
		}

		public function getUIDsForTeamID($team_id)
		{
			$crit = $this->getCriteria();
			$crit->addWhere(self::TID, $team_id);

			$uids = array();
			if ($res = $this->doSelect($crit))
			{
				while ($row = $res->getNextRow())
				{
					$uids[$row->get(self::UID)] = $row->get(self::UID);
				}
			}

			return $uids;
		}
		
		public function clearTeamsByUserID($user_id)
		{
			$crit = $this->getCriteria();
			$crit->addWhere(self::UID, $user_id);
			$res = $this->doDelete($crit);
		}

		public function getNumberOfMembersByTeamID($team_id)
		{
			$crit = $this->getCriteria();
			$crit->addWhere(self::TID, $team_id);
			$count = $this->doCount($crit);

			return $count;
		}

		public function cloneTeamMemberships($cloned_team_id, $new_team_id)
		{
			$crit = $this->getCriteria();
			$crit->addWhere(self::TID, $cloned_team_id);
			$memberships_to_add = array();
			if ($res = $this->doSelect($crit))
			{
				while ($row = $res->getNextRow())
				{
					$memberships_to_add[] = $row->get(self::UID);
				}
			}

			foreach ($memberships_to_add as $uid)
			{
				$crit = $this->getCriteria();
				$crit->addInsert(self::UID, $uid);
				$crit->addInsert(self::TID, $new_team_id);
				$crit->addInsert(self::SCOPE, TBGContext::getScope()->getID());
				$this->doInsert($crit);
			}
		}

	}
