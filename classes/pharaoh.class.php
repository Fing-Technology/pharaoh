<?php
/*
+---------------------------------------------------------------------------
|
|   pharaoh.class.php (php 5.x)
|
|   by Benjam Welker
|   http://iohelix.net
|
+---------------------------------------------------------------------------
|
|	This module is built to play the game of Pharaoh (Khet), it cares not about
|	database structure or the goings on of the website, only about Pharaoh
|
+---------------------------------------------------------------------------
|
|   > Pharaoh game module
|   > Date started: 2009-12-22
|
|   > Module Version Number: 0.8.0
|
|   $Id: pharaoh.class.php 198 2009-09-23 00:29:54Z cchristensen $
+---------------------------------------------------------------------------
*/

define('I_RIGHT', 1);
define('I_LEFT', -1);
define('I_UP', -10);
define('I_DOWN', 10);

class Pharaoh {

	/**
	 *		PROPERTIES
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/** public property players
	 *		Holds our player's data
	 *		format: (indexed by player_id then associative)
	 *		array(
	 *			player_id  => array('player_id', 'color', 'turn', 'extra_info' => array( ... )) ,
	 *			player_id  => array('player_id', 'color', 'turn', 'extra_info' => array( ... )) ,
	 *			'silver'   => reference to $this->players[silver_player_id] ,
	 *			'red'      => reference to $this->players[red_player_id] ,
	 *			'player'   => reference to $this->players[player_id] ,
	 *			'opponent' => reference to $this->players[opponent_id] ,
	 *		)
	 *
	 *		extra_info is an array that holds information about the current player state
	 *
	 * @var array of player data
	 */
	public $players;


	/** public property current_player
	 *		The current player's id
	 *
	 * @var int
	 */
	public $current_player;


	/** public property winner
	 *		Holds the winner
	 *		'silver' or 'red'
	 *
	 * @var string
	 */
	public $winner;


	/** protected property board
	 *		Holds the game board
	 *
	 * @var string
	 */
	protected $_board;


	/** protected property _laser_path
	 *		Holds the path the laser took
	 *		around the board
	 *		array(
	 *			array([board index], [incoming direction]),
	 *			array([board index], [incoming direction]),
	 *			...
	 *		)
	 *
	 *		Path is not necessarily continuous
	 *
	 * @var array
	 */
	protected $_laser_path;


	/** protected property _hits
	 *		Holds the indexes of pieces hit
	 *
	 * @var array of int board indexes
	 */
	protected $_hits;



	/**
	 *		METHODS
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/** public function __construct
	 *		Class constructor
	 *		Sets all outside data
	 *
	 * @param void
	 * @action instantiates object
	 * @return void
	 */
	public function __construct( )
	{
		call(__METHOD__);
	}


	/** public function __get
	 *		Class getter
	 *		Returns the requested property if the
	 *		requested property is not _private
	 *
	 * @param string property name
	 * @return mixed property value
	 */
	public function __get($property)
	{
		if ( ! property_exists($this, $property)) {
			throw new MyException(__METHOD__.': Trying to access non-existent property ('.$property.')', 2);
		}

		if ('_' === $property[0]) {
			throw new MyException(__METHOD__.': Trying to access _private property ('.$property.')', 2);
		}

		return $this->$property;
	}


	/** public function __set
	 *		Class setter
	 *		Sets the requested property if the
	 *		requested property is not _private
	 *
	 * @param string property name
	 * @param mixed property value
	 * @action optional validation
	 * @return bool success
	 */
	public function __set($property, $value)
	{
		switch ($property) {
			case 'board' :
				try {
					$this->set_board($value);
				}
				catch (MyException $e) {
					throw $e;
				}
				return;
				break;

			default :
				// do nothing
				break;
		}

		if ( ! property_exists($this, $property)) {
			throw new MyException(__METHOD__.': Trying to access non-existent property ('.$property.')', 3);
		}

		if ('_' === $property[0]) {
			throw new MyException(__METHOD__.': Trying to access _private property ('.$property.')', 3);
		}

		$this->$property = $value;
	}


	/** public function __toString
	 *		Returns the ascii version of the board
	 *		when asked to output the object
	 *
	 * @param void
	 * @return string ascii version of the board
	 */
	public function __toString( )
	{
		return $this->_get_board_ascii( );
	}


	/** public function do_move
	 *		Performs the given move
	 *		and then calls the laser firing method
	 *		Move syntax:
	 *			-move: from:to e.g.- B4:B5
	 *				- if splitting an obelisk tower, replace : with .
	 *				- e.g.- B4.B5
	 *			-rotate: square-direction (0 = CCW, 1 = CW)) e.g.- B4-0
	 *
	 * @param string move
	 * @return string board
	 */
	public function do_move($move)
	{
		call(__METHOD__);
debug($this->_board);
debug($move);

		$index = self::get_target_index(substr($move, 0, 2));

		// grab the color of the current piece
		$piece = $this->_board[$index];
		$color = self::get_piece_color($piece);

		// check for move or rotate
		try {
			if ('-' == $move[2]) { // rotate
				$this->_rotate_piece($index, $move[3]);
			}
			else { // move
				$this->_move_piece($index, self::get_target_index(substr($move, 3, 2)), (':' == $move[3]));
			}
		}
		catch (MyException $e) {
			throw $e;
		}

		$hits = $this->fire_laser($color);

		foreach ($hits as $hit) {
			// check if we hit a pharaoh
			if ('p' == $this->_board[$hit]) {
				$this->winner = 'silver';
			}
			elseif ('P' == $this->_board[$hit]) {
				$this->winner = 'red';
			}

			// remove the piece
			// or remove one of the stacked obelisks
			if ('W' == $this->_board[$hit]) {
				$this->_board[$hit] = 'V';
			}
			elseif ('w' == $this->_board[$hit]) {
				$this->_board[$hit] = 'v';
			}
			else {
				$this->_board[$hit] = '0';
			}
		}
		call($this->_get_board_ascii( ));
	}


	/** public function fire_laser
	 *		FIRE ZEE MISSILES !!!
	 *		But I am le tired
	 *
	 *		Fires the laser of the given color
	 *
	 * @param string color (red or silver)
	 * @return array of squares hit (empty array if none)
	 */
	function fire_laser($color)
	{
		$color = strtolower($color);

		if ( ! in_array($color, array('silver', 'red'))) {
			throw new MyException(__METHOD__.': Trying to fire laser for unknown color: '.$color);
		}

		$reflections = array(
			// pyramid
			'A' => array(I_LEFT  => I_UP,    I_DOWN => I_RIGHT), //  .\
			'B' => array(I_LEFT  => I_DOWN,  I_UP   => I_RIGHT), //  `/
			'C' => array(I_RIGHT => I_DOWN,  I_UP   => I_LEFT),  //  \`
			'D' => array(I_RIGHT => I_UP,    I_DOWN => I_LEFT),  //  /.

			// eye of horus
			'H' => array( //  \
				I_RIGHT => array(I_RIGHT, I_DOWN),
				I_LEFT  => array(I_LEFT,  I_UP),
				I_DOWN  => array(I_RIGHT, I_DOWN),
				I_UP    => array(I_LEFT,  I_UP),
			),
			'I' => array( //  /
				I_RIGHT => array(I_RIGHT, I_UP),
				I_LEFT  => array(I_LEFT,  I_DOWN),
				I_DOWN  => array(I_LEFT,  I_DOWN),
				I_UP    => array(I_RIGHT, I_UP),
			),

			// djed
			'X' => array( //  \
				I_RIGHT => I_DOWN,
				I_LEFT  => I_UP,
				I_DOWN  => I_RIGHT,
				I_UP    => I_LEFT,
			),
			'Y' => array( //  /
				I_RIGHT => I_UP,
				I_LEFT  => I_DOWN,
				I_DOWN  => I_LEFT,
				I_UP    => I_RIGHT,
			),
		);

		// fire the laser
		// red
		$start = 0;
		$dir = I_DOWN;
		if ('silver' == $color) {
			$start = 79;
			$dir = I_UP;
		}

		$i = 0; // infinite loop protection
		$current = $start;
		$used = array( );
		$queue = array( );
		$queue_used = array( );
		$hit = array( );
		$this->_laser_path = array( );
		while ($i < 99) { // no ad infinitum here
			// check if we hit a wall
			$long_wall = (0 > $current) || (80 <= $current);
			$short_wall = (1 == abs($dir)) && (floor($current / 10) !== floor(($current - $dir) / 10));
			if ($long_wall || $short_wall) {
				if ( ! (list($current, $dir) = $this->_check_queue($queue))) {
					break;
				}
				else {
					$current += $dir;
					continue;
				}
			}

			// make sure we haven't been here before
			if (in_array(array($current, $dir), $used, true)) {
				if ( ! (list($current, $dir) = $this->_check_queue($queue))) {
					break;
				}
				else {
					$current += $dir;
					continue;
				}
			}

			// add to our laser path
			$this->_laser_path[] = array($current, $dir);
			$used[] = array($current, $dir);

			// check the current location for a piece
			if ('0' != ($piece = $this->_board[$current])) {
				// check for hit or reflection
				if ( ! isset($reflections[strtoupper($piece)][$dir])) {
					$hit[] = $current;
					if ( ! (list($current, $dir) = $this->_check_queue($queue))) {
						break;
					}
					else {
						$current += $dir;
						continue;
					}
				}

				$dir = $reflections[strtoupper($piece)][$dir];
			}

			// this is where we split off in two directions through the beam splitter
			if (is_array($dir)) {
				// place the second element (the reflection) in the queue
				// and run the first element (the pass through)
				// because if we put the pass through in the queue
				// it causes problems with the $used array
				// because that value is already in there
				if ( ! in_array(array($current, $dir[1]), $queue_used, true)) {
					$queue[] = array($current, $dir[1]);
					$queue_used[] = array($current, $dir[1]);
				}

				$dir = $dir[0];
			}

			// increment for the next round
			$current += $dir;

			++$i; // keep those pesky infinite loops at bay
		}

debug($this->_laser_path);
call($this->_get_laser_ascii( ));
call($hit);
		return $hit;
	}


	public function get_laser_path( )
	{
		return $this->_laser_path;
	}


	private function _check_queue( & $queue)
	{
		if (count($queue)) {
			return array_pop($queue);
		}

		return false;
	}


	public function set_board($board)
	{
		call(__METHOD__);

		// test the board and make sure all is well
		if (80 != strlen($board)) {
			throw new MyException(__METHOD__.': Board is not the right size');
		}

		$this->_board = $board;
	}


	protected function _move_piece($from_index, $to_index, $both_obelisks = true)
	{
		call(__METHOD__);

		// check for a piece on the from square
		if ('0' == $this->_board[$from_index]) {
			throw new MyException(__METHOD__.': No piece found to move');
		}

		// check for only one square away
		$difference = abs($from_index - $to_index);
		if (0 == $difference) {
			throw new MyException(__METHOD__.': The from square and to square are the same');
		}

		if ( ! in_array($difference, array(1, 9, 10, 11))) {
			throw new MyException(__METHOD__.': Piece can only move one square');
		}
		// TODO: this needs work, it works, but it's not pretty
		// TOWER: this is going to get _complicated_

		// check for moving off edge of board
		$off_vertical = ((0 > $to_index) || (80 <= $to_index));
		$off_horizontal = ((1 == abs($from_index - $to_index)) && (floor($to_index / 10) !== floor($from_index / 10)));
		if ($off_vertical || $off_horizontal) {
			throw new MyException(__METHOD__.': Piece cannot move off the edge of the board');
		}
		// TODO: this needs work, it works, but it's not pretty

		// check for wrong color piece moving onto a colored square
		$not_silver = array(0, 10, 20, 30, 40, 50, 60, 70, 8, 78);
		$not_red = array(9, 19, 29, 39, 49, 59, 69, 79, 1, 71);
		$color_array = 'not_'.self::get_piece_color($this->_board[$from_index]);
		if (in_array($to_index, ${$color_array})) {
			throw new MyException(__METHOD__.': Cannot move onto square of opposite color');
		}
		// TODO: this needs work, it works, but it's not pretty

		// TOWER: test for moving a pharaoh into a corner of the tower
		// it's not allowed

		// check for a piece in the way
		if ('0' != $this->_board[$to_index]) {
			// both the Djed and Eye of Horus can swap places with other pieces
			if ( ! in_array(strtoupper($this->_board[$from_index]), array('H','I','X','Y'))) {
				throw new MyException(__METHOD__.': Target square not empty - '.$this->_board[$from_index]);
			}
			// but only with pyramids or obelisks
			elseif ( ! in_array(strtoupper($this->_board[$to_index]), array('A','B','C','D','V','W'))) {
				throw new MyException(__METHOD__.': Target piece not swappable - '.$this->_board[$from_index]);
			}
		}

		// make sure we are not trying to split a non-obelisk tower
		if ( ! $both_obelisks && ('W' != strtoupper($this->_board[$from_index]))) {
			$both_obelisks = true;
		}

		// if we made it here, the move is easy
		if ($both_obelisks) {
			// swap the two places
			$temp = $this->_board[$to_index];
			$this->_board[$to_index] = $this->_board[$from_index];
			$this->_board[$from_index] = $temp;
		}
		else { // we're splitting an obelisk tower
			// set both from and to equal to a single obelisk
			$this->board[$from_index] = $this->board[$to_index] = (('W' == $this->board[$from_index]) ? 'V' : 'v');
		}
		call($this->_get_board_ascii( ));
	}


	protected function _rotate_piece($index, $direction)
	{
		call(__METHOD__);

		$rotation = array(
			'A' => array('D', 'B'),
			'B' => array('A', 'C'),
			'C' => array('B', 'D'),
			'D' => array('C', 'A'),

			'X' => array('Y', 'Y'),
			'Y' => array('X', 'X'),

			'H' => array('I', 'I'),
			'I' => array('H', 'H'),
		);

		$piece = $this->_board[$index];
		$silver = ($piece == strtoupper($piece));
		$piece = strtoupper($piece);

		if ( ! isset($rotation[$piece])) {
			return false;
		}

		$this->_board[$index] = ($silver ? $rotation[$piece][$direction] : strtolower($rotation[$piece][$direction]));
	}


	static public function get_board_ascii($board)
	{
		$ascii = '
     A   B   C   D   E   F   G   H   I   J
   +---+---+---+---+---+---+---+---+---+---+';

		for ($length = strlen($board), $i = 0; $i < $length; ++$i) {
			$char = $board[$i];

			if (0 == ($i % 10)) {
				$ascii .= "\n ".(floor($i / 10) + 1).' |';
			}

			if ('0' == $char) {
				$char = ' ';
			}

			$ascii .= ' '.$char.' |';

			if (9 == ($i % 10)) {
				$ascii .= ' '.(floor($i / 10) + 1).'
   +---+---+---+---+---+---+---+---+---+---+';
  			}
		}

		$ascii .= '
     A   B   C   D   E   F   G   H   I   J
';

/*
     A   B   C   D   E   F   G   H   I   J
  +---+---+---+---+---+---+---+---+---+---+
1 | R | S |   |   |   |   |   |   | R | S | 1
  +---+---+---+---+---+---+---+---+---+---+
2 | R |   |   |   |   |   |   |   |   | S | 2
  +---+---+---+---+---+---+---+---+---+---+
3 | R |   |   |   |   |   |   |   |   | S | 3
  +---+---+---+---+---+---+---+---+---+---+
4 | R |   |   |   |   |   |   |   |   | S | 4
  +---+---+---+---+---o---+---+---+---+---+
5 | R |   |   |   |   |   |   |   |   | S | 5
  +---+---+---+---+---+---+---+---+---+---+
6 | R |   |   |   |   |   |   |   |   | S | 6
  +---+---+---+---+---+---+---+---+---+---+
7 | R |   |   |   |   |   |   |   |   | S | 7
  +---+---+---+---+---+---+---+---+---+---+
8 | R | S |   |   |   |   |   |   | R | S | 8
  +---+---+---+---+---+---+---+---+---+---+
     A   B   C   D   E   F   G   H   I   J
*/

		return $ascii;
	}


	protected function _get_board_ascii($board = null)
	{
		if ( ! $board) {
			$board = $this->_board;
		}

		return self::get_board_ascii($board);
	}


	static public function get_laser_ascii($board, $laser_path)
	{
		foreach ($laser_path as $node) {
			if ('0' == $board[$node[0]]) {
				$board[$node[0]] = (1 == abs($node[1])) ? '-' : '|';
			}
		}

		return self::get_board_ascii($board);
	}


	protected function _get_laser_ascii($board = null, $laser_path = null)
	{
		if ( ! $board) {
			$board = $this->_board;
		}

		if ( ! $laser_path) {
			$laser_path = $this->_laser_path;
		}

		return self::get_laser_ascii($board, $laser_path);
	}


	/** static public function get_target_index
	 *		Converts a human readable target (H7)
	 *		into a computer readable string index (76)
	 *
	 * @param string target
	 * @return int string index
	 */
	static public function get_target_index($target)
	{
		try {
			$target = self::test_target($target);
		}
		catch (MyException $e) {
			throw $e;
		}

		$chars = array('A','B','C','D','E','F','G','H','I','J');
		$index = array_search($target[0], $chars);

		$index += 10 * ((int) $target[1] - 1);

		return $index;
	}


	/** static public function get_piece_color
	 *		Grabs the color of the given piece
	 *
	 * @param string piece code
	 * @return string piece color
	 */
	static public function get_piece_color($piece)
	{
		if (strtoupper($piece) === $piece) {
			return 'silver';
		}

		return 'red';
	}


	/** static public function test_target
	 *		Tests the target for proper format
	 *
	 * @param string target
	 * @return string target
	 */
	static public function test_target($target)
	{
		// make sure it's uppercase and only 2 characters long
		$target = strtoupper($target);
		$target = substr($target, 0, 2);

		// test the format
		if ( ! preg_match('/[A-H]\d/', $target)) {
			throw new MyException(__METHOD__.': Invalid target format');
		}

		return $target;
	}


} // end Pharaoh


if ( ! class_exists('MyException')) {
	class MyException extends Exception { }
}