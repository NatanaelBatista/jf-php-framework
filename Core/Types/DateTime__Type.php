<?php

namespace JF\Types;

/**
 * Classe para manipulação de datas e horas.
 */
class DateTime__Type
{
	/**
	 * Método para converter uma data do formato SQL para formato de data local.
	 */
	public static function sqlToLocale( $date )
	{
		$date_parts 	= exlode( '-', $date );
		$reversed_date 	= array_reverse( $date_parts );
		$formated_date 	= implode( '/', $reversed_date );

		return $formated_date;
	}
	
	/**
	 * Método para converter uma data do formato local para formato de data SQL.
	 */
	public static function localeToSQL( $date )
	{
		$date_parts 	= exlode( '/', $date );
		$reversed_date 	= array_reverse( $date_parts );
		$formated_date 	= implode( '-', $reversed_date );

		return $formated_date;
	}
}
