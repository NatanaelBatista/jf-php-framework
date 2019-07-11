<?php

namespace JF;

/**
 * Classe principal do framework
 */
class Utils
{
    /**
     * Método para exportar uma variável.
     */
    public static function var_export( $var = null, $to_php_file = false )
    {
        $var        = var_export( $var, true );
        $response   = preg_replace( '/array \(/', 'array(', $var );
        $response   = preg_replace( '/=> [\r\n] +array\(/', '=> array(', $response );
        $response   = $to_php_file
            ? '<?php' . N . N . 'return ' . $response . ';' . N
            : '';

        return $response;
    }

    /**
     * Remove os caracteres especiais de um texto.
     */
    public static function simpleText( $text )
    {
        $text   = preg_replace( '/[Ä]/', '',            $text );
        $text   = preg_replace( '/[ÀÁÂÃÄ]/',    'A',    $text );
        $text   = preg_replace( '/[ÈÉÊË]/',     'E',    $text );
        $text   = preg_replace( '/[ÌÍÎÏ]/',     'I',    $text );
        $text   = preg_replace( '/[ÒÓÔÕÖ]/',    'O',    $text );
        $text   = preg_replace( '/[ÙÚÛÜ]/',     'U',    $text );
        $text   = preg_replace( '/[Ç]/',        'C',    $text );

        $text   = preg_replace( '/[àáâãäº]/',   'a',    $text );
        $text   = preg_replace( '/[èéêë]/',     'e',    $text );
        $text   = preg_replace( '/[ìíîï]/',     'i',    $text );
        $text   = preg_replace( '/[òóôõöº]/',   'o',    $text );
        $text   = preg_replace( '/[ùúûü]/',     'u',    $text );
        $text   = preg_replace( '/[ç]/',        'c',    $text );

        return $text;
    }
}
