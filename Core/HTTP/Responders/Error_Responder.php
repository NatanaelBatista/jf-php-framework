<?php

namespace JF\HTTP\Responders;

use JF\HTTP\Responder;

/**
 * Classe que formata e envia resposta das requisições ao cliente.
 */
class Error_Responder extends Responder
{
    /**
     * Armazena os header do tipo de resposta.
     */
    protected static $headers = ['application/json'];

    /**
     * Instancia a classe da rota, executa e envia a resposta ao cliente.
     */
    public static function send( $exception )
    {
        http_response_code( 404 );
        self::setHeader();
        
        // Define as mensagens de erro da requisição
        $handler    = '';
        $service    = '';
        $namespace  = '';
        
        // Captura a mensagem de erro a ser mostrada e o nome do arquivo
        $error_msg  = sprintf(
            "%s - %s [%s]: %s",
            $exception[ 'type' ],
            str_replace( '\\', '/', $exception[ 'file' ] ),
            $exception[ 'line' ],
            $exception[ 'message' ]
        );

        header( 'Content-Type: application/json' );
        echo json_encode( array( 'error' => $error_msg ) );
        exit();
    }

    /**
     * Configura o header da resposta de acordo com o formato do arquivo.
     */
    public static function setHeader( $type = null, $charset = null )
    {
        parent::setHeader( 'json', $charset );
    }
}
