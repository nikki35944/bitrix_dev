<?php

namespace Zup;

use \Bitrix\Main\IO;
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Config as Conf;
use \Bitrix\Main\Config\Option;
use \Bitrix\Main\Loader;
use \Bitrix\Main\Entity\Base;
use \Bitrix\Main\Application;


class App {


	public array $config;

	// хранилище xml-файлов в виде объектов
	// для работы после парсинга и обработки (пример: объединение)
	private array $objects;

	// хранилище руководителей
	private array $headers;

	// хранилище пройденных пользователей
	// для отсеивания дублей и сопоставления состояний
	private array $employees;

	// пустые полльзователи, без email
	private array $empty;

	// новые полльзователи, с email, но не найдены
	private array $new;

	// хранилище ID пройденных пользователей
	// для поиска и замены старых ID на актуальные (после смены фамилии или email)
	private array $replaces;

	// хранилище xml-элементов
	// для их удаления вне цикла
	private array $remove;


	public static function moveElement($element, $dom){
		$parent = dom_import_simplexml($dom);
		$child  = dom_import_simplexml($element);
		$child  = $parent->ownerDocument->importNode($child, TRUE);
		return $parent->appendChild($child);
	}

	public function __construct($config = []){
		set_time_limit(0);
		$this->config = [
			'USER_LOGIN' => \Bitrix\Main\Config\Option::get("zup", "gs_zup_user_login") ? \Bitrix\Main\Config\Option::get("zup", "gs_zup_user_login") : '',
			'USER_PASSWORD' => \Bitrix\Main\Config\Option::get("zup", "gs_zup_user_password") ? \Bitrix\Main\Config\Option::get("zup", "gs_zup_user_password") : '',
			'DOMAIN' => $_SERVER['SERVER_NAME'],
			'ROOT_ID' => \Bitrix\Main\Config\Option::get("zup", "gs_zup_root_id") ? \Bitrix\Main\Config\Option::get("zup", "gs_zup_root_id") : '',
			'DIRECTORIES' => json_decode(\Bitrix\Main\Config\Option::get("zup", "gs_zup_dep_arr"),true),
			'OUT_DIRECTORY' => $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . \Bitrix\Main\Config\Option::get("zup", "gs_zup_out_dir"),
		];
	}

	public function loadFiles($employeesEntry = true){
		$currentTime = time();
		$currentDate = date("Y-m-d", $currentTime);
		//$currentDate = date("Y-m-d", strtotime("-1 days"));

		foreach ($this->config['DIRECTORIES'] as $directory){

			$lastTimeFile = null;
			$files = scandir($directory['PATH']);

			foreach ($files as $fileItem){

				$file = new IO\File($directory['PATH'] . $fileItem);
				if($file->isExists()){

					// todo...
					$currentFileTime = $file->getCreationTime();
					$currentFileDate = date("Y-m-d", $currentFileTime);

					if ($currentDate == $currentFileDate){

						if(!$lastTimeFile || intval($lastTimeFile) < $currentFileTime){

							try {

								$lastTimeFile = $currentFileTime;
								$content = $file->getContents();
								$xml = simplexml_load_string($content);

								$refactorXml = $this->refactorStructure($directory, $xml);
								$refactorXml = $this->refactorEmployees($refactorXml, $employeesEntry);

								$this->objects[$directory['ID']] = $refactorXml;

							}catch(IO\FileNotFoundException $e) {
								// todo
							}
						}
					}
				}
			}
		}
		foreach ($this->remove as $element){
			unset($element[0]);
		}
	}

	public function saveFiles(){
		foreach ($this->objects as $i => $object){
			$filePath = $this->config['OUT_DIRECTORY'] . $i . '.xml';

			unlink($filePath);
			$content = $object->asXml();
			$this->replaces['default:'] = '';
			$content = str_replace(array_keys($this->replaces), array_values($this->replaces), $content);
			file_put_contents($filePath, $content);
		}
	}

	public function mergeObjects($rootId, $save = false){
		$employees = [];
		$root = $this->objects[$rootId];

		foreach ($this->objects as $id => $object){
			if($rootId == $id) continue;

			foreach ($object->Классификатор->Подразделения->Подразделение as $department){
				$target = dom_import_simplexml($root->Классификатор->Подразделения);
				$insert = $target->ownerDocument->importNode(dom_import_simplexml($department), true);
				$target->appendChild($insert);
			}

			foreach ($object->ОрганизационнаяСтруктура->Работники->Работник as $employee){
				if(!isset($employees[strval($employee->Ид)])){
					$target = dom_import_simplexml($root->ОрганизационнаяСтруктура->Работники);
					$insert = $target->ownerDocument->importNode(dom_import_simplexml($employee), true);
					$target->appendChild($insert);
					$employees[strval($employee->Ид)] = true;
				}
			}

			foreach ($object->ГрафикОтсутствий->ЗаписиГрафика->ЗаписьГрафика as $employee){
				$target = dom_import_simplexml($root->ГрафикОтсутствий->ЗаписиГрафика);
				$insert = $target->ownerDocument->importNode(dom_import_simplexml($employee), true);
				$target->appendChild($insert);
				self::moveElement($insert, $target);
			}
		}

		if($save){
			$content = $root->asXml();
			$this->replaces['default:'] = '';
			$content = str_replace(array_keys($this->replaces), array_values($this->replaces), $content);

			$filePath = $this->config['OUT_DIRECTORY'] . 'root.xml';
			unlink($filePath);
			file_put_contents($filePath, $content);
		}

		return $root;
	}

	public function refactorStructure($root, $xml){
		$move = [];
		$remove = [];

		foreach ($xml->Классификатор->Подразделения->Подразделение as $department){

			// ООО "Главстрой-СПб специализированный застройщик"
			// удаляем "Администрация 1". Все вложенное должно быть в корне
			if(in_array($department->Ид, ['a5b613c8-c1e9-11eb-8189-30e171700293'])){
				$remove[] = $department;
			}

			// ООО "СМУ-Северная долина"
			// удаляем "Администрация 1". Все вложенное должно быть в корне
			if(in_array($department->Ид, ['d1b1d157-485f-11e8-80eb-30e171700293'])){
				foreach ($department->Подразделения->Подразделение as $inner_department){

					// значит "Администрация" в которой "2:Северная Долина"
					if(in_array($inner_department->Ид, ['e4df8278-4261-11e5-9ba6-00155d011357'])){

						foreach ($inner_department->Подразделения->Подразделение as $inner_in_department){

							// значит "2:Северная Долина". Все вложенное должно быть в корне
							if(in_array($inner_in_department->Ид, ['f8cb734c-485f-11e8-80eb-30e171700293'])){
								foreach ($inner_in_department->Подразделения->Подразделение as $inner_in_in_department){

									$remove[] = $inner_in_department;
									$move[] = [$inner_in_in_department, $department];

								}
							}else{
								$move[] = [$inner_in_department, $department];
							}

						}
					}

					$move[] = [$inner_department, $department];
				}

				$remove[] = $department;
			}

			// ООО "СМУ-Северная долина"
			// все что в "4:Северная Долина СМУ" выносим в корень
			if(in_array($department->Ид, ['3f79dad4-f6c1-11ec-81c1-30e171700293'])){
				foreach ($department->Подразделения->Подразделение as $inner_department){
					$move[] = [$inner_department, $department];
				}

				$remove[] = $department;
			}

			// ООО "СМУ-Северная долина"
			// все что в "Администрация" выносим в корень
			if(in_array($department->Ид, ['e4df8278-4261-11e5-9ba6-00155d011357'])){
				foreach ($department->Подразделения->Подразделение as $inner_department){
					$move[] = [$inner_department, $department];
				}
			}

			// ООО "СМУ-Юнтолово"
			// удаляем "2: Отделка", "1: Администрация" и "3: Юнтолово". Все вложенное должно быть в корне
			if(in_array($department->Ид, ['b8d39eff-8934-11e9-8105-30e171700293', '631f50de-6c2b-11e5-9ba6-00155d011357', '82f3a1ef-6c2b-11e5-9ba6-00155d011357'])){
				foreach ($department->Подразделения->Подразделение as $inner_department){
					$move[] = [$inner_department, $department];
				}

				$remove[] = $department;
			}

		}

		foreach ($move as $i => $department){

			$target = dom_import_simplexml($department[1]);
			$insert = $target->ownerDocument->importNode(dom_import_simplexml($department[0]), true);

			if($target->nextSibling){
				$target->parentNode->insertBefore($insert, $target->nextSibling);
			}else{
				$target->parentNode->appendChild($insert);
			}

		}

		foreach ($remove as $department){
			unset($department[0]);
		}

		$xml->Классификатор->Ид = $root['ID'];
		$xml->Классификатор->Наименование = $root['NAME'];

		if(intval($root['ID']) != $this->config['ROOT_ID']){

			$rootDepartment = new \SimpleXMLElement("<Подразделения></Подразделения>");

			$department = $rootDepartment->addChild('Подразделение');
			$department->addChild('Ид', $root['ID']);
			$department->addChild('Наименование', $root['NAME']);

			self::moveElement($xml->Классификатор->Подразделения, $department);
			self::moveElement($department, $rootDepartment);

			self::moveElement($rootDepartment, $xml->Классификатор);
		}

		// todo:
		// должно быть в другом месте. удаление доублирующегося дерева
		foreach ($xml->Классификатор->Подразделения as $department){
			$remove[] = $department;
		}

		for($i = 0; $i < count($remove) - 1; $i++){
			unset($remove[$i][0]);
		}

		$this->refactorHeaderDepartments($xml);

		return $xml;
	}

	public function refactorHeaderDepartments($xml){
		foreach ($xml->Классификатор->Подразделения->Подразделение as $department){
			if(in_array($department->Наименование, ['Администрация'])){
				$this->headers[strval($department->Ид)] = $xml->Классификатор->Ид;
			}

			foreach ($department->Подразделения->Подразделение as $inner_department){
				if(in_array($inner_department->Наименование, ['Администрация'])){
					$this->headers[strval($inner_department->Ид)] = strval($department->Ид);
				}
			}


			if(in_array($department->Наименование, ['Администрация'])){
				$xml->Классификатор->addChild('Руководитель', strval($department->Руководитель));
			}

			foreach ($department->Подразделения->Подразделение as $inner_department){
				if(in_array($inner_department->Наименование, ['Администрация'])){
					$department->addChild('Руководитель', strval($inner_department->Руководитель));
				}
			}
		}
	}

	public function refactorEmployees($xml, $entryDb){

		foreach ($xml->ГрафикОтсутствий->ЗаписиГрафика->ЗаписьГрафика as $row){
			if(strval($row->Состояние) == 'Работает'){
				$this->remove[] = $row;
			}
		}

		foreach ($xml->ОрганизационнаяСтруктура->Работники->Работник as $employee){

			$withEmail = false;
			$lastState = false;

			foreach ($employee->Подразделения->Подразделение as $department){
				if(isset($this->headers[strval($department)])){
					$employee->Подразделения->addChild('Подразделение', $this->headers[strval($department)]);
				}
			}

			foreach ($employee->ИсторияСостояний->Состояние as $state){

				// todo:
				// последняя дата состояния
				$lastState = $state->ДатаИзменения;
			}

			if($lastState != false){
				$ts = strptime(strval($lastState), '%Y-%m-%d');
				$lastState = mktime(0, 0, 0, $ts['tm_mon'] + 1, $ts['tm_mday'], $ts['tm_year'] + 1900);
			}

			foreach ($employee->Контакты->Контакт as $contact){

				if(strval($contact->Тип) == 'Почта'){
					$withEmail = true;

					$email = strval($contact->Значение);
					$login = trim(strstr($email, '@', true));

					// todo:
					// в любом случае удаляем персональные данные
					$this->remove[] = $employee->ИНН;
					$this->remove[] = $employee->Адрес;

					// todo:
					// пользователь уже есть
					if(isset($this->employees[$email])){

						// todo:
						// его состояние в текущей итерации новее, чем в пройденой
						if($lastState != false){
							if($lastState < $this->employees[$email]['STATE']){

								$employee->Ид = strval($this->employees[$email]['ELEMENT']->Ид);
								$this->replaces[strval($employee->Ид)] = strval($this->employees[$email]['ELEMENT']->Ид);

								$this->copyStatesEmployee($employee, $this->employees[$email]['ELEMENT']);
								$this->copyStatesEmployee($this->employees[$email]['ELEMENT'], $employee, true);
							}else{

								//$this->employees[$email]['ELEMENT']->Ид = strval($employee->Ид);
								$this->replaces[strval($this->employees[$email]['ELEMENT']->Ид)] = strval($employee->Ид);

								$this->copyStatesEmployee($this->employees[$email]['ELEMENT'], $employee);
								$this->copyStatesEmployee($employee, $this->employees[$email]['ELEMENT'], true);
							}
						}
					}

					if($user = (\CUser::GetByLogin($login))->Fetch()){
						if(empty($user['XML_ID']) || strval($employee->Ид) != $user['XML_ID']){

							$object = new \CUser();
							$object->Update($user['ID'], ["XML_ID" => strval($employee->Ид)]);
						}

						$this->employees[$email] = [
							'STATE' => $lastState,
							'ELEMENT' => $employee,
						];

						break;
					}else{
						// todo:
						// пользователь не найден, но с email
						$this->remove[] = $employee;

						if($entryDb){
							$this->saveEmployee($employee);
						}

						$this->new[] = (array) $employee;
					}
				}
			}

			if(!$withEmail){
				// todo:
				// пользователь без email
				$this->remove[] = $employee;

				if($entryDb){
					$this->saveEmployee($employee);
				}

				$this->empty[] = (array) $employee;
			}
		}

		return $xml;
	}

	public function copyStatesEmployee($employee, $donor, $reset = false){
		$history = ($reset) ? $employee->addChild('ИсторияСостояний', null) : $employee->ИсторияСостояний;

		if($reset) $this->remove[] = $employee->ИсторияСостояний;

		foreach ($donor->ИсторияСостояний->Состояние as $state){
			$this->addStateHistoryEmployee($history, [
				'VALUE' => strval($state->Значение),
				'DATE' => strval($state->ДатаИзменения),
				'DEPARTMENT' => strval($state->Подразделение),
				'POST' => strval($state->Должность)
			]);
		}

		return $employee;
	}

	public function addStateHistoryEmployee($history, $state){
		$event = $history->addChild('Состояние', null);
		$event->addChild('Значение', $state['VALUE']);
		$event->addChild('ДатаИзменения', $state['DATE']);
		$event->addChild('Подразделение', $state['DEPARTMENT']);
		$event->addChild('Должность', $state['POST']);
	}

	public function importFiles($files = false, $log = false){

		$this->auth();

		if(!$files) {
			$files = ['root.xml'];
		}

		foreach ($files as $file){

			$stages = ['init', 'file', 'import'];
			$path = $this->config['OUT_DIRECTORY'] . $file;

			foreach ($stages as $stage){
				if($stage == 'import'){
					for($i = 0; $i < 25; $i++){
						$result = $this->uploadFile($path, $stage, $log);
						$strpos = strpos($result, 'success');
						if($strpos !== false){
							break;
						}

						$strpos = strpos($result, 'failure');
						if($strpos !== false){
							exit(strval($result));
						}
					}
				}else{
					$result = $this->uploadFile($path, $stage);

					$strpos = strpos($result, 'failure');
					if($strpos !== false){
						exit(strval($result));
					}
				}
			}
		}
	}

	public function auth(){
		$url = 'https://' . $this->config['DOMAIN'] . '/bitrix/admin/';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_ENCODING, '');
		curl_setopt($ch, CURLOPT_TIMEOUT, 240);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 30);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_POSTFIELDS, [
			'TYPE' => 'AUTH',
			'AUTH_FORM' => 'Y',
			'USER_REMEMBER' => 'Y',
			'USER_LOGIN' => $this->config['USER_LOGIN'],
			'USER_PASSWORD' => $this->config['USER_PASSWORD'],
		]);
		curl_setopt($ch, CURLOPT_USERPWD, $this->config['USER_LOGIN'] . ":" . $this->config['USER_PASSWORD']);
		curl_setopt($ch, CURLOPT_COOKIEJAR, $this->config['OUT_DIRECTORY'] . 'cookie.txt');
		curl_setopt($ch, CURLOPT_COOKIEFILE, $this->config['OUT_DIRECTORY'] . 'cookie.txt');
		curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: text/plain']);

		$out = curl_exec($ch);

		if (curl_errno($ch)) {
			$error_msg = curl_error($ch);
			die($error_msg);
		}

		curl_close($ch);
		return true;
	}

	public function uploadFile($file, $mode = 'file', $log = false){

		$url = 'https://' . $this->config['DOMAIN'] . '/bitrix/admin/1c_intranet.php';
		$pathinfo = pathinfo($file);
		$filename = $pathinfo['basename'];

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_ENCODING, '');
		curl_setopt($ch, CURLOPT_TIMEOUT, 240);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 30);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_URL, $url . '?mode=' . $mode . '&filename=' . $filename . '');
		curl_setopt($ch, CURLOPT_USERPWD, $this->config['USER_LOGIN'] . ":" . $this->config['USER_PASSWORD']);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $this->config['OUT_DIRECTORY'] . 'cookie.txt');

		if($mode == 'file'){
			$fp = fopen($file, 'r');
			curl_setopt($ch, CURLOPT_INFILE, $fp);
			curl_setopt($ch, CURLOPT_UPLOAD, true);
			curl_setopt($ch, CURLOPT_INFILESIZE, filesize($file));
			curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/xml']);
		}else{
			curl_setopt($ch, CURLOPT_POSTFIELDS, []);
			curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: text/plain']);
		}

		$out = curl_exec($ch);

		if (curl_errno($ch)) {
			$error_msg = curl_error($ch);
			die($error_msg);
		}

		curl_close($ch);

		if($mode == 'file' && isset($fp)){
			fclose($fp);
		}

		$out = iconv('CP1251', 'UTF-8', $out);

		//if($log){
			print_r('<pre>' . $mode . ' (' . $filename . '): ' . $out . '</pre> <hr />');
		//}

		return $out;
	}

	public function clearStateHistory(){

		$iblock_id = intval(\COption::GetOptionInt('intranet', 'iblock_state_history', 0));
		if($iblock_id <= 0){
			return false;
		}

		$this->clearIblock($iblock_id);
		return true;
	}

	public function clearHonour(){

		$iblock_id = intval(\COption::GetOptionInt('intranet', 'iblock_honour', 0));
		if($iblock_id <= 0){
			return false;
		}

		$this->clearIblock($iblock_id);
		return true;
	}

	public function clearAbsence(){

		$iblock_id = intval(\COption::GetOptionInt('intranet', 'iblock_absence', 0));
		if($iblock_id <= 0){
			return false;
		}

		$this->clearIblock($iblock_id);
		return true;
	}

	public function clearIblock($iblock_id){

		if(\CIBlock::GetPermission($iblock_id) >= 'W'){
			try {

				Loader::includeModule('iblock');
				$res = \CIBlockElement::GetList(['ID' => 'ASC'], ['IBLOCK_ID' => $iblock_id], false, [], ['ID']);

				while ($row = $res->Fetch()){
					\CIBlockElement::Delete($row['ID']);
				}

			}catch(\Bitrix\Main\LoaderException $e) {
				// todo:
			}
		}
	}

	public function saveNewEmployees(){

		$output = fopen($this->config['OUT_DIRECTORY'] . 'new.csv','w');
		fputcsv($output,['Ид', 'Наименование', 'Фамилия', 'Имя', 'Отчество'], ';');
		foreach($this->new as $e) {
			fputcsv($output, [$e['Ид'], $e['Наименование'], $e['Фамилия'], $e['Имя'], $e['Отчество']], ';');
		}
		fclose($output);
	}

	public function saveEmptyEmployees(){

		$output = fopen($this->config['OUT_DIRECTORY'] . 'empty.csv','w');
		fputcsv($output,['Ид', 'Наименование', 'Фамилия', 'Имя', 'Отчество'], ';');
		foreach($this->empty as $e) {
			fputcsv($output, [$e['Ид'], $e['Наименование'], $e['Фамилия'], $e['Имя'], $e['Отчество']], ';');
		}
		fclose($output);
	}

	public function saveEmployee($employee){
		$email = null;
		$data = [
			'SECOND_NAME' => strval($employee->Фамилия),
			'NAME' => strval($employee->Имя),
			'LAST_NAME' => strval($employee->Отчество),
			'BIRTHDAY' => strval($employee->ДатаРождения),
			'GENDER' => strval($employee->Пол),
			'POSITION' => strval($employee->Должность),
			'DEPARTMENTS' => []
		];

		foreach($employee->Контакты->Контакт as $contact){
			if(strval($contact->Тип) == 'Почта'){
				$email = strval($contact->Значение);
			}
		}

		foreach($employee->Подразделения->Подразделение as $department){
			$data['DEPARTMENTS'][] = strval($department);
		}

		$employeeArray = [
			'TYPE' => (is_null($email)) ? 'EMPTY' : 'NEW',
			'REF_KEY' => strval($employee->Ид),
			'EMAIL' => $email,
			'DATA' => json_encode($data),
		];

		if(\Zup\Employee::getCount(['REF_KEY' => $employeeArray['REF_KEY']]) > 0){
			$old = \Zup\Employee::getByRefKey(['REF_KEY' => $employeeArray['REF_KEY']])->Fetch();
			$employeeArray['ID'] = $old['ID'];
			$employeeResult = \Zup\Employee::update($employeeArray['ID'], $employeeArray);
		}else{
			$employeeResult = \Zup\Employee::create($employeeArray);
		}
		AddMessage2Log('true', "zup");
		if(is_array($employeeResult)){
			foreach ($employeeResult as $error) {
				// todo: то надо записывать в логи
				$string = '<div>['.$error->getCode().'] '.$error->getMessage() . '</div>';
				file_put_contents(
					$_SERVER["DOCUMENT_ROOT"] . "/local/logs/xml/" . date("Y_m") . "_zup.log",
					date('d m o, H:i:s') . $string . PHP_EOL,
					FILE_APPEND
				);

			}
		}

		if(is_int($employeeResult) && $employeeResult > 0){
			// todo: успешно записалось, а $employeeResult ID записанной строки
		}

		if(is_bool($employeeResult) && $employeeResult === true){
			// todo: успешно обновилась, а $employeeArray ID записанной строки
			$employeeResult = $employeeArray['ID'];
		}

		return $employeeResult;
	}

	public function startFiles($params = []){
		// todo: очистка доски почета, графика отсутствий и истории состояний
		if($params['clear']['honour']){
			$this->clearHonour();
		}
		if($params['clear']['absence']){
			$this->clearAbsence();
		}
		if($params['clear']['history']){
			$this->clearStateHistory();
		}

		// todo: загрузка и парсинг исходников для импорта
		$this->loadFiles();

		// todo: сохранение отдельных списков в csv файлах
		if($params['clear']['save']['employees']){
			$this->saveNewEmployees();
			$this->saveEmptyEmployees();
		}
		// todo: сохранения отрефактаренного файла
		if($params['clear']['save']['file']){
			$this->saveFiles(true);
		}

		$this->mergeObjects(\Bitrix\Main\Config\Option::get("zup", "gs_zup_root_id") ? \Bitrix\Main\Config\Option::get("zup", "gs_zup_root_id") : '', true);
	
	}

	public function startImport(){

		$this->importFiles(false, true);

	}
}