<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arActivityDescription = array(
    "NAME" => GetMessage("BPDDA_DESCR_NAME"),
    "DESCRIPTION" => GetMessage("BPDDA_DESCR_DESCR"),
    "TYPE" => "activity",  // Тип – действие
    "CLASS" => "WriteToLogActivity", // Класс с Activity
    "JSCLASS" => "BizProcActivity",  // Стандартная JS библиотека, которая будет рисовать Activity
    "CATEGORY" => array(
        "ID" => "other", // Activity будет располагаться в категории "Прочее"
    ),
);
?>