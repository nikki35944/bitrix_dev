<?php

Bitrix\Main\Loader::registerAutoloadClasses('zup', [

    'zup\Config' => 'dev/config.php',
    'zup\App' => 'classes/general/App.php',
	'zup\Manager' => 'lib/Manager.php',

    'zup\Employee' => 'classes/general/Employee.php',
	'zup\Mysql\EmployeeTable' => 'classes/mysql/EmployeeTable.php'
]);
