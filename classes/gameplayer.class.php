<?php
/*
+---------------------------------------------------------------------------
|
|   gameplayer.class.php (php 5.x)
|
|   by Benjam Welker
|   http://iohelix.net
|
+---------------------------------------------------------------------------
|
|   > Game Player Extension module
|   > Date started: 2008-02-28
|
|   > Module Version Number: 0.8.0
|
+---------------------------------------------------------------------------
*/

// TODO: comments & organize better

class GamePlayer
	extends Player
{

	/**
	 *		PROPERTIES
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/** const property EXTEND_TABLE
	 *		Holds the player extend table name
	 *
	 * @var string
	 */
	const EXTEND_TABLE = T_PHARAOH;


	/** protected property allow_email
	 *		Flag shows whether or not to send emails to this player
	 *
	 * @var bool
	 */
	protected $allow_email;


	/** protected property max_games
	 *		Number of games player can be in at one time
	 *
	 * @var int
	 */
	protected $max_games;


	/** protected property current_games
	 *		Number of games player is currently playing in
	 *
	 * @var int
	 */
	protected $current_games;


	/** protected property color
	 *		Holds the players skin color preference
	 *
	 * @var string
	 */
	protected $color;


	/** protected property wins
	 *		Holds the players win count
	 *
	 * @var int
	 */
	protected $wins;


	/** protected property draws
	 *		Holds the players draw count
	 *
	 * @var int
	 */
	protected $draws;


	/** protected property losses
	 *		Holds the players loss count
	 *
	 * @var int
	 */
	protected $losses;


	/** protected property last_online
	 *		Holds the date the player was last online
	 *
	 * @var int (unix timestamp)
	 */
	protected $last_online;



	/**
	 *		METHODS
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/** public function __construct
	 *		Class constructor
	 *		Sets all outside data
	 *
	 * @param int optional player id
	 * @action instantiates object
	 * @return void
	 */
	public function __construct($id = null)
	{
		$this->_mysql = Mysql::get_instance( );

		// check and make sure we have logged into this game before
		if (0 != (int) $id) {
			$query = "
				SELECT COUNT(*)
				FROM ".self::EXTEND_TABLE."
				WHERE player_id = '{$id}'
			";
			$count = (int) $this->_mysql->fetch_value($query);

			if (0 == $count) {
				throw new MyException(__METHOD__.': '.GAME_NAME.' Player (#'.$id.') not found in database');
			}
		}

	 	parent::__construct($id);
	}


	/** public function log_in
	 *		Runs the parent's log_in function
	 *		then, if success, tests game player
	 *		database to see if this player has been
	 *		here before, if not, it adds then to the
	 *		database, and if so, refreshes the last_online value
	 *
	 * @param void
	 * @action logs the player in
	 * @action optionally adds new game player data to the database
	 * @return bool success
	 */
	public function log_in( )
	{
		// this will redirect and exit upon failure
		parent::log_in( );

		// test an arbitrary property for existence, so we don't _pull twice unnecessarily
		// but don't test color, because it might actually be null when valid
		if (is_null($this->last_online)) {
			$this->_mysql->insert(self::EXTEND_TABLE, array('player_id' => $this->id));

			$this->_pull( );
		}

		// don't update the last online time if we logged in as an admin
		if ( ! isset($_SESSION['admin_id'])) {
			$this->_mysql->insert(self::EXTEND_TABLE, array('last_online' => NULL), " WHERE player_id = '{$this->id}' ");
		}

		return true;
	}


	/** public function register
	 *		Registers a new player in the extend table
	 *		also calls the parent register function
	 *		which performs some validity checks
	 *
	 * @param void
	 * @action creates a new player in the database
	 * @return bool success
	 */
	public function register( )
	{
		call(__METHOD__);

		try {
			parent::register( );
		}
		catch (MyException $e) {
			call('Exception Thrown: '.$e->getMessage( ));
			throw $e;
		}

		if ($this->id) {
			// add the user to the table
			$this->_mysql->insert(self::EXTEND_TABLE, array('player_id' => $this->id));

			// update the last_online time so we don't break things later
			$this->_mysql->insert(self::EXTEND_TABLE, array('last_online' => NULL), " WHERE player_id = '{$this->id}' ");
		}
	}


	/** public function add_win
	 *		Adds a win to this player's stats
	 *		both here, and in the database
	 *
	 * @param void
	 * @action adds a win in the database
	 * @return void
	 */
	public function add_win( )
	{
		$this->wins++;

		// note the trailing space on the field name, it's not a typo
		$this->_mysql->insert(self::EXTEND_TABLE, array('wins ' => 'wins + 1'), " WHERE player_id = '{$this->id}' ");
	}


	/** public function add_draw
	 *		Adds a draw to this player's stats
	 *		both here, and in the database
	 *
	 * @param void
	 * @action adds a draw in the database
	 * @return void
	 */
	public function add_draw( )
	{
		$this->draws++;

		// note the trailing space on the field name, it's not a typo
		$this->_mysql->insert(self::EXTEND_TABLE, array('draws ' => 'draws + 1'), " WHERE player_id = '{$this->id}' ");
	}


	/** public function add_loss
	 *		Adds a loss to this player's stats
	 *		both here, and in the database
	 *
	 * @param void
	 * @action adds a loss in the database
	 * @return void
	 */
	public function add_loss( )
	{
		$this->losses++;

		// note the trailing space on the field name, it's not a typo
		$this->_mysql->insert(self::EXTEND_TABLE, array('losses ' => 'losses + 1'), " WHERE player_id = '{$this->id}' ");
	}


	/** public function admin_delete
	 *		Deletes the given players from the players database
	 *
	 * @param mixed csv or array of player IDs
	 * @action deletes the players from the database
	 * @return void
	 */
	public function admin_delete($player_ids)
	{
		call(__METHOD__);

		// make sure the user doing this is an admin
		if ( ! $this->is_admin) {
			throw new MyException(__METHOD__.': Player is not an admin');
		}

		array_trim($player_ids, 'int');

		$player_ids = Player::clean_deleted($player_ids);

		if ( ! $player_ids) {
			throw new MyException(__METHOD__.': No player IDs given');
		}

		$this->_mysql->delete(self::EXTEND_TABLE, " WHERE player_id IN (".implode(',', $player_ids).") ");
	}


	/** public function admin_add_admin
	 *		Gives the given players admin status
	 *
	 * @param mixed csv or array of player IDs
	 * @action gives the given players admin status
	 * @return void
	 */
	public function admin_add_admin($player_ids)
	{
		// make sure the user doing this is an admin
		if ( ! $this->is_admin) {
			throw new MyException(__METHOD__.': Player is not an admin');
		}

		array_trim($player_ids, 'int');
		$player_ids[] = 0; // make sure we have at least one entry

		if (isset($GLOBALS['_ROOT_ADMIN'])) {
			$query = "
				SELECT player_id
				FROM ".Player::PLAYER_TABLE."
				WHERE username = '{$GLOBALS['_ROOT_ADMIN']}'
			";
			$player_ids[] = (int) $this->_mysql->fetch_value($query);
		}

		$this->_mysql->insert(self::EXTEND_TABLE, array('is_admin' => 1), " WHERE player_id IN (".implode(',', $player_ids).") ");
	}


	/** public function admin_remove_admin
	 *		Removes admin status from the given players
	 *
	 * @param mixed csv or array of player IDs
	 * @action removes the given players admin status
	 * @return void
	 */
	public function admin_remove_admin($player_ids)
	{
		// make sure the user doing this is an admin
		if ( ! $this->is_admin) {
			throw new MyException(__METHOD__.': Player is not an admin');
		}

		array_trim($player_ids, 'int');
		$player_ids[] = 0; // make sure we have at least one entry

		if (isset($GLOBALS['_ROOT_ADMIN'])) {
			$query = "
				SELECT player_id
				FROM ".Player::PLAYER_TABLE."
				WHERE username = '{$GLOBALS['_ROOT_ADMIN']}'
			";
			$root_admin = (int) $this->_mysql->fetch_value($query);

			if (in_array($root_admin, $player_ids)) {
				unset($player_ids[array_search($root_admin, $player_ids)]);
			}
		}

		// remove the player doing the removing
		unset($player_ids[array_search($_SESSION['player_id'], $player_ids)]);

		// remove the admin doing the removing
		unset($player_ids[array_search($_SESSION['admin_id'], $player_ids)]);

		$this->_mysql->insert(self::EXTEND_TABLE, array('is_admin' => 0), " WHERE player_id IN (".implode(',', $player_ids).") ");
	}


	/** public function save
	 *		Saves all changed data to the database
	 *
	 * @param void
	 * @action saves the player data
	 * @return void
	 */
	public function save( )
	{
		// update the player data
		$query = "
			SELECT allow_email
				, max_games
				, color
			FROM ".self::EXTEND_TABLE."
			WHERE player_id = '{$this->id}'
		";
		$player = $this->_mysql->fetch_assoc($query);

		if ( ! $player) {
			throw new MyException(__METHOD__.': Player data not found for player #'.$this->id);
		}

		// TODO: test the last online date and make sure we still have valid data

		$update_player = false;
		if ((bool) $player['allow_email'] != $this->allow_email) {
			$update_player['allow_email'] = (int) $this->allow_email;
		}

		if ($player['max_games'] != $this->max_games) {
			$update_player['max_games'] = (int) $this->max_games;
		}

		if ($player['color'] != $this->color) {
			$update_player['color'] = $this->color;
		}

		if ($update_player) {
			$this->_mysql->insert(self::EXTEND_TABLE, $update_player, " WHERE player_id = '{$this->id}' ");
		}
	}


	/** protected function _pull
	 *		Pulls all game player data from the database
	 *		as well as the parent's data
	 *
	 * @param void
	 * @action pulls the player data
	 * @action pulls the game player data
	 * @return void
	 */
	protected function _pull( )
	{
		parent::_pull( );

		$query = "
			SELECT *
			FROM ".self::EXTEND_TABLE."
			WHERE player_id = '{$this->id}'
		";
		$result = $this->_mysql->fetch_assoc($query);

		if ( ! $result) {
// TODO: find out what is going on here and fix.
#			throw new MyException(__METHOD__.': Data not found in database (#'.$this->id.')');
return false;
		}

		$this->is_admin = ( ! $this->is_admin) ? (bool) $result['is_admin'] : true;

		$this->allow_email = (bool) $result['allow_email'];
		$this->max_games = (int) $result['max_games'];
		$this->color = $result['color'];
		$this->wins = (int) $result['wins'];
		$this->draws = (int) $result['draws'];
		$this->losses = (int) $result['losses'];
		$this->last_online = strtotime($result['last_online']);

		// grab the player's current game count
		$query = "
			SELECT COUNT(*)
			FROM ".Game::GAME_TABLE." AS G
				LEFT JOIN ".Game::GAME_HISTORY_TABLE." AS GH
					USING (game_id)
			WHERE ((G.white_id = '{$this->id}'
						OR G.black_id = '{$this->id}')
					AND GH.board IS NOT NULL)
				AND G.state = 'Playing'
		";
		$this->current_games = $this->_mysql->fetch_value($query);
	}



	/**
	 *		STATIC METHODS
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/** static public function get_list
	 *		Returns a list array of all game players
	 *		in the database
	 *		This function supersedes the parent's function and
	 *		just grabs the whole lot in one query
	 *
	 * @param bool restrict to approved players
	 * @return array game player list (or bool false on failure)
	 */
	static public function get_list($only_approved = false)
	{
		$Mysql = Mysql::get_instance( );

		$WHERE = ($only_approved) ? " WHERE P.is_approved = 1 " : '';

		$query = "
			SELECT *
				, P.is_admin AS full_admin
				, E.is_admin AS half_admin
			FROM ".Player::PLAYER_TABLE." AS P
				INNER JOIN ".self::EXTEND_TABLE." AS E
					USING (player_id)
			{$WHERE}
			ORDER BY P.username
		";
		$list = $Mysql->fetch_array($query);

		return $list;
	}


	/** static public function get_count
	 *		Returns a count of all game players
	 *		in the database
	 *
	 * @param void
	 * @return int game player count
	 */
	static public function get_count( )
	{
		$Mysql = Mysql::get_instance( );

		$query = "
			SELECT COUNT(*)
			FROM ".self::EXTEND_TABLE." AS E
				JOIN ".Player::PLAYER_TABLE." AS P
					USING (player_id)
			WHERE P.is_approved = 1
			-- TODO: AND E.is_approved = 1
		";
		$count = $Mysql->fetch_value($query);

		return $count;
	}


	/** static public function get_maxed
	 *		Returns an array of all player IDs
	 *		who have reached their max games count
	 *
	 * @param void
	 * @return array of int player IDs
	 */
	static public function get_maxed( )
	{
		$Mysql = Mysql::get_instance( );

		// run through the maxed invites and set the key
		// to the player id and the value to the invite count
		// for ease of use later
		$invites = array( );
		// TODO: set a setting for this
		if (true || $invites_count_toward_max_games) {
			$query = "
				SELECT COUNT(G.game_id) AS invite_count
					, PE.player_id
				FROM ".Game::GAME_TABLE." AS G
					LEFT JOIN ".self::EXTEND_TABLE." AS PE
						ON (PE.player_id = G.white_id
							OR PE.player_id = G.black_id)
				WHERE G.state = 'Waiting'
					AND PE.max_games > 0
				GROUP BY PE.player_id
			";
			$maxed_invites = $Mysql->fetch_array($query);

			foreach ($maxed_invites as $invite) {
				$invites[$invite['player_id']] = $invite['invite_count'];
			}
		}

		$query = "
			SELECT COUNT(G.game_id) AS game_count
				, PE.player_id
				, PE.max_games
			FROM ".Game::GAME_TABLE." AS G
				LEFT JOIN ".self::EXTEND_TABLE." AS PE
					ON (PE.player_id = G.white_id
						OR PE.player_id = G.black_id)
			WHERE G.state = 'Playing'
				AND PE.max_games > 0
			GROUP BY PE.player_id
		";
		$maxed_players = $Mysql->fetch_array($query);

		$player_ids = array( );
		foreach ($maxed_players as $data) {
			if ( ! isset($invites[$data['player_id']])) {
				$invites[$data['player_id']] = 0;
			}

			if (($data['game_count'] + $invites[$data['player_id']]) >= $data['max_games']) {
				$player_ids[] = $data['player_id'];
			}
		}

		return $player_ids;
	}


	/** static public function delete_inactive
	 *		Deletes the inactive users from the database
	 *
	 * @param int age in days
	 * @return void
	 */
	static public function delete_inactive($age)
	{
		call(__METHOD__);

		$Mysql = Mysql::get_instance( );

		$age = (int) abs($age);

		if (0 == $age) {
			return false;
		}

		$exception_ids = array( );

		// make sure the 'unused' player is not an admin
		$query = "
			SELECT EP.player_id
			FROM ".self::EXTEND_TABLE." AS EP
				JOIN ".Player::PLAYER_TABLE." AS P
					USING (player_id)
			WHERE P.is_admin = 1
				OR EP.is_admin = 1
		";
		$results = $Mysql->fetch_value_array($query);
		$exception_ids = array_merge($exception_ids, $results);

		// make sure the 'unused' player is not currently in a game
		$query = "
			SELECT DISTINCT white_id
			FROM ".Game::GAME_TABLE."
			WHERE state = 'Playing'
		";
		$results = $Mysql->fetch_value_array($query);
		$exception_ids = array_merge($exception_ids, $results);

		$query = "
			SELECT DISTINCT black_id
			FROM ".Game::GAME_TABLE."
			WHERE state = 'Playing'
		";
		$results = $Mysql->fetch_value_array($query);
		$exception_ids = array_merge($exception_ids, $results);

		// make sure the 'unused' player isn't awaiting approval
		$query = "
			SELECT player_id
			FROM ".Player::PLAYER_TABLE."
			WHERE is_approved = 0
		";
		$results = $Mysql->fetch_value_array($query);
		$exception_ids = array_merge($exception_ids, $results);

		$exception_ids[] = 0; // don't break the IN clause
		$exception_id_list = implode(',', $exception_ids);

		// select unused accounts
		$query = "
			SELECT player_id
			FROM ".self::EXTEND_TABLE."
			WHERE wins + losses <= 2
				AND player_id NOT IN ({$exception_id_list})
				AND last_online < DATE_SUB(NOW( ), INTERVAL {$age} DAY)
		";
		$player_ids = $Mysql->fetch_value_array($query);
		call($player_ids);

		if ($player_ids) {
			Game::player_deleted($player_ids);
			$Mysql->delete(self::EXTEND_TABLE, " WHERE player_id IN (".implode(',', $player_ids).") ");
		}
	}

} // end of GamePlayer class


/*		schemas
// ===================================

--
-- Table structure for table `ph_ph_player`
--

DROP TABLE IF EXISTS `ph_ph_player`;
CREATE TABLE IF NOT EXISTS `ph_ph_player` (
  `player_id` int(11) unsigned NOT NULL DEFAULT '0',
  `is_admin` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `allow_email` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `max_games` tinyint(2) unsigned NOT NULL DEFAULT '0',
  `color` varchar(25) NULL DEFAULT NULL,
  `wins` smallint(5) unsigned NOT NULL DEFAULT '0',
  `draws` smallint(5) unsigned NOT NULL DEFAULT '0',
  `losses` smallint(5) unsigned NOT NULL DEFAULT '0',
  `last_online` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY `id` (`player_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci ;

*/

