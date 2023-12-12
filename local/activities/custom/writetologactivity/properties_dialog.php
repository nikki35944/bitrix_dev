<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

?>
<script>
var b_log_counter = -1;
function AddCondition(field, val)
{
    var addrowTable = document.getElementById('bwfvc_addrow_table1');

    b_log_counter++;
    var newRow = addrowTable.insertRow(-1);

    newRow.id = "delete_row_log_" + b_log_counter;

    var newCell = newRow.insertCell(-1);
    var newSelect = document.createElement("input");
        newSelect.type = 'text';
        newCell.align="left";
        newSelect.size = '40';
    newSelect.setAttribute('b_log_counter', b_log_counter);

    newSelect.id = "id_var_name_" + b_log_counter;
    newSelect.name = "fields[var_value_" + b_log_counter + "]";
    var i = -1;
    var i1 = -1;

    newSelect.value = BWFVCUnHtmlSpecialChars(field);
    newCell.appendChild(newSelect);

    var newCell = newRow.insertCell(-1);
    newCell.innerHTML = ":";

    var newCell = newRow.insertCell(-1);
    newCell.id = "id_row_value_" + b_log_counter;
        newCell.align="right";
        newCell.innerHTML = '<input size="30" type="text" id="id_var_value_' + b_log_counter + '" name="values[var_value_' + b_log_counter + ']" value="' + val + '">';

        var newCell = newRow.insertCell(-1);
        newCell.id = "id_dialog_" + b_log_counter;

        newCell.innerHTML = '<input type="button" value="..." onclick="BPAShowSelector(\'id_var_value_'+b_log_counter+'\', \'string\');">';

        var newCell = newRow.insertCell(-1);
    newCell.align="right";
    newCell.innerHTML = '<a href="#" onclick="BWFVCDeleteCondition(' + b_log_counter + '); return false;">Удалить поле</a>';

}

function BWFVCUnHtmlSpecialChars(string, quote)
{
    string = string.toString();

    if (quote)
        string = string.replace(/'/g, "'");
        string = string.replace(/"/g, "\"");
        string = string.replace(/>/g, ">");
        string = string.replace(/</g, "<");
        string = string.replace(/&/g, '&');
    return string;
}
function BWFVCDeleteCondition(ind)
{
    var addrowTable = document.getElementById('bwfvc_addrow_table1');

    var cnt = addrowTable.rows.length;
    for (i = 0; i < cnt; i++)
    {
        if (addrowTable.rows[i].id != 'delete_row_log_' + ind)
            continue;
            addrowTable.deleteRow(i);
            break;
    }
        // if (addrowTable.rows.length <= 0)
        //    addrowTable.style.display = 'none';
}
function Write2File()
{
    var f_table = document.getElementById('write2filetable');
    if (f_table.rows.length <= 0)
    {
        var newRow = f_table.insertRow(-1);
        var newCell = newRow.insertCell(-1);
        //newCell.align="right";
        //newCell.style.padding.left = '100px';
        newCell.innerHTML = '<input size="40" type="text" id="path2file" name="path2file" value="<?= ($arCurrentValues["path2file"])? $arCurrentValues["path2file"]: "/bitrix/test.log"?>">';
    }
    else
        f_table.deleteRow("-1");
}
</script>

<tr id="write_file_form" style="display:line">
    <td colspan="2">
        <input type="checkbox" id="write2file" name="write2file" onchange="Write2File();"><?= GetMessage("WRITE_2_LOG");?>
        <table width="100%" border="0" cellpadding="2" cellspacing="2" id="write2filetable">
        </table>
    </td>
</tr>

<tr id="pd_list" style="display:line">
    <td colspan="2">
        <table width="100%" border="0" cellpadding="2" cellspacing="2" id="bwfvc_addrow_table1">
        </table>
        <a href="#" onclick="AddCondition('', ''); return false;"><?= GetMessage("ADD_CONDITION");?></a>
    </td>
</tr>
<script>
<?
foreach ($arCurrentValues["MapFields"] as $fieldKey => $documentFieldValue)
{
    ?>
    AddCondition('<?= CUtil::JSEscape($fieldKey) ?>', '<?= CUtil::JSEscape($documentFieldValue) ?>');
    <?
}
if (count($arCurrentValues) <= 0)
{
    ?>AddCondition("", "");<?
}
?>
var check = '<?= $arCurrentValues["write2file"];?>';
if (check == "Y")
{
    document.getElementById("write2file").checked = "checked";
    Write2File();
}
</script>