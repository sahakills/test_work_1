<?
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
define('TEMPLATE', 'test');
$APPLICATION->SetTitle('Тестовое задание масло март');
?>

<?php
    $APPLICATION->IncludeComponent(
        'solution:feedback.testwork',
        '',
        [
            'IBLOCK_ID' => '16',
            'HL_BLOCK' => 2,
            'ENUM_PROPS' => [
                'CATEGORY',
                'TYPE',
                'STORE',
            ],
        ]
    );
?>
<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>