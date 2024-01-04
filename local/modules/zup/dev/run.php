<?php

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/local/php_interface/include/Zup/Manager.php");

$config = include_once($_SERVER["DOCUMENT_ROOT"] . "/local/php_interface/include/Zup/config.php");
$manager = new \Zup\Manager($config);

if(isset($_GET['ACTION']) && $_GET['ACTION'] == 'FILES'){

	// todo: очистка доски почета, графика отсутствий и истории состояний
	// $manager->clearHonour();
	// $manager->clearAbsence();
	// $manager->clearStateHistory();

	// todo: загрузка и парсинг исходников для импорта
	$manager->loadFiles();

	// todo: сохранение отдельных списков в csv файлах
	// $manager->saveNewEmployees($out_dir);
	// $manager->saveEmptyEmployees($out_dir);

	// todo: сохранения отрефактаренного файла
	// $manager->saveFiles(true);
	$manager->mergeObjects(72082, true);

	exit();
}

if(isset($_GET['ACTION']) && $_GET['ACTION'] == 'IMPORT'){

	// todo: импорт заранее подготовленного файла
	$manager->importFiles(false, true);

	exit();
}

header("HTTP/1.1 404 Not Found");
exit();