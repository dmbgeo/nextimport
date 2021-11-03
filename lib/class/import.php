<?php



namespace Dmbgeo\NextImport;

use Bitrix\Main\Config\Option;

class Import
{
    public static $module_id = 'dmbgeo.nextimport';
    public static $iblockFields = array(
        'ID' => 'ID элемента.',
        'CODE' => 'Символьный идентификатор.',
        'EXTERNAL_ID или XML_ID' => 'Внешний идентификатор.',
        'NAME' => 'Название элемента.',
        'IBLOCK_ID' => 'ID информационного блока.',
        'IBLOCK_SECTION_ID' => 'ID группы. Если не задан, то элемент не привязан к группе. Если элемент привязан к нескольким группам, то в этом поле ID одной из них. Эта группа будет основной для элемента, то есть её код или ID будет стоять в URL страницы элемента. По умолчанию содержит привязку к разделу с минимальным ID из массива идентификаторов поля IBLOCK_SECTION.',
        'IBLOCK_SECTION' => 'Массив идентификаторов групп, к которым относится элемент.',
        'IBLOCK_CODE' => 'Символический код инфоблока.',
        'ACTIVE' => 'Флаг активности (Y|N).',
        'ACTIVE_FROM' => 'Дата начала действия элемента.',
        'ACTIVE_TO' => 'Дата окончания действия элемента.',
        'SORT' => 'Порядок сортировки элементов между собой (в пределах одной группы-родителя).',
        'PREVIEW_PICTURE' => 'Код картинки в таблице файлов для предварительного просмотра (анонса).',
        'PREVIEW_TEXT' => 'Предварительное описание (анонс).',
        'PREVIEW_TEXT_TYPE' => 'Тип предварительного описания (text/html).',
        'DETAIL_PICTURE' => 'Код картинки в таблице файлов для детального просмотра.',
        'DETAIL_TEXT' => 'Детальное описание',
        'DETAIL_TEXT_TYPE' => 'Тип детального описания (text/html).',
        'SEARCHABLE_CONTENT' => 'Содержимое для поиска при фильтрации групп. Вычисляется автоматически. Складывается из полей NAME и DESCRIPTION (без html тэгов, если DESCRIPTION_TYPE установлен в html).',
        'DATE_CREATE' => 'Дата создания элемента.',
        'CREATED_BY' => 'Код пользователя, создавшего элемент.',
        'CREATED_USER_NAME' => 'Имя пользователя, создавшего элемент. (доступен только для чтения).',
        'TIMESTAMP_X' => 'Время последнего изменения полей элемента.',
        'MODIFIED_BY' => 'Код пользователя, в последний раз изменившего элемент.',
        'USER_NAME' => 'Имя пользователя, в последний раз изменившего элемент. (доступен только для чтения).',
        'LANG_DIR' => 'Путь к папке сайта. Определяется из параметров информационного блока. Изменяется автоматически. (доступен только для чтения).',
        'LIST_PAGE_URL' => 'Шаблон URL-а к странице для публичного просмотра списка элементов информационного блока. Определяется из параметров информационного блока. Изменяется автоматически. (доступен только для чтения).',
        'DETAIL_PAGE_URL' => 'Шаблон URL-а к странице для детального просмотра элемента. Определяется из параметров информационного блока. Изменяется автоматически. (доступен только для чтения).',
        'SHOW_COUNTER' => 'Количество показов элемента (изменяется при вызове метода CIBlockElement::CounterInc).',
        'SHOW_COUNTER_START' => 'Дата первого показа элемента (изменяется при вызове метода CIBlockElement::CounterInc).',
        'WF_COMMENTS' => 'Комментарий администратора документооборота.',
        'WF_STATUS_ID' => 'Код статуса элемента в документообороте.',
        'LOCK_STATUS' => 'Текущее состояние блокированности на редактирование элемента. Может принимать значения: red - заблокирован, green - доступен для редактирования, yellow - заблокирован текущим пользователем.',
        'TAGS' => 'Теги элемента. Используются для построения облака тегов модулем Поиска.'
    );

    public static function dump_file($message, $var = false)
    {
        if (is_array($message) || is_object($message) || $var) {
            $message = var_export($message, true);
        }
        $log = date("Y-m-d H:i:s") . " => " . $message . "\n";
        $logs_path = __DIR__ . '/logs/';
        global $DB;
        $date = date($DB->DateFormatToPHP("DD.MM.YYYY"), time());
        if (!is_dir($logs_path)) {
            mkdir($logs_path);
        }

        $result = file_put_contents($logs_path . 'logs_' . $date . '.txt', $log, FILE_APPEND);

        return $result;
    }

    public static function getSectionIdByPath($IBLOCK_ID, $path)
    {
        $cache = \Bitrix\Main\Data\Cache::createInstance();
        if ($cache->initCache(36000, $IBLOCK_ID . str_replace('/', '_', $path), 'getSectionIdByPath')) {
            $secton_id = $cache->getVars();
        } elseif ($cache->startDataCache()) {
            $secton_id = null;
            if (\Bitrix\Main\Loader::includeModule('iblock')) {
                $path = trim($path, "/");
                $paths = explode("/", $path);

                if (!empty($paths)) {
                    $path = array_shift($paths);
                    $number_sections = count($paths);
                    $resSection = \CIBlockSection::GetList(
                        array(),
                        array(
                            'GLOBAL_ACTIVE' => 'Y',
                            'ACTIVE' => 'Y',
                            'IBLOCK_ID' => $IBLOCK_ID,
                            'CODE' => $path,
                        ),
                        false,
                        array('ID')
                    );

                    if ($arSection = $resSection->Fetch()) {
                        $secton_id = $arSection['ID'];
                        foreach ($paths as $path) {
                            $resSection = \CIBlockSection::GetList(
                                array(),
                                array(
                                    'GLOBAL_ACTIVE' => 'Y',
                                    'ACTIVE' => 'Y',
                                    'IBLOCK_ID' => $IBLOCK_ID,
                                    'SECTION_ID' => $arSection['ID'],
                                    'CODE' => $path,
                                ),
                                false,
                                array('ID')
                            );
                            if ($arSection = $resSection->Fetch()) {
                                $secton_id = $arSection['ID'];
                            } else {
                                $secton_id = null;
                                break;
                            }
                            $number_sections--;
                        }
                    }
                }
                if ($number_sections > 0) {
                    $secton_id = null;
                }
                $cache->endDataCache($secton_id);
            }
        }
        return $secton_id;
    }


    public static function xlsx_to_array($filename = '', &$logs)
    {
        if (!file_exists($filename) || !is_readable($filename))
            return false;


        $data = array();

        if ($xlsx = \SimpleXLSX::parse($filename)) {
            $PROJECTS_IBLOCK_ID = Option::get(self::$module_id, 'DMBGEO_NEXTIMPORT_PROJECTS_IBLOCK_ID', '');
            $APARTAMENTS_IBLOCK_ID = Option::get(self::$module_id, 'DMBGEO_NEXTIMPORT_APARTAMENTS_IBLOCK_ID', '');
            foreach ($xlsx->sheetNames() as $index => $lang) {
                $headers = array();
                foreach ($xlsx->rows($index) as $key => $row) {
                    $row = array_diff($row, array(''));
                    if ($key === 1) {
                        $headers = $row;
                    } elseif ($key > 1) {
                        $values = array();
                        foreach ($headers as $id => $key) {
                            $values[$key] = $row[$id] ?? "";
                        }

                        $values["LANGUAGE_ID"] = $lang;
                        $values["CODE"] = $values["PROJECT"] . "_" . $values["FLOOR"] . "_" . $values["NUMBER"];
                        $values["PROJECTS_IBLOCK_ID"] = $PROJECTS_IBLOCK_ID;
                        $values["APARTAMENTS_IBLOCK_ID"] = $APARTAMENTS_IBLOCK_ID;
                        $values["FLOOR"] = (int)str_replace(array(" ", "#", "$", "м²"), array("", "", "", ""), $values["FLOOR"]);
                        $values["NUMBER"] = (int)str_replace(array(" ", "#", "$", "м²"), array("", "", "", ""), $values["NUMBER"]);
                        $values["AREA"] = (float)str_replace(array(" ", "#", "$", "м²"), array("", "", "", ""), $values["AREA"]);
                        $values["PRICE"] = (float)str_replace(array(" ", "#", "$", "м²"), array("", "", "", ""), $values["PRICE"]);
                        $values["OLD_PRICE"] = (float)str_replace(array(" ", "#", "$", "м²"), array("", "", "", ""), $values["OLD_PRICE"]);
                        $values["SALE_DATE"] = trim($values["SALE_DATE"]) == 'Y' ? 'Да' : "";

                        $data[] = $values;
                    }
                }
            }
        } else {
            $logs[] = \SimpleXLSX::parseError();
            return false;
        }

        return $data;
    }

    public static function getData($path_file_teams, &$logs)
    {

        $cache = \Bitrix\Main\Data\Cache::createInstance();
        if ($cache->initCache(3600, 'data', 'dmbgeo.nextimport')) {
            $data = $cache->getVars();
        } elseif ($cache->startDataCache()) {


            $data = self::xlsx_to_array($path_file_teams, $logs);

            if (!is_array($data)) {
                $logs[] = 'Ошибка файла импорта';
            }

            $cache->endDataCache($data);
        }

        return $data;
    }

    public static function run($data, &$logs)
    {

        if (\Bitrix\Main\Loader::includeModule('iblock')) {
            $IDS = array();
            $projects = array();

            foreach ($data as $apartament) {

                if (!isset($projects[$apartament['LANGUAGE_ID']][$apartament['PROJECT']])) {
                    $projects[$apartament['LANGUAGE_ID']][$apartament['PROJECT']] = self::getIdProject($apartament);
                }

                if ($projects[$apartament['LANGUAGE_ID']][$apartament['PROJECT']] == null) {
                    $logs[] = 'не существет проект' . $apartament['PROJECT'] . ' по пути ' . '/' . $apartament['LANGUAGE_ID'] . '/' . $apartament['PROJECT'] . '/';
                    continue;
                }

                $apartament['PROJECT'] = $projects[$apartament['LANGUAGE_ID']][$apartament['PROJECT']];



                $apartament['SECTION_ID'] = self::getIdSection($apartament, $logs);

                $apartament['DETAIL_PICTURE'] = self::getPathImage($apartament['DETAIL_PICTURE']);


                if ($ID = self::getIdApartament($apartament, $logs)) {
                    $IDS[] = $ID;
                }

                unset($apartament);
            }
        }

        return $IDS;
    }

    public static function getIdSection($apartament, &$logs = array())
    {

        if (!$SECTION_ID = self::getSectionIdByPath($apartament['APARTAMENTS_IBLOCK_ID'], '/' . $apartament['LANGUAGE_ID'] . '/' . $apartament['PROJECT']['CODE'])) {
            $PARENT_SECTION_ID = self::getSectionIdByPath($apartament['APARTAMENTS_IBLOCK_ID'], '/' . $apartament['LANGUAGE_ID'] . '/');

            $bs = new \CIBlockSection;
            $SECTION_ID = $bs->Add(array(
                'IBLOCK_ID' => $apartament['APARTAMENTS_IBLOCK_ID'],
                'NAME' => $apartament['PROJECT']['NAME'],
                "IBLOCK_SECTION_ID" => $PARENT_SECTION_ID,
                'CODE' => $apartament['PROJECT']['CODE'],
                'ACTIVE' => 'Y'
            ));
            $logs[] = 'Добавлен раздел — [' . $SECTION_ID . '] ' . '/' . $apartament['LANGUAGE_ID'] . '/' . $apartament['PROJECT']['CODE'];
        }

        return $SECTION_ID;
    }

    public static function getIdEnum($IBLOCK_ID, $CODE, $VALUE)
    {
        if ($VALUE) {
            $property_enums = \CIBlockPropertyEnum::GetList(array(), array("IBLOCK_ID" => $IBLOCK_ID, "CODE" => $CODE, 'VALUE' => $VALUE));
            if ($enum_fields = $property_enums->GetNext()) {
                return $enum_fields["ID"];
            } elseif ($property = \CIBlockProperty::GetList(
                [],
                [
                    'IBLOCK_ID' => $IBLOCK_ID,
                    'CODE' => $CODE
                ]
            )->Fetch()) {
                $ibpenum = new \CIBlockPropertyEnum;
                if ($PropID = $ibpenum->Add(array('PROPERTY_ID' => $property['ID'], 'VALUE' => $VALUE))) {
                    return  $PropID;
                }
            }
        }
        return null;
    }


    public static function getPathImage($url)
    {
        $urlParse = parse_url($url);

        if ($urlParse) {
            if (isset($urlParse['host']) && $urlParse['host'] != $_SERVER['HTTP_HOST']) {
                $path = $_SERVER['DOCUMENT_ROOT'] . "/" . \COption::GetOptionString("main", "upload_dir", "upload") . '/images/';
                if (!is_dir($path)) {
                    mkdir($path);
                }

                file_put_contents($path . basename($urlParse), file_get_contents($urlParse));

                return $path . basename($urlParse);
            } elseif (isset($urlParse['path']) && is_file($_SERVER['DOCUMENT_ROOT'] . $urlParse['path'])) {
                return $_SERVER['DOCUMENT_ROOT'] . $urlParse['path'];
            }
        }

        return $url;
    }




    public static function getIdApartament($apartament, &$logs = array())
    {

        global $USER;
        $el = new \CIBlockElement;
        $PROP = array();

        if (!$PROP['STATUS'] = self::getIdEnum($apartament['APARTAMENTS_IBLOCK_ID'], 'STATUS', $apartament['STATUS'])) {
            unset($PROP['STATUS']);
        }
        if (!$PROP['VIEW'] = self::getIdEnum($apartament['APARTAMENTS_IBLOCK_ID'], 'VIEW', $apartament['VIEW'])) {
            unset($PROP['VIEW']);
        }

        if (!$PROP['ROOMS'] = self::getIdEnum($apartament['APARTAMENTS_IBLOCK_ID'], 'ROOMS', $apartament['ROOMS'])) {
            unset($PROP['ROOMS']);
        }

        if (!$PROP['SALE_DATE'] = self::getIdEnum($apartament['APARTAMENTS_IBLOCK_ID'], 'SALE_DATE', $apartament['SALE_DATE'])) {
            $PROP['SALE_DATE'] = '';
        }


        $PROP['POINTS'] = $apartament['POINTS'];
        $PROP['NUMBER'] = $apartament['NUMBER'];
        $PROP['AREA'] = $apartament['AREA'];
        $PROP['PROJECT'] = $apartament['PROJECT']["ID"];
        $PROP['INSTALLMENT_PLAN'] = $apartament['INSTALLMENT_PLAN'];
        $PROP['PRICE'] = array('VALUE' => $apartament['PRICE'], 'DESCRIPTION' => $apartament['OLD_PRICE']);
        $PROP['FLOOR'] = $apartament['FLOOR'];


        $arLoadProductArray = array(
            "MODIFIED_BY" => $USER->GetID(),
            "IBLOCK_SECTION_ID" => $apartament['SECTION_ID'],
            "IBLOCK_ID" => $apartament['APARTAMENTS_IBLOCK_ID'],
            "PROPERTY_VALUES" => $PROP,
            "NAME" => $apartament['NAME'],
            "ACTIVE" => $apartament['ACTIVE'] == 'Да' ? "Y" : "N",
            'CODE' => $apartament['CODE']
        );


        $DETAIL_PICTURE = \CFile::MakeFileArray($apartament['DETAIL_PICTURE']);
        if (is_array($DETAIL_PICTURE) && $DETAIL_PICTURE['size'] > 0) {
            $arLoadProductArray['DETAIL_PICTURE'] = $DETAIL_PICTURE;
        } else {
            $arLoadProductArray['ACTIVE'] = "N";
            $logs[] = 'Загружаемый файл (' . $apartament['DETAIL_PICTURE'] . ') не корректен для квартиры ' . $apartament['LANGUAGE_ID'] . '/' . $apartament['PROJECT']['CODE'] . '/' . $apartament['CODE'];
        }
        $APARTAMENT_ID = false;
        $apartamentDB = \CIBlockElement::GetList(array(), array('IBLOCK_ID' => $apartament['APARTAMENTS_IBLOCK_ID'], 'CODE' => $apartament['CODE'], "SECTION_ID" => $apartament['SECTION_ID']));
        if ($arApartament = $apartamentDB->Fetch()) {
            if ($el->update($arApartament['ID'], $arLoadProductArray, false, true, true) !== false) {
                $logs[] = 'Обновлена квартира из проекта ' . $apartament['PROJECT']['NAME'] . ' по пути  /' . $apartament['LANGUAGE_ID'] . '/' . $apartament['PROJECT']['CODE'] . '/ — [' . $arApartament['ID'] . '] ' . $apartament['NAME'];
                return $arApartament['ID'];
            } else {
                $logs[] = 'Ошибка квартиры ' . $apartament['LANGUAGE_ID'] . '/' . $apartament['CODE'] . ':' . $el->LAST_ERROR;
            }
        } else {
            $APARTAMENT_ID = $el->Add($arLoadProductArray, false, true, true);
            if ($APARTAMENT_ID !== false) {
                $logs[] = 'Импортирована квартира из проекта ' . $apartament['PROJECT']['NAME'] . ' по пути  /' . $apartament['LANGUAGE_ID'] . '/' . $apartament['PROJECT']['CODE'] . '/ — [' . $APARTAMENT_ID . '] ' . $apartament['NAME'];
                return $APARTAMENT_ID;
            } else {
                $logs[] = 'Ошибка квартиры ' . $apartament['LANGUAGE_ID'] . '/' . $apartament['CODE'] . ':' . $el->LAST_ERROR;
            }
        }
        // unlink($apartament['DETAIL_PICTURE']);
        return false;
    }

    public static function getIdProject($apartament, &$logs = array())
    {
        global $USER;
        $PROJECT = null;

        if ($SECTION_ID = self::getSectionIdByPath($apartament['PROJECTS_IBLOCK_ID'], '/' . $apartament['LANGUAGE_ID'])) {
            $projectDB = \CIBlockElement::GetList(array(), array('IBLOCK_ID' => $apartament['PROJECTS_IBLOCK_ID'], 'IBLOCK_SECTION_ID' => $SECTION_ID, 'CODE' => $apartament['PROJECT']));
            if ($arProject = $projectDB->Fetch()) {
                $PROJECT = array(
                    'ID' => $arProject['ID'],
                    'NAME' => $arProject['NAME'],
                    'CODE' => $arProject['CODE'],
                    'IBLOCK_SECTION_ID' => $arProject['IBLOCK_SECTION_ID']
                );
            }
        }

        return $PROJECT;
    }

    public static function clearCache()
    {
        $cache = \Bitrix\Main\Data\Cache::createInstance();
        $cache->clean('data', 'dmbgeo.nextimport');
    }

    public static function clearLogs()
    {
        $cache = \Bitrix\Main\Data\Cache::createInstance();
        $cache->clean('logs', 'dmbgeo.nextimport');
    }


    public static function setLogs($logs)
    {
        self::clearLogs();
        $cache = \Bitrix\Main\Data\Cache::createInstance();
        if ($cache->initCache(36000, 'logs', 'dmbgeo.nextimport')) {
            $logs = $cache->getVars();
        } elseif ($cache->startDataCache()) {
            $cache->endDataCache($logs);
        }
    }

    public static function getLogs()
    {

        $cache = \Bitrix\Main\Data\Cache::createInstance();
        if ($cache->initCache(36000, 'logs', 'dmbgeo.nextimport')) {
            $logs = $cache->getVars();
        } else {
            $logs = array();
        }
        return $logs;
    }
}
