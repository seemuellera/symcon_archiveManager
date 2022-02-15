<?php

// Klassendefinition
class ArchiveManager extends IPSModule {
 
	// Der Konstruktor des Moduls
	// Überschreibt den Standard Kontruktor von IPS
	public function __construct($InstanceID) {
		// Diese Zeile nicht löschen
		parent::__construct($InstanceID);

		// Selbsterstellter Code
	}

	// Überschreibt die interne IPS_Create($id) Funktion
	public function Create() {
		
		// Diese Zeile nicht löschen.
		parent::Create();

		// Properties
		$this->RegisterPropertyString("Sender","ArchiveManager");
		$this->RegisterPropertyInteger("RefreshInterval",0);
		$this->RegisterPropertyBoolean("DebugOutput",false);
		$this->RegisterPropertyInteger("ArchiveId",0);
		$this->RegisterPropertyString("ModuleGUID","");
		$this->RegisterPropertyString("VariableList","");
		
		// Variables
		$this->RegisterVariableBoolean("Status","Status","~Switch");
		$this->RegisterVariableInteger("ManagedDeviceCount","Number of Managed Device Instances");
		$this->RegisterVariableInteger("ManagedVariableCount","Number of Managed Variables");
		$this->RegisterVariableInteger("CompliantVariableCount","Number of Compliant Variables");
		
		// Default Actions
		$this->EnableAction("Status");
		
		// Timer
		$this->RegisterTimer("RefreshInformation", 0 , 'ARCHIVEMGR_RefreshInformation($_IPS[\'TARGET\']);');

    }

	public function Destroy() {

		// Never delete this line
		parent::Destroy();
	}
 
	// Überschreibt die intere IPS_ApplyChanges($id) Funktion
	public function ApplyChanges() {

		$newInterval = $this->ReadPropertyInteger("RefreshInterval") * 1000;
		$this->SetTimerInterval("RefreshInformation", $newInterval);
		
		// Diese Zeile nicht löschen
		parent::ApplyChanges();
	}


	public function GetConfigurationForm() {
        	
		// Initialize the form
		$form = Array(
            		"elements" => Array(),
					"actions" => Array()
        		);

		// Add the Elements
		$form['elements'][] = Array(
								"type" => "ExpansionPanel", 
								"caption" => "General Settings",
								"expanded" => true,
								"items" => Array(
										Array("type" => "NumberSpinner", "name" => "RefreshInterval", "caption" => "Refresh Interval"),
										Array("type" => "CheckBox", "name" => "DebugOutput", "caption" => "Enable Debug Output")									
									)
								);
								
		$form['elements'][] = Array(
								"type" => "ExpansionPanel", 
								"caption" => "Global Settings",
								"expanded" => true,
								"items" => Array(
										Array("type" => "SelectModule", "name" => "ArchiveId", "caption" => "Select Archive instance", "moduleID" => "{43192F0B-135B-4CE7-A0A7-1475603F3060}"),
										Array(
											"type" => "Select", 
											"name" => "ModuleGUID", 
											"caption" => "Device Module",
											"options" => $this->getModuleList()
										)
									)
								);
								
		$variableListColumns = Array(
			Array(
				"caption" => "Variable Ident",
				"name" => "VariableIdent",
				"width" => "200px",
				"edit" => Array("type" => "ValidationTextBox"),
				"add" => "unnamed"
			),
			Array(
				"caption" => "Display in Webfront",
				"name" => "DisplayWF",
				"width" => "100px",
				"edit" => Array("type" => "CheckBox"),
				"add" => true
			),
			Array(
				"caption" => "Aggregation Type",
				"name" => "AggregationType",
				"width" => "150px",
				"edit" => Array(
					"type" => "Select",
					"options" => Array(
						Array('caption' => 'Standard', 'value' => 'Standard'),
						Array('caption' => 'Counter', 'value' => 'Counter'),
					)
				),
				"add" => "Standard"
			),
			Array(
				"caption" => "Ignore null and negative values",
				"name" => "IgnoreNull",
				"width" => "150px",
				"edit" => Array("type" => "CheckBox"),
				"add" => ""
			)
		);
		$form['elements'][] = Array(
			"type" => "List", 
			"columns" => $variableListColumns, 
			"name" => "VariableList", 
			"caption" => "Variables to be managed", 
			"add" => true, 
			"delete" => true,
			"rowCount" => 10
		);
		
		
		// Add the buttons for the test center
		$form['actions'][] = Array(	"type" => "Button", "label" => "Refresh", "onClick" => 'ARCHIVEMGR_RefreshInformation($id);');
		
		// Return the completed form
		return json_encode($form);

	}
	
	// Version 1.0
	protected function LogMessage($message, $severity = 'INFO') {
		
		$logMappings = Array();
		// $logMappings['DEBUG'] 	= 10206; Deactivated the normal debug, because it is not active
		$logMappings['DEBUG'] 	= 10201;
		$logMappings['INFO']	= 10201;
		$logMappings['NOTIFY']	= 10203;
		$logMappings['WARN'] 	= 10204;
		$logMappings['CRIT']	= 10205;
		
		if ( ($severity == 'DEBUG') && ($this->ReadPropertyBoolean('DebugOutput') == false )) {
			
			return;
		}
		
		$messageComplete = $severity . " - " . $message;
		parent::LogMessage($messageComplete, $logMappings[$severity]);
	}

	public function RefreshInformation() {

		// Do nothing if status is off
		if (! GetValue($this->GetIDForIdent("Status"))) {
			
			return;
		}
		
		$this->LogMessage("Refresh in Progress", "DEBUG");
		
		SetValue($this->GetIDForIdent("ManagedDeviceCount"), count($this->getDeviceInstances()));
		SetValue($this->GetIDForIdent("ManagedVariableCount"), $this->countManagedVariables());
	}

	public function RequestAction($Ident, $Value) {
	
	
		switch ($Ident) {
		
			case "Status":
				SetValue($this->GetIDForIdent($Ident), $Value);
				// Initialize an immediate refresh if turned on
				if ($Value) {
					
					$this->RefreshInformation();
				}
				break;
			default:
				throw new Exception("Invalid Ident");
		}
	}
	
	public function MessageSink($TimeStamp, $SenderId, $Message, $Data) {
	
		$this->LogMessage("$TimeStamp - $SenderId - $Message - " . implode(";",$Data), "DEBUG");
	}

	protected function getModuleList() {
		
		$allModuleGUIDs = IPS_GetModuleListByType(3);
		
		$allModules = Array();
		
		foreach ($allModuleGUIDs as $currentGUID) {
			
			$moduleDetails = IPS_GetModule($currentGUID);
			$allModules[$moduleDetails['ModuleName']] = $currentGUID;
		}
		
		ksort($allModules);
		
		$allModulesSorted = Array();
		
		foreach ($allModules as $moduleName => $moduleGUID) {
			
			$allModulesSorted[] = Array('caption' => $moduleName, 'value' => $moduleGUID);	
		}

		return $allModulesSorted;
	}
	
	protected function getDeviceInstances() {
		
		$allDeviceInstances = IPS_GetInstanceListByModuleID($this->ReadPropertyString("ModuleGUID"));
		
		return $allDeviceInstances;
	}
	
	protected function getManagedVariables($Ident) {
		
		$allDeviceInstances = $this->getDeviceInstances();
		$allManagedVariables = Array();
		
		foreach ($allDeviceInstances as $currentDevice) {
			
			$variableId = IPS_GetObjectIDByIdent($Ident, $currentDevice);
			
			if ($variableId) {
				
				$allManagedVariables[] = $variableId;
			}
		}
		
		return $allManagedVariables;
	}
	
	protected function getArchiveDefinition() {
		
		$configurationJson = $this->ReadPropertyString("VariableList");
		$configuration = json_decode($configurationJson, true);
		
		return $configuration;
	}
	
	protected function getArchiveDefinitionForIdent($Ident) {
		
		$variableDefinitions = $this->getArchiveDefinition();
		
		foreach ($variableDefinitions as $currentDefinition) {
			
			if ($currentDefinition['VariableIdent'] == $Ident) {
				
				return $currentDefinition;
			}
		}
		
		// No ident found, returning false
		return false;
	}
	
	protected function getArchiveDefinitionIdents() {
		
		$variableDefinitions = $this->getArchiveDefinition();
		$allIdents = Array();
		
		foreach ($variableDefinitions as $currentDefinition) {
			
			$allIdents[] = $currentDefinition['VariableIdent'];
		}
		
		return $allIdents;
	}
	
	protected function countManagedVariables() {
		
		$variableCount = 0;
		
		$allVariableIdents = $this->getArchiveDefinitionIdents();
		
		if (! $allVariableIdents) {
			
			return 0;
		}
		
		foreach ($allVariableIdents as $currentIdent) {
			
			$allVariablesForCurrentIdent = $this->getManagedVariables($currentIdent);
			$variableCount += count($allVariablesForCurrentIdent);
		}
		
		return $variableCount;
	}
	
	public function CheckCompliance() {
		
		$variableCount = 0;
		
		$allVariableIdents = $this->getArchiveDefinitionIdents();
		
		if (! $allVariableIdents) {
			
			return 0;
		}
		
		foreach ($allVariableIdents as $currentIdent) {
			
			$archiveDefinition = getArchiveDefinitionForIdent($currentIdent);
			$allVariablesForCurrentIdent = $this->getManagedVariables($currentIdent);
			
			foreach ($allVariablesForCurrentIdent as $currentVariable) {
				
				
			}
		}
	}
}
