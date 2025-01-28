<?php


class VouteDevice extends IPSModule
{
    public function Create()
    {
         // Never delete this line!
         parent::Create();

         $this->SetVisualizationType(1);

         $this->RegisterVariableInteger("Segments", "Segments", "", 0);
         $this->RegisterVariableInteger("Brightness", "Brightness", "", 0);
         $this->RegisterVariableInteger("Auto", "Auto", "", 0);
         $this->RegisterVariableBoolean("Status", "Status", "", 0);

         $this->RegisterPropertyInteger('script', '0');
         $this->RegisterPropertyString('config', '{"segments":[]}');
         $this->RegisterPropertyString('type', 'cct');

         $this->EnableAction("Segments");
         $this->EnableAction("Brightness");
         $this->EnableAction("Auto");
         $this->EnableAction("Status");
    }

    public function ApplyChanges() {
        parent::ApplyChanges();

        $type = $this->ReadPropertyString('type');
        if($type === 'cct' || $type === 'rgbcct') {
            $this->RegisterVariableInteger("Temperature", "Temperature", "", 0);
            $this->EnableAction("Temperature");   
        } else {
            $this->UnregisterVariable("Temperature");
        }

        if($type === 'rgb' || $type === 'rgbcct') {
            $this->RegisterVariableInteger("Color", "Color", "", 0);
            $this->EnableAction("Color");
        } else {
            $this->UnregisterVariable("Color");
        }

        $this->UpdateVisualizationValue($this->GetFullUpdateMessage());
    }

    public function RequestAction($Ident, $Value)
    {
        if(in_array($Ident, ['Segments', 'Status', 'Temperature', 'Color', 'Brightness', 'Auto'])) {
            $script = $this->ReadPropertyInteger('script');
            if($script && @IPS_GetScript($script)) {
                IPS_RunScriptWaitEx($script, [
                    "VARIABLE" => IPS_GetObjectIDByIdent($Ident, $this->InstanceID),
                    "VALUE" => $Value
                ]);
            }
        }

        $this->UpdateVisualizationValue($this->GetFullUpdateMessage());
    }

    public function GetVisualizationTile() {
        $initialHandling = '<script>handleMessage(' . json_encode($this->GetFullUpdateMessage()) . ');</script>';

        $config = json_decode($this->ReadPropertyString('config'), true);
        
        $type = $this->ReadPropertyString('type');

        $module = file_get_contents(__DIR__ . '/module.html');
        $module = str_replace("'{{LAYOUT}}'", json_encode($config['segments']), $module);
        $module = str_replace("'{{HAS_COLOR}}'", $type === 'rgb' || $type === 'rgbcct' ? 'true' : 'false', $module);
        $module = str_replace("'{{HAS_BRIGHTNESS}}'", 'true', $module);
        $module = str_replace("'{{HAS_TEMPERATURE}}'", $type === 'cct' || $type === 'rgbcct' ? 'true' : 'false', $module);

        return $module . $initialHandling;
    }

    //------------------------------------------------
    // Public methods

    public function RefreshTile() {
        $this->UpdateVisualizationValue($this->GetFullUpdateMessage());
    }

    //------------------------------------------------
    // private methods

    private function GetFullUpdateMessage() {
        return json_encode([
            'Segments' => $this->GetValue('Segments'),
            'Status' => $this->GetValue('Status'),
            'Temperature' => $this->GetValue('Temperature'),
            'Brightness' => $this->GetValue('Brightness'),
            'Auto' => $this->GetValue('Auto')
        ]);
    }
}
