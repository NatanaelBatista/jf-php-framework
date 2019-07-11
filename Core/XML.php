<?php

namespace JF;

/**
 * Classe que envia uma resposta a uma requisição em json.
 *
 * @author  Márcio Jalber <marciojalber@gmail.com>
 * @since   31/08/2015
 */
class XML
{
    /**
     * Método para criar um elemento XML a partir de um array ou objeto.
     */
    public static function create( $element, $data )
    {
        if ( is_scalar( $data ) )
        {
            return simplexml_load_string( "<$element>$data</$element>\n" );
        }

        $xml = simplexml_load_string( "<$element/>" );
        self::setContent( $xml, $data );
        return $xml;
    }

    /**
     * Método para converter um conteúdo em elementos XML e injetá-lo num objeto XML.
     */
    protected static function setContent( $xml, $data )
    {
        foreach ( $data as $key => $value )
        {
            $key = is_integer( $key ) ? 'item' : $key;
            
            if ( is_null( $value ) || is_scalar( $value ) )
            {
                $xml->addChild( $key, $value );
            }
            else if ( is_resource( $value ) )
            {
                $value = $value . ': ' . get_resource_type( $value );
                $xml->addChild( $key, $value );
            }
            else {
                $child = $xml->addChild( $key );
                self::setContent( $child, $value );
            }
        }
    }

}
