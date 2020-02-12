<?php

namespace JF\HTTP;

use JF\Exceptions\InfoException;
use JF\Exceptions\ErrorException;

/**
 * Classe que manipula requisições HTTP.
 *
 * @author  Márcio Jalber [marciojalber@gmail.com]
 * @since   26/04/2019
 */
class API
{
    /**
     * Analisa se uma requisição é uma chamada à API.
     */
    public static function parse()
    {
        $route      = Router::get( 'route' );
        $feature    = str_replace( '/', '\\', ucfirst( $route ) );
        $feature    = str_replace( '-', '_', $feature );
        $feature    = preg_replace_callback( '@(\\\.|_.)@', function( $matches ) {
            return strtoupper( $matches[ 1 ] );
        }, $feature );
        $feature    = "Features\\{$feature}\Feature";

        if ( !class_exists( $feature ) )
        {
            return;
        }
        
        if ( !is_subclass_of( $feature, 'JF\\Domain\\Feature') )
        {
            return;
        }

        $uses       = class_uses( $feature );
        $traits     = [ 'JF\\HTTP\\HTTP_Service_Trait', 'JF\\HTTP\\API_Trait' ];

        if ( !array_intersect( $traits, $uses ) )
        {
            return;
        }

        $method     = $_SERVER[ 'REQUEST_METHOD' ];
        $methods    = $feature::acceptHTTPMethods();
        $args       = $_SERVER[ 'REQUEST_METHOD' ] == 'GET'
            ? json_decode( json_encode( Input::args() ) )
            : json_decode( json_encode( Input::post() ) );

        array_walk( $methods, function( &$value ) {
            $value      = strToUpper( $value );
        });

        if ( !in_array( $method, $methods ) )
        {
            $msg        = "Método $method não permitido para a chamada do serviço {$request->r}.";
            throw new ErrorException( $msg );
        }

        return (object) [
            'feature'   => $feature,
            'args'      => $args,
        ];
    }
}
