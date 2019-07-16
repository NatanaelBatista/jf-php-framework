<?php

namespace JF\HTML;

use JF\Config;
use JF\FileSystem\Dir;

/**
 * Monta páginas HTML.
 */
final class ParserHTML
{
    /**
     * Verifica o arquivo HTML de uma rota existe e está atualizado.
     */
    public static function isUpdated( $route )
    {
        $html_path              = self::getPagePath( $route );
        $log_path               = self::getLogPath( $route );

        if ( !file_exists( $html_path ) || !file_exists( $log_path ) )
        {
            return false;
        }

        $last_parse             = json_decode( file_get_contents( $log_path ) );
        $config_servers_path    = Config::path( 'servers' );
        $config_ui_path         = Config::path( 'ui' );
        $has_time_env           = isset( $last_parse->config_servers );
        $has_time_ui            = isset( $last_parse->config_ui );
        $has_time_dependencies  = isset( $last_parse->dependencies );
        $has_SERVER_ADDR        = isset( $last_parse->SERVER_ADDR );
        $has_DIR_BASE           = isset( $last_parse->DIR_BASE );

        if ( !$has_SERVER_ADDR || $_SERVER[ 'SERVER_ADDR' ] != $last_parse->SERVER_ADDR )
        {
            return false;
        }

        if ( !$has_DIR_BASE || DIR_BASE != $last_parse->DIR_BASE )
        {
            return false;
        }

        if ( !$has_time_env || !$has_time_ui || !$has_time_dependencies )
        {
            return false;
        }

        if ( !$has_time_env || !$has_time_ui || !$has_time_dependencies )
        {
            return false;
        }

        if ( $last_parse->config_servers < filemtime( $config_servers_path ) )
        {
            return false;
        }

        if ( $last_parse->config_ui < filemtime( $config_ui_path ) )
        {
            return false;
        }

        foreach ( $last_parse->dependencies as $file_source => $file_time )
        {
            $file_source    = DIR_BASE . '/' . $file_source;
            $file_new_time  = file_exists( $file_source )
                ? filemtime( $file_source )
                : null;
            
            if ( !$file_time || !$file_new_time || $file_time < $file_new_time )
            {
                return false;
            }
        }

        return true;
    }

    /**
     * Constrói uma página a partir de uma view.
     */
    public static function parseView( $route )
    {
        $maker      = new PageMaker( $route );
        $result     = $maker->makePage();
        $new_parse  = self::prepareParseLog( $result->depends );

        self::makePagePath( $route );
        $page_path  = self::getPagePath( $route );
        $doc_path   = self::getDocPath( $route );
        $log_path   = self::getLogPath( $route );

        file_put_contents( $page_path, $result->html );
        file_put_contents( $doc_path, $result->doc );
        file_put_contents( $log_path, json_encode( $new_parse ) );
    }

    /**
     * Prepara o arquivo de log de construção da página.
     */
    public static function prepareParseLog( $dependencies )
    {
        $config_servers_path    = Config::path( 'servers' );
        $config_ui_path         = Config::path( 'ui' );
        $new_parse              = [
            'SERVER_ADDR'       => $_SERVER[ 'SERVER_ADDR' ],
            'DIR_BASE'          => DIR_BASE,
            'config_servers'    => file_exists( $config_servers_path )
                ? filemtime( $config_servers_path )
                : null,
            'config_ui'         => file_exists( $config_ui_path )
                ? filemtime( $config_ui_path )
                : null,
            'dependencies'      => $dependencies,
        ];

        return $new_parse;
    }

    /**
     * Retorna o caminho para o arquivo de página.
     */
    public static function getPagePath( $route )
    {
        return DIR_UI . '/pages/' . $route . '.html';
    }

    /**
     * Retorna o caminho para o arquivo de página.
     */
    public static function makePagePath( $route )
    {
        $route_parts    = explode( '/', $route );
        $path_route     = DIR_UI . '/pages/' . $route_parts[ 0 ];
        Dir::makeDir( $path_route );
    }

    /**
     * Retorna o caminho para o log de construção da página.
     */
    public static function getDocPath( $route )
    {
        return DIR_VIEWS . "/$route/.document";
    }

    /**
     * Retorna o caminho para o log de construção da página.
     */
    public static function getLogPath( $route )
    {
        return DIR_VIEWS . "/$route/.build";
    }
}
