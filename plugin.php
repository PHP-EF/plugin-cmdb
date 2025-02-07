<?php
// **
// USED TO DEFINE PLUGIN INFORMATION & CLASS
// **

// PLUGIN INFORMATION - This should match what is in plugin.json
$GLOBALS['plugins']['CMDB'] = [ // Plugin Name
	'name' => 'CMDB', // Plugin Name
	'author' => 'TehMuffinMoo', // Who wrote the plugin
	'category' => 'Asset Database', // One to Two Word Description
	'link' => 'https://github.com/php-ef/plugin-cmdb', // Link to plugin info
	'version' => '1.0.6', // SemVer of plugin
	'image' => 'logo.png', // 1:1 non transparent image for plugin
	'settings' => true, // does plugin need a settings modal?
	'api' => '/api/plugin/cmdb/settings', // api route for settings page, or null if no settings page
];

class cmdbPlugin extends phpef {
	private $sql;

	public function __construct() {
	  parent::__construct();
	  // Create or open the SQLite database
	  $dbFile = dirname(__DIR__,2). DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'cmdb.db';
	  $this->sql = new PDO("sqlite:$dbFile");
	  $this->sql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	  $this->hasDB();
	}

	// Check if Database & Tables Exist
	private function hasDB() {
		if ($this->sql) {
			try {
				// Query to check if both tables exist
				$result = $this->sql->query("SELECT name FROM sqlite_master WHERE type='table' AND name IN ('cmdb', 'cmdb_columns', 'cmdb_sections', 'misc')");
				$tables = $result->fetchAll(PDO::FETCH_COLUMN);
			
				if (in_array('cmdb', $tables) && in_array('cmdb_columns', $tables) && in_array('cmdb_sections', $tables) && in_array('misc', $tables)) {
					return true;
				} else {
					$this->createCMDBTables();
				}
			} catch (PDOException $e) {
				$this->api->setAPIResponse("Error",$e->getMessage());
				return false;
			}
		} else {
			$this->api->setAPIResponse("Error","Database Not Initialized");
			return false;
		}
	}

	// Create CMDB Tables
	private function createCMDBTables() {
		// Create CMDB Table
		$this->sql->exec("CREATE TABLE IF NOT EXISTS cmdb (
		  id INTEGER PRIMARY KEY AUTOINCREMENT
		)");

		// Create CMDB Columns Table
		$this->sql->exec("CREATE TABLE IF NOT EXISTS cmdb_sections (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			name TEXT,
			weight INTEGER
		)");

		// Create CMDB Columns Table
		$this->sql->exec("CREATE TABLE IF NOT EXISTS cmdb_columns (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			name TEXT UNIQUE,
			description TEXT,
			dataType TEXT,
			fieldType TEXT,
			options TEXT,
			section INTEGER,
			visible BOOLEAN,
			weight INTEGER,
			columnName TEXT UNIQUE,
			FOREIGN KEY (section) REFERENCES cmdb_sections(id)
		)");

		$this->sql->exec("CREATE TABLE IF NOT EXISTS misc (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			key TEXT,
			value TEXT
		)");

		$this->sql->exec('INSERT INTO misc (key,value) VALUES ("rebuildRequired","false");');
	
		// Populate CMDB Sections table with list of base sections (Name / Weight)
		$BaseSections = [
			['General',1],
			['Network',2],
			['Hardware',3],
		];
		foreach ($BaseSections as $BaseSection) {
			$Sections = $this->getSections();
			if (!in_array($BaseSection[0],array_column($Sections,"name"))) {
				$this->addSection($BaseSection[0],$BaseSection[1]);
			}
		}

		// Populate CMDB Columns table with list of base columns (Column Name / Name / Description / Data Type / Field Type / Section ID / Visible / Weight)
		$BaseColumns = [
			['ServerName','Server Name','The name of the server','TEXT','INPUT',1,TRUE,1],
			['Description','Description','The description of the server','TEXT','INPUT',1,TRUE,2],
			['OperatingSystem','Operating System','The Operating System of the server','TEXT','INPUT',1,TRUE,3],
			['FQDN','FQDN','The FQDN of the server','TEXT','INPUT',2,TRUE,1],
			['IP','IP Address','The IP Address of the server','TEXT','INPUT',2,TRUE,2],
			['SubnetMask','Subnet Mask','The Subnet Mask of the server','TEXT','INPUT',2,TRUE,3],
			['DNSServers','DNS Servers','The DNS Servers of the server','TEXT','INPUT',2,TRUE,4],
			['DNSSuffix','DNS Suffix','The DNS Suffix of the server','TEXT','INPUT',2,TRUE,5],
			['Gateway','Gateway','The Gateway IP of the server','TEXT','INPUT',2,TRUE,6],
			['CPU','CPUs','The number of CPUs the server has','INTEGER','INPUT',3,TRUE,1],
			['Memory','Memory (GB)','The allocated memory of the server','INTEGER','INPUT',3,TRUE,2]
		];

		$ColumnDefinitions = $this->getColumnDefinitions();
		foreach ($BaseColumns as $BaseColumn) {
			if (!in_array($BaseColumn[0],array_column($ColumnDefinitions,"name"))) {
				$Data = [
					'columnName' => $BaseColumn[0],
					'name' => $BaseColumn[1],
					'description' => $BaseColumn[2],
					'dataType' => $BaseColumn[3],
					'fieldType' => $BaseColumn[4],
					'section' => $BaseColumn[5],
					'visible' => $BaseColumn[6]
				];
				$this->addColumnDefinition($Data,$BaseColumn[7],true);
			}
		}
		$Columns = $this->getDefinedColumns();
		foreach ($BaseColumns as $BaseColumn) {
			if (!in_array($BaseColumn[0],$Columns)) {
				$this->addColumn($BaseColumn[0],$BaseColumn[2]);
			}
		}
	}

	// Rebuild Required
	public function rebuildRequired($val = null) {
		if ($val !== null) {
			if ($val === true) {
				$value = "true";
			} elseif ($val == false) {
				$value = "false";
			}
			$this->sql->exec('UPDATE misc SET value = "'.$value.'" WHERE key = "rebuildRequired"');
		} else {
			$stmt = $this->sql->prepare('SELECT * FROM misc WHERE key = :key');
			$stmt->execute([':key' => 'rebuildRequired']);
			$results = $stmt->fetch();
			if ($results['value'] == "true") {
				return true;
			} elseif ($results['value'] == "false") {
				return false;
			}
		}
	}

	// Update CMDB Columns
	public function updateCMDBColumns($rebuild = false) {
		// Decode JSON data
		$columns = $this->getColumnDefinitions();
		$definedColumns = $this->getDefinedColumns();

		// Get current columns in the cmdb table
		$current_columns = [];

		foreach ($definedColumns as $currentColumn) {
			$current_columns[$currentColumn['name']] = $currentColumn['type'];
		}
		
		// Create a set of columns from the cmdb_columns table
		$latest_columns = [];
		foreach ($columns as $column) {
			if (!empty($column['columnName'])) { // Check for non-empty column names
				$latest_columns[$column['columnName']] = $column['dataType'];
			}
		}

		// Add new columns
		foreach ($latest_columns as $column => $data_type) {
			if (!array_key_exists($column, $current_columns)) {
				// Construct the SQL query to add the new column
				$query = "ALTER TABLE cmdb ADD COLUMN $column $data_type";
				
				// Execute the query
				if (!$this->sql->query($query)) {
					$this->api->setAPIResponse('Error', 'Failed to add new column: ' . $column);
					return false;
				}
			}
		}

		if ($rebuild) {
			// Collect columns to keep
			$columns_to_keep = array_intersect_key($current_columns, $latest_columns);
			
			// Ensure the 'id' column is kept and set as the first column
			$columns_to_keep = ['id' => $current_columns['id']] + $columns_to_keep;
		
			// Check if there are columns to remove
			if (count($columns_to_keep) < count($current_columns)) {
				// Create a new table with the desired columns
				$create_table_sql = "CREATE TABLE cmdb_new (id INTEGER PRIMARY KEY AUTOINCREMENT, " . implode(", ", array_map(function($col, $type) {
					return "$col $type";
				}, array_keys(array_slice($columns_to_keep, 1, null, true)), array_slice($columns_to_keep, 1, null, true))) . ")";
				
				if ($this->sql->query($create_table_sql)) {
					// Copy data from the old table to the new table
					$columns_list = implode(", ", array_keys($columns_to_keep));
					if ($this->sql->query("INSERT INTO cmdb_new ($columns_list) SELECT $columns_list FROM cmdb")) {
						// Replace the old table with the new table
						if ($this->sql->query("DROP TABLE cmdb")) {
							if ($this->sql->query("ALTER TABLE cmdb_new RENAME TO cmdb")) {
								$this->rebuildRequired(false);
								$this->api->setAPIResponseMessage('Successfully rebuilt database');
							} else {
								$this->api->setAPIResponse('Error','Failed to rename database');
							}
						} else {
							$this->api->setAPIResponse('Error','Failed to drop old database');
						}
					} else {
						$this->api->setAPIResponse('Error','Failed to clone current database');
					}
				} else {
					$this->api->setAPIResponse('Error','Failed to create temporary database');
				}
			} else {
				$this->api->setAPIResponseMessage('Rebuild is not required');
				$this->rebuildRequired(false);
			}
		}
	}

	// Get a list of all columns in the CMDB Table
	public function getDefinedColumns() {
		$result = $this->sql->query('PRAGMA table_info("cmdb")');
		$Columns = $result->fetchAll(PDO::FETCH_ASSOC);
		return $Columns;
	}
	
	// Check if a single column exists within the CMDB Table
	public function columnExists($columnName) {
		$Columns = $this->getDefinedColumns();
		foreach ($Columns as $Column) {
			if ($Column['name'] == $columnName) {
				return true;
			}
		}
		return false;
	}

	// Check if all specified columns exist within the CMDB Table
	public function allColumnsExist($data) {
		$columns = $this->getDefinedColumns();
		foreach ($data as $key => $value) {
		  if (!in_array($key,array_column($columns,"name"))) {
			$this->api->setAPIResponse('Error',"The column: <b>".$key."</b> does not exist in the database. You must rebuild the database before using this field.");
			return false;
		  }
		}
		return true;
	}

	// Add new column to CMDB Table
	public function addColumn($columnName,$dataType) {
		$this->sql->exec("ALTER TABLE cmdb ADD COLUMN $columnName $dataType");
	}

	// ** Column Definitions ** //

	// Get a list of Column Definitions
	public function getColumnDefinitions() {
		$result = $this->sql->query('SELECT * FROM cmdb_columns');
		$Columns = $result->fetchAll(PDO::FETCH_ASSOC);
		return $Columns;
	}

	// Get a Column Definition by Id
	public function getColumnDefinitionById($ID) {
		$dbquery = $this->sql->prepare('SELECT * FROM cmdb_columns WHERE id = :id');
		$dbquery->execute(['id' => $ID]);
		$Column = $dbquery->fetch(PDO::FETCH_ASSOC);
		return $Column;
	}

	// Get a list of Column Definition by Section Id
	public function getColumnDefinitionBySectionId($SectionID) {
		$dbquery = $this->sql->prepare('SELECT * FROM cmdb_columns WHERE section = :section ORDER BY weight');
		$dbquery->execute(['section' => $SectionID]);
		$Columns = $dbquery->fetchAll(PDO::FETCH_ASSOC);
		return $Columns;
	}

	// Add new column definition to CMDB Columns Table
	public function addColumnDefinition($data,$Weight = null,$SkipChecks = false) {
		$dbquery = $this->sql->prepare('SELECT EXISTS (SELECT 1 FROM cmdb_columns WHERE columnName = :columnName OR name = :name COLLATE NOCASE);');
		$dbquery->execute([":columnName" => $data['columnName'],":name" => $data['name']]);
		$results = $dbquery->fetchColumn() > 0;

		if (!$SkipChecks) {
			// Check if 'fqdn' exists as a value in 'columnName' within cmdb_columns
			$dbquery = $this->sql->prepare('SELECT EXISTS (SELECT 1 FROM cmdb_columns WHERE columnName = :columnName COLLATE NOCASE)');
			$dbquery->execute([":columnName" => $data['columnName']]);
			$existsInColumns = $dbquery->fetchColumn();

			// Check if 'fqdn' is an actual column in the 'cmdb' table
			$dbquery = $this->sql->prepare("SELECT EXISTS (SELECT 1 FROM pragma_table_info('cmdb') WHERE name = :name COLLATE NOCASE)");
			$dbquery->execute([":name" => $data['columnName']]);
			$existsAsColumn = $dbquery->fetchColumn();
		} else {
			$existsInColumns = false;
			$existsAsColumn = false;
		}

		if ($existsInColumns || $existsAsColumn) {
			if ($this->rebuildRequired()) {
				$this->api->setAPIResponse('Error','Column already exists. DB Rebuild is required');
				return false;
			}
			$this->api->setAPIResponse('Error','Column already exists.');
			return false;
		} else {
			$Options = $data['options'] ?? '';
			if ($Weight) {
				$dbquery = $this->sql->prepare("INSERT INTO cmdb_columns (columnName, name, description, dataType, fieldType, section, visible, options, weight) VALUES (:columnName, :name, :description, :dataType, :fieldType, :section, :visible, :options, :weight);");
				$execute = [":columnName" => $data['columnName'],":name" => $data['name'],":description" => $data['description'], ":dataType" => $data['dataType'], ":fieldType" => $data['fieldType'], ":section" => $data['section'], ":visible" => $data['visible'], ":options" => $data['options'], ":weight" => $Weight];
			} else {
				$dbquery = $this->sql->prepare("INSERT INTO cmdb_columns (columnName, name, description, dataType, fieldType, section, visible, options, weight) VALUES (:columnName, :name, :description, :dataType, :fieldType, :section, :visible, :options, (SELECT IFNULL(MAX(weight), 0) + 1 FROM cmdb_columns WHERE section = :section));");
				$execute = [":columnName" => $data['columnName'],":name" => $data['name'],":description" => $data['description'], ":dataType" => $data['dataType'], ":fieldType" => $data['fieldType'], ":section" => $data['section'], ":visible" => $data['visible'], ":options" => $data['options']];
			}
			if ($dbquery->execute($execute)) {
				$this->api->setAPIResponseMessage('Successfully added column');
				$this->updateCMDBColumns();
				return true;
			}
		}
		$this->api->setAPIResponse('Error','Failed to add column');
		return false;
	}

	// Updates a column definition within the CMDB cmdb_columns Table
	public function updateColumnDefinition($id,$data) {
		if ($this->getColumnDefinitionById($id)) {
			$updateFields = [];
			foreach ($data as $key => $value) {
			  $updateFields[] = "$key = '$value'";
			}
			if (!empty($updateFields)) {
				$prepare = "UPDATE cmdb_columns SET " . implode(", ", $updateFields) . " WHERE id = :id";
				$dbquery = $this->sql->prepare($prepare);
				if ($dbquery->execute([':id' => $id])) {
					$this->api->setAPIResponseMessage('Successfully updated column');
					return true;
				}
				$this->api->setAPIResponse('Error','Failed to update column');
				return false;
			} else {
				$this->api->setAPIResponseMessage('Nothing to update');
			}
		} else {
			$this->api->setAPIResponse('Error','Column does not exist');
			return false;
		}
	}

	// Remove column from the CMDB Columns Table
	public function removeColumnDefinition($id) {
		if ($this->getColumnDefinitionById($id)) {
			$dbquery = $this->sql->prepare("DELETE FROM cmdb_columns WHERE id = :id;");
			if ($dbquery->execute([':id' => $id])) {
				$this->api->setAPIResponseMessage('Successfully removed column');
				$this->rebuildRequired(true);
				return true;
			}
			$this->api->setAPIResponse('Error','Failed to remove column');
			return false;
		} else {
			$this->api->setAPIResponse('Error','Column does not exist');
			return false;
		}
	}

	// Function to manage updating weights of Column Definitions
	public function updateColumnWeight($id,$weight) {
		$Column = $this->getColumnDefinitionById($id);
		if ($Column) {
			// Update the weight of the specific row
			$updateRow = $this->sql->prepare("UPDATE cmdb_columns SET weight = :weight WHERE id = :id AND section = :section;");
			$execute = [":id" => $id, ":weight" => $weight, ":section" => $Column['section']];
			if ($updateRow->execute($execute)) {
				// Shift the weights of other rows
				$updateOtherRows = $this->sql->prepare("UPDATE cmdb_columns SET weight = weight + 1 WHERE id != :id AND section = :section AND weight >= :weight;");
				if ($updateOtherRows->execute($execute)) {
					// Enforce strict weight assignment
					$enforceConsecutiveWeights = $this->sql->prepare('
					WITH NumberedRows AS (
						SELECT 
							id, 
							ROW_NUMBER() OVER (ORDER BY weight) AS row_number
						FROM 
							cmdb_columns
						WHERE 
							section = :section
					)
					UPDATE cmdb_columns
					SET weight = (SELECT row_number FROM NumberedRows WHERE cmdb_columns.id = NumberedRows.id)
					WHERE section = :section;');
					if ($enforceConsecutiveWeights->execute([":section" => $Column['section']])) {
						return true;
					}
				}
			}
		} else {
			$this->api->setAPIResponse('Error','Column not found');
			return false;
		}
	}

	// ** Sections ** //

	// Get a list of CMDB Sections
	public function getSections() {
		$result = $this->sql->query('SELECT * FROM cmdb_sections ORDER BY weight');
		$Sections = $result->fetchAll(PDO::FETCH_ASSOC);
		return $Sections;
	}

	// Get a CMDB Section by ID
	public function getSectionById($ID) {
		$dbquery = $this->sql->prepare('SELECT * FROM cmdb_sections WHERE id = :id');
		$dbquery->execute(["id" => $ID]);
		$Section = $dbquery->fetch(PDO::FETCH_ASSOC);
		return $Section;
	}

	// Get a joined list of columns and sections
	public function getColumnsAndSections() {
		$dbquery = $this->sql->prepare('SELECT 
			cmdb_columns.id,
			cmdb_columns.name,
			cmdb_columns.description,
			cmdb_columns.dataType,
			cmdb_columns.fieldType,
			cmdb_columns.visible,
			cmdb_columns.columnName,
			cmdb_sections.id AS section_id,
			cmdb_sections.name AS section_name,
			cmdb_sections.weight AS section_weight,
			cmdb_columns.weight AS column_weight
		FROM 
			cmdb_sections
		JOIN 
			cmdb_columns
		ON 
			cmdb_sections.id = cmdb_columns.section;
		');
		$dbquery->execute();
		$Columns = $dbquery->fetchAll(PDO::FETCH_ASSOC);
		return $Columns;
	}

	// Add new section to CMDB Sections Table
	public function addSection($name,$weight = null) {
		if ($weight) {
			$dbquery = $this->sql->prepare("INSERT INTO cmdb_sections (name, weight) VALUES (:name, :weight);");
			$execute = [":name" => $name, ":weight" => $weight];
		} else {
			$dbquery = $this->sql->prepare("INSERT INTO cmdb_sections (name, weight) VALUES (:name, (SELECT IFNULL(MAX(weight), 0) + 1 FROM cmdb_sections));");
			$execute = [":name" => $name];
		}
		if ($dbquery->execute($execute)) {
			$this->api->setAPIResponseMessage('Successfully added section');
			return true;
		}
		$this->api->setAPIResponse('Error','Failed to add section');
		return false;
	}

	// Updates a section within the CMDB Sections Table
	public function updateSection($id,$name) {
		if ($this->getSectionById($id)) {
			$dbquery = $this->sql->prepare("UPDATE cmdb_sections SET name = :name WHERE id = :id;");
			if ($dbquery->execute([':id' => $id,':name' => $name])) {
				$this->api->setAPIResponseMessage('Successfully updated section');
				return true;
			}
			$this->api->setAPIResponse('Error','Failed to update section');
			return false;
		} else {
			$this->api->setAPIResponse('Error','Section does not exist');
			return false;
		}
	}

	// Remove section from the CMDB Sections Table
	public function removeSection($id) {
		if ($this->getSectionById($id)) {
			$dbquery = $this->sql->prepare("DELETE FROM cmdb_sections WHERE id = :id;");
			if ($dbquery->execute([':id' => $id])) {
				$this->api->setAPIResponseMessage('Successfully removed section');
				return true;
			}
			$this->api->setAPIResponse('Error','Failed to remove section');
			return false;
		} else {
			$this->api->setAPIResponse('Error','Section does not exist');
			return false;
		}
	}

	// Function to manage updating weights of Column Definitions
	public function updateSectionWeight($id,$weight) {
		$Section = $this->getSectionById($id);
		if ($Section) {
			// Update the weight of the specific row
			$updateRow = $this->sql->prepare("UPDATE cmdb_sections SET weight = :weight WHERE id = :id;");
			$execute = [":id" => $id, ":weight" => $weight];
			if ($updateRow->execute($execute)) {
				// Shift the weights of other rows
				$updateOtherRows = $this->sql->prepare("UPDATE cmdb_sections SET weight = weight + 1 WHERE id != :id AND weight >= :weight;");
				if ($updateOtherRows->execute($execute)) {
					// Enforce strict weight assignment
					$enforceConsecutiveWeights = $this->sql->prepare('
					WITH NumberedRows AS (
						SELECT 
							id, 
							ROW_NUMBER() OVER (ORDER BY weight) AS row_number
						FROM 
							cmdb_sections
					)
					UPDATE cmdb_sections
					SET weight = (SELECT row_number FROM NumberedRows WHERE cmdb_sections.id = NumberedRows.id);');
					if ($enforceConsecutiveWeights->execute()) {
						return true;
					}
				}
			}
		} else {
			$this->api->setAPIResponse('Error','Section not found');
			return false;
		}
	}

	// ** RECORDS ** //

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
		if (!empty($data)) {
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
		} else {
			$this->api->setAPIResponseMessage("Nothing to update");
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

	public function buildCMDBForm() {
		$Sections = $this->getSections();
		$Columns = $this->getColumnDefinitions();
	
		// Sort sections by weight
		usort($Sections, function($a, $b) {
			return $a['weight'] <=> $b['weight'];
		});
	
		// Sort columns by weight within each section
		usort($Columns, function($a, $b) {
			return $a['weight'] <=> $b['weight'];
		});
	
		$settings = array();
	
		foreach ($Sections as $section) {
			$sectionName = $section['name'];
			$settings[$sectionName] = array();
	
			foreach ($Columns as $column) {
				if ($column['section'] == $section['id']) {
					$fieldType = strtolower($column['fieldType']);
					$options = [
						'label' => $column['name'],
						'description' => $column['description']
					];
	
					if ($fieldType == "select") {
						$optionsArray = explode(',', $column['options']);
						$options['options'] = array_map(function($option) {
							return ['name' => trim($option), 'value' => trim($option)];
						}, $optionsArray);
					}
	
					$settings[$sectionName][] = $this->settingsOption($fieldType, $column['columnName'], $options);
				}
			}
		}
		return $settings;
	}

	public function _pluginGetSettings() {
		$Ansible = new awxPluginAnsible();
		$AnsibleLabels = $Ansible->GetAnsibleLabels() ?? [];
		$AnsibleLabelsKeyValuePairs = [];
		$AnsibleLabelsKeyValuePairs[] = [
			"name" => "None",
			"value" => ""
		];
		if ($AnsibleLabels) {
			$AnsibleLabelsKeyValuePairs = array_merge($AnsibleLabelsKeyValuePairs,array_map(function($item) {
				return [
					"name" => $item['name'],
					"value" => $item['name']
				];
			}, $AnsibleLabels));
		}
		return array(
			'Plugin Settings' => array(
				$this->settingsOption('auth', 'ACL-READ', ['label' => 'CMDB Read ACL']),
				$this->settingsOption('auth', 'ACL-WRITE', ['label' => 'CMDB Write ACL']),
				$this->settingsOption('auth', 'ACL-ADMIN', ['label' => 'CMDB Admin ACL']),
				$this->settingsOption('auth', 'ACL-JOB', ['label' => 'Grants access to use Ansible Integration'])
			),
			'Ansible Settings' => array(
				$this->settingsOption('url', 'Ansible-URL', ['label' => 'Ansible AWX URL']),
				$this->settingsOption('token', 'Ansible-Token', ['label' => 'Ansible AWX Token']),
				$this->settingsOption('select-multiple', 'Ansible-Tag', ['label' => 'The tag to use when filtering available jobs', 'options' => $AnsibleLabelsKeyValuePairs]),
				$this->settingsOption('blank'),
				$this->settingsOption('checkbox','Ansible-JobByLabel', ['label' => 'Organise Jobs by Label'])
			),
		);
	}
}