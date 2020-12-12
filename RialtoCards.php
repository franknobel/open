<?php


namespace Indexcall\Rialto;


use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\SectionTable;
use Bitrix\Iblock\PropertyEnumerationTable;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\Loader;
use Maximaster\Tools\Orm\Iblock\ElementTable as MElementTable;

/**
 * Класс для работы с сущностью "Заказы для раздела Биржа"
 */
class RialtoCards
{
    public $id = null;
    public $name = null;
    public $sort = null;
    public $code = null;
    public $section = null;
    public $url = null;
    public $description = null;
    public $image = null;
    public $properties = [];

    public static function Delete($id){
        if ($id > 0) {
            Loader::IncludeModule("iblock");
            return \CIBlockElement::Delete($id);
        }
        return false;
    }


    public static function Deactivate($id){
        if ($id > 0) {
            Loader::IncludeModule("iblock");
            $el = new \CIBlockElement;
            $res = $el->Update($id, ['ACTIVE'=>'N']);
            return $res;
        }
        return false;
    }

    private function SetElement($id) {
        if($id > 0) {
            Loader::includeModule('iblock');

            if($info = ElementTable::getList(['select' => ['*', 'URL' => 'IBLOCK.DETAIL_PAGE_URL'], 'filter' => ['ID' => $id]])->fetch()) {
                $this->id = $info['ID'];
                $this->name = $info['NAME'];
                $this->code = $info['CODE'];
                $this->section = $info['IBLOCK_SECTION_ID'];
                $this->sort = $info['SORT'];
                $this->url = \CIBlock::ReplaceDetailUrl($info['URL'], $info, false, 'E');
                $this->description = $info['DETAIL_TEXT'];
                $this->image = $info['PREVIEW_PICTURE'];

                //Получить свойства, старым способом, D7 не выводит множественные свойства должным образом.
                $order = [
                    'PRICE',
                    'PRICE_CURRENCY',
                    'BY_AGREEMENT',
                    'SCORE_OR_SITE',
                    'FILES',
                    'MORE_PHOTO',
                    'SERVICES',
                    'COMPANY',
                    'SPECIALIST',
                    'EMPLOYMENT',
                    'SCHEDULE',
                    'WORK_WEEK',
                    'WORK_TIME',
                    'COUNTRY',
                    'CITY',
                    'ADRES',
                    'EXPERIENCE',
                    'KEY_SKILLS'
                ];
                $dbProperties = \CIBlockElement::getProperty(RIALTO_IBLOCK_ID, $id, array("sort", "asc"), array('CODE' => $order));
                while ($arProperty = $dbProperties->GetNext()) {
                    if ($arProperty['MULTIPLE'] == 'Y') {
                        if (!empty($arProperty['VALUE']))
                            $this->properties[$arProperty['CODE']][] = $arProperty['VALUE'];
                    } else
                        $this->properties[$arProperty['CODE']] = $arProperty['VALUE'];
                }

                return $info['ID'];
            }
        }

        return false;
    }

    private function SetElementWithCode($section, $code) {
        if($section > 0 && $code <> '') {
            Loader::includeModule('iblock');
            if($info = ElementTable::getList(['select' => ['*', 'URL' => 'IBLOCK.DETAIL_PAGE_URL'], 'filter' => ['IBLOCK_ID' => RIALTO_IBLOCK_ID,'IBLOCK_SECTION_ID' => $section, 'CODE' => $code]])->fetch()) {
                $this->id = $info['ID'];
                $this->name = $info['NAME'];
                $this->code = $info['CODE'];
                $this->section = $info['IBLOCK_SECTION_ID'];
                $this->sort = $info['SORT'];
                $this->active_from = $info['ACTIVE_FROM'];
                $this->active_to = $info['ACTIVE_TO'];
                $this->url = \CIBlock::ReplaceDetailUrl($info['URL'], $info, false, 'E');
                $this->description = $info['DETAIL_TEXT'];
                $this->image = $info['PREVIEW_PICTURE'];//\CFile::GetPath($info['PREVIEW_PICTURE']);

                //Получить свойства, старым способом, D7 не выводит множественные свойства должным образом.
                $order = [
                    'PRICE',
                    'PRICE_CURRENCY',
                    'BY_AGREEMENT',
                    'SCORE_OR_SITE',
                    'FILES',
                    'MORE_PHOTO',
                    'SERVICES',
                    'COMPANY',
                    'SPECIALIST',
                    'EMPLOYMENT',
                    'SCHEDULE',
                    'WORK_WEEK',
                    'WORK_TIME',
                    'COUNTRY',
                    'CITY',
                    'ADRES',
                    'EXPERIENCE',
                    'KEY_SKILLS'
                ];
                $dbProperties = \CIBlockElement::getProperty(RIALTO_IBLOCK_ID, $this->id, array("sort", "asc"), array('CODE' => $order));
                while ($arProperty = $dbProperties->GetNext()) {
                    if ($arProperty['MULTIPLE'] == 'Y') {
                        if (!empty($arProperty['VALUE']))
                            $this->properties[$arProperty['CODE']][] = $arProperty['VALUE'];
                    } else
                        $this->properties[$arProperty['CODE']] = $arProperty['VALUE'];
                }

                return $info['ID'];
            }
        }

        return false;
    }

    public static function GetElement ($id) {
        $object = new RialtoCards();
        $object->SetElement($id);
        return $object;
    }
    public static function GetElementByCode ($section , $code) {
        $object = new RialtoCards();
        $object->SetElementWithCode($section , $code);
        return $object;
    }

    public static function AddElement ( $fields ) {
        Loader::IncludeModule("iblock");
        $el = new \CIBlockElement;

        if(!isset($fields['IBLOCK_ID']) || empty($fields['IBLOCK_ID']) )
            $fields['IBLOCK_ID'] = RIALTO_IBLOCK_ID;

        if ($ID = $el->Add($fields))
            return $ID;
        else
            return false;
    }

    public static function UpdateElement ( $id = 0, $fields ) {
        if($id >0) {
            Loader::IncludeModule("iblock");
            $el = new \CIBlockElement;

            if (!isset($fields['IBLOCK_ID']) || empty($fields['IBLOCK_ID']))
                $fields['IBLOCK_ID'] = RIALTO_IBLOCK_ID;

            if ($el->Update($id, $fields))
                return $id;
            else
                return false;
        }else{
            return false;
        }
    }
    //Получить свойство типа ... массивом со всеми данными/
    public static function GetEnumPropList ($enumName) {
        Loader::includeModule('iblock');
        $arProps = [];
        $dbIblockProps = PropertyTable::getList(
            array(
                'select' => array('*'),
                'filter' => array('IBLOCK_ID' => RIALTO_IBLOCK_ID, 'ACTIVE'=>'Y', 'CODE'=>$enumName )
            )
        );

        while ($arIblockProps = $dbIblockProps->fetch()) {
            switch ($arIblockProps["PROPERTY_TYPE"]) {
                case "S": //Строка
                case "N": //Число
                    $arProps[$arIblockProps['CODE']][] = $arIblockProps['VALUE'];
                    break;
                case "L"://Список
                    $enumIterator = PropertyEnumerationTable::getList(
                        array('select' => array('*'),
                            'filter' => array('PROPERTY_ID' => $arIblockProps['ID']),
                            'order' => array('ID' => 'ASC', 'SORT' => 'ASC', 'VALUE' => 'ASC')
                        )
                    );
                    while ($enum = $enumIterator->fetch())
                        $arProps[$arIblockProps['CODE']][] = $enum;
                    break;
                case "F": //Файл
                    //$arProps[$arIblockProps['CODE']][] = $arIblockProps['VALUE'];
                    break;
            }
        }

        return $arProps;
    }

    /* *
     * todo: Переносить на новый класс (Maximaster\Tools\Orm\Iblock\ElementTable) для сортировки и фильтрации на лету.
     */
    public static function getList ($param) {
        $arResultItems = [];
        Loader::includeModule('iblock');
        $arTmpItems = ElementTable::getList($param)->fetchAll();
        foreach($arTmpItems as $element)
            $arResultItems[$element['ID']] = $element;

        return sizeof($arResultItems) > 0 ? $arResultItems : false;
    }
    /* *
     * Используется новый класс (Maximaster\Tools\Orm\Iblock\ElementTable) для сортировки и фильтрации на лету.
     * * @param array $params['select', 'filter','offset','limit','property'];
     *
     */
    public static function getListWithProps ($params, $all = true) {
        $arResultItems = [];
        Loader::includeModule('iblock');

        $arTmp = ElementTable::query();
        if (sizeof($params['select'])) $arTmp->setSelect($params['select']);
        if (sizeof($params['property']))
            foreach ($params['property'] as $property)
                $arTmp->addSelect('PROPERTY_'.$property.'_VALUE', $property);
        if (sizeof($params['filter'])) $arTmp->setFilter($params['filter']);
        if (sizeof($params['offset'])) $arTmp->setOffset($params['offset']);
        if (sizeof($params['limit']))  $arTmp->setLimit($params['limit']);
        if ($all)
            $arResultItems = $arTmp->fetchAll();
        else
            $arResultItems = $arTmp->fetch();

        return sizeof($arResultItems) > 0 ? $arResultItems : false;
    }


}