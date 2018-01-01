<?php
class BotvacRobot extends IPSModule {

  public function Create() {
    parent::Create();
    $this->RegisterPropertyString('Serial', '');
    $this->RegisterPropertyString('Secret', '');
    $this->RegisterPropertyInteger('UpdateInterval', 120);

    $this->RegisterTimer('Update', 0, 'BR_Update($_IPS[\'TARGET\'], 0);');
  }

  public function ApplyChanges() {
    parent::ApplyChanges();
    $this->SetTimerInterval('Update', $this->ReadPropertyInteger('UpdateInterval') * 1000);

    if(!IPS_VariableProfileExists('Botvac.State')) {
      IPS_CreateVariableProfile('Botvac.State', 1);
      IPS_SetVariableProfileAssociation('Botvac.State', 0, 'Ungültig', 'Warning', -1);
      IPS_SetVariableProfileAssociation('Botvac.State', 1, 'Inaktiv', 'Sleep', -1);
      IPS_SetVariableProfileAssociation('Botvac.State', 2, 'Aktiv', 'Motion', -1);
      IPS_SetVariableProfileAssociation('Botvac.State', 3, 'Pausiert', 'Hourglass', -1);
      IPS_SetVariableProfileAssociation('Botvac.State', 4, 'Fehler', 'Warning', -1);
    }

    if(!IPS_VariableProfileExists('Botvac.YesNo')) {
      IPS_CreateVariableProfile('Botvac.YesNo', 0);
      IPS_SetVariableProfileAssociation('Botvac.YesNo', false, 'Nein', '', -1);
      IPS_SetVariableProfileAssociation('Botvac.YesNo', true, 'Ja', '', -1);
    }

    $this->UpdateCommandProfile();

    if(!IPS_VariableProfileExists('Botvac.Action')) {
      IPS_CreateVariableProfile('Botvac.Action', 1);
      IPS_SetVariableProfileAssociation('Botvac.Action', 0, 'Inaktiv', 'Sleep', -1);
      IPS_SetVariableProfileAssociation('Botvac.Action', 1, 'Reinigung: Haus', 'Motion', -1);
      IPS_SetVariableProfileAssociation('Botvac.Action', 2, 'Reinigung: Spot', 'Motion', -1);
      IPS_SetVariableProfileAssociation('Botvac.Action', 3, 'Reinigung: Manuell', 'Motion', -1);
      IPS_SetVariableProfileAssociation('Botvac.Action', 4, 'Docking', 'Plug', -1);
      IPS_SetVariableProfileAssociation('Botvac.Action', 5, 'Menü in Verwendung', 'Information', -1);
      IPS_SetVariableProfileAssociation('Botvac.Action', 6, 'Reinigung: Pausiert', 'Hourglass', -1);
      IPS_SetVariableProfileAssociation('Botvac.Action', 7, 'Updating', 'Information', -1);
      IPS_SetVariableProfileAssociation('Botvac.Action', 8, 'Kopiere Logs', 'Information', -1);
      IPS_SetVariableProfileAssociation('Botvac.Action', 9, 'Positionserkennung', 'Information', -1);
      IPS_SetVariableProfileAssociation('Botvac.Action', 10, 'IEC Test', 'Information', -1);
    }

    $this->RegisterVariableInteger('STATE', 'Zustand', 'Botvac.State', 1);
    $this->RegisterVariableInteger('ACTION', 'Aktion', 'Botvac.Action', 2);
    $this->RegisterVariableString('ERROR', 'Fehler', '', 3);

    if(!@$this->GetIDForIdent('BATTERY')) $this->RegisterVariableInteger('BATTERY', 'Batterie', '~Battery.100', 80);

    if(!@$this->GetIDForIdent('ECO')) {
      $EcoID = $this->RegisterVariableBoolean('ECO', 'Eco Modus', '~Switch', 41);
      IPS_SetIcon($EcoID, 'Flower');
    }
    if(!@$this->GetIDForIdent('MODEL')) {
      $ModelID = $this->RegisterVariableString('MODEL', 'Model', '', 91);
      IPS_SetIcon($ModelID, 'Robot');
    }
    if(!@$this->GetIDForIdent('SCHEDULE')) {
      $ScheduleID = $this->RegisterVariableBoolean('SCHEDULE', 'Zeitplan', '~Switch', 51);
      IPS_SetIcon($ScheduleID, 'Clock');
    }
    if(!@$this->GetIDForIdent('DOCKED')) {
      $DockedID = $this->RegisterVariableBoolean('DOCKED', 'Im Dock', 'Botvac.YesNo', 52);
      $this->EnableAction('DOCKED');
      IPS_SetIcon($DockedID, 'Plug');
    }
    if(!@$this->GetIDForIdent('FIRMWARE')) {
      $FirmwareID = $this->RegisterVariableString('FIRMWARE', 'Firmware', '', 92);
      IPS_SetIcon($FirmwareID, 'Factory');
    }
    if(!@$this->GetIDForIdent('CMD')) {
      $CmdID = $this->RegisterVariableInteger('CMD', 'Kommando', 'Botvac.Command.'.$this->InstanceID, 10);
      IPS_SetIcon($CmdID, 'Script');
    }

    $this->EnableAction('ECO');
    $this->EnableAction('SCHEDULE');
    $this->EnableAction('CMD');
    $this->DisableAction('DOCKED');
  }

  public function RequestAction($ident, $value) {
    if($ident == 'CMD') {
      $mode = GetValueBoolean($this->GetIDForIdent('ECO')) ? 1 : 2;

      switch ($value) {
        case 1:
          $result = $this->Request('startCleaning', array('category' => 2, 'mode' => $mode, 'modifier' => 1));
          break;
        case 2:
          $result = $this->Request('startCleaning', array('category' => 3, 'mode' => $mode, 'modifier' => 1, 'spotWidth' => 200, 'spotHeight' => 200));
          break;
        case 6:
          $result = $this->Request('stopCleaning');
          break;
        case 7:
          $result = $this->Request('pauseCleaning');
          break;
        case 8:
          $result = $this->Request('resumeCleaning');
          break;
        case 9:
          $result = $this->Request('sendToBase');
          break;
      }
    } elseif($ident == 'ECO') {
      SetValueBoolean($this->GetIDForIdent('ECO'), $value);
    } elseif($ident == 'SCHEDULE') {
      SetValueBoolean($this->GetIDForIdent('SCHEDULE'), $value);
      if($value) {
        $result = $this->Request('enableSchedule');
      } else {
        $result = $this->Request('disableSchedule');
      }
    }
    $this->Update();
  }

  public function Update() {
    $result = $this->Request('getRobotState');

    if(@$result['result'] != 'ok') return false;

    SetValueInteger($this->GetIDForIdent('STATE'), @$result['state']);
    SetValueInteger($this->GetIDForIdent('ACTION'), @$result['action']);
    IPS_SetHidden($this->GetIDForIdent('ACTION'), @$result['action'] == 0);

    if(@$result['cleaning']) {
      if($this->isCleaning()) SetValueBoolean($this->GetIDForIdent('ECO'), @$result['cleaning']['mode'] == 1);
    }

    SetValueString($this->GetIDForIdent('ERROR'), $this->ErrorString(@$result['error']));
    IPS_SetHidden($this->GetIDForIdent('ERROR'), GetValueString($this->GetIDForIdent('ERROR')) == '');

    if(@$result['details']) {
      SetValueBoolean($this->GetIDForIdent('SCHEDULE'), @$result['details']['isScheduleEnabled']);
      SetValueBoolean($this->GetIDForIdent('DOCKED'), @$result['details']['isDocked']);
      SetValueInteger($this->GetIDForIdent('BATTERY'), @$result['details']['charge']);
      $charging = '';
      if($result['details']['isCharging']) $charging = ' (lädt)';
      IPS_SetName($this->GetIDForIdent('BATTERY'), "Batterie$charging");
    }

    if(@$result['availableServices']) {
      $ScheduleID = $this->GetIDForIdent('SCHEDULE');
      IPS_SetDisabled($ScheduleID, $result['availableServices']['schedule'] != 'basic-1');
      IPS_SetHidden($ScheduleID, $result['availableServices']['schedule'] != 'basic-1');
    }

    if(@$result['meta']) {
      SetValueString($this->GetIDForIdent('MODEL'), @$result['meta']['modelName']);
      SetValueString($this->GetIDForIdent('FIRMWARE'), @$result['meta']['firmware']);
    }

    $this->UpdateCommandProfile(@$result['availableCommands']);

    return $result;
  }

  private function ErrorString($id) {
    $errors = array();
    $errors['ui_alert_dust_bin_full'] = "Behälter voll";
    $errors['ui_alert_recovering_location'] = "Ermittle eigene Position";
    $errors['ui_error_picked_up'] = "Auf den Boden setzen";
    $errors['ui_error_brush_stuck'] = "Bürste hängt";
    $errors['ui_error_stuck'] = "Hängt fest";
    $errors['ui_error_dust_bin_emptied'] = "Behälter geleert";
    $errors['ui_error_dust_bin_missing'] = "Behälter fehlt";
    $errors['ui_error_navigation_falling'] = "Weg freiräumen";
    $errors['ui_error_navigation_noprogress'] = "Weg freiräumen";
    return @$errors[$id];
  }

  private function UpdateCommandProfile($availables = false) {
    $name = 'Botvac.Command.'.$this->InstanceID;
    if(IPS_VariableProfileExists($name) && $availables !== false) IPS_DeleteVariableProfile($name);
    if(!IPS_VariableProfileExists($name)) {
      IPS_CreateVariableProfile($name, 1);
      IPS_SetVariableProfileAssociation($name, 0, 'Auswählen', '', -1);
    }

    if($availables !== false) {
      if($availables['start'] == 1) IPS_SetVariableProfileAssociation($name, 1, 'Reinigung: Haus', '', -1);
      if($availables['start'] == 1) IPS_SetVariableProfileAssociation($name, 2, 'Reinigung: Spot', '', -1);
      if($availables['stop'] == 1) IPS_SetVariableProfileAssociation($name, 6, 'Stop', '', -1);
      if($availables['pause'] == 1) IPS_SetVariableProfileAssociation($name, 7, 'Pause', '', -1);
      if($availables['resume'] == 1) IPS_SetVariableProfileAssociation($name, 8, 'Wiederaufnahme', '', -1);
      if($availables['goToBase'] == 1) IPS_SetVariableProfileAssociation($name, 9, 'Zur Station', '', -1);
    }
  }

  public function isCleaning() {
    return in_array(GetValueInteger($this->GetIDForIdent('ACTION')), array(1, 2, 3, 6));
  }

  public function Request($cmd, $params = false) {
    $serial = strtolower($this->ReadPropertyString('Serial'));
    $secret = $this->ReadPropertyString('Secret');
    $date = gmdate('D, d M Y H:i:s') . ' GMT';

    $data = array('reqId' => 1, 'cmd' => $cmd);
    if($params !== false) $data['params'] = $params;
    $data = json_encode($data);

    $url = "https://nucleo.ksecosys.com/vendors/vorwerk/robots/$serial/messages";
    $auth = hash_hmac('sha256', implode("\n", array($serial, $date, $data)), $secret);

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

    $headers = array();
    $headers[] = 'Accept: application/vnd.neato.nucleo.v1';
    $headers[] = 'Date: ' . $date;
    $headers[] = 'Authorization: NEATOAPP ' . $auth;

    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    $result = curl_exec($curl);
    curl_close($curl);
    return json_decode($result, true);
  }

}
