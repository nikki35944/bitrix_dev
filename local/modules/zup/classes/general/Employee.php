<?php

namespace Zup;

use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);


class Employee  {


	public static $_select = [
		'ID', 'ACTIVE',
        'REF_KEY', 'EMAIL', 'DATA',
		'DATE_CREATE', 'DATE_UPDATE',
	];


	public static function getList($filter, $select = false){
		if(!$select) $select = self::$_select;

		return  \Zup\Mysql\EmployeeTable::getList([
			'select' => $select,
			'filter' => $filter,
		]);
	}

	public static function getById($id, $parameters = []){
		if(empty($parameters) || (empty($parameters) || !isset($parameters['select']))){
			$parameters['select'] = self::$_select;
		}
		return \Zup\Mysql\EmployeeTable::getByPrimary($id, $parameters);
	}

	public static function getCount($filter){
		return \Zup\Mysql\EmployeeTable::getCount($filter);
	}

	public static function getByRefKey($refKey, $select = ['ID']){
		return \Zup\Employee::getList(['REF_KEY' => $refKey], $select);
	}

	public static function create($data){

		$add = \Zup\Mysql\EmployeeTable::add($data);
		if($add->isSuccess()){
			return $add->getId();
		}

		return $add->getErrors();
	}

	public static function update($id, $data){

		$update = \Zup\Mysql\EmployeeTable::update($id, $data);
		return ($update->isSuccess()) ? true : $update->getErrors();
	}
}
