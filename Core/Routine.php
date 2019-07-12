<?php

namespace JF;

use JF\Config;
use JF\DB\DB_Backup;
use JF\Doc\DocParser;
use JF\Exceptions\ErrorException;
use JF\Log;
use JF\Messager;

/**
 * Classe que cuida da execução de rotinas.
 */
class Routine
{
    /**
     * Verifica se a rotina deve ser executada.
     */
    public function expired( $last_execution, $diff )
    {

    }

    /**
     * Executa a rotina.
     */
    protected function execute()
    {

    }

    /**
     * Verifica se a rotina deve ser executada.
     */
    public static function processAll()
    {
        self::processBackupDB();

        $observer_file  = DIR_PRODUCTS . '/logs/routine/.doc-observer';
        $old_timestamps = file_exists( $observer_file )
            ? json_decode( file_get_contents( $observer_file ), true )
            : null;
        $run_docparser  = !$old_timestamps;
        $new_timestamps = [];
        $obj_path       = new \FilesystemIterator( DIR_ROUTINES );
        $len_basepath   = strlen( DIR_BASE ) + 1;

        foreach ( $obj_path as $routine_path )
        {
            $pathname           = $routine_path->getPathName();
            $pathname           = str_replace( '\\', '/', $pathname );
            $routine_class      = substr( $pathname, $len_basepath, -4 );
            $namespaces         = Config::get( 'namespaces' );

            foreach ( $namespaces as $namespace => $local )
            {
                if ( strpos( $routine_class, $local ) === 0 )
                {
                    $routine_class = $namespace . substr( $routine_class, strlen( $local ) );
                }
            }

            $routine_class      = str_replace( '/', '\\', $routine_class );
            $len_start          = strlen( 'Routines\\' );
            $len_end            = strlen( '__Routine' );
            $execution_filename = substr( $routine_class, $len_start ) . '.log';
            $execution_filename = DIR_PRODUCTS . '/executions/' . $execution_filename;
            $timestamp          = filemtime( $pathname );
            $new_timestamp      = &$new_timestamps[ $routine_class ];
            $new_timestamp      = $timestamp;

            if ( !class_exists( $routine_class ) )
            {
                $msg            = Messager::get(
                    'routine',
                    'routine_not_found',
                    $routine_class
                );
                throw new ErrorException( $msg );
            }

            if ( !$old_timestamps || $run_docparser )
            {
                goto nextStep;
            }

            $has_timestamp      = isset( $old_timestamps[ $routine_class ] );

            if ( !$has_timestamp || $timestamp > $old_timestamps[ $routine_class ] )
            {
                $run_docparser  = true;
            }

            nextStep:

            if ( !file_exists( $execution_filename ) )
            {
                file_put_contents( $execution_filename, null );
            }

            $last_execution     = file_get_contents( $execution_filename );
            $last_execution     = $last_execution
                ? new \DateTime( $last_execution )
                : null;
            $now                = new \DateTime();
            $diff               = $last_execution
                ? $now->diff( $last_execution )
                : null;
            $routine            = new $routine_class();

            if ( $last_execution && !$routine->expired( $last_execution, $diff ) )
            {
                continue;
            }
            
            file_put_contents( $execution_filename, $now->format( 'Y-m-d H:i:s' ) );
            
            $start          = $now->format( 'U.u' );
            $result         = $routine->execute();
            $end            = (new \DateTime())->format( 'U.u' );
            $routine_name   = substr( $routine_class, $len_start );
            $options        = self::makeOptions( $start, $end, $routine_name );
            Log::register( $result, 'routine', $options );
        }

        $new_timestamps     = json_encode( $new_timestamps );
        
        file_put_contents( $observer_file, $new_timestamps );
        
        if ( $run_docparser )
        {
            // DocParser::run();
        }
    }

    /**
     * Obtém o nome do arquivo de log da rotina.
     */
    private static function makeOptions( $start, $end, $routine_class )
    {
        $start_obj      = \DateTime::createFromFormat( 'U.u', $start );
        $end_obj        = \DateTime::createFromFormat( 'U.u', $end );
        $options        = [
            'start'     => $start_obj->format( 'Y-m-d H:i:s.u' ),
            'end'       => $end_obj->format( 'Y-m-d H:i:s.u' ),
            'name'      => $routine_class,
            'duration'  => $end - $start,
        ];

        return $options;
    }

    /**
     * Verifica se a rotina deve ser executada.
     */
    public static function processBackupDB()
    {
        $env                = ENV;
        $config             = Config::get( [
            "db/$env.backups",
            'db/all.backups',
        ]);

        if ( !$config || empty( $config->frequency ) )
        {
            return;
        }

        $frequency          = $config->frequency;
        $frequency_measure  = $frequency[ 0 ];
        $frequency_value    = $frequency[ 1 ];
        $execution_filename = self::dbBackupLog();

        if ( !file_exists( $execution_filename ) )
        {
            file_put_contents( $execution_filename, null );
        }

        $last_execution     = file_get_contents( $execution_filename );
        
        if ( !$last_execution )
        {
            return self::executeBackupDB( $config );
        }
        
        $last_execution     = new \DateTime( $last_execution );
        $hours              = $frequency_measure !== 'd'
            ? (int) $last_execution->format( 'H' )
            : '0';
        $mins               = '0';
        $last_execution->setTime( $hours, $mins );

        $now                = new \DateTime();
        $diff               = $now->diff( $last_execution );

        if ( $diff->$frequency_measure >= $frequency_value )
        {
            return self::executeBackupDB( $config );
        }
    }

    /**
     * Executa a rotina de Backups.
     */
    private static function executeBackupDB( $config )
    {
        $execution_filename = self::dbBackupLog();
        $now                = new \DateTime();
        $env                = ENV;
        $schemas            = Config::get( [
            "db/$env.schemas",
            'db/all.schemas',
        ]);

        file_put_contents( $execution_filename, $now->format( 'Y-m-d H:i:s' ) );
        
        $destination        = !empty( $config->path )
            ? $config->path
            : DIR_BACKUPS;
        $start              = $now->format( 'U.u' );
        $localhost          = [ 'localhost', '127.0.0.1' ];
        $hosts              = isset( $config->hosts )
            ? array_merge( $config->hosts, $localhost )
            : $localhost;

        set_time_limit( 0 );
        ini_set( 'memory_limit', -1 );

        foreach ( $schemas as $schema_name => $schema )
        {
            if ( empty( $schema->hostname ) )
            {
                continue;
            }

            if ( !in_array( $schema->hostname, $hosts ) )
            {
                continue;
            }

            $options        = in_array( $schema_name, $config->schemas )
                ? []
                : [ 'noData' => true ];
            DB_Backup::backup( $schema_name, $destination, $options );
        }
        
        $env                = ENV;
        $keep               = Config::get( [
            "db/$env.backups.keep",
            'db/all.backups.keep',
        ]);

        if ( !$keep )
        {
            return;
        }

        $keep_measure   = strtoupper( $keep[ 0 ] );
        $time_separator = in_array( $keep_measure, array( 'H', 'M', 'S' ) )
            ? 'T'
            : '';
        $keep_value     = strtoupper( $keep[ 1 ] );
        $interval_exp   = "P{$time_separator}{$keep_value}{$keep_measure}";
        $interval       = new \DateInterval( $interval_exp );
        $now            = new \DateTime();
        $now->sub( $interval );
        $date_limit     = $now->format( 'Ymd_His' );

        $path_obj       = new \FilesystemIterator( $destination );

        foreach ( $path_obj as $backup )
        {
            $filename = str_replace( '\\', '/', $backup->getFileName() );
            $pathname = str_replace( '\\', '/', $backup->getPathName() );

            if ( $filename <= $date_limit )
            {
                unlink( $pathname );
            }
        }
    }

    /**
     * Retorna o nome do arquivo de log de backups.
     */
    public static function dbBackupLog()
    {
        return DIR_PRODUCTS . '/executions/db-backup.log';
    }
}
