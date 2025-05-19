<?php
use Bitrix\Main\Loader;
use Bitrix\Iblock\IblockTable;
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

Loader::includeModule('highloadblock');
Loader::includeModule('iblock');
$arResult = [
    'typeIblock',
    'iblock'
];
// Создаем тип инфоблока
$iblockType = new CIBlockType;
$iblockTypeFields = [
    'ID' => 'feedback',
    'SECTIONS' => 'Y',
    'LANG' => [
        'ru' => [
            'NAME' => 'Заявки',
            'SECTION_NAME' => 'Разделы',
            'ELEMENT_NAME' => 'Элементы'
        ],
        'en' => [
            'NAME' => 'Feedback',
            'SECTION_NAME' => 'Sections',
            'ELEMENT_NAME' => 'Elements'
        ],
    ],
];
$iblockTypeResult = CIBlockType::GetByID($iblockTypeFields['ID'])->Fetch();
$arResult['typeIblock'] = $iblockTypeResult['ID'];
if(!$iblockTypeResult) {
    echo 'Создаем типа инфоблок..'.PHP_EOL;
    $arResult['typeIblock'] = $iblockType->Add($iblockTypeFields);
}

// Создаем инфоблок
$iblock = new CIBlock;
$iblockFields = [
    'ACTIVE' => 'Y',
    'NAME' => 'Заявки',
    'CODE' => 'FeedbackTest1',
    'API_CODE' => 'FeedbackTest1',
    'IBLOCK_TYPE_ID' => $arResult['typeIblock'],
    'SITE_ID' => array(SITE_ID),
    'VERSION' => 2,
];
$rsIblockResult = IblockTable::getList([
    'filter' => [
        'CODE' => $iblockFields['CODE']
    ],
    'select' => [
        'ID'
    ]
])->fetch()['ID'];
$arResult['iblock'] = $rsIblockResult;
if (empty($rsIblockResult)) {
    echo 'Создаем инфоблок..'.PHP_EOL;
    $arResult['iblock'] = $iblock->Add($iblockFields);
}

//создаем свойства инфоблока
$oPropsIblockResult = CIBlockProperty::GetList(Array("sort"=>"asc", "name"=>"asc"), Array( "IBLOCK_ID"=>$arResult['iblock']));
while ($arProp = $oPropsIblockResult->Fetch()) {
    $arPropsIblockResult[] = $arProp;
}
$arPropsIblock = [
    [
        'NAME' => 'Категория',
        'CODE' => 'CATEGORY',
        'PROPERTY_TYPE' => 'L',
        'VALUES' => [
            'Масла',
            'автохимия',
            'фильтры',
            'Автoаксессуары',
            'обогреватели',
            'запчасти',
            'сопутствующие товары',
            'Шины',
            'диски'
        ],
    ],
    [
        'NAME' => 'Вид заявок',
        'CODE' => 'TYPE',
        'PROPERTY_TYPE' => 'L',
        'VALUES' => [
            'Запрос цены и сроков поставки',
            'Пополнение складов',
        ],
    ],
    [
        'NAME' => 'Склад',
        'CODE' => 'STORE',
        'PROPERTY_TYPE' => 'L',
        'VALUES' => [
            'Склад 1',
            'Склад 2',
        ],
    ],
    [
        'NAME' => 'Состав заявки',
        'CODE' => 'COMPOUND',
        'PROPERTY_TYPE' => 'S',
        'MULTIPLE' => 'Y',
        'USER_TYPE' => 'directory',
        'USER_TYPE_SETTINGS' => [
            'TABLE_NAME' => 'b_hlbd_sostavzayavki',
        ]
    ],
    [
        'NAME' => 'Комментарий',
        'CODE' => 'COMMENT',
        'PROPERTY_TYPE' => 'S',
        'USER_TYPE' => 'HTML',
        'USER_TYPE_SETTINGS' => [
            'height' => 200
        ]
    ],
    [
        'NAME' => 'Файл',
        'CODE' => 'FILE',
        'PROPERTY_TYPE' => 'F',
        'MULTIPLE' => 'Y'
    ]
];
foreach ($arPropsIblock as $arProp) {
    //проверяем свойства
    if (!empty($arPropsIblockResult)) {
        if (!in_array($arProp['CODE'], array_column($arPropsIblockResult, 'CODE'))) {
            $oProperty = new CIBlockProperty;
            $arPropFields = [
                'NAME' => $arProp['NAME'],
                'CODE' => $arProp['CODE'],
                'IBLOCK_ID' => $arResult['iblock'],
                'PROPERTY_TYPE' => $arProp['PROPERTY_TYPE'],
                'ACTIVE' => 'Y',
                'SORT' => 500,
                'MULTIPLE' => $arProp['MULTIPLE'] ?? 'N',
                'USER_TYPE' => $arProp['USER_TYPE'] ?? '',
                'USER_TYPE_SETTINGS' => $arProp['USER_TYPE_SETTINGS'] ?? false,
                'LIST_TYPE' => 'L'
            ];

            if ($arProp['PROPERTY_TYPE'] === 'L' && !empty($arProp['VALUES'])) {
                $arPropFields['VALUES'] = [];
                foreach ($arProp['VALUES'] as $value) {
                    $arPropFields['VALUES'][] = [
                        'VALUE' => $value,
                        'DEF' => 'N',
                        'SORT' => 500,
                    ];
                }
            }

            if (!$oProperty->Add($arPropFields)) {
                throw new \Exception("Ошибка при добавлении свойства {$arPropFields['CODE']}: " . $oProperty->LAST_ERROR);
            }
        }
        echo "Свойство {$arProp["CODE"]} уже есть".PHP_EOL;
    }
}


echo '<pre>';
print_r($arPropsIblockResult);
echo '</pre>';