<?php


class VouteDevice extends IPSModule
{
    public function Create()
    {
         // Never delete this line!
         parent::Create();

         $this->RequireParent('{A4F98949-29AB-4E9D-9CFE-6172C65F29E1}'); // VouteCoordinator

         $this->SetVisualizationType(1);

         $this->RegisterVariableBoolean("Status", "Status", [
            'PRESENTATION' => VARIABLE_PRESENTATION_SWITCH
         ], 0);
         $this->RegisterVariableInteger("Segments", "Segments", "", 0);
         $this->RegisterVariableInteger("Brightness", "Brightness", [
            'PRESENTATION' => VARIABLE_PRESENTATION_SLIDER,
            'SUFFIX' => ' %'
         ], 2);
         
         $this->RegisterPropertyInteger('script', '0');
         $this->RegisterPropertyString('config', '{"segments":[]}');
         $this->RegisterPropertyString('type', 'cct');
         $this->RegisterPropertyBoolean('autoAdjust', true);

         $this->EnableAction("Segments");
         $this->EnableAction("Brightness");
         $this->EnableAction("Status");

         IPS_SetHidden($this->GetIDForIdent('Segments'), true);
    }

    public function ApplyChanges() {
        parent::ApplyChanges();

        $autoAdjust = $this->ReadPropertyBoolean('autoAdjust');
        if($autoAdjust) {
            $this->RegisterVariableInteger("Auto", "Auto", [
                'PRESENTATION' => VARIABLE_PRESENTATION_ENUMERATION,
                'OPTIONS' => [[
                    'Value' => 1,
                    'Caption' => 'Day',
                    'IconActive' => false,
                    'Icon' => '',
                    'Color' => 0
                ],[
                    'Value' => 2,
                    'Caption' => 'Night',
                    'IconActive' => false,
                    'Icon' => '',
                    'Color' => 0
                ],[
                    'Value' => 0,
                    'Caption' => 'Manual',
                    'IconActive' => false,
                    'Icon' => '',
                    'Color' => 0
                ]]
             ], 1);
             $this->EnableAction("Auto");
        } else {
            $this->UnregisterVariable("Auto");
        }

        $type = $this->ReadPropertyString('type');
        if($type === 'cct' || $type === 'rgbcct') {
            $this->RegisterVariableInteger("Temperature", "Temperature", [
                'PRESENTATION' => VARIABLE_PRESENTATION_SLIDER
             ], 3);
            $this->EnableAction("Temperature");   
        } else {
            $this->UnregisterVariable("Temperature");
        }

        if($type === 'rgb' || $type === 'rgbcct') {
            $this->RegisterVariableInteger("Color", "Color", [
                'PRESENTATION' => VARIABLE_PRESENTATION_COLOR
             ], 4);
            $this->EnableAction("Color");
        } else {
            $this->UnregisterVariable("Color");
        }

        if($type === 'rgbcct') {
            $this->RegisterVariableInteger("Mode", "Mode", "", 0);
            IPS_SetHidden($this->GetIDForIdent('Mode'), true);
        } else {
            $this->UnregisterVariable("Mode");
        }

        $this->UpdateVisualizationValue($this->GetFullUpdateMessage());
    }

    public function RequestAction($Ident, $Value)
    {
        if(in_array($Ident, ['Segments', 'Status', 'Temperature', 'Color', 'Mode', 'Brightness', 'Auto'])) {
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
        $autoAdjust = $this->ReadPropertyBoolean('autoAdjust');

        $module = file_get_contents(__DIR__ . '/module.html');
        $module = str_replace("'{{LAYOUT}}'", json_encode($config['segments']), $module);
        $module = str_replace("'{{HAS_AUTO}}'", $autoAdjust ? 'true' : 'false', $module);
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
        $type = $this->ReadPropertyString('type');
        $autoAdjust = $this->ReadPropertyBoolean('autoAdjust');

        $temperature = ($type === 'cct' || $type === 'rgbcct') ? $this->GetValue('Temperature') : 0;
        $color = ($type === 'rgb' || $type === 'rgbcct') ? $this->GetValue('Color') : 0;
        $auto = ($autoAdjust) ? $this->GetValue('Auto') : 0;

        return json_encode([
            'Segments' => $this->GetValue('Segments'),
            'Status' => $this->GetValue('Status'),
            'Temperature' => $temperature,
            'Color' => $color,
            'Brightness' => $this->GetValue('Brightness'),
            'Auto' => $auto
        ]);
    }
}
