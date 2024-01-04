<?

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/options.php");
IncludeModuleLangFile(__FILE__);

CModule::IncludeModule("iblock");

global $APPLICATION;
$module_id = "zup";

$RIGHT = $APPLICATION->GetGroupRight($module_id);
if($RIGHT >= "R"){

	$bVarsFromForm = false;
	$aTabs = [
        [
            "DIV" => 'index',
            "TAB" => 'Настройки',
            "TITLE" => 'Настройки',
            "OPTIONS" => [
                "gs_zup_user_login" => [
                    'Логин пользователя для импорта',
                    ["text"]
                ],
                "gs_zup_user_password" => [
                    'Пароль пользователя для импорта',
                    ["password"]
                ],
	            "gs_zup_out_dir" => [
		            'Папка для хранения результирующего XML',
		            ["text"]
	            ],
	            "gs_zup_root_id" => [
		            'Корневое подразделение (для склейки файлов)',
		            ["text"]
	            ],
            ]
        ],
        [
            "DIV" => 'dir',
            "TAB" => 'Подразделения',
            "TITLE" => 'Подразделения',
            "OPTIONS" => [
                "gs_zup_dep_id" => [
                    'ID подразделения',
                    ["text"]
                ],
                "gs_zup_dep_name" => [
                    'Имя подразделения',
                    ["text"]
                ],
                "gs_zup_dep_path" => [
                    'Путь к папке подразделения',
                    ["text"]
                ],
                "gs_zup_dep_arr" => [
                    'Массив',
                    ["array"]
                ],
            ]
        ],
        [
            "DIV" => "rights",
            "TAB" => GetMessage("MAIN_TAB_RIGHTS"),
            "TITLE" => GetMessage("MAIN_TAB_TITLE_RIGHTS"),
            "OPTIONS" => []
        ]
	];
	$tabControl = new CAdminTabControl("tabControl", $aTabs);

	if($_SERVER['REQUEST_METHOD'] == "POST" && strlen($Update . $Apply . $RestoreDefaults) > 0 && check_bitrix_sessid()){
		if(strlen($RestoreDefaults) > 0){
			COption::RemoveOption($module_id);
		}else{
			if (!$bVarsFromForm){
				foreach($aTabs as $i => $aTab){
					foreach($aTab["OPTIONS"] as $name => $arOption){
						$disabled = array_key_exists("disabled", $arOption) ? $arOption["disabled"] : "";
						if($disabled) continue;

						$val = $_POST[$name];
						if($arOption[1][0] == "checkbox" && $val!="Y") $val="N";
						COption::SetOptionString($module_id, $name, $val, $arOption[0]);
					}
				}
			}
		}

		ob_start();
		$Update = $Update.$Apply;
		require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights.php");
		ob_end_clean();
	}
	$tabControl->Begin(); ?>
        <form method="post" action="<?=$APPLICATION->GetCurPage()?>?mid=<?=urlencode($mid)?>&amp;lang=<?=LANGUAGE_ID?>" id="options">
            <? foreach($aTabs as $caTab => $aTab){
                $tabControl->BeginNextTab();
                if ($aTab["DIV"] != "rights") {
                    foreach($aTab["OPTIONS"] as $name => $arOption){
                        if ($bVarsFromForm){
                            $val = $_POST[$name];
                        }else{
                            $val = COption::GetOptionString($module_id, $name);
                        }
                        $type = $arOption[1];
                        $disabled = array_key_exists("disabled", $arOption)? $arOption["disabled"]: ""; ?>
                        <tr <?if(isset($arOption[2]) && strlen($arOption[2])) echo 'style="display:none" class="show-for-'.htmlspecialcharsbx($arOption[2]).'"'?>>
                            <td width="40%" <?if($type[0]=="textarea") echo 'class="adm-detail-valign-top"'?>>
                                <label for="<?=htmlspecialcharsbx($name)?>"><?=$arOption[0]?>:</label>
                            <td width="30%">
                                <?if($type[0]=="checkbox"){?>
                                    <input type="checkbox" name="<?=htmlspecialcharsbx($name)?>" id="<?=htmlspecialcharsbx($name)?>" value="Y"<?if($val=="Y")echo" checked";?><?if($disabled)echo' disabled="disabled"';?>><?if($disabled) echo '<br>'.$disabled;?>
                                <?}elseif($type[0]=="text"){?>
                                    <input type="text" size="<?=$type[1]?>" maxlength="255" value="<?=htmlspecialcharsbx($val)?>" name="<?=htmlspecialcharsbx($name)?>">
                                <?}elseif($type[0]=="password"){?>
                                    <input type="password" size="<?=$type[1]?>" maxlength="255" value="<?=htmlspecialcharsbx($val)?>" name="<?=htmlspecialcharsbx($name)?>">
                                <?}elseif($type[0]=="textarea"){?>
                                    <textarea rows="<?=$type[1]?>" name="<?=htmlspecialcharsbx($name)?>" style=
                                    "width:100%"><?=htmlspecialcharsbx($val)?></textarea>
                                <?}elseif($type[0]=="select"){?>
                                    <?if(count($type[1])){?>
                                        <select name="<?=htmlspecialcharsbx($name)?>" onchange="doShowAndHide()">
                                            <?foreach($type[1] as $key => $value){?>
                                                <option value="<?=htmlspecialcharsbx($key)?>" <?if ($val == $key) echo 'selected="selected"'?>><?=htmlspecialcharsEx($value)?></option>
                                            <?}?>
                                        </select>
                                    <?}else{?>
                                        <?=GetMessage("ZERO_ELEMENT_ERROR");?>
                                    <?}?>
                                <?}elseif($type[0] == "note"){?>
                                    <?=BeginNote(), $type[1], EndNote();?>
                                <?}elseif($type[0]=="array"){?>
                                    <textarea rows="<?=$type[1]?>" name="<?=htmlspecialcharsbx($name)?>" style=
                                    "width:100%"><?=htmlspecialcharsbx($val)?></textarea>
                                <?}?>
                            </td>
                            <td width="30%">
                                <?if ($arOption[3]){?>
                                    <p><?=$arOption[3];?></p>
                                <?}?>
                            </td>
                        </tr>
                    <?}
                }elseif($aTab["DIV"] == "rights"){
                    require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/admin/group_rights.php");
                }
            }?>

            <? $tabControl->Buttons(); ?>

            <input type="submit" name="Update" value="<?=GetMessage("MAIN_SAVE")?>" title="<?=GetMessage("MAIN_OPT_SAVE_TITLE")?>" class="adm-btn-save">
            <input type="submit" name="Apply" value="<?=GetMessage("MAIN_OPT_APPLY")?>" title="<?=GetMessage("MAIN_OPT_APPLY_TITLE")?>">
            <?if(strlen($_REQUEST["back_url_settings"]) > 0):?>
                <input type="button" name="Cancel" value="<?=GetMessage("MAIN_OPT_CANCEL")?>" title="<?=GetMessage("MAIN_OPT_CANCEL_TITLE")?>" onclick="window.location='<?=htmlspecialcharsbx(CUtil::addslashes($_REQUEST["back_url_settings"]))?>'">
                <input type="hidden" name="back_url_settings" value="<?=htmlspecialcharsbx($_REQUEST["back_url_settings"])?>">
            <?endif?>
            <input type="submit" name="RestoreDefaults" title="<?=GetMessage("MAIN_HINT_RESTORE_DEFAULTS")?>" OnClick="return confirm('<?=AddSlashes(GetMessage("MAIN_HINT_RESTORE_DEFAULTS_WARNING"))?>')" value="<?=GetMessage("MAIN_RESTORE_DEFAULTS")?>">
            <?=bitrix_sessid_post();?>
        </form>
	<? $tabControl->End(); ?>
	<script>
        function doShowAndHide(){
            var form = BX('options');
            var selects = BX.findChildren(form, {tag: 'select'}, true);
            for (var i = 0; i < selects.length; i++){
                var selectedValue = selects[i].value;
                var trs = BX.findChildren(form, {tag: 'tr'}, true);
                for (var j = 0; j < trs.length; j++){
                    if (/show-for-/.test(trs[j].className)){
                        if (trs[j].className.indexOf(selectedValue) >= 0){
                            trs[j].style.display = 'table-row';
                        }else{
                            trs[j].style.display = 'none';
                        }
                    }
                }
            }
        }
        BX.ready(doShowAndHide);

        function ArrToJSON(){

        }
        BX.ready(ArrToJSON);
	</script>
<?}?>
