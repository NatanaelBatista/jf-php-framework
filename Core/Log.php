<?php

namespace JF;

use JF\Config;
use JF\HTTP\Request;
use JF\HTTP\ControllerParser;

/**
 * Classe que salva e recupera informações de log.
 *
 * @author  Márcio Jalber <marciojalber@gmail.com>
 * @since   01/04/2015
 */
class Log
{
    /**
     * Armazena o erro ocorrido.
     */
    private $error  = '';

    /**
     * Salva o texto do log.
     */
    private $text   = '';

    /**
     * Indica se o log já foi salvo.
     */
    private $saved  = false;

    /**
     * Indica se o log já foi salvo.
     */
    private $context  = null;

    /**
     * Método para registrar um log.
     * 
     * @return null
     */
    public static function register( $error, $context, Array $options = array() )
    {
        $log_instance           = new self();
        $log_instance->error    = $error;
        $log_instance->context  = $context;
        $log_instance->logPathIsWritable();

        if ( $context === 'routine' )
        {
            $log_instance->saveRoutineLog( $options );
            return;
        }

        $log_instance->makeLogText();
        $log_instance->saveLogFeature();
        $log_instance->saveLogRoute();
        $log_instance->saveLogDate();
    }

    /**
     * Método para escrever o log.
     *
     * @return  null
     */
    protected function logPathIsWritable()
    {
        $dir_logs = $this->logPath();
        
        if ( !file_exists( $dir_logs ) )
        {
            mkdir( $dir_logs, 0777, true );
            return;
        }

        if ( !is_writable( $dir_logs ) )
        {
            $text = 'Estamos sem permissão para escrever na pasta "%s"';
            exit( sprintf( $text, $dir_logs ) );
        }
    }

    /**
     * Método para escrever o log.
     *
     * @return  null
     */
    protected function makeLogText()
    {
        // Prepara as variáveis básicas
        $filename       = isset( $this->error[ 'file' ] )
            ? str_replace( '\\', '/', $this->error[ 'file' ] )
            : null;
        $base_path      = substr( $filename, strlen( DIR_BASE ) );
        $ip             = Request::ipClient();
        $request        = isset( $_SERVER[ 'REQUEST_URI' ] )
            ? $_SERVER[ 'REQUEST_URI' ]
            : $_SERVER[ 'SCRIPT_FILENAME' ];
        $http_referer   = isset( $_SERVER[ 'HTTP_REFERER' ] )
            ? $_SERVER[ 'HTTP_REFERER' ]
            : '';
        $line           = $this->error[ 'line' ];

        // Prepara o texto do log
        $log        = [];
        $log[]      = '[' . uniqid( null, true ) . ']';
        $log[]      = '';
        $log[]      = 'date        = "' . date( 'Y-m-d' ) . '"';
        $log[]      = 'time        = "' . date( 'H:i:s' ) . '"';
        $log[]      = 'type        = "' . $this->error[ 'type' ] . '"';
        $log[]      = 'message     = "' . $this->error[ 'message' ] . '"';
        $log[]      = 'basepath    = "' . DIR_BASE . '"';
        $log[]      = 'file        = "' . str_replace( DIR_BASE, '..', $filename ) . '"';
        $log[]      = 'line        = "' . $this->error[ 'line' ] . '"';
        $log[]      = 'ip          = "' . $ip. '"';
        $log[]      = 'request     = "' . $request . '"';
        $log[]      = 'referer     = "' . $http_referer . '"';
        
        if ( !empty( $this->error[ 'stack' ] ) )
        {
            $trace  = $this->error[ 'stack' ];
            $trace  = str_replace( DIR_BASE, '..', $trace );
            $trace  = preg_replace( '@[\n\r]+@', '|', $trace );
            $log[]  = 'trace       = "' . $trace . '"';
        }

        $log[]      = '';
        $log        = implode( PHP_EOL, $log );

        // Retorna o texto do log
        $this->text = $log;
    }

    /**
     * Método para escrever o log.
     *
     * @return  null
     */
    protected function saveLogRoute()
    {
        if ( defined( 'ROUTE' ) && ROUTE )
        {
            $path           = $this->logPath() . '/' . str_replace( '/', '_', ROUTE );
            $this->saved    = $this->write( $path );
        }
    }

    /**
     * Método para escrever o log.
     */
    protected function saveLogFeature()
    {
        // Se já salvou o log, não executa novo salvamento
        if ( $this->saved )
        {
            return;
        }
     
        $controller         = ControllerParser::controller();
        $controller_parts   = explode( '\\', ControllerParser::controller() );

        if ( array_pop( $controller_parts ) != 'Controller' )
        {
            return;
        }

        $namespaces         = Config::get( 'namespaces' );
        $new_classname      = $controller;

        foreach ( $namespaces as $namespace => $path )
        {
            if ( strpos( $controller, $namespace ) === 0 )
            {
                $new_classname = $path . substr( $controller, strlen( $namespace ) );
                break;
            }
        }

        $path               = str_replace( '\\', '/', $new_classname );
        $logfile            = DIR_BASE . '/' . substr( $path, 0, -10 );
        $this->saved        = $this->write( $logfile );
    }

    /**
     * Método para escrever o log.
     */
    protected function saveLogDate()
    {
        // Se já salvou o log, não executa novo salvamento
        if ( $this->saved )
        {
            return;
        }

        // Prepara os possíveis caminhos do arquivo de log
        $year_path      = $this->logPath()
            . '/' . date( 'Y' );
        
        $month_path     = $year_path
            . '/' . date( 'm' );
        
        $day_path       = $month_path
            . '/' . date( 'd' );
        
        $hour_path      = $day_path
            . '/' . date( 'H' );

        $log_contexts   = array(
            'year'      => $year_path,
            'month'     => $month_path,
            'day'       => $day_path,
        );
        
        // Tenta salvar o log
        foreach ( $log_contexts as $freq => $path )
        {
            if ( $freq === 'day' )
            {
                return $this->write( $path );
            }

            if ( !file_exists( $path ) )
            {
                mkdir( $path, 0777, true );
                continue;
            }
            
            if ( !is_writable( $path ) )
            {
                exit( "Estamos sem permissão para criar a pasta '$path'!" );
            }
        }
    }

    /**
     * Método para escrever o log.
     *
     * @return boolean
     */
    protected function write( $file_path )
    {
        $logFile    = new \SplFileObject( $file_path . '.errors-log', 'a' );
        $log_saved  = $logFile->fwrite( $this->text . PHP_EOL );
        $logFile    = null;

        return $log_saved;
    }

    /**
     * Retorna o caminho dos arquivos de log.
     *
     * @return boolean
     */
    protected function logPath()
    {
        return DIR_LOGS . '/' . $this->context;
    }

    /**
     * Retorna o caminho dos arquivos de log.
     */
    protected function saveRoutineLog( Array $options )
    {
        $name           = str_replace( '_', '-', $options[ 'name' ] );
        $name           = str_replace( '\\', '/', $name );
        $start          = $options[ 'start' ];
        $end            = $options[ 'end' ];
        $duration       = $options[ 'duration' ];
        
        $log_filename   = DIR_LOGS . '/routine/' . $name . '.log';
        $error          = preg_replace( '/[\r\n]+/', PHP_EOL . '           ', $this->error );

        $log            = [];
        $log[]          = '[' . uniqid( null, true ) . ']';
        $log[]          = '';
        $log[]          = 'DATE        = "' . date( 'Y-m-d' ) . '"';
        $log[]          = 'START       = "' . $start . '"';
        $log[]          = 'END         = "' . $end . '"';
        $log[]          = 'DURATION    = "' . $duration . '"';
        $log[]          = 'RESULT      = "' . $error . '"';
        $log[]          = '';
        $log[]          = '';
        $log            = implode( PHP_EOL, $log );

        $log_file       = new \SplFileObject( $log_filename, 'a' );
        $result         = $log_file->fwrite( $log );
        $log_file       = null;

        return $result;
    }
}
