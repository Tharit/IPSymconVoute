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
         $this->RegisterVariableInteger("ColorTemperature", "ColorTemperature", "", 0);
         $this->RegisterVariableInteger("Auto", "Auto", "", 0);
         $this->RegisterVariableBoolean("Status", "Status", "", 0);

         $this->RegisterPropertyInteger('script', '0');

         $this->EnableAction("Segments");
         $this->EnableAction("Brightness");
         $this->EnableAction("ColorTemperature");
         $this->EnableAction("Auto");
         $this->EnableAction("Status");
    }

    public function ApplyChanges() {
        parent::ApplyChanges();

        $this->UpdateVisualizationValue($this->GetFullUpdateMessage());
    }

    public function RequestAction($Ident, $Value)
    {
        if(in_array($Ident, ['Segments', 'Status', 'ColorTemperature', 'Brightness', 'Auto'])) {
            $script = $this->ReadPropertyInteger('script');
            if($script && @IPS_GetScript($script)) {
                IPS_RunScriptEx($script, [
                    "VARIABLE" => IPS_GetObjectIDByIdent($Ident, $this->InstanceID),
                    "VALUE" => $Value
                ]);
            }
        }

        $this->UpdateVisualizationValue($this->GetFullUpdateMessage());
    }

    public function GetVisualizationTile() {
        $initialHandling = '<script>handleMessage(' . json_encode($this->GetFullUpdateMessage()) . ');</script>';

        $module = file_get_contents(__DIR__ . '/module.html');

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
            'ColorTemperature' => $this->GetValue('ColorTemperature'),
            'Brightness' => $this->GetValue('Brightness'),
            'Auto' => $this->GetValue('Auto')
        ]);
    }
}
