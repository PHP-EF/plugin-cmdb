<?php
// **
// USED TO DEFINE PLUGIN INFORMATION & CLASS
// **

// PLUGIN INFORMATION
$GLOBALS['plugins']['cmdb'] = [ // Plugin Name
	'name' => 'cmdb', // Plugin Name
	'author' => 'TehMuffinMoo', // Who wrote the plugin
	'category' => 'CMDB', // One to Two Word Description
	'link' => 'https://github.com/TehMuffinMoo/php-ef-plugin-cmdb', // Link to plugin info
	'version' => '1.0.0', // SemVer of plugin
	'image' => 'logo.png', // 1:1 non transparent image for plugin
	'settings' => true, // does plugin need a settings modal?
	'api' => '/api/plugin/cmdb/settings', // api route for settings page, or null if no settings page
];

class cmdbPlugin extends ib {
	private $sql;

	public function __construct() {
	  parent::__construct();
	  // Create or open the SQLite database
	  $dbFile = dirname(__DIR__,2). DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'cmdb.db';
	  $this->sql = new PDO("sqlite:$dbFile");
	  $this->sql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	  $this->createCMDBTable();
	}

	public function columnExists($columnName) {
		$result = $this->sql->query('PRAGMA table_info("cmdb")');
		$Columns = $result->fetchAll(PDO::FETCH_ASSOC);
		foreach ($Columns as $Column) {
			if ($Column['name'] == $columnName) {
				return true;
			}
		}
		return false;
	}

	public function addColumn($columnName,$dataType) {
		$this->sql->exec("ALTER TABLE cmdb ADD COLUMN $columnName $dataType");
	}

	public function getAllRecords() {
		$dbquery = $this->sql->prepare("SELECT * FROM cmdb");
        $dbquery->execute();
		$records = $dbquery->fetchAll(PDO::FETCH_ASSOC);
		return $records;
	}

	private function createCMDBTable() {
		// Create initial CMDB Table
		$this->sql->exec("CREATE TABLE IF NOT EXISTS cmdb (
		  id INTEGER PRIMARY KEY AUTOINCREMENT
		)");
	
		// Populate CMDB table with list of required columns (Name / Type)
		$BaseColumns = [
			['CPU','INTEGER'],
			['Memory','INTEGER'],
			['ServerName','TEXT'],
			['FQDN','TEXT'],
			['IP','TEXT'],
			['SubnetMask','TEXT'],
			['DNSServers','TEXT'],
			['DNSSuffix','TEXT'],
			['Gateway','TEXT'],
			['Description','TEXT'],
			['OperatingSystem','TEXT']
		];

		foreach ($BaseColumns as $BaseColumn) {
			if (!$this->columnExists($BaseColumn[0])) {
				$this->addColumn($BaseColumn[0],$BaseColumn[1]);
			}
		}
	}

	public function _pluginGetSettings() {
		$roles = $this->auth->getRBACRolesForMenu();
		return array(
			'Plugin Settings' => array(
				settingsOption('select', 'ACL-READ', ['label' => 'CMDB Read ACL', 'options' => $roles]),
				settingsOption('select', 'ACL-WRITE', ['label' => 'CMDB Write ACL', 'options' => $roles]),
			),
			'Ansible Settings' => array(
				settingsOption('url', 'URL', ['label' => 'Ansible AWX URL']),
				settingsOption('password-alt', 'Token', ['label' => 'Ansible AWX Token'])
			),
		);
	}
}