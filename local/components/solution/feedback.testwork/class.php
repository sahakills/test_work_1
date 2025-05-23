<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

//TODO:: добавить кеширование

use Bitrix\Main\Application;
use Bitrix\Main\Engine\ActionFilter\Csrf;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Iblock\PropertyEnumerationTable;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\Loader;
use Bitrix\Main\SystemException;
use Bitrix\Main\UserFieldTable;
use Bitrix\Highloadblock as HL;
use Bitrix\Main\Mail\Event;
use Bitrix\Main\Error;
use Bitrix\Main\Errorable;
use Bitrix\Main\ErrorCollection;

class FeedbackTestWork extends CBitrixComponent implements Controllerable, Errorable
{
    private $eventEmail = 'FEEDBACK_TEST_WORK';
    protected ErrorCollection $errorCollection;

    public function configureActions()
    {
        return [
            'feedbackSend' => [
                'prefilters' => [
                    new Csrf(),
                ],
            ],
        ];
    }

    public function onPrepareComponentParams($arParams)
    {
        $this->errorCollection = new ErrorCollection();
        return $arParams;
    }

    public function getErrorByCode($code)
    {
        return $this->errorCollection->getErrorByCode($code);
    }

    public function getErrors()
    {
        return $this->errorCollection->toArray();
    }

    public function feedbackSendAction()
    {
        $arRequestPost = Application::getInstance()->getContext()->getRequest()->getPostList()->toArray();
        $arRequestFile = Application::getInstance()->getContext()->getRequest()->getFileList()->toArray();
        try {
            $this->saveFeedback($arRequestPost, $arRequestFile);
            $this->sendMail($arRequestPost);
            return true;
        } catch (\Exception $e) {
            $this->errorCollection[] = new Error($e->getMessage());
            return [
                "result" => "Произошла ошибка",
            ];
        }
    }

    private function saveFeedback(array $requestFields, array $requestFile)
    {
        Loader::includeModule('iblock');
        $this->checkFields($requestFields);
        $obElement = new CIBlockElement;
        $arProps = [
            'CATEGORY' => $requestFields['FIELDS']['CATEGORY'],
            'TYPE' => $requestFields['FIELDS']['TYPE'],
            'STORE' => $requestFields['FIELDS']['STORE'],
            'COMMENT' => $requestFields['FIELDS']['COMMENT'],
        ];

        if (!empty($requestFile['FILE'])) {
            $arProps['FILE'] = $this->saveFiles($requestFile);
        }

        if (!empty($requestFields['COMPOUND'])) {
            $arProps['COMPOUND'] = $this->saveCompound($requestFields['COMPOUND']);
        }

        $arFields = [
            'NAME' => $requestFields['FIELDS']['NAME'],
            'IBLOCK_ID' => 16, //TODO::Перейти на code
            'PROPERTY_VALUES' => $arProps
        ];
        if (!$obElement->Add($arFields)) {
            $this->errorCollection[] = new Error('Ошибка при сохранение элемента');
        };
    }

    private function sendMail($arFields)
    {
        $arMailDate = [];
        foreach ($arFields['FIELDS'] as $key => $sValue) {
            $arMailDate[$key] = $sValue;
        }
        if (!empty($arFields['COMPOUND'])) {
            $arMailDate['COMPOUND'] = $arFields['COMPOUND'];
        }
        Event::send([
            $this->eventEmail,
            SITE_ID,
            'C_FIELDS' => $arMailDate
        ]);
    }

    private function checkFields(array $requestFields)
    {

        if (empty($requestFields['FIELDS']['NAME'])) {
            $this->errorCollection[] = new Error('Поле заголвок не задано');
        }

        if (empty($requestFields['FIELDS']['CATEGORY'])) {
            $this->errorCollection[] = new Error('Поле категория не задано');
        }

        if (empty($requestFields['FIELDS']['TYPE'])) {
            $this->errorCollection[] = new Error('Поле вид заявки не задано');
        }
    }

    private function saveFiles(array $arFiles)
    {
        if (!is_array($arFiles['FILE']['name'])) {
            if (is_uploaded_file($arFiles['FILE']['tmp_name'])) {
                $fileId = \CFile::SaveFile($arFiles['FILE'], 'iblock');
                if ($fileId) {
                    return $fileId;
                }
            }
        }
        if (is_array($arFiles['FILE']['name'])) {
            $arResult = [];
            $fileCount = count($arFiles['FILE']['name']);
            for ($i = 0; $i < $fileCount; $i++) {
                $fileData = [
                    'name' => $arFiles['FILE']['name'][$i],
                    'type' => $arFiles['FILE']['type'][$i],
                    'tmp_name' => $arFiles['FILE']['tmp_name'][$i],
                    'error' => $arFiles['FILE']['error'][$i],
                    'size' => $arFiles['FILE']['size'][$i]
                ];
                $fileId = \CFile::SaveFile($fileData, 'feedback');

                if ($fileId) {
                    $arResult[] = $fileId;
                }
            }
            return $arResult;
        }
    }

    private function saveCompound(array $arCompound): array
    {
        Loader::includeModule('highloadblock');
        //TODO::Переделать
        $entity = HL\HighloadBlockTable::compileEntity(2);
        $entityClass = $entity->getDataClass();
        $arResult = [];
        foreach ($arCompound as $arItem) {
            $strXmlId = md5(implode(',' , [$arItem, rand(0, 1000)]));
            $entityClass::add([
                'UF_NAME' => $arItem['NAME'],
                'UF_BRAND' => $arItem['BRAND'],
                'UF_COUNT' => $arItem['COUNT'],
                'UF_CLIENT' => $arItem['CLIENT'],
                'UF_PACK' => $arItem['PACK'],
                'UF_XML_ID' => $strXmlId
            ]);
            $arResult[] = $strXmlId;
        }
        return $arResult;
    }

    public function executeComponent()
    {
        CModule::IncludeModule('iblock');
        $this->GetProperties();
        $this->GetPropertiesUser();
        $this->errorCollection[] = new Error('test');
        $this->arResult['ERRORS'] = $this->errorCollection;
        $this->includeComponentTemplate();
    }

    private function GetProperties()
    {
        foreach ($this->arParams['ENUM_PROPS'] as $sItem) {
            $this->arResult['PROPS'][$sItem] = $this->getEnumPropertyValues($sItem);
        }
    }

    private function GetPropertiesUser()
    {
        //TODO::Переделать
        $userField = UserFieldTable::getList([
            'filter' => ['USER_TYPE_ID' => 'enumeration', 'FIELD_NAME' => 'UF_BRAND'],
        ])->fetch();

        if (!$userField) {
            $this->errorCollection[] = new Error('Пользовательское поле UF_BRAND не найдено');
        }

        $enumList = \CUserFieldEnum::getList([], [
            'USER_FIELD_ID' => $userField['ID'],
        ]);
        while ($enumValue = $enumList->fetch()) {
            $this->arResult['PROPS']['UF_BRAND'][] = $enumValue;
        }
    }

    private function getEnumPropertyValues(string $propertyCode): array
    {
        if (empty($propertyCode) || $this->arParams['IBLOCK_ID'] <= 0) {
            $this->errorCollection[] = new Error("Не заданы обязательные параметры: код свойства или ID инфоблока.");
        }
        // Получаем свойство по коду
        $property = PropertyTable::getList([
            'select' => ['ID'],
            'filter' => [
                'IBLOCK_ID' => $this->arParams['IBLOCK_ID'],
                'CODE' => $propertyCode,
                'ACTIVE' => 'Y'
            ],
            'limit' => 1
        ])->fetch();

        if (!$property) {
            $this->errorCollection[] = new Error("Свойство с кодом '{$propertyCode}' не найдено в инфоблоке ID {$this->arParams['IBLOCK_ID']}.");
        }

        // Получаем значения списка
        $arResult = [];
        $enumResult = PropertyEnumerationTable::getList([
            'select' => ['ID', 'VALUE', 'XML_ID', 'DEF', 'SORT'],
            'filter' => ['PROPERTY_ID' => $property['ID']],
            'order' => ['SORT' => 'ASC']
        ]);

        while ($enum = $enumResult->fetch()) {
            $arResult[] = [
                'ID' => $enum['ID'],
                'VALUE' => $enum['VALUE'],
                'XML_ID' => $enum['XML_ID'],
                'DEF' => $enum['DEF'],
                'SORT' => $enum['SORT'],
            ];
        }

        if (empty($arResult)) {
            $this->errorCollection[] = new Error("У свойства '{$propertyCode}' нет доступных значений.");
        }

        return $arResult;
    }
}