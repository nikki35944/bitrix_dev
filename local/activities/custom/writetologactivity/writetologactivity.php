<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class CBPWriteTOLogActivity
    extends CBPActivity
{
    public function __construct($name)
    {
        parent::__construct($name);
        $this->arProperties = array(
            "Title" => "",
            "MapFields" => null,
            "write2file" => null,
            "path2file"=> null
        );
    }

    public function Execute()
    {
        if (is_array($this->MapFields) && count($this->MapFields))
        {
            $printVal = $this->__get("MapFields");
            $bFirst = true;

            foreach($printVal as $key => $val)
            {
                if ($this->write2file == "Y")
                {

                    $f = fopen ($_SERVER["DOCUMENT_ROOT"].$this->path2file, "a+");
                    if ($bFirst)
                        fwrite ($f, print_r ("\n\n\n===========================================================\n",true));
                    fwrite ($f, print_r ($key." : ".$val."\n",true));
                    fclose($f);
                    $bFirst = false;
                }
                else
                    $this->WriteToTrackingService($key." : ".$val);
            }
        }
        return CBPActivityExecutionStatus::Closed;
    }

    public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = "")
    {
        $runtime = CBPRuntime::GetRuntime();

        if (!is_array($arCurrentValues))
        {
            $arCurrentValues = array();

            $arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);

            if (is_array($arCurrentActivity["Properties"])
                && array_key_exists("MapFields", $arCurrentActivity["Properties"])
                && is_array($arCurrentActivity["Properties"]["MapFields"]))
            {
                foreach ($arCurrentActivity["Properties"]["MapFields"] as $k => $v)
                {
                    $arCurrentValues["MapFields"][$k] = $v;
                }
                $arCurrentValues["write2file"] = $arCurrentActivity["Properties"]["write2file"];
                $arCurrentValues["path2file"] = $arCurrentActivity["Properties"]["path2file"];
            }
        }

        $runtime = CBPRuntime::GetRuntime();
        return $runtime->ExecuteResourceFile(
            __FILE__,
            "properties_dialog.php",
            array(
                "arCurrentValues" => $arCurrentValues,
                "formName" => $formName,
            )
        );
    }

    public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$arErrors)
    {
        $runtime = CBPRuntime::GetRuntime();
        $arProperties = array("MapFields" => array());

        if (is_array($arCurrentValues) && count($arCurrentValues)>0)
        {
            if  (is_array($arCurrentValues["fields"]) && count($arCurrentValues["fields"]) > 0
                && is_array($arCurrentValues["values"]) && count($arCurrentValues["values"]) > 0)
            {
                foreach($arCurrentValues["fields"] as $key => $value)
                    if (strlen($value) > 0 && strlen($arCurrentValues["values"][$key]) > 0)
                        $arProperties["MapFields"][$value] = $arCurrentValues["values"][$key];
            }
            $arProperties ["write2file"] = $arCurrentValues["write2file"] == "Y" ? "Y" : "N";
            $arProperties ["path2file"] = $arCurrentValues["path2file"];
        }

        $arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
        $arCurrentActivity["Properties"] = $arProperties;

        return true;
    }

    public static function ValidateProperties($arTestProperties = array(), CBPWorkflowTemplateUser $user = null)
    {
        $arErrors = array();
        $path = "";
        if ($arTestProperties["path2file"])
        {
            $path = $_SERVER["DOCUMENT_ROOT"].$arTestProperties["path2file"];
            if (!file_exists($path) && !is_writable(GetDirPath($path)))
                $arErrors[] = array("code" => "DirNotWritable", "parameter" => "path2file", "message" => GetMessage("DIR_NOT_WRITABLE"));
            if (file_exists($path) && !!is_writable(GetDirPath($path)))
                $arErrors[] = array("code" => "FileNotWritable", "parameter" => "path2file", "message" => GetMessage("FILE_NOT_WRITABLE"));
        }

        return array_merge($arErrors, parent::ValidateProperties($arTestProperties, $user));
    }
}