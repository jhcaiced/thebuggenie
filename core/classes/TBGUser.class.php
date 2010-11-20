<?php

	/**
	 * User class
	 *
	 * @author Daniel Andre Eikeland <zegenie@zegeniestudios.net>
	 ** @version 3.0
	 * @license http://www.opensource.org/licenses/mozilla1.1.php Mozilla Public License 1.1 (MPL 1.1)
	 * @package thebuggenie
	 * @subpackage core
	 */

	/**
	 * User class
	 *
	 * @package thebuggenie
	 * @subpackage core
	 */
	class TBGUser extends TBGIdentifiableClass 
	{
		protected $_b2dbtablename = 'TBGUsersTable';
		
		/**
		 * Unique username (login name)
		 *
		 * @var string
		 * @access protected
		 */
		protected $_username = '';
		
		/**
		 * Whether or not the user has authenticated
		 * 
		 * @var boolean
		 */
		protected $authenticated = false;
		
		/**
		 * Hashed password
		 *
		 * @var string
		 * @access protected
		 */
		protected $_password = '';
		
		/**
		 * The users scope
		 *
		 * @var TBGScope
		 * @access protected
		 */
		protected $_scope = null;
		
		/**
		 * User real name
		 *
		 * @var string
		 * @access protected
		 */
		protected $_realname = '';
		
		/**
		 * User short name (buddyname)
		 *
		 * @var string
		 * @access protected
		 */
		protected $_buddyname = '';
		
		/**
		 * User email
		 *
		 * @var string
		 * @access protected
		 */
		protected $_email = '';
		
		/**
		 * Is email private?
		 *
		 * @var boolean
		 * @access protected
		 */
		protected $_private_email = true;
		
		/**
		 * The user state
		 *
		 * @var TBGDatatype
		 */
		protected $_userstate = null;
		
		/**
		 * User homepage
		 *
		 * @var string
		 * @access protected
		 */
		protected $_homepage = '';

		/**
		 * Users language
		 *
		 * @var string
		 */
		protected $_language = '';
		
		/**
		 * Array of team ids where the current user is a member
		 *
		 * @var array
		 * 
		 * @access protected
		 */
		protected $teams = null;
		
		/**
		 * The users avatar
		 *
		 * @var string
		 * @access protected
		 */
		protected $_avatar = null;
		
		/**
		 * Whether to use the users gravatar or not
		 * 
		 * @var boolean
		 */
		protected $_use_gravatar = null;
		
		/**
		 * The users login error - if any
		 *
		 * @var string
		 * @access protected
		 */
		protected $login_error = '';
		
		/**
		 * Array of issues to follow up
		 *
		 * @var array
		 * @access protected
		 */
		protected $_starredissues = null;
		
		/**
		 * Array of issues assigned to the user
		 *
		 * @var array
		 * @access protected
		 */
		protected $userassigned = null;
		
		/**
		 * Array of issues assigned to the users team(s)
		 *
		 * @var array
		 * @access protected
		 */
		protected $teamassigned = array();
		
		/**
		 * Array of saved searches to show on the frontpage
		 *
		 * @var array
		 * @access protected
		 */
		protected $indexsearches = array();
		
		/**
		 * The users group 
		 * 
		 * @var TBGGroup
		 */
		protected $_group_id = null;
	
		/**
		 * The users customer, if any
		 * 
		 * @var TBGCustomer
		 */
		protected $_customer_id = null;
	
		/**
		 * A list of the users associated projects, if any
		 * 
		 * @var array
		 */
		protected $_associated_projects = null;
		
		/**
		 * Timestamp of when the user was last seen
		 *
		 * @var integer
		 */
		protected $_lastseen = 0;

		/**
		 * The timezone this user is in
		 *
		 * @var integer
		 */
		protected $_timezone = null;
		
		protected $_quota;

		protected $row = null;
		protected $_joined = 0;
		protected $_friends = null;
		
		protected $_enabled = false;
		protected $_activated = false;
		protected $_deleted = false;
		
		public static function getUsersByVerified($activated)
		{
			$crit = new B2DBCriteria();
			$crit->addWhere(TBGUsersTable::ACTIVATED, ($activated) ? 1 : 0);
			$res = TBGUsersTable::getTable()->doSelect($crit);
			
			$users = array();
			while ($row = $res->getNextRow())
			{
				$users[] = array('id' => $row->get(TBGUsersTable::ID));
			}
			return $users;
		}

		public static function getUsersByEnabled($enabled)
		{
			$crit = new B2DBCriteria();
			$crit->addWhere(TBGUsersTable::ENABLED, ($enabled) ? 1 : 0);
			$res = TBGUsersTable::getTable()->doSelect($crit);
			
			$users = array();
			while ($row = $res->getNextRow())
			{
				$users[] = array('id' => $row->get(TBGUsersTable::ID));
			}
			return $users;
		}

		/**
		 * Retrieve a user by username
		 *
		 * @param string $username
		 *
		 * @return TBGUser
		 */
		public static function getByUsername($username)
		{
			if ($row = TBGUsersTable::getTable()->getByUsername($username))
			{
				return TBGContext::factory()->TBGUser($row->get(TBGUsersTable::ID), $row);
			}
			return null;
		}
		
		public static function getUsers($details, $noScope = false, $unique = false)
		{
			$users = array();
			$crit = new B2DBCriteria();
			if (strlen($details) > 1)
			{
				if (stristr($details, "@"))
				{
					$crit->addWhere(TBGUsersTable::EMAIL, "%$details%", B2DBCriteria::DB_LIKE);
				}
				else
				{
					$crit->addWhere(TBGUsersTable::UNAME, "%$details%", B2DBCriteria::DB_LIKE);
				}
		
				if ($noScope == false)
				{
					$crit->addWhere(TBGUsersTable::SCOPE, TBGContext::getScope()->getID());
				}
			}
			else
			{
				$crit->addWhere(TBGUsersTable::UNAME, "$details%", B2DBCriteria::DB_LIKE);
			}
	
			$res = TBGUsersTable::getTable()->doSelect($crit);
	
			if ($res->count() == 0 && strlen($details) > 1)
			{
				$crit = new B2DBCriteria();
				$ctn = $crit->returnCriterion(TBGUsersTable::UNAME, "%$details%", B2DBCriteria::DB_LIKE);
				$ctn->addOr(TBGUsersTable::BUDDYNAME, "%$details%", B2DBCriteria::DB_LIKE);
				$ctn->addOr(TBGUsersTable::REALNAME, "%$details%", B2DBCriteria::DB_LIKE);
				$crit->addWhere($ctn);
				if ($noScope == false)
				{
					$crit->addWhere(TBGUsersTable::SCOPE, TBGContext::getScope()->getID());
				}
				$res = TBGUsersTable::getTable()->doSelect($crit);
			}
	
			if ($res->count() == 0)
			{
				return false;
			}
			elseif ($res->count() == 1)
			{
				while ($row = $res->getNextRow())
				{
					$users[] = array('id' => $row->get(TBGUsersTable::ID));
				}
				return $users;
			}
			elseif ($unique == true)
			{
				return false;
			}
			else
			{
				while ($row = $res->getNextRow())
				{
					$users[] = array('id' => $row->get(TBGUsersTable::ID));
				}
				return $users;
			}
		}
		
		/**
		 * Take a raw password and convert it to the hashed format
		 * 
		 * @param string $password
		 * 
		 * @return hashed password
		 */
		public static function hashPassword($password)
		{
			return sha1($password.TBGSettings::getPasswordSalt());
		}
		
		/**
		 * Returns the logged in user, or default user if not logged in
		 *
		 * @param string $uname
		 * @param string $upwd
		 * 
		 * @return TBGUser
		 */
		public static function loginCheck($username = null, $password = null)
		{
			try
			{
				$row = null;
				$event = TBGEvent::createNew('TBGUser', 'loginCheck');
				$event->trigger();

				if ($event->isProcessed())
				{
					$row = $event->getReturnValue();
				}
				else
				{
					if ($username === null && $password === null)
					{
						if (TBGContext::getRequest()->hasCookie('tbg3_username') && TBGContext::getRequest()->hasCookie('tbg3_password'))
						{
							$username = TBGContext::getRequest()->getCookie('tbg3_username');
							$password = TBGContext::getRequest()->getCookie('tbg3_password');
							$row = TBGUsersTable::getTable()->getByUsernameAndPassword($username, $password);
							
							if (!$row)
							{
								TBGContext::getResponse()->deleteCookie('tbg3_username');
								TBGContext::getResponse()->deleteCookie('tbg3_password');
								throw new Exception('No such login');
								//TBGContext::getResponse()->headerRedirect(TBGContext::getRouting()->generate('login'));
							}
						}
					}
					if ($username !== null && $password !== null)
					{
						// First test a pre-encrypted password
						$row = TBGUsersTable::getTable()->getByUsernameAndPassword($username, $password);

						if (!$row)
						{
							// Then test an unencrypted password
							$row = TBGUsersTable::getTable()->getByUsernameAndPassword($username, self::hashPassword($password));
							
							if(!$row)
							{
								// This is a legacy account from a 2.1 upgrade - try md5
								$row = TBGUsersTable::getTable()->getByUsernameAndPassword($username, md5($password));
								if(!$row)
								{
									// Invalid
									TBGContext::getResponse()->deleteCookie('tbg3_username');
									TBGContext::getResponse()->deleteCookie('tbg3_password');
									throw new Exception('No such login');
									//TBGContext::getResponse()->headerRedirect(TBGContext::getRouting()->generate('login'));
								}
								else 
								{
									// convert md5 to new password type
									$user = new TBGUser($row->get(TBGUsersTable::ID), $row);
									$user->changePassword($password);
									$user->save();
									unset($user);
								}
							}
						}
					}
					elseif (TBGContext::isCLI())
					{
						$row = TBGUsersTable::getTable()->getByUsername(TBGContext::getCurrentCLIusername());
					}
					elseif (!TBGSettings::isLoginRequired())
					{
						$row = TBGUsersTable::getTable()->getByUserID(TBGSettings::getDefaultUserID());
					}
				}

				if ($row)
				{
					if (!$row->get(TBGScopesTable::ENABLED))
					{
						throw new Exception('This account belongs to a scope that is not active');
					}
					elseif (!$row->get(TBGUsersTable::ACTIVATED))
					{
						throw new Exception('This account has not been activated yet');
					}
					elseif (!$row->get(TBGUsersTable::ENABLED))
					{
						throw new Exception('This account has been suspended');
					}
					$user = TBGContext::factory()->TBGUser($row->get(TBGUsersTable::ID), $row);
				}
				elseif (TBGSettings::isLoginRequired())
				{
					throw new Exception('Login required');
				}
				else
				{
					throw new Exception('No such login');
				}
			}
			catch (Exception $e)
			{
				throw $e;
			}
	
			try
			{
				if ($user->isAuthenticated())
				{
					$user->updateLastSeen();
					if ($user->getScope() instanceof TBGScope)
					{
						$_SESSION['b2_scope'] = $user->getScope()->getID();
					}
					if (!($user->getGroup() instanceof TBGGroup))
					{
						throw new Exception('This user account belongs to a group that does not exist anymore. <br>Please contact the system administrator.');
					}
				}
			}
			catch (Exception $e)
			{
				throw $e;
			}
			
			return $user;
	
		}
		
		/**
		 * Create and return a temporary password
		 * 
		 * @return string
		 */
		public static function createPassword($len = 8)
		{
			$pass = '';
			$lchar = 0;
			$char = 0;
			for($i = 0; $i < $len; $i++)
			{
				while($char == $lchar)
				{
					$char = mt_rand(48, 109);
					if($char > 57) $char += 7;
					if($char > 90) $char += 6;
				}
				$pass .= chr($char);
				$lchar = $char;
			}
			return $pass;
		}
		
		/**
		 * Creates a new user and returns it
		 *
		 * @param string $username
		 * @param string $realname
		 * @param string $buddyname
		 * @param integer $scope
		 * @param boolean $activated
		 * @param boolean $enabled
		 * 
		 * @return TBGUser
		 */
		public static function createNew($username, $realname, $buddyname, $scope, $activated = false, $enabled = false, $password = 'password', $email = '', $pass_is_hash = false, $u_id = null, $lastseen = null)
		{
			if (TBGUsersTable::getTable()->getByUsername($username) instanceof B2DBRow)
			{
				throw new Exception(TBGContext::getI18n()->__('This username already exists'));
			}
			$crit = new B2DBCriteria();
			if ($u_id !== null)
			{
				$crit->addInsert(TBGUsersTable::ID, $u_id);
			}
			if ($lastseen !== null)
			{
				$crit->addInsert(TBGUsersTable::LASTSEEN, $lastseen);
			}
			$crit->addInsert(TBGUsersTable::UNAME, $username);
			$crit->addInsert(TBGUsersTable::REALNAME, $realname);
			$crit->addInsert(TBGUsersTable::BUDDYNAME, $buddyname);
			$crit->addInsert(TBGUsersTable::EMAIL, $email);
			if ($pass_is_hash)
			{
				$crit->addInsert(TBGUsersTable::PASSWORD, $password);
			}
			else
			{
				$crit->addInsert(TBGUsersTable::PASSWORD, self::hashPassword($password));
			}
			$crit->addInsert(TBGUsersTable::SCOPE, $scope);
			$crit->addInsert(TBGUsersTable::ACTIVATED, $activated);
			$crit->addInsert(TBGUsersTable::ENABLED, $enabled);
			$crit->addInsert(TBGUsersTable::JOINED, NOW);
			$crit->addInsert(TBGUsersTable::AVATAR, 'smiley');
			$crit->addInsert(TBGUsersTable::GROUP_ID, '2');
			$res = TBGUsersTable::getTable()->doInsert($crit);
	
			if ($u_id === null) $u_id = $res->getInsertID();
			
			$returnUser = TBGContext::factory()->TBGUser($u_id);
			$event = TBGEvent::createNew('core', 'TBGUser::createNew', $returnUser);
			$event->trigger();
			if (!$event->isProcessed())
			{
				$returnUser->setEnabled();
				$returnUser->setActivated();
				$returnUser->save();
			}
			return $returnUser;
		}
		
		/**
		 * Returns whether the current user is a guest or not
		 * 
		 * @return boolean
		 */
		public static function isThisGuest()
		{
			if (TBGContext::getUser() instanceof TBGUser)
			{
				return TBGContext::getUser()->isGuest();
			}
			else
			{
				return true;
			}
		}
		
		/**
		 * Class constructor
		 *
		 * @param B2DBRow $row
		 */
		public function _construct(B2DBRow $row)
		{
			try
			{
				/*if (($row->get(TBGUsersTable::USERSTATE) == TBGSettings::get('offlinestate') || $row->get(TBGUsersTable::USERSTATE) == TBGSettings::get('awaystate')) && !TBGContext::getRequest()->getParameter('setuserstate')) 
				{ 
					$this->setState(TBGSettings::get('onlinestate')); 
				}*/
				if ($this->_group_id != 0)
				{
					$this->_group_id = TBGContext::factory()->TBGGroup($row->get(TBGUsersTable::GROUP_ID), $row);
				}
				if ($row->get(TBGUsersTable::CUSTOMER_ID) != 0)
				{
					$this->_customer_id = TBGContext::factory()->TBGCustomer($row->get(TBGUsersTable::CUSTOMER_ID), $row);
				}
			}
			catch (Exception $e)
			{
				TBGLogging::log("Something went wrong setting up user with id {$this->getID()}: ".$e->getMessage());
				throw $e;
			}
			TBGLogging::log("User with id {$this->getID()} set up successfully");
		}
		
		public function getName()
		{
			return $this->_realname;
		}
		
		public function getID()
		{
			return $this->_id;
		}
		
		public function getNameWithUsername()
		{
			return ($this->_buddyname) ? $this->_buddyname . ' (' . $this->_username . ')' : $this->_username;
		}
		
		public function __toString()
		{
			return $this->getNameWithUsername();
		}
		
		/**
		 * Checks whether the user has a login error or not
		 *
		 * @return boolean
		 */
		public function hasLoginError()
		{
			if ($this->login_error != '' && $this->login_error != 'guest')
			{
				return true;
			}
			else
			{
				return false;
			}
		}
		
		public function isAuthenticated()
		{
			return $this->authenticated;
		}
		
		public function updateLastSeen()
		{
			$crit = new B2DBCriteria();
			$crit->addUpdate(TBGUsersTable::LASTSEEN, NOW);
			$crit->addWhere(TBGUsersTable::ID, $this->_id);
			TBGUsersTable::getTable()->doUpdate($crit);
			$this->_lastseen = NOW;
		}
		
		public function getLastSeen()
		{
			return $this->_lastseen;
		}
		
		public function getJoinedDate()
		{
			return $this->_joined;
		}
		
		/**
		 * Checks if the user is a member of the given team
		 *
		 * @param integer $teamid
		 * 
		 * @return boolean
		 */
		public function isMemberOf($teamid)
		{
			$this->_populateTeams();
			if ($teamid != 0)
			{
				return array_key_exists($teamid, $this->teams);
			}
			return false;
		}
		
		/**
		 * Populates team array when needed
		 *
		 */
		protected function _populateTeams()
		{
			if ($this->teams === null)
			{
				$this->teams = array();
				TBGLogging::log('Populating user teams');
				$crit = new B2DBCriteria();
				$crit->addWhere(TBGTeamMembersTable::UID, $this->_id);
		
				if (B2DB::getTable('TBGTeamMembersTable')->doCount($crit) > 0)
				{
					$res = B2DB::getTable('TBGTeamMembersTable')->doSelect($crit);
					while ($row = $res->getNextRow())
					{
						$this->teams[$row->get(TBGTeamsTable::ID)] = TBGContext::factory()->TBGTeam($row->get(TBGTeamsTable::ID), $row);
					}
				}
				TBGLogging::log('...done (Populating user teams)');
			}
		}
	
		/**
		 * Checks whether or not the user is logged in
		 *
		 * @return boolean
		 */
		public function isLoggedIn()
		{
			return ($this->_id != 0) ? true : false;
		}
		
		/**
		 * Checks whether or not the current user is a "regular" or "guest" user
		 *
		 * @return boolean
		 */
		public function isGuest()
		{
			return (bool) (!$this->isLoggedIn() || ($this->getID() == TBGSettings::getDefaultUserID() && TBGSettings::isDefaultUserGuest()));
		}
	
		/**
		 * Returns an array of issue ids which are directly assigned to the current user
		 *
		 * @return array
		 */
		public function getUserAssignedIssues()
		{
			if ($this->userassigned === null)
			{
				$this->userassigned = array();
				if ($res = TBGIssuesTable::getTable()->getOpenIssuesByUserAssigned($this->getUID()))
				{
					while ($row = $res->getNextRow())
					{
						$this->userassigned[$row->get(TBGIssuesTable::ID)] = TBGContext::factory()->TBGIssue($row->get(TBGIssuesTable::ID), $row);
					}
					ksort($this->userassigned, SORT_NUMERIC);
				}
			}
			return $this->userassigned;
		}
	
		/**
		 * Returns an array of issue ids assigned to the given team
		 *
		 * @param integer $team_id The team id
		 * @return array
		 */
		public function getUserTeamAssignedIssues($team_id)
		{
			if (!array_key_exists($team_id, $this->teamassigned))
			{
				$this->teamassigned[$team_id] = array();
				if ($res = TBGIssuesTable::getTable()->getOpenIssuesByTeamAssigned($team_id))
				{
					while ($row = $res->getNextRow())
					{
						$this->teamassigned[$team_id][$row->get(TBGIssuesTable::ID)] = TBGContext::factory()->TBGIssue($row->get(TBGIssuesTable::ID), $row);
					}
				}
				ksort($this->teamassigned[$team_id], SORT_NUMERIC);
			}
			return $this->teamassigned[$team_id];
		}

		/**
		 * Populate the array of starred issues
		 */
		protected function _populateStarredIssues()
		{
			if ($this->_starredissues === null)
			{
				$this->_starredissues = array();
				if ($res = B2DB::getTable('TBGUserIssuesTable')->getUserStarredIssues($this->getUID()))
				{
					while ($row = $res->getNextRow())
					{
						$this->_starredissues[$row->get(TBGIssuesTable::ID)] = TBGContext::factory()->TBGIssue($row->get(TBGIssuesTable::ID), $row);
					}
					ksort($this->_starredissues, SORT_NUMERIC);
				}
			}
		}
		
		/**
		 * Returns an array of issues ids which are "starred" by this user
		 *
		 * @return array
		 */
		public function getStarredIssues()
		{
			$this->_populateStarredIssues();
			return $this->_starredissues;
		}
		
		/**
		 * Returns whether or not an issue is starred
		 * 
		 * @param integer $issue_id The issue ID to check
		 * 
		 * @return boolean
		 */
		public function isIssueStarred($issue_id)
		{
			$this->_populateStarredIssues();
			return array_key_exists($issue_id, $this->_starredissues);
		}
		
		/**
		 * Adds an issue to the list of issues "starred" by this user 
		 *
		 * @param integer $issue_id ID of issue to add
		 * @return boolean
		 */
		public function addStarredIssue($issue_id)
		{
			$this->_populateStarredIssues();
			TBGLogging::log("Starring issue with id {$issue_id} for user with id " . $this->getUID());
			if ($this->isLoggedIn() == true && $this->isGuest() == false)
			{
				if (array_key_exists($issue_id, $this->_starredissues))
				{
					TBGLogging::log('Already starred');
					return true;
				}
				TBGLogging::log('Logged in and unstarred, continuing');
				$crit = new B2DBCriteria();
				$crit->addInsert(TBGUserIssuesTable::ISSUE, $issue_id);
				$crit->addInsert(TBGUserIssuesTable::UID, $this->_id);
				$crit->addInsert(TBGUserIssuesTable::SCOPE, TBGContext::getScope()->getID());
				
				B2DB::getTable('TBGUserIssuesTable')->doInsert($crit);
				$issue = TBGContext::factory()->TBGIssue($issue_id);
				$this->_starredissues[$issue->getID()] = $issue;
				ksort($this->_starredissues);
				TBGLogging::log('Starred');
				return true;
			}
			else
			{
				TBGLogging::log('Not logged in');
				return false;
			}
		}
	
		/**
		 * Removes an issue from the list of flagged issues
		 *
		 * @param integer $issue_id ID of issue to remove
		 */
		public function removeStarredIssue($issue_id)
		{
			$crit = new B2DBCriteria();
			$crit->addWhere(TBGUserIssuesTable::ISSUE, $issue_id);
			$crit->addWhere(TBGUserIssuesTable::UID, $this->_id);
				
			B2DB::getTable('TBGUserIssuesTable')->doDelete($crit);
			unset($this->_starredissues[$issue_id]);
			return true;
		}
	
		/**
		 * Sets up the internal friends array
		 */
		protected function _setupFriends()
		{
			if ($this->_friends === null)
			{
				$this->_friends = array();
				if ($res = TBGBuddiesTable::getTable()->getFriendsByUserID($this->getID()))
				{
					while ($row = $res->getNextRow())
					{
						$this->_friends[$row->get(TBGBuddiesTable::BID)] = TBGContext::factory()->TBGUser($row->get(TBGBuddiesTable::BID));
					}
				}
			}
		}

		/**
		 * Adds a friend to the buddy list
		 *
		 * @param TBGUser $user Friend to add
		 * 
		 * @return boolean
		 */
		public function addFriend($user)
		{
			if (!($this->isFriend($user)))
			{
				TBGBuddiesTable::getTable()->addFriend($this->getID(), $user->getID());
				$this->_friends[$user->getID()] = $user;
				return true;
			}
			else
			{
				return false;
			}
		}
	
		/**
		 * Get all this users friends
		 *
		 * @return array An array of TBGUsers
		 */
		public function getFriends()
		{
			$this->_setupFriends();
			return $this->_friends;
		}
		
		/**
		 * Removes a user from the list of buddies
		 *
		 * @param TBGUser $user User to remove
		 */
		public function removeFriend($user)
		{
			TBGBuddiesTable::getTable()->removeFriendByUserID($this->getID(), $user->getID());
			if (is_array($this->_friends))
			{
				unset($this->_friends[$user->getID()]);
			}
		}
	
		/**
		 * Check if the given user is a friend of this user
		 *
		 * @param TBGUser $user The user to check
		 * 
		 * @return boolean
		 */
		public function isFriend($user)
		{
			$this->_setupFriends();
			if (empty($this->_friends)) return false;
			return array_key_exists($user->getID(), $this->_friends);
		}
	
		/**
		 * Change the password to a new password
		 *
		 * @param string $newpassword
		 */
		public function changePassword($newpassword)
		{
			$this->_password = self::hashPassword($newpassword);
		}
		
		/**
		 * Set the user state to this state 
		 *
		 * @param integer $s_id
		 * @return nothing
		 */
		public function setState($s_id)
		{
			$crit = new B2DBCriteria();
			$crit->addUpdate(TBGUsersTable::USERSTATE, $s_id);
			$crit->addWhere(TBGUsersTable::ID, $this->_id);
			
			TBGUsersTable::getTable()->doUpdate($crit);
			$this->_userstate = $s_id;
		}
		
		/**
		 * Get the current user state
		 *
		 * @return TBGDatatype
		 */
		public function getState()
		{
			$now = NOW;
			if (($this->_lastseen < ($now - (60 * 10))) && ($this->_userstate != TBGSettings::get('offlinestate') && $this->_userstate != TBGSettings::get('awaystate')))
			{
				$this->setState(TBGSettings::get('awaystate'));
			}
			if ($this->_lastseen < ($now - (60 * 30)) && $this->_userstate != TBGSettings::get('offlinestate'))
			{
				$this->setState(TBGSettings::get('offlinestate'));
			}
			TBGEvent::createNew('core', 'TBGUser::getState', $this)->trigger();
			
			if (!$this->_userstate instanceof TBGUserstate)
			{
				if ($this->_userstate == 0)
				{
					$this->_userstate = TBGSettings::get('offlinestate');
				}
				$this->_userstate = TBGContext::factory()->TBGUserstate($this->_userstate);
			}
			return $this->_userstate;
		}
		
		public function isEnabled()
		{
			return $this->_enabled;
		}

		public function setActivated($val = true)
		{
			$this->_activated = (boolean) $val;
		}

		public function isActivated()
		{
			return $this->_activated;
		}
		
		public function isDeleted()
		{
			return $this->_deleted;
		}
		
		/**
		 * Returns an array of teams which the current user is a member of
		 *
		 * @return array
		 */
		public function getTeams()
		{
			$this->_populateTeams();
			return $this->teams;
		}
		
		public function clearTeams()
		{
			B2DB::getTable('TBGTeamMembersTable')->clearTeamsByUserID($this->getID());
		}
		
		public function addToTeam(TBGTeam $team)
		{
			$team->addMember($this);
			$this->teams = null;
			$this->_populateTeams();
		}
		
		public function getType()
		{
			return self::TYPE_USER;
		}
		
		private function _setUserDetail($detail, $value)
		{
			$crit = new B2DBCriteria();
			$crit->addUpdate($detail, $value);
			TBGUsersTable::getTable()->doUpdateById($crit, $this->_id);
			return true;
		}
	
		/**
		 * Set whether or not the email address is hidden for normal users
		 *
		 * @param boolean $val
		 */
		public function setEmailPrivate($val)
		{
			$this->_private_email = (bool) $val;
		}
		
		/**
		 * Returns whether or not the email address is private
		 *
		 * @return boolean
		 */
		public function isEmailPrivate()
		{
			return $this->_private_email;
		}

		/**
		 * Returns whether or not the email address is public
		 *
		 * @return boolean
		 */
		public function isEmailPublic()
		{
			return !$this->_private_email;
		}
		
		/**
		 * Sets the login error to something
		 *
		 * @param string $login_error
		 */
		public function setLoginError($login_error)
		{
			$this->login_error = $login_error;
		}
		
		/**
		 * Returns the current users login error
		 *
		 * @return string
		 */
		public function getLoginError()
		{
			return $this->login_error;
		}
		
		/**
		 * Returns the scope of this user
		 *
		 * @return TBGScope
		 */
		public function getScope()
		{
			return $this->_scope;
		}
		
		/**
		 * Returns the UID of this user
		 *
		 * @return integer
		 */
		public function getUID()
		{
			return $this->_id;
		}
		
		/**
		 * Returns the user group
		 *
		 * @return TBGGroup
		 */
		public function getGroup()
		{
			return $this->_group_id;
		}

		public function getGroupID()
		{
			if (is_object($this->getGroup()))
			{
				return $this->getGroup()->getID();
			}
			elseif (is_numeric($this->getGroup()))
			{
				return $this->getGroup();
			}

			return null;
		}
		
		public function setGroup($group)
		{
			if (!is_object($group))
			{
				$group = TBGContext::factory()->TBGGroup($group);
			}
			$this->_group_id = $group;
		}
		
		/**
		 * Returns the login name (username)
		 *
		 * @return string
		 */
		public function getUname()
		{
			return $this->_username;
		}
		
		/**
		 * Set the username
		 *
		 * @param string $username
		 */
		public function setUsername($username)
		{
			$this->_username = $username;
		}

		public function getUsername()
		{
			return $this->getUname();
		}
		
		/**
		 * Returns a hash of the user password
		 *
		 * @return string
		 */
		public function getHashPassword()
		{
			return $this->_password;
		}

		/**
		 * Return whether or not the users password is this
		 *
		 * @param string $password Unhashed password
		 *
		 * @return boolean
		 */
		public function hasPassword($password)
		{
			return $this->hasPasswordHash(self::hashPassword($password));
		}

		/**
		 * Return whether or not the users password is this
		 *
		 * @param string $password Hashed password
		 *
		 * @return boolean
		 */
		public function hasPasswordHash($password)
		{
			return (bool) ($password == $this->getHashPassword());
		}

		/**
		 * Returns the real name (full name) of the user
		 *
		 * @return string
		 */
		public function getRealname()
		{
			return $this->_realname;
		}
		
		/**
		 * Returns the buddy name (friendly name) of the user
		 *
		 * @return string
		 */
		public function getBuddyname()
		{
			return $this->_buddyname;
		}

		/**
		 * Return the users nickname (buddyname)
		 *
		 * @uses self::getBuddyname()
		 *
		 * @return string
		 */
		public function getNickname()
		{
			return $this->getBuddyname();
		}
		
		/**
		 * Returns the email of the user
		 *
		 * @return string
		 */
		public function getEmail()
		{
			return $this->_email;
		}
		
		/**
		 * Returns the users homepage
		 *
		 * @return unknown
		 */
		public function getHomepage()
		{
			return $this->_homepage;
		}

		/**
		 * Set this users homepage
		 *
		 * @param string $homepage
		 */
		public function setHomepage($homepage)
		{
			$this->_homepage = $homepage;
		}
		
		/**
		 * Set the avatar image
		 *
		 * @param string $avatar
		 */
		public function setAvatar($avatar)
		{
			$this->_avatar = $avatar;
		}
		
		/**
		 * Returns the avatar of the user
		 *
		 * @return string
		 */
		public function getAvatar()
		{
			return ($this->_avatar != '') ? $this->_avatar : 'user';
		}
		
		/**
		 * Return the users avatar url
		 * 
		 * @param boolean $small[optional] Whether to get the URL for the small avatar (default small)
		 * 
		 * @return string an URL to put in an <img> tag
		 */
		public function getAvatarURL($small = true)
		{
			$url = '';
			if ($this->usesGravatar())
			{
				$url = 'http://www.gravatar.com/avatar/' . md5(trim($this->getEmail())) . '.png?d=wavatar&amp;s=';
				$url .= ($small) ? 22 : 48; 
			}
			else
			{
				$url = TBGSettings::getURLsubdir() . 'avatars/' . $this->getAvatar();
				if ($small) $url .= '_small';
				$url .= '.png';
			}
			return $url;
		}
		
		/**
		 * Return whether the user uses gravatar for avatars
		 * 
		 * @return boolean
		 */
		public function usesGravatar()
		{
			if ($this->isGuest()) return false;
			return (bool) $this->_use_gravatar;
		}
		
		/**
		 * Updates user information
		 *
		 * @param string $realname
		 * @param string $buddyname
		 * @param string $homepage
		 * @param string $email
		 */
		public function updateUserDetails($realname = null, $buddyname = null, $homepage = null, $email = null, $uname = null)
		{
			$crit = new B2DBCriteria();
			
			if ($realname !== null) $crit->addUpdate(TBGUsersTable::REALNAME, $realname);
			if ($buddyname !== null) $crit->addUpdate(TBGUsersTable::BUDDYNAME, $buddyname);
			if ($homepage !== null) $crit->addUpdate(TBGUsersTable::HOMEPAGE, $homepage);
			if ($email !== null) $crit->addUpdate(TBGUsersTable::EMAIL, $email);
			if ($uname !== null) $crit->addUpdate(TBGUsersTable::UNAME, $uname);
			
			$res = TBGUsersTable::getTable()->doUpdateById($crit, $this->_id);
			
			if ($realname !== null) $this->_realname = $realname;
			if ($buddyname !== null) $this->_buddyname = $buddyname;
			if ($homepage !== null) $this->_homepage = $homepage;
			if ($email !== null) $this->_email = $email;
			if ($uname !== null) $this->_username = $uname;
		}

		/**
		 * Set the users email address
		 *
		 * @param string $email A valid email address
		 */
		public function setEmail($email)
		{
			$this->_email = $email;
		}

		/**
		 * Set the users realname
		 *
		 * @param string $realname
		 */
		public function setRealname($realname)
		{
			$this->_realname = $realname;
		}

		/**
		 * Set the users buddyname
		 *
		 * @param string $buddyname
		 */
		public function setBuddyname($buddyname)
		{
			$this->_buddyname = $buddyname;
		}

		/**
		 * Set whether the user uses gravatar
		 *
		 * @param string $val
		 */
		public function setUsesGravatar($val)
		{
			$this->_use_gravatar = (bool) $val;
		}

		public function setEnabled($val = true)
		{
			$this->_enabled = $val;
		}
		
		public function setValidated($val)
		{
			$crit = new B2DBCriteria();
			$crit->addUpdate(TBGUsersTable::ACTIVATED, ($val) ? 1 : 0);
			TBGUsersTable::getTable()->doUpdateById($crit, $this->getID());
			$this->_activated = $val;
		}
		
		/**
		 * Find one user based on details
		 * 
		 * @param string $details Any user detail (email, username, realname or buddyname)
		 * 
		 * @return TBGUser
		 */
		public static function findUser($details)
		{
			$res = TBGUsersTable::getTable()->getByDetails($details);
			
			if (!$res || $res->count() > 1) return false;
			$row = $res->getNextRow();
			
			return TBGContext::factory()->TBGUser($row->get(TBGUsersTable::ID), $row);
		}

		/**
		 * Find users based on details
		 * 
		 * @param string $details Any user detail (email, username, realname or buddyname)
		 * @param integer $limit[optional] an optional limit on the number of results
		 * 
		 * @return array
		 */
		public static function findUsers($details, $limit = null)
		{
			$retarr = array();
			
			if ($res = TBGUsersTable::getTable()->getByDetails($details))
			{
				while ($row = $res->getNextRow())
				{
					$retarr[$row->get(TBGUsersTable::ID)] = TBGContext::factory()->TBGUser($row->get(TBGUsersTable::ID), $row);
				}
			}
			return $retarr;
		}
	
		/**
		 * Perform a permission check on this user
		 * 
		 * @param string $permission_type The permission key
		 * @param integer $target_id[optional] a target id if applicable
		 * @param string $module_name[optional] the module for which the permission is valid
		 * @param boolean $explicit[optional] whether to check for an explicit permission and return false if not set
		 * @param boolean $permissive[optional] whether to return false or true when explicit fails
		 * 
		 * @return boolean
		 */
		public function hasPermission($permission_type, $target_id = 0, $module_name = 'core', $explicit = false, $permissive = false)
		{
			TBGLogging::log('Checking permission '.$permission_type);
			$group_id = ($this->getGroup() instanceof TBGGroup) ? $this->getGroup()->getID() : 0;
			$retval = TBGContext::checkPermission($permission_type, $this->getID(), $group_id, $this->getTeams(), $target_id, $module_name, $explicit, $permissive);
			TBGLogging::log('...done (Checking permissions '.$permission_type.')');
			
			return $retval;
		}

		/**
		 * Whether this user can access the specified module
		 * 
		 * @param string $module The module key
		 * 
		 * @return boolean
		 */
		public function hasModuleAccess($module)
		{
			return TBGContext::getModule($module)->hasAccess($this->getID());
		}
	
		/**
		 * Whether this user can access the specified page
		 * 
		 * @param string $page The page key
		 * 
		 * @return boolean
		 */
		public function hasPageAccess($page, $target_id = null)
		{
			if ($target_id === null)
			{
				return $this->hasPermission("page_{$page}_access", 0, "core", true, true);
			}
			else
			{
				$retval = $this->hasPermission("page_{$page}_access", $target_id, "core", true, TBGSettings::isPermissive());
				return ($retval === null) ? $this->hasPermission("page_{$page}_access", 0, "core", true, TBGSettings::isPermissive()) : $retval;
			}
		}

		/**
		 * Save changes made to the user object
		 *
		 * @return TBGUser The user object
		 */
		public function postSave()
		{
			TBGSettings::saveSetting('timezone', $this->_timezone, 'core', null, $this->getID());
		}

		/**
		 * Get this users timezone
		 *
		 * @return mixed
		 */
		public function getTimezone()
		{
			return $this->_timezone;
		}

		/**
		 * Set this users timezone
		 *
		 * @param integer $timezone
		 */
		public function setTimezone($timezone)
		{
			$this->_timezone = $timezone;
		}
		
		/**
		 * Return whether the user can vote on issues for a specific product
		 *  
		 * @param integer $product_id The Product id
		 * 
		 * @return boolean
		 */
		public function canVoteOnIssuesForProduct($product_id)
		{
			return (bool) $this->hasPermission("b2canvote", $product_id);
		}
		
		/**
		 * Return whether the user can vote for a specific issue
		 * 
		 * @param integer $issue_id The issue id
		 * 
		 * @return boolean
		 */
		public function canVoteForIssue($issue_id)
		{
			return !(bool) $this->hasPermission("b2cantvote", $issue_id);
		}

		/**
		 * Return if the user can add builds to an issue for a given project
		 * 
		 * @param integer $project_id The project id
		 * 
		 * @return boolean
		 */
		public function canAddBuildsToIssuesForProject($project_id)
		{
			return (bool) $this->hasPermission('b2canaddbuilds', $project_id);
		}

		/**
		 * Return if the user can add components to an issue for a given project
		 * 
		 * @param integer $project_id The project id
		 * 
		 * @return boolean
		 */
		public function canAddComponentsToIssuesForProject($project_id)
		{
			return (bool) $this->hasPermission('b2canaddcomponents', $project_id);
		}

		/**
		 * Return if the user can report new issues
		 *
		 * @param integer $product_id[optional] A product id
		 * @return boolean
		 */
		public function canReportIssues($product_id = null)
		{
			$retval = null;
			if ($product_id !== null)
			{
				$retval = $this->hasPermission('cancreateissues', $product_id, 'core', true, null);
				$retval = ($retval !== null) ? $retval : $this->hasPermission('cancreateandeditissues', $product_id, 'core', true, null);
			}
			return ($retval !== null) ? $retval : (bool) ($this->hasPermission('cancreateissues') || $this->hasPermission('cancreateandeditissues'));
		}

		/**
		 * Return if the user can search for issues
		 *
		 * @return boolean
		 */
		public function canSearchForIssues()
		{
			return (bool) ($this->hasPermission('canfindissues') || $this->hasPermission('canfindissuesandsavesearches'));
		}

		/**
		 * Return if the user can edit the main menu
		 *
		 * @return boolean
		 */
		public function canEditMainMenu()
		{
			return (bool) ($this->hasPermission('caneditmainmenu'));
		}

		/**
		 * Return if the user can see comments
		 *
		 * @return boolean
		 */
		public function canViewComments()
		{
			return (bool) ($this->hasPermission('canviewcomments') || $this->hasPermission('canpostandeditcomments'));
		}

		/**
		 * Return if the user can post comments
		 *
		 * @return boolean
		 */
		public function canPostComments()
		{
			return (bool) ($this->hasPermission('canpostcomments') || $this->hasPermission('canpostandeditcomments'));
		}

		/**
		 * Return if the user can see non public comments
		 *
		 * @return boolean
		 */
		public function canSeeNonPublicComments()
		{
			return (bool) ($this->hasPermission('canseenonpubliccomments') || $this->hasPermission('canpostseeandeditallcomments'));
		}

		/**
		 * Return if the user can create public saved searches
		 *
		 * @return boolean
		 */
		public function canCreatePublicSearches()
		{
			return (bool) ($this->hasPermission('cancreatepublicsearches') || $this->hasPermission('canfindissuesandsavesearches'));
		}

		/**
		 * Return whether the user can access a saved search
		 *
		 * @param B2DBrow $savedsearch
		 * 
		 * @return boolean
		 */
		public function canAccessSavedSearch($savedsearch)
		{
			return (bool) ($savedsearch->get(TBGSavedSearchesTable::IS_PUBLIC) || $savedsearch->get(TBGSavedSearchesTable::UID) == $this->getID());
		}

		/**
		 * Return if the user can access configuration pages
		 *
		 * @param integer $section[optional] a section, or the configuration frontpage
		 * 
		 * @return boolean
		 */
		public function canAccessConfigurationPage($section = null)
		{
			return (bool) ($this->hasPermission('canviewconfig', $section, 'core', true) || $this->hasPermission('cansaveconfig', $section, 'core', true) || $this->hasPermission('canviewconfig', 0, 'core', true) || $this->hasPermission('cansaveconfig', 0, 'core', true));
		}

		/**
		 * Return if the user can save configuration in a section
		 *
		 * @return boolean
		 */
		public function canSaveConfiguration($section, $module = 'core')
		{
			return (bool) ($this->hasPermission('cansaveconfig', $section, $module, true) || $this->hasPermission('cansaveconfig', 0, $module, true));
		}

		/**
		 * Return if the user can manage a project
		 *
		 * @param TBGProject $project
		 * 
		 * @return boolean
		 */
		public function canManageProject(TBGProject $project)
		{
			return (bool) $this->hasPermission('canmanageproject', $project->getID());
		}

		/**
		 * Return if the user can manage releases for a project
		 *
		 * @param TBGProject $project
		 *
		 * @return boolean
		 */
		public function canManageProjectReleases(TBGProject $project)
		{
			return (bool) ($this->hasPermission('canmanageprojectreleases', $project->getID()) || $this->hasPermission('canmanageproject', $project->getID()));
		}

		/**
		 * Return if the user can edit project details and settings
		 *
		 * @param TBGProject $project
		 *
		 * @return boolean
		 */
		public function canEditProjectDetails(TBGProject $project)
		{
			return (bool) ($this->hasPermission('caneditprojectdetails', $project->getID(), 'core', true) || $this->hasPermission('canmanageproject', $project->getID(), 'core', true));
		}

		/**
		 * Return if the user can add scrum user stories
		 *
		 * @param TBGProject $project
		 *
		 * @return boolean
		 */
		public function canAddScrumUserStories(TBGProject $project)
		{
			return (bool) ($this->hasPermission('canaddscrumuserstories', $project->getID(), 'core', true) || $this->hasPermission('candoscrumplanning', $project->getID(), 'core', true) || $this->hasPermission('canaddscrumuserstories', 0, 'core', true) || $this->hasPermission('candoscrumplanning', 0, 'core', true));
		}

		/**
		 * Return if the user can add scrum sprints
		 *
		 * @param TBGProject $project
		 *
		 * @return boolean
		 */
		public function canAddScrumSprints(TBGProject $project)
		{
			return (bool) ($this->hasPermission('canaddscrumsprints', $project->getID(), 'core', true) || $this->hasPermission('candoscrumplanning', $project->getID(), 'core', true) || $this->hasPermission('canaddscrumsprints', 0, 'core', true) || $this->hasPermission('candoscrumplanning', 0, 'core', true));
		}

		/**
		 * Return if the user can assign scrum user stories
		 *
		 * @param TBGProject $project
		 *
		 * @return boolean
		 */
		public function canAssignScrumUserStories(TBGProject $project)
		{
			return (bool) ($this->hasPermission('canassignscrumuserstoriestosprints', $project->getID(), 'core', true) || $this->hasPermission('candoscrumplanning', $project->getID(), 'core', true) || $this->hasPermission('canassignscrumuserstoriestosprints', 0, 'core', true) || $this->hasPermission('candoscrumplanning', 0, 'core', true));
		}

		/**
		 * Return a list of the users latest log items
		 * 
		 * @param integer $number Limit to a number of changes
		 * 
		 * @return array
		 */
		public function getLatestActions($number = 10)
		{
			if ($items = TBGLogTable::getTable()->getByUserID($this->getUID(), $number))
			{
				return $items;
			}
			else
			{
				return array();
			}
		}

		/**
		 * Clears the associated projects cache (useful only when you know that you've changed assignees this same request
		 * 
		 * @return null
		 */
		public function clearAssociatedProjectsCache()
		{
			$this->_associated_projects = null;
		}
		
		/**
		 * Get all the projects a user is associated with
		 * 
		 * @return array
		 */
		public function getAssociatedProjects()
		{
			if ($this->_associated_projects === null)
			{
				$this->_associated_projects = array();
				
				$projects = B2DB::getTable('TBGProjectAssigneesTable')->getProjectsByUserID($this->getUID());
				$edition_projects = B2DB::getTable('TBGEditionAssigneesTable')->getProjectsByUserID($this->getUID());
				$component_projects = B2DB::getTable('TBGComponentAssigneesTable')->getProjectsByUserID($this->getUID());

				$project_ids = array_merge(array_keys($projects), array_keys($edition_projects), array_keys($component_projects));
				foreach ($project_ids as $project_id)
				{
					$this->_associated_projects[$project_id] = TBGContext::factory()->TBGProject($project_id);
				}
			}
			
			return $this->_associated_projects;
		}
		
		/**
		 * Return an array of issues that has changes pending
		 * 
		 * @return array
		 */
		public function getIssuesPendingChanges()
		{
			return TBGChangeableItem::getChangedItems('TBGIssue');
		}

		public function setLanguage($language)
		{
			$this->_language = $language;
		}

		public function getLanguage()
		{
			return ($this->_language != '') ? $this->_language : TBGContext::getI18n()->getCurrentLanguage();
		}

		/**
		 * Return an array of issues that has changes pending
		 * 
		 * @param int $number number of issues to be retrieved
		 * 
		 * @return array
		 */		
		public function getIssues($number = null)
		{
			$res = B2DB::getTable('TBGIssuesTable')->getIssuesPostedByUser($this->getID(), $number);
			if($res)
			{
				return $res->getAllRows();
			}
			else
			{
				return array();
			}
			
		}
		
	}
