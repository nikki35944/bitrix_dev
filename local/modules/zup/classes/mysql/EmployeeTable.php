<?php

namespace Zup\Mysql;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\ORM\Fields\FloatField;

Loc::loadMessages(__FILE__);


class EmployeeTable extends \Bitrix\Main\Entity\DataManager {


	public static function getTableName() {
		return 'gs_employee';
	}

	public static function onBeforeAdd(\Bitrix\Main\Entity\Event $event){
		$result = new \Bitrix\Main\Entity\EventResult;
		$parameters = $event->getParameters();

        // todo: добавляем дату создания
		$modifyFields = [
			'DATE_UPDATE' =>  new \Bitrix\Main\Type\DateTime(null, 'Y-m-d H:i:s'),
			'DATE_CREATE' => new \Bitrix\Main\Type\DateTime(null, 'Y-m-d H:i:s')
		];

		// todo: DEMO
		if(is_string($parameters['fields']['KEY'])){
            // if(!$key_type_id = \Parking\EngineType::getIdByName($parameters['fields']['KEY'], true)){
            //     $result->addError(new \Bitrix\Main\ORM\EntityError('KEY'));
            // }
            // $modifyFields['KEY'] = $key_type_id;
		}

		$result->modifyFields($modifyFields);
		return $result;
	}

	public static function onBeforeUpdate(\Bitrix\Main\Entity\Event $event){
		$result = new \Bitrix\Main\Entity\EventResult;
		$parameters = $event->getParameters();

        // todo: удаляем дату создания
		$result->unsetField('DATE_CREATE');

        // todo: добавляем дату обновления
		$modifyFields = [
			'DATE_UPDATE' => new \Bitrix\Main\Type\DateTime(null, 'Y-m-d H:i:s')
		];

        // todo: DEMO
        if(is_string($parameters['fields']['KEY'])){
            // if(!$key_type_id = \Parking\EngineType::getIdByName($parameters['fields']['KEY'], true)){
            //     $result->addError(new \Bitrix\Main\ORM\EntityError('KEY'));
            // }
            // $modifyFields['KEY'] = $key_type_id;
        }

		$result->modifyFields($modifyFields);
		return $result;
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return [
			new IntegerField(
				'ID',
				[
					'primary' => true,
					'autocomplete' => true,
					'title' => Loc::getMessage('EMPLOYEE_ENTITY_ID_FIELD')
				]
			),
			new BooleanField(
				'ACTIVE',
				[
					'values' => array('N', 'Y'),
					'default' => 'Y',
					'title' => Loc::getMessage('EMPLOYEE_ENTITY_ACTIVE_FIELD')
				]
			),
			new StringField(
				'REF_KEY',
				[
					//'required' => true,
					'validation' => [__CLASS__, 'validateRefKey'],
					'title' => Loc::getMessage('EMPLOYEE_ENTITY_REF_KEY_FIELD')
				]
			),
			new StringField(
				'EMAIL',
				[
					//'required' => true,
					'validation' => [__CLASS__, 'validateEmail'],
					'title' => Loc::getMessage('EMPLOYEE_ENTITY_EMAIL_FIELD')
				]
			),
			new StringField(
				'TYPE',
				[
					'title' => Loc::getMessage('EMPLOYEE_ENTITY_TYPE_FIELD')
				]
			),
			new TextField(
				'DATA',
				[
					//'required' => true,
					'title' => Loc::getMessage('EMPLOYEE_ENTITY_DATA_FIELD')
				]
			),
			new DatetimeField(
				'DATE_CREATE',
				[
					//'required' => true,
					'title' => Loc::getMessage('EMPLOYEE_ENTITY_DATE_CREATE_FIELD')
				]
			),
			new DatetimeField(
				'DATE_UPDATE',
				[
					//'required' => true,
					'title' => Loc::getMessage('EMPLOYEE_ENTITY_DATE_UPDATE_FIELD')
				]
			),
		];
	}

	/**
	 * Returns validators for REF_KEY field.
	 *
	 * @return array
	 */
	public static function validateRefKey() {
		return [
			new LengthValidator(null, 255),
		];
	}

	/**
	 * Returns validators for EMAIL field.
	 *
	 * @return array
	 */
	public static function validateEmail() {
		return [
			new LengthValidator(null, 255),
		];
	}
}
