<?php

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");


global $APPLICATION;
$APPLICATION->SetTitle('Импорт квартир и участников спортакиады');


use \Bitrix\Main\Loader;
use \Dmbgeo\NextImport\Import;

$strError = "";
if (!Loader::IncludeModule("iblock")) {
    $strError .= "Модуль iblock не установлен <br>";
}

if (!Loader::IncludeModule("dmbgeo.nextimport")) {
    $strError .= "Модуль dmbgeo.nextimport не установлен <br>";
}


require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/iblock/prolog.php");
IncludeModuleLangFile(__FILE__);
$success = false;
if ($_SERVER["REQUEST_METHOD"] == "POST" && check_bitrix_sessid()) {
    CUtil::JSPostUnescape();
    if (empty($_REQUEST['URL_DATA_FILE_APARTAMENTS'])) {
        $strError .= "Файл квартир не выбран <br>";
    }


    if ($strError == '') {
        $_REQUEST['STEP'] = intVal($_REQUEST['STEP']);
        $_REQUEST['COUNT'] = intVal($_REQUEST['COUNT']);
        $_REQUEST['apartament_success'] = intVal($_REQUEST['apartament_success']);
        if ($_REQUEST['STEP'] == -1) {
            Import::clearCache();
            Import::setLogs(array());
        }
        $_REQUEST['STEP']++;
        $logs = Import::getLogs();
        $offset = $_REQUEST['STEP'] * $_REQUEST['COUNT'];

        $data = Import::getData($_SERVER["DOCUMENT_ROOT"] . $_REQUEST['URL_DATA_FILE_APARTAMENTS'], $logs);
        $all_apartament = count($data);
        if ($offset < count($data)) {
            $result = Import::run(array_slice($data, $offset, $_REQUEST['COUNT'], true), $logs);
            if ($result) {
                $_REQUEST['apartament_success']  = $_REQUEST['apartament_success'] + count($result);
            }
?>
            <script type="text/javascript">
                function DoNext() {
                    document.getElementById("dataload").submit();
                }
                setTimeout('DoNext()', 2000);
            </script>
        <?
            // }else {
            //     $strError .= implode("<br>", $logs);
            // }
            Import::setLogs($logs);
        } elseif ($_REQUEST['STEP'] > 0) {
            $success = true;
        } else {
            $strError .= implode("<br>", $logs);
        }
    }
}

$aTabs = array(
    array(
        "DIV" => "form",
        "TAB" => 'Импорт данных',
        "ICON" => "iblock",
        "TITLE" => 'Импорт данных в формате xlsx',
    )
);
if ($strError) {
    CAdminMessage::ShowMessage($strError);
    $_REQUEST['STEP'] = '-1';
}
$tabControl = new CAdminTabControl("tabControl", $aTabs, false, true);
$tabControl->Begin();
$tabControl->BeginNextTab();

if (!$success) :

    if ($_SERVER["REQUEST_METHOD"] == "POST" && intval($_REQUEST['STEP']) >= 0) :
        ?>
        <tr>
            <td>
                <? CAdminMessage::ShowMessage(array(
                    "TYPE" => "PROGRESS",
                    "MESSAGE" => 'Продолжение пошаговой загрузки...',
                    "DETAILS" =>
                    'Всего обработано квартир: <b>' . $_REQUEST['apartament_success'] . ' из ' . $all_apartament . '</b><br>',
                    "HTML" => true,
                )) ?>
            </td>
        </tr>
    <? endif; ?>
    <form method="POST" action="<? echo $APPLICATION->GetCurPageParam('lang=' . LANGUAGE_ID) ?>" enctype="multipart/form-data" name="dataload" id="dataload">
        <input type="hidden" name="STEP" value="<?= isset($_REQUEST['STEP']) ? $_REQUEST['STEP'] : '-1'; ?>">
        <input type="hidden" name="import" value="Y">
        <input type="hidden" name="apartament_success" value="<?= isset($_REQUEST['apartament_success']) ? $_REQUEST['apartament_success'] : '0'; ?>">
        <tr>
            <td width="50%">Количество обработки квартир за раз </td>
            <td width="50%"><input type="text" name="COUNT" value="<?= !empty($_REQUEST['COUNT']) ? $_REQUEST['COUNT'] : 50; ?>" size="50"></td>
        </tr>
        <tr>
            <td width="40%">Файл квартир</td>
            <td width="60%">
                <input type="text" name="URL_DATA_FILE_APARTAMENTS" value="<? echo htmlspecialcharsbx($_REQUEST['URL_DATA_FILE_APARTAMENTS']); ?>" size="30">
                <input type="button" value="Открыть" OnClick="BtnClickApartaments()">
                <? CAdminFileDialog::ShowScript(array(
                    "event" => "BtnClickApartaments",
                    "arResultDest" => array(
                        "FORM_NAME" => "dataload",
                        "FORM_ELEMENT_NAME" => "URL_DATA_FILE_APARTAMENTS",
                    ),
                    "arPath" => array(
                        "SITE" => SITE_ID,
                        "PATH" => "/" . COption::GetOptionString("main", "upload_dir", "upload"),
                    ),
                    "select" => 'F', // F - file only, D - folder only
                    "operation" => 'O', // O - open, S - save
                    "showUploadTab" => true,
                    "showAddToMenuTab" => false,
                    "fileFilter" => 'xlsx',
                    "allowAllFiles" => true,
                    "SaveConfig" => true,
                ));
                ?>
            </td>
        </tr>

        <? $tabControl->Buttons(); ?>
        <input type="submit" class="adm-btn" name="opts_reset" value="Импортировать">
        <?= bitrix_sessid_post(); ?>
    </form>
<? endif; ?>
<? if ($success) : ?>
    <?
    $strlog = "";
    foreach ($logs as $log) {
        $strlog .= '<b>' . $log . '</b><br>';
    }
    CAdminMessage::ShowMessage(array(
        "MESSAGE" => "Импорт завершен успешно",
        "DETAILS" => 'Всего обработано квартир: <b>' . $_REQUEST['apartament_success'] . ' из ' . $all_apartament . '</b><br>
        <br>' . $strlog,
        "HTML" => true,
        "TYPE" => "OK",
    ));
    ?>
    <div>

    </div>
<? endif; ?>
<?php

$tabControl->EndTab();
$tabControl->End();
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");
?>