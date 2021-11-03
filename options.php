<?php

use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;

$module_id = 'dmbgeo.nextimport';
$module_path = str_ireplace($_SERVER["DOCUMENT_ROOT"], '', __DIR__) . $module_id . '/';
CModule::IncludeModule('main');
CModule::IncludeModule($module_id);
CModule::IncludeModule('iblock');


Loc::loadMessages($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/options.php");
Loc::loadMessages(__FILE__);
if ($APPLICATION->GetGroupRight($module_id) < "S") {
    $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));
}

$request = \Bitrix\Main\HttpApplication::getInstance()->getContext()->getRequest();
//получим инфоблоки пользователей на сайте, чтоб добавить в настройки

$aTabs[] = array(
    'DIV' => "MAIN",
    'TAB' => "Настройки модуля",
    'OPTIONS' => array(),
);

$params[] = 'DMBGEO_NEXTIMPORT_APARTAMENTS_IBLOCK_ID';
$params[] = 'DMBGEO_NEXTIMPORT_PROJECTS_IBLOCK_ID';


if ($request->isPost() && $request['Apply'] && check_bitrix_sessid()) {

    foreach ($params as $param) {
        if (array_key_exists($param, $_POST) === true) {
            Option::set($module_id, $param, is_array($_POST[$param]) ? implode(",", $_POST[$param]) : $_POST[$param]);
        } else {
            Option::set($module_id, $param, "N");
        }
    }
}

$tabControl = new CAdminTabControl('tabControl', $aTabs);

?>
<? $tabControl->Begin(); ?>

<form method='post' action='<? echo $APPLICATION->GetCurPage() ?>?mid=<?= htmlspecialcharsbx($request['mid']) ?>&amp;lang=<?= $request['lang'] ?>' name='DMBGEO_PREFILTERS_settings'>

    <? $n = count($aTabs); ?>
    <? foreach ($aTabs as $key => $aTab) :
        if ($aTab['OPTIONS'] || true) : ?>
            <? $tabControl->BeginNextTab(); ?>
            <? __AdmSettingsDrawList($module_id, $aTab['OPTIONS']); ?>
            <? $DMBGEO_NEXTIMPORT_PROJECTS_IBLOCK_ID = Option::get($module_id, 'DMBGEO_NEXTIMPORT_PROJECTS_IBLOCK_ID'); ?>
            <tr>
                <td><? echo Loc::getMessage('DMBGEO_NEXTIMPORT_PROJECTS_IBLOCK_ID') ?></td>
                <td><? echo GetIBlockDropDownListEx($DMBGEO_NEXTIMPORT_PROJECTS_IBLOCK_ID, 'DMBGEO_NEXTIMPORT_PROJECTS_IBLOCK_TYPE_ID', 'DMBGEO_NEXTIMPORT_PROJECTS_IBLOCK_ID', false); ?></td>
            </tr>
            <? $DMBGEO_NEXTIMPORT_APARTAMENTS_IBLOCK_ID = Option::get($module_id, 'DMBGEO_NEXTIMPORT_APARTAMENTS_IBLOCK_ID'); ?>
            <tr>
                <td><? echo Loc::getMessage('DMBGEO_NEXTIMPORT_APARTAMENTS_IBLOCK_ID') ?></td>
                <td><? echo GetIBlockDropDownListEx($DMBGEO_NEXTIMPORT_APARTAMENTS_IBLOCK_ID, 'DMBGEO_NEXTIMPORT_APARTAMENTS_IBLOCK_TYPE_ID', 'DMBGEO_NEXTIMPORT_APARTAMENTS_IBLOCK_ID', false); ?></td>
            </tr>

        <? endif ?>


    <? endforeach; ?>
    <?

    $tabControl->Buttons(); ?>

    <input type="submit" name="Apply" value="<? echo GetMessage('MAIN_SAVE') ?>">
    <input type="reset" name="reset" value="<? echo GetMessage('MAIN_RESET') ?>">
    <?= bitrix_sessid_post(); ?>
</form>
<? $tabControl->End(); ?>