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

	public function getAllColumns() {
		$result = $this->sql->query('PRAGMA table_info("cmdb")');
		$Columns = $result->fetchAll(PDO::FETCH_ASSOC);
		return $Columns;
	}

	public function columnExists($columnName) {
		$Columns = $this->getAllColumns();
		foreach ($Columns as $Column) {
			if ($Column['name'] == $columnName) {
				return true;
			}
		}
		return false;
	}

	public function allColumnsExist($data) {
		$columns = $this->getAllColumns();
		foreach ($data as $key => $value) {
		  if (!in_array($key,array_column($columns,"name"))) {
			$this->api->setAPIResponse('Error',$key." does not exist in the database");
			return false;
		  }
		}
		return true;
	}

	public function addColumn($columnName,$dataType) {
		$this->sql->exec("ALTER TABLE cmdb ADD COLUMN $columnName $dataType");
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

	public function getAllRecords() {
		$dbquery = $this->sql->prepare("SELECT * FROM cmdb");
        $dbquery->execute();
		$records = $dbquery->fetchAll(PDO::FETCH_ASSOC);
		return $records;
	}

	public function getRecordById($id) {
		$dbquery = $this->sql->prepare("SELECT * FROM cmdb WHERE id = :id");
        $dbquery->execute([':id' => $id]);
		$records = $dbquery->fetchAll(PDO::FETCH_ASSOC);
		return $records;
	}

	public function updateRecord($id,$data) {
      // Check if all record exists
	  if ($this->getRecordById($id)) {
		// Check if all fields exist
		if ($this->allColumnsExist($data)) {
		  // Update fields if all exist
		  $updateFields = [];
		  foreach ($data as $key => $value) {
			$updateFields[] = "$key = '$value'";
		  }
		  $prepare = "UPDATE cmdb SET " . implode(", ", $updateFields) . " WHERE id = :id";
		  $dbquery = $this->sql->prepare($prepare);
		  if ($dbquery->execute(['id' => $id])) {
			$this->api->setAPIResponseMessage("CMDB record updated successfully");
		  } else {
		    $this->api->setAPIResponse('Error',$conn->lastErrorMsg());
		  }
		}
	  }
	}

	public function newRecord($data) {
	  // Check if all fields exist
	  if ($this->allColumnsExist($data)) {
		// Prepare fields and values for insertion
		$columns = implode(", ", array_keys($data));
		$values = implode(", ", array_map(function($value) {
			return "'$value'";
		}, array_values($data)));
		
		$prepare = "INSERT INTO cmdb ($columns) VALUES ($values)";
		$dbquery = $this->sql->prepare($prepare);
		
		if ($dbquery->execute()) {
			$this->api->setAPIResponseMessage("CMDB record created successfully");
		} else {
			$this->api->setAPIResponse('Error', $this->sql->lastErrorMsg());
		}
	  }
	}

	public function deleteRecord($id) {
		// Check if all record exists
		if ($this->getRecordById($id)) {
		  $dbquery = $this->sql->prepare("DELETE FROM cmdb WHERE id = :id");
		  if ($dbquery->execute([':id' => $id])) {
			  $this->api->setAPIResponseMessage("CMDB record deleted successfully");
		  } else {
			  $this->api->setAPIResponse('Error', $this->sql->lastErrorMsg());
		  }
		} else {
			$this->api->setAPIResponse("Error","CMDB record does not exist");
		}
	}

	public function _pluginGetSettings() {
		return array(
			'Plugin Settings' => array(
				$this->settingsOption('auth', 'ACL-READ', ['label' => 'CMDB Read ACL']),
				$this->settingsOption('auth', 'ACL-WRITE', ['label' => 'CMDB Write ACL']),
			),
			'Ansible Settings' => array(
				$this->settingsOption('url', 'URL', ['label' => 'Ansible AWX URL']),
				$this->settingsOption('password-alt', 'Token', ['label' => 'Ansible AWX Token'])
			),
		);
	}
}