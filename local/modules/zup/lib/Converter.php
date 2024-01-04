<?php

namespace Zup;


class Converter extends \Bitrix\Main\Text\Converter {


	public function encode($text, $textType = "") {
		if ($text instanceof \Bitrix\Main\Type\DateTime)
			return $text->format('Y-m-d H:i:s');

		return $text;
	}

	public function decode($text, $textType = "") {
		if(is_string(new \Bitrix\Main\Type\DateTime(null, 'Y-m-d H:i:s')))
		return $text;
	}
}
