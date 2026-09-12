<?php

namespace Config;

use CodeIgniter\Database\Config;

/**
 * Database Configuration
 */
class Database extends Config
{
	/**
	 * The directory that holds the Migrations
	 * and Seeds directories.
	 *
	 * @var string
	 */
	public $filesPath = APPPATH . 'Database' . DIRECTORY_SEPARATOR;

	/**
	 * Lets you choose which connection group to
	 * use if no other is specified.
	 *
	 * @var string
	 */
	public $defaultGroup = 'default';

	/**
	 * The default database connection.
	 *
	 * @var array
	 */
	public $default = [
		'DSN'      => '',
		'hostname' => 'localhost',
		'username' => 'root',
		'password' => '',
		'database' => 'kuro_panel',
		'DBDriver' => 'MySQLi',
		'DBPrefix' => '',
		'pConnect' => false,
		'DBDebug'  => (ENVIRONMENT !== 'production'),
		'charset'  => 'utf8',
		'DBCollat' => 'utf8_general_ci',
		'swapPre'  => '',
		'encrypt'  => false,
		'compress' => false,
		'strictOn' => false,
		'failover' => [],
		'port'     => 3306,
	];

	/**
	 * This database connection is used when
	 * running PHPUnit database tests.
	 *
	 * @var array
	 */
	public $tests = [
		'DSN'      => '',
		'hostname' => '127.0.0.1',
		'username' => '',
		'password' => '',
		'database' => ':memory:',
		'DBDriver' => 'SQLite3',
		'DBPrefix' => 'db_',  // Needed to ensure we're working correctly with prefixes live. DO NOT REMOVE FOR CI DEVS
		'pConnect' => false,
		'DBDebug'  => (ENVIRONMENT !== 'production'),
		'charset'  => 'utf8',
		'DBCollat' => 'utf8_general_ci',
		'swapPre'  => '',
		'encrypt'  => false,
		'compress' => false,
		'strictOn' => false,
		'failover' => [],
		'port'     => 3306,
	];

	//--------------------------------------------------------------------

	public function __construct()
	{
		parent::__construct();

		// Ensure that we always set the database group to 'tests' if
		// we are currently running an automated test suite, so that
		// we don't overwrite live data on accident.
		if (ENVIRONMENT === 'testing')
		{
			$this->defaultGroup = 'tests';
		}

		// Plain env vars (Render/cPanel friendly) override defaults.
		// Supports both DB_HOST style and CI4 database.default.* style.
		$get = function (string $plain, string $dotted, $fallback) {
			$v = getenv($plain);
			if ($v !== false && $v !== '') return $v;
			if (isset($_ENV[$plain]) && $_ENV[$plain] !== '') return $_ENV[$plain];
			$v = getenv($dotted);
			if ($v !== false && $v !== '') return $v;
			if (isset($_ENV[$dotted]) && $_ENV[$dotted] !== '') return $_ENV[$dotted];
			return $fallback;
		};
		$this->default['hostname'] = $get('DB_HOST', 'database.default.hostname', $this->default['hostname']);
		$this->default['username'] = $get('DB_USER', 'database.default.username', $this->default['username']);
		$this->default['password'] = $get('DB_PASS', 'database.default.password', $this->default['password']);
		$this->default['database'] = $get('DB_NAME', 'database.default.database', $this->default['database']);
		$this->default['port']     = (int)$get('DB_PORT', 'database.default.port', $this->default['port']);
	}

	//--------------------------------------------------------------------

}
