<?php
/*
+---------------------------------------------------------------------------
|
|   schema.class.php (php 5.x)
|
|   by Benjam Welker
|   http://iohelix.net
|
+---------------------------------------------------------------------------
|
|   > MySQL Schema module
|   > Date started: 2009-11-02
|
|   > Module Version Number: 0.8.0
|
|   $Id: schema.class.php 202 2009-10-07 16:18:19Z cchristensen $
+---------------------------------------------------------------------------
*/

// TODO: comments & organize better

class Schema extends Mysql
{

	/**
	 *		PROPERTIES
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/** public property db_schema
	 *		The current schema from the DB
	 *
	 * @var array
	 */
	public $db_schema;
	
	/** public property new_schema
	 *		The desired schema from the DB
	 *
	 * @var array
	 */
	public $new_schema;



	/**
	 *		STATIC METHODS
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/** static public function get_schema
	 *		Grabs the current schema from the DB
	 *
	 * @param void
	 * @return array current DB schema
	 */
	static public function get_schema( )
	{
		$_this = self::get_instance( );

		// grab a list of all the tables
		$query = "
			SHOW TABLES
		";
		$tables = $_this->fetch_array($query);
debug($tables);

		$tables_schema = array( );
		foreach ($tables as $table) {
			$query = "
				DESCRIBE {$table}
			";
			$table_schema = $_this->fetch_assoc($query);
debug($table_schema);
		}

	}

} // end Schema class