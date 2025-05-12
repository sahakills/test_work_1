<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die(); ?>
<?php

use Bitrix\Main\Page\Asset;

$this->setFrameMode(true);
Asset::getInstance()->addCss('/bitrix/css/main/bootstrap_v4/bootstrap.min.css');
Asset::getInstance()->addJs('/bitrix/js/main/jquery/jquery-3.6.0.min.js');
?>
<div class="container">
    <form name="feedback" enctype="multipart/form-data" action="<?= $APPLICATION->GetCurDir() ?>">
        <h4>Новая заявка</h4>

        <div class="form-group">
            <label for="title">Заголовок заявки</label>
            <input type="text" class="form-control" name="FIELDS[NAME]">
        </div>
        <? if (!empty($arResult['PROPS']['CATEGORY'])): ?>
            <div class="form-section">
                <label>Категория</label>
                <? foreach ($arResult['PROPS']['CATEGORY'] as $key => $arItem): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="FIELDS[CATEGORY]" id="cat<?= $key ?>"
                               value="<?= $arItem['ID'] ?>">
                        <label class="form-check-label" for="cat<?= $key ?>"><?= $arItem['VALUE'] ?></label>
                    </div>
                <? endforeach; ?>
            </div>
        <? endif; ?>
        <? if (!empty($arResult['PROPS']['TYPE'])): ?>
            <div class="form-section">
                <label>Вид заявки</label>
                <? foreach ($arResult['PROPS']['TYPE'] as $key => $arItem): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="FIELDS[TYPE]" id="req<?= $key ?>"
                               value="<?= $arItem['ID'] ?>">
                        <label class="form-check-label" for="req<?= $key ?>"><?= $arItem['VALUE'] ?></label>
                    </div>
                <? endforeach; ?>
            </div>
        <? endif; ?>
        <? if (!empty($arResult['PROPS']['STORE'])): ?>
            <div class="form-group">
                <label for="warehouse">Склад поставки</label>
                <select class="form-control" name="FIELDS[STORE]" id="warehouse">
                    <option selected disabled>(выберите склад поставки)</option>
                    <? foreach ($arResult['PROPS']['STORE'] as $key => $arItem): ?>
                        <option value="<?= $arItem['ID'] ?>"><?= $arItem['VALUE'] ?></option>
                    <? endforeach; ?>
                </select>
            </div>
        <? endif; ?>
        <h5>Состав заявки</h5>
        <div id="requestItems">
            <div class="form-row align-items-end mb-2 request-item">
                <div class="col-md-2">
                    <label>Бренд</label>
                    <select class="form-control" name="COMPOUND[0][BRAND]">
                        <? foreach ($arResult['PROPS']['UF_BRAND'] as $key => $arItem): ?>
                            <option value="<?= $arItem['ID'] ?>"><?= $arItem['VALUE'] ?></option>
                        <? endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Наименование</label>
                    <input type="text" name="COMPOUND[0][NAME]" class="form-control">
                </div>
                <div class="col-md-2">
                    <label>Количество</label>
                    <input type="number" name="COMPOUND[0][COUNT]" class="form-control">
                </div>
                <div class="col-md-2">
                    <label>Фасовка</label>
                    <input type="text" name="COMPOUND[0][PACK]" class="form-control">
                </div>
                <div class="col-md-2">
                    <label>Клиент</label>
                    <input type="text" name="COMPOUND[0][CLIENT]" class="form-control">
                </div>
                <div class="col-md-2 plus-minus-buttons">
                    <button class="btn btn-success btn-sm mr-1 add-row">+</button>
                    <button class="btn btn-danger btn-sm remove-row">−</button>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label>Загрузить файл</label>
            <input type="file" accept="image/png, image/jpeg"   multiple name="FILE[]" class="form-control-file">
        </div>

        <div class="form-group">
            <label>Комментарий</label>
            <textarea class="form-control" name="FIELDS[COMMENT]" rows="3"></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Отправить</button>
    </form>
    <div class="feedback-answer justify-content-center align-content-center">
        Ваша заявка отправлена
    </div>
</div>