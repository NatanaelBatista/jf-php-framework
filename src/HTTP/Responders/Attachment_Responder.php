<?php

namespace JF\HTTP\Responders;

use JF\HTTP\Router;

/**
 * Classe que formata e envia resposta das requisições ao cliente.
 */
trait Attachment_Responder
{
    /**
     * Instancia a classe da rota, executa e envia a resposta ao cliente.
     */
    public static function sendAttachment( $data, $controller_obj, $content_type )
    {
        if ( isset( $data[ 'error' ] ) || isset( $data->error ) )
        {
            static::setHeader( 'json', $controller_obj->charset() );
            echo json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
            return;
        }

        if ( is_null( $data ) || !is_scalar( $data ) )
        {
            echo json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
            return;
        }
        
        $filename           = $data;
        $new_filename       = basename( $filename );
        
        if ( isset( $controller_obj->filename ) )
        {
            $new_filename   = $controller_obj->filename;
            $new_filename  .= Router::get( 'response_type' ) === 'pdf'
                ? '.pdf':
                '';
        }

        if ( !file_exists( $filename ) )
        {
            $data           = ['error' => "Arquivo '$new_filename' não encontrado!"];
            echo json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
            return;
        }

        static::setHeader( $content_type, $controller_obj->charset );
        header( "Content-Length: " . filesize( $filename ) );
        $content_disposition = $content_type === 'download'
            ? 'attachment'
            : 'inline';
        header( "Content-Disposition: $content_disposition; filename=$new_filename" );
        readfile( $filename );
    }
}
