<?php
class BotvacControl extends IPSModule {

  public function Create() {
    parent::Create();
    $this->RegisterPropertyString('Email', '');
    $this->RegisterPropertyString('Password', '');
    $this->RegisterPropertyString('Token', '');
    $this->RegisterPropertyInteger('Category', 0);
  }

  public function ApplyChanges() {
    parent::ApplyChanges();
  }

  public function Sync() {
    if($this->ReadPropertyString('Token') == '') $this->CreateToken();

    $url = 'https://vorwerk-beehive-production.herokuapp.com/dashboard';
    $params = array();
    $headers = array();
    $headers[] = "Accept: application/json";
    $headers[] = "Authorization: Token token=" . $this->ReadPropertyString('Token');
    $result = $this->Request($url, 'GET', $params, $headers);
    if(isset($result['robots'])) {
      foreach ($result['robots'] as $robot) {
        $serial = $robot['serial'];
        $secret = $robot['secret_key'];
        $name = $robot['name'];
        $this->CreateOrUpdateRobot($serial, $secret, $name);
      }
    }
  }

  private function CreateOrUpdateRobot($serial, $secret, $name) {
    $Category = $this->ReadPropertyInteger('Category');
    $id = $this->FindBySerial($serial);
    if($Category && !$id) {
      $id = IPS_CreateInstance('{0EDA2062-A28C-4A59-BB7E-BC00264827C6}');
      IPS_SetProperty($id, 'Serial', $serial);
      IPS_SetParent($id, $Category);
    }
    if($id) {
      IPS_SetProperty($id, 'Secret', $secret);
      IPS_SetName($id, $name);
      IPS_ApplyChanges($id);
      BR_Update($id);
    }
    echo "$name (Serial: $serial)\n";
  }

  private function FindBySerial($serial) {
    $ids = IPS_GetInstanceListByModuleID('{0EDA2062-A28C-4A59-BB7E-BC00264827C6}');
    $found = false;
    foreach($ids as $id) {
      if(strtolower(IPS_GetProperty($id, 'Serial')) == strtolower($serial)) {
        $found = $id;
        break;
      }
    }
    return $found;
  }

  private function CreateToken() {
    $url = 'https://vorwerk-beehive-production.herokuapp.com/sessions';
    $params = array();
    $params['platform'] = 'ios';
    $params['email'] = $this->ReadPropertyString('Email');
    $params['password'] = $this->ReadPropertyString('Password');
    $params['token'] = substr(hash('sha512', openssl_random_pseudo_bytes(100)), 0, 64);
    $headers = array();
    $headers[] = "Accept: application/json";
    $result = $this->Request($url, 'POST', $params, $headers);
    if(isset($result['access_token'])) {
      IPS_SetProperty($this->InstanceID, 'Token', $result['access_token']);
      IPS_ApplyChanges($this->InstanceID);
      $this->SetStatus(102);
    } else {
      $this->SetStatus(201);
    }
  }

  public function Request($url, $method, $params = array(), $headers = array()) {
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    if($method == 'POST') curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    $result = curl_exec($curl);
    curl_close($curl);
    return json_decode($result, true);
  }

}