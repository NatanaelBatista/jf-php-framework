<?php

namespace JF\HTML;

use JF\Exceptions\ErrorException;
use JF\FileSystem\Dir;
use JF\HTML\ParserHTML;
use JF\HTTP\Router;
use JF\Messager;

/**
 * Classe que formata e envia resposta das requisições ao cliente.
 */
class HTML_Responder
{
    /**
     * Instancia a classe da rota, executa e envia a resposta ao cliente.
     */
    public static function send()
    {
        $route      = Router::get( 'route' );

        if ( Router::get( 'type' ) != 'view' )
        {
            return;
        }

        if ( !file_exists( DIR_PAGES ) )
        {
            Dir::makeDir( DIR_PAGES );
        }

        if ( !is_writable( DIR_PAGES ) )
        {
            $msg = Messager::get( 'html', 'path_is_not_writable', DIR_PAGES );
            throw new ErrorException( $msg );
        }

        if ( !ParserHTML::isUpdated( $route ) )
        {
            ParserHTML::parseView( $route );
        }
        
        http_response_code( 200 );
        header( 'Content-Type: text/html; charset=UTF-8' );

        $filename   = ParserHTML::getPagePath( $route );
        
        include $filename;
        exit();
    }
}
