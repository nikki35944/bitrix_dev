<?php
namespace Zup;

use Bitrix\Main\Localization\Loc,
	Bitrix\Main\ORM\Data\DataManager,
	Bitrix\Main\ORM\Fields\BooleanField,
	Bitrix\Main\ORM\Fields\DatetimeField,
	Bitrix\Main\ORM\Fields\IntegerField,
	Bitrix\Main\ORM\Fields\StringField,
	Bitrix\Main\ORM\Fields\TextField,
	Bitrix\Main\ORM\Fields\Validators\LengthValidator;

Loc::loadMessages(__FILE__);

/**
 * Class EmployeeTable
 * 
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> ACTIVE bool ('N', 'Y') optional default 'Y'
 * <li> REF_KEY string(255) mandatory
 * <li> EMAIL string(255) mandatory
 * <li> DATA text mandatory
 * <li> DATE_CREATE datetime mandatory
 * <li> DATE_UPDATE datetime mandatory
 * </ul>
 *
 * @package Bitrix\Employee
 **/

class EmployeeTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'gs_employee';
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
					'required' => true,
					'validation' => [__CLASS__, 'validateRefKey'],
					'title' => Loc::getMessage('EMPLOYEE_ENTITY_REF_KEY_FIELD')
				]
			),
			new StringField(
				'EMAIL',
				[
					'required' => true,
					'validation' => [__CLASS__, 'validateEmail'],
					'title' => Loc::getMessage('EMPLOYEE_ENTITY_EMAIL_FIELD')
				]
			),
			new TextField(
				'DATA',
				[
					'required' => true,
					'title' => Loc::getMessage('EMPLOYEE_ENTITY_DATA_FIELD')
				]
			),
			new DatetimeField(
				'DATE_CREATE',
				[
					'required' => true,
					'title' => Loc::getMessage('EMPLOYEE_ENTITY_DATE_CREATE_FIELD')
				]
			),
			new DatetimeField(
				'DATE_UPDATE',
				[
					'required' => true,
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
	public static function validateRefKey()
	{
		return [
			new LengthValidator(null, 255),
		];
	}

	/**
	 * Returns validators for EMAIL field.
	 *
	 * @return array
	 */
	public static function validateEmail()
	{
		return [
			new LengthValidator(null, 255),
		];
	}
}