<?php

namespace JF\DB;

use JF\Config;
use JF\Exceptions\ErrorException;
use JF\Messager;

/**
 * Classe que representa um banco-de-dados.
 */
class DB_Backup
{
    /**
     * Executa o backup de um banco-de-dados.
     */
    public static function backup( $schema_name, $path )
    {
        if ( !$path )
        {
            $msg = Messager::get( 'db', 'target_path_not_informed', $schema_name );;
            throw new ErrorException( $msg );
        }
        $target         = $path . '/' . date( 'Ymd_His' ) . '_' . $schema_name . '.sql';
        $db             = DB::instance( $schema_name, true );

        if ( !$db )
        {
            return;
        }

        $config         = $db->config();
        $username       = $config->username;
        $password       = !empty( $config->password )
            ? ' -p' . $config->password
            : '';
        $dbname         = $config->dbname;
        $dump_cmd       = "mysqldump -u{$username}{$password} {$dbname} > {$target}";

        return shell_exec( $dump_cmd );
    }

    /**
     * Limpa uma tabela no banco-de-dados.
     */
    public function restore()
    {
        $db             = DB::instance( $this->schemaName, true );

        if ( !$db )
        {
            return;
        }

        $config         = $db->config();
        $username       = $config->username;
        $password       = !empty( $config->password )
            ? ' -p' . $config->password
            : '';
        $dbname         = $config->dbname;
        $source         = DIR_STORAGE . '/bkpdb_' . $dbname . '.sql';
        $restore_cmd    = "mysql -u{$username}{$password} < {$source}";

        return shell_exec( $dump_cmd );
    }
}
