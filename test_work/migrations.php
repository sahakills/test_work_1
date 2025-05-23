<?php

use Bitrix\Main\Loader;
use Bitrix\Iblock\IblockTable;
use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\UserFieldTable;
use Bitrix\Main\Mail\EventMessageTable;
use Bitrix\Main\Mail\EventTypeTable;

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
if (!$iblockTypeResult) {
    echo 'Создаем типа инфоблок..' . PHP_EOL;
    $arResult['typeIblock'] = $iblockType->Add($iblockTypeFields);
}

// Создаем инфоблок
$iblock = new CIBlock;
$iblockFields = [
    'ACTIVE' => 'Y',
    'NAME' => 'Заявки',
    'CODE' => 'FeedbackTest',
    'API_CODE' => 'FeedbackTest',
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
    echo 'Создаем инфоблок..' . PHP_EOL;
    $arResult['iblock'] = $iblock->Add($iblockFields);
}

//создаем свойства инфоблока
$oPropsIblockResult = CIBlockProperty::GetList(array("sort" => "asc", "name" => "asc"), array("IBLOCK_ID" => $arResult['iblock']));
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
            echo "Создано свойство {$arProp["CODE"]}";
        }
        echo "Свойство {$arProp["CODE"]} уже есть" . PHP_EOL;
    }
}
Loader::IncludeModule("highloadblock");
$arHlBlockBlock = [
    'NAME' => 'Sostavzayavki1',
    'TABLE_NAME' => 'sostavzayavki'
];
$rsHlBlock = HighloadBlockTable::getList([
    'filter' => [
        '=NAME' => $arHlBlockBlock['NAME']
    ],
    'select' => [
        'ID'
    ]
])->fetch();
$arResult['hlBlock'] = $rsHlBlock['ID'];
if (!$rsHlBlock) {
    $hlblock = HighloadBlockTable::add($arHlBlockBlock);
    if (!$hlblock->isSuccess()) {
        throw new \Exception(implode('; ', $hlblock->getErrorMessages()));
    }
    $arResult['hlBlock'] = $hlblock->getId();
    //Добавление пользовательских полей
    $userTypeEntity = new CUserTypeEntity();

    $arUserFields = [
        [
            'ENTITY_ID' => 'HLBLOCK_',
            'FIELD_NAME' => 'UF_COUNT',
            'USER_TYPE_ID' => 'string',
            'XML_ID' => '',
            'SORT' => 100,
            'MULTIPLE' => 'N',
            'MANDATORY' => 'N',
            'SHOW_FILTER' => 'N',
            'SHOW_IN_LIST' => 'Y',
            'EDIT_IN_LIST' => 'Y',
            'IS_SEARCHABLE' => 'N',
            'SETTINGS' => [
                'SIZE' => 20,
                'ROWS' => 1,
                'REGEXP' => '',
                'MIN_LENGTH' => 0,
                'MAX_LENGTH' => 0,
                'DEFAULT_VALUE' => '',
            ],
            'EDIT_FORM_LABEL' => ['ru' => 'Колличество', 'en' => 'Count'],
            'LIST_COLUMN_LABEL' => ['ru' => 'Колличество', 'en' => ''],
            'LIST_FILTER_LABEL' => ['ru' => 'Колличество', 'en' => ''],
        ],
        [
            'ENTITY_ID' => 'HLBLOCK_',
            'FIELD_NAME' => 'UF_PACK',
            'USER_TYPE_ID' => 'string',
            'XML_ID' => '',
            'SORT' => 100,
            'MULTIPLE' => 'N',
            'MANDATORY' => 'N',
            'SHOW_FILTER' => 'N',
            'SHOW_IN_LIST' => 'Y',
            'EDIT_IN_LIST' => 'Y',
            'IS_SEARCHABLE' => 'N',
            'SETTINGS' => [
                'SIZE' => 20,
                'ROWS' => 1,
                'REGEXP' => '',
                'MIN_LENGTH' => 0,
                'MAX_LENGTH' => 0,
                'DEFAULT_VALUE' => '',
            ],
            'EDIT_FORM_LABEL' => ['ru' => 'Фасовка', 'en' => 'pack'],
            'LIST_COLUMN_LABEL' => ['ru' => 'Фасовка', 'en' => 'pack'],
            'LIST_FILTER_LABEL' => ['ru' => 'Фасовка', 'en' => 'pack'],
        ],
        [
            'ENTITY_ID' => 'HLBLOCK_',
            'FIELD_NAME' => 'UF_CLIENT',
            'USER_TYPE_ID' => 'string',
            'XML_ID' => '',
            'SORT' => 100,
            'MULTIPLE' => 'N',
            'MANDATORY' => 'N',
            'SHOW_FILTER' => 'N',
            'SHOW_IN_LIST' => 'Y',
            'EDIT_IN_LIST' => 'Y',
            'IS_SEARCHABLE' => 'N',
            'SETTINGS' => [
                'SIZE' => 20,
                'ROWS' => 1,
                'REGEXP' => '',
                'MIN_LENGTH' => 0,
                'MAX_LENGTH' => 0,
                'DEFAULT_VALUE' => '',
            ],
            'EDIT_FORM_LABEL' => ['ru' => 'Клиент', 'en' => 'Client'],
            'LIST_COLUMN_LABEL' => ['ru' => 'Клиент', 'en' => 'Client'],
            'LIST_FILTER_LABEL' => ['ru' => 'Клиент', 'en' => 'Client'],
        ],
        [
            'ENTITY_ID' => 'HLBLOCK_',
            'FIELD_NAME' => 'UF_BRAND',
            'USER_TYPE_ID' => 'enumeration',
            'XML_ID' => '',
            'SORT' => 100,
            'MULTIPLE' => 'N',
            'MANDATORY' => 'N',
            'SHOW_FILTER' => 'N',
            'SHOW_IN_LIST' => 'Y',
            'EDIT_IN_LIST' => 'Y',
            'IS_SEARCHABLE' => 'N',
            'SETTINGS' => [
                'DISPLAY' => 'LIST',
                'LIST_HEIGHT' => 1,
                'CAPTION_NO_VALUE' => '',
                'SHOW_NO_VALUE' => 'Y',
            ],
            'EDIT_FORM_LABEL' => ['ru' => 'Бренд', 'en' => 'brend'],
            'VALUES' => [
                'n0' => [
                    'VALUE' => 'Бренд 1',
                    'DEF' => 'N',
                    'SORT' => 500,
                ],
                'n1' => [
                    'VALUE' => 'Бренд 2',
                    'DEF' => 'N',
                    'SORT' => 500,
                ],
                'n2' => [
                    'VALUE' => 'Бренд 3',
                    'DEF' => 'N',
                    'SORT' => 500,
                ],
            ]
        ],
        [
            'ENTITY_ID' => 'HLBLOCK_',
            'FIELD_NAME' => 'UF_NAME',
            'USER_TYPE_ID' => 'string',
            'XML_ID' => '',
            'SORT' => 100,
            'MULTIPLE' => 'N',
            'MANDATORY' => 'N',
            'SHOW_FILTER' => 'N',
            'SHOW_IN_LIST' => 'Y',
            'EDIT_IN_LIST' => 'Y',
            'IS_SEARCHABLE' => 'N',
            'SETTINGS' => [
                'SIZE' => 20,
                'ROWS' => 1,
                'REGEXP' => '',
                'MIN_LENGTH' => 0,
                'MAX_LENGTH' => 0,
                'DEFAULT_VALUE' => '',
            ],
            'EDIT_FORM_LABEL' => ['ru' => 'Наименование', 'en' => 'Name'],
        ],
        [
            'ENTITY_ID' => 'HLBLOCK_',
            'FIELD_NAME' => 'UF_DESCRIPTION',
            'USER_TYPE_ID' => 'string',
            'XML_ID' => '',
            'SORT' => 100,
            'MULTIPLE' => 'N',
            'MANDATORY' => 'N',
            'SHOW_FILTER' => 'N',
            'SHOW_IN_LIST' => 'Y',
            'EDIT_IN_LIST' => 'Y',
            'IS_SEARCHABLE' => 'N',
            'SETTINGS' => [
                'SIZE' => 20,
                'ROWS' => 1,
                'REGEXP' => '',
                'MIN_LENGTH' => 0,
                'MAX_LENGTH' => 0,
                'DEFAULT_VALUE' => '',
            ],
            'EDIT_FORM_LABEL' => ['ru' => 'Описание'],
        ],
        [
            'ENTITY_ID' => 'HLBLOCK_',
            'FIELD_NAME' => 'UF_FULL_DESCRIPTION',
            'USER_TYPE_ID' => 'string',
            'XML_ID' => '',
            'SORT' => 100,
            'MULTIPLE' => 'N',
            'MANDATORY' => 'N',
            'SHOW_FILTER' => 'N',
            'SHOW_IN_LIST' => 'Y',
            'EDIT_IN_LIST' => 'Y',
            'IS_SEARCHABLE' => 'N',
            'SETTINGS' => [
                'SIZE' => 20,
                'ROWS' => 1,
                'REGEXP' => '',
                'MIN_LENGTH' => 0,
                'MAX_LENGTH' => 0,
                'DEFAULT_VALUE' => '',
            ],
            'EDIT_FORM_LABEL' => ['ru' => 'Полное описание'],
        ],
        [
            'ENTITY_ID' => 'HLBLOCK_',
            'FIELD_NAME' => 'UF_XML_ID',
            'USER_TYPE_ID' => 'string',
            'XML_ID' => '',
            'SORT' => 100,
            'MULTIPLE' => 'N',
            'MANDATORY' => 'N',
            'SHOW_FILTER' => 'N',
            'SHOW_IN_LIST' => 'Y',
            'EDIT_IN_LIST' => 'Y',
            'IS_SEARCHABLE' => 'N',
            'SETTINGS' => [
                'SIZE' => 20,
                'ROWS' => 1,
                'REGEXP' => '',
                'MIN_LENGTH' => 0,
                'MAX_LENGTH' => 0,
                'DEFAULT_VALUE' => '',
            ],
            'EDIT_FORM_LABEL' => ['ru' => 'XML_ID', 'en' => 'XML_ID'],
        ],
        [
            'ENTITY_ID' => 'HLBLOCK_',
            'FIELD_NAME' => 'UF_SORT',
            'USER_TYPE_ID' => 'integer',
            'XML_ID' => '',
            'SORT' => 300,
            'MULTIPLE' => 'N',
            'MANDATORY' => 'N',
            'SHOW_FILTER' => 'N',
            'SHOW_IN_LIST' => 'Y',
            'EDIT_IN_LIST' => 'Y',
            'IS_SEARCHABLE' => 'N',
            'SETTINGS' => [
                'SIZE' => 20,
                'MIN_VALUE' => 0,
                'MAX_VALUE' => 0,
                'DEFAULT_VALUE' => '',
            ],
            'EDIT_FORM_LABEL' => ['ru' => 'Сортировка'],
        ]
    ];
    $arUserFieldsHl = UserFieldTable::getList([
        'filter' => [
            'ENTITY_ID' => 'HLBLOCK_'.$arResult['hlBlock']
        ]
    ])->fetchAll();
    foreach ($arUserFields as $arValue) {
        $arValue['ENTITY_ID'] .= $arResult['hlBlock'];
        if (!in_array( $arValue['ENTITY_ID'], array_column($arUserFieldsHl, 'FIELD_NAME'))) {
            if ($intUserTypeEntity = $userTypeEntity->Add($arValue)) {
                if ($arValue['USER_TYPE_ID'] === 'enumeration' && !empty($arValue['VALUES'])) {
                    $arUserFieldsEnum = new CUserFieldEnum();
                    $arUserFieldsEnum->SetEnumValues($intUserTypeEntity, $arValue['VALUES']);
                }
            }
        }
    }
}
// Параметры
$eventName = 'FEEDBACK_TEST_WORK';

//Создание типа почтового события
$eventTypeResult = CEventType::GetList(['EVENT_NAME' => $eventName]);
if (!$eventTypeResult->Fetch()) {
    $et = new CEventType;
    $etId = $et->Add([
        'LID' => 'ru',
        'EVENT_NAME' => $eventName,
        'NAME' => 'Заявка с сайта',
        'DESCRIPTION' => '#EMAIL_TO# - Email получателя\n#MESSAGE# - Текст сообщения',
        'EVENT_TYPE' => 'email'
    ]);
    if ($etId) {
        $arResult['MAIL_EVENT_TYPE'] = $eventName;
    } else {
        echo 'Ошибка при создании типа события\n';
        return;
    }
} else {
    $arResult['MAIL_EVENT_TYPE'] = $eventName;
}

//Создание шаблона почтового события
$eventMessageResult = CEventMessage::GetList('ID', 'ASC', ['EVENT_NAME' => $eventName]);
if (!$eventMessageResult->Fetch()) {
    $em = new CEventMessage;
    $templateId = $em->Add([
        'ACTIVE' => 'Y',
        'EVENT_NAME' => $eventName,
        'LID' => [SITE_ID],
        'EMAIL_FROM' => '#DEFAULT_EMAIL_FROM#',
        'EMAIL_TO' => '#EMAIL_TO#',
        'SUBJECT' => 'Новое сообщение с сайта',
        'BODY_TYPE' => 'text',
        'MESSAGE' => "Контактные данные #FIELDS# состав заявки #COMPOUND#" ,
    ]);

    if ($templateId) {
        $arResult['MAIL_MESSAGE'] = $templateId;
    } else {
        echo "Ошибка при создании шаблона события: " . $em->LAST_ERROR . "\n";
    }
} else {
    echo "Шаблон для '$eventName' уже существует\n";
}
echo '<pre>';
print_r($arResult);
echo '</pre>';