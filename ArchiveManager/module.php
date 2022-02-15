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
		
		// Variables
		$this->RegisterVariableBoolean("Status","Status","~Switch");
		
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

		$this->LogMessage("Refresh in Progress", "DEBUG");
	}

	public function RequestAction($Ident, $Value) {
	
	
		switch ($Ident) {
		
			case "Status":
				SetValue($Ident, $Value);
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
			$allModules[]['caption'] = $moduleDetails['ModuleName'];
			$allModules[]['value'] = $currentGUID;
		}
		
		$moduleNames = array_column($allModules, 'caption');
		array_multisort($moduleNames, SORT_ASC, $allModules);

		return $allModules;
	}
}
