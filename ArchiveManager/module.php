<?php

// Klassendefinition
class ArchiveManager extends IPSModule {
 
	// Der Konstruktor des Moduls
	// Überschreibt den Standard Kontruktor von IPS
	public function __construct($InstanceID) {
		// Diese Zeile nicht löschen
		parent::__construct($InstanceID);

		// Selbsterstellter Code
		$this->retentionPoliciesDirect = Array(
			Array('caption' => "none", 'value' => -1),
			Array('caption' => "one value per minute", 'value' => 0),
			Array('caption' => "one value per 5 minutes", 'value' => 1),
			Array('caption' => "one value per hour", 'value' => 2)
		);

		$this->retentionPoliciesHistorical = Array(
			Array('caption' => "none", 'value' => -1),
			Array('caption' => "one value per minute", 'value' => 0),
			Array('caption' => "one value per 5 minutes", 'value' => 1),
			Array('caption' => "one value per hour", 'value' => 2),
			Array('caption' => "one value per day", 'value' => 3),
			Array('caption' => "one value per week", 'value' => 4),
			Array('caption' => "one value per month", 'value' => 5),
			Array('caption' => "one value per year", 'value' => 6),
			Array('caption' => "delete values", 'value' => 7)
		);
	}

	// Überschreibt die interne IPS_Create($id) Funktion
	public function Create() {
		
		// Diese Zeile nicht löschen.
		parent::Create();

		// Properties
		$this->RegisterPropertyString("Sender","ArchiveManager");
		$this->RegisterPropertyInteger("RefreshInterval",0);
		$this->RegisterPropertyInteger("ArchiveId",0);
		$this->RegisterPropertyString("ModuleGUID","");
		$this->RegisterPropertyString("VariableList","");
		$this->RegisterPropertyInteger("RemediationInterval",0);
		
		// Variables
		$this->RegisterVariableBoolean("Status","Status","~Switch");
		$this->RegisterVariableInteger("ManagedDeviceCount","Number of Managed Device Instances");
		$this->RegisterVariableInteger("ManagedVariableCount","Number of Managed Variables");
		$this->RegisterVariableInteger("CompliantVariableCount","Number of Compliant Variables");
		$this->RegisterVariableInteger("RemediationVariableCount","Number of last remediations");
		
		// Default Actions
		$this->EnableAction("Status");
		
		// Timer
		$this->RegisterTimer("RefreshInformation", 0 , 'ARCHIVEMGR_RefreshInformation($_IPS[\'TARGET\']);');
		$this->RegisterTimer("Remediate", 0 , 'ARCHIVEMGR_Remediate($_IPS[\'TARGET\']);');

    }

	public function Destroy() {

		// Never delete this line
		parent::Destroy();
	}
 
	// Überschreibt die intere IPS_ApplyChanges($id) Funktion
	public function ApplyChanges() {

		$newInterval = $this->ReadPropertyInteger("RefreshInterval") * 1000;
		$this->SetTimerInterval("RefreshInformation", $newInterval);
		
		$newRemediationInterval = $this->ReadPropertyInteger("RemediationInterval") * 1000;
		$this->SetTimerInterval("Remediate", $newRemediationInterval);
		
		$this->RegisterReference($this->ReadPropertyInteger("ArchiveId"));
		
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
										Array("type" => "NumberSpinner", "name" => "RefreshInterval", "caption" => "Refresh Interval")
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
										),
										Array("type" => "NumberSpinner", "name" => "RemediationInterval", "caption" => "Remediation Interval")
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
				"add" => false
			),
			Array(
				"caption" => "Thinning Direct",
				"name" => "ThinningDirect",
				"width" => "150px",
				"edit" => Array(
					"type" => "Select",
					"options" => $this->retentionPoliciesDirect
				),
				"add" => -1
			),
			Array(
				"caption" => "Thinning after 1 month",
				"name" => "Thinning1Month",
				"width" => "150px",
				"edit" => Array(
					"type" => "Select",
					"options" => $this->retentionPoliciesHistorical
				),
				"add" => -1
			),	
			Array(
				"caption" => "Thinning after 6 months",
				"name" => "Thinning6Months",
				"width" => "150px",
				"edit" => Array(
					"type" => "Select",
					"options" => $this->retentionPoliciesHistorical
				),
				"add" => -1
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
		$form['actions'][] = Array(	"type" => "Button", "label" => "Remediate", "onClick" => 'ARCHIVEMGR_Remediate($id);');
		
		// Return the completed form
		return json_encode($form);

	}
	
	public function RefreshInformation() {

		// Do nothing if status is off
		if (! GetValue($this->GetIDForIdent("Status"))) {
			
			return;
		}
		
		$this->LogMessage("Refresh in Progress", KL_DEBUG);
		
		SetValue($this->GetIDForIdent("ManagedDeviceCount"), count($this->getDeviceInstances()));
		SetValue($this->GetIDForIdent("ManagedVariableCount"), $this->countManagedVariables());
		SetValue($this->GetIDForIdent("CompliantVariableCount"), $this->CheckCompliance());
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
	
		$this->LogMessage("$TimeStamp - $SenderId - $Message - " . implode(";",$Data), KL_DEBUG);
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
			
			$variableId = @IPS_GetObjectIDByIdent($Ident, $currentDevice);
			
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
				
				$archiveDefinition = Array();
				$archiveDefinition['status'] = true;
				$archiveDefinition['visibleWF'] = $currentDefinition['DisplayWF'];
				if ($currentDefinition['AggregationType'] == 'Standard') {
				
					$archiveDefinition['aggregationType'] = 0;	
				}
				if ($currentDefinition['AggregationType'] == 'Counter') {
				
					$archiveDefinition['aggregationType'] = 1;	
				}
				$archiveDefinition['ignoreNull'] = $currentDefinition['IgnoreNull'];
				
				return $archiveDefinition;
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
	
	protected function getVariableArchiveSettings($variableId) {
		
		$archiveSettings = Array();
		
		$loggingStatus = AC_GetLoggingStatus($this->ReadPropertyInteger("ArchiveId"), $variableId);
		
		if (! $loggingStatus) {
			
			$archiveSettings['status'] = false;
			$archiveSettings['visibleWF'] = false;
			$archiveSettings['aggregationType'] = false;
			$archiveSettings['ignoreNull'] = false;
			
			return $archiveSettings;
		}
		
		$archiveSettings['status'] = true;
		
		$visibleWF = AC_GetGraphStatus($this->ReadPropertyInteger("ArchiveId"), $variableId);
		$archiveSettings['visibleWF'] = $visibleWF;
		
		$aggregationType = AC_GetAggregationType($this->ReadPropertyInteger("ArchiveId"), $variableId);
		$archiveSettings['aggregationType'] = $aggregationType;
		
		if ($aggregationType == 0) {
			
			$archiveSettings['ignoreNull'] = false;
		}
		else {
			
			$ignoreNull = AC_GetCounterIgnoreZeros($this->ReadPropertyInteger("ArchiveId"), $variableId);
			$archiveSettings['ignoreNull'] = $ignoreNull;
		}
		
		return $archiveSettings;
	}
	
	public function CheckCompliance() {
		
		// Do nothing if status is off
		if (! GetValue($this->GetIDForIdent("Status"))) {
			
			return;
		}
		
		
		$variableCount = 0;
		
		$allVariableIdents = $this->getArchiveDefinitionIdents();
		
		if (! $allVariableIdents) {
			
			return 0;
		}
		
		foreach ($allVariableIdents as $currentIdent) {
			
			$archiveDefinition = $this->getArchiveDefinitionForIdent($currentIdent);
			$allVariablesForCurrentIdent = $this->getManagedVariables($currentIdent);
			
			foreach ($allVariablesForCurrentIdent as $currentVariable) {
				
				$archiveSettings = $this->getVariableArchiveSettings($currentVariable);
				if ($this->compareArchiveSettings($archiveDefinition, $archiveSettings) ) {
					
					$variableCount++;
				}
				else {
					
					$this->LogMessage("Variable $currentVariable is not compliant", KL_DEBUG);
				}
			}
		}
		
		return $variableCount;
	}
	
	protected function compareArchiveSettings($archiveDefinition, $archiveSettings) {
		
		if ($archiveDefinition['status'] != $archiveSettings['status']) {
			
			return false;
		}
		
		if ($archiveDefinition['visibleWF'] != $archiveSettings['visibleWF']) {
			
			return false;
		}
		
		if ($archiveDefinition['aggregationType'] != $archiveSettings['aggregationType']) {
			
			return false;
		}
		
		if ($archiveDefinition['ignoreNull'] != $archiveSettings['ignoreNull']) {
			
			return false;
		}
		
		return true;
	}
	
	public function Remediate() {
		
		$remediationCount = 0;
		
		$allVariableIdents = $this->getArchiveDefinitionIdents();
		
		if (! $allVariableIdents) {
			
			return false;
		}
		
		foreach ($allVariableIdents as $currentIdent) {
			
			$archiveDefinition = $this->getArchiveDefinitionForIdent($currentIdent);
			$allVariablesForCurrentIdent = $this->getManagedVariables($currentIdent);
			
			foreach ($allVariablesForCurrentIdent as $currentVariable) {
				
				$archiveSettings = $this->getVariableArchiveSettings($currentVariable);
				if (! $this->compareArchiveSettings($archiveDefinition, $archiveSettings) ) {
					
					$this->RemediateVariable($archiveDefinition, $archiveSettings, $currentVariable);
					$remediationCount++;
				}
				else {
					
					$this->LogMessage("Variable $currentVariable is compliant", KL_DEBUG);
				}
			}
		}
		
		SetValue($this->GetIDForIdent("RemediationVariableCount"), $remediationCount);
		return $remediationCount;
	}
	
	protected function RemediateVariable($archiveDefinition, $archiveSettings, $variableId) {
		
		if ($archiveDefinition['status'] != $archiveSettings['status']) {
			
			AC_SetLoggingStatus($this->ReadPropertyInteger("ArchiveId"), $variableId, $archiveDefinition['status']);
			
			if (! $archiveDefinition['status']) {
				
				// return immediately as archiving was turned off, no other settings make sense
				return;
			}
		}
		
		if ($archiveDefinition['visibleWF'] != $archiveSettings['visibleWF']) {
			
			AC_SetGraphStatus($this->ReadPropertyInteger("ArchiveId"), $variableId, $archiveDefinition['visibleWF']);
		}
		
		if ($archiveDefinition['aggregationType'] != $archiveSettings['aggregationType']) {
			
			AC_SetAggregationType($this->ReadPropertyInteger("ArchiveId"), $variableId, $archiveDefinition['aggregationType']);
		}
		
		// This settings only needs to be checked if the aggregation type is counter
		if ($archiveDefinition['aggregationType'] == 1) {
			
			if ($archiveDefinition['ignoreNull'] != $archiveSettings['ignoreNull']) {
			
				AC_SetCounterIgnoreZeros ($this->ReadPropertyInteger("ArchiveId"), $variableId, $archiveDefinition['ignoreNull']);
			}
		}
		
		return;
	}
}
