<?php

namespace JF\DB;

use JF\DB\DB;

/**
 * Data Object Transfer - Classe representativa de um registro dda tabela.
 */
class DTO
{
    /**
     * Esquema de conexão.
     */
    protected static $schema;
    
    /**
     * Nome da tabela.
     */
    protected static $table;
    
    /**
     * Dados sensíveis / privados do registro.
     */
    protected static $hide      = [];
    
    /**
     * Colunas da tabela.
     */
    protected static $columns   = [];
    
    /**
     * Status do registro.
     */
    protected $_status          = 'created';
    
    /**
     * Dados sensíveis do registro.
     */
    protected $_changed         = [];

    /**
     * Retorna o nome do esquema de conexão.
     */
    public static function schema()
    {
        return static::$schema;
    }

    /**
     * Retorna o nome do banco-de-dados.
     */
    public static function dbname()
    {
        return static::db()->config( 'dbname' );
    }

    /**
     * Retorna o nome da tabela do banco.
     */
    public static function table()
    {
        return static::$table;
    }

    /**
     * Retorna os campos de dado sensíveis do DTO ou se um campo específico é sensível.
     */
    public static function hide( $column = null )
    {
        return $column
            ? in_array( $column, static::$hide )
            : static::$hide;
    }

    /**
     * Chave primária da tabela.
     */
    public static function primaryKey()
    {
        return 'id';
    }

    /**
     * Pesquisa simples por um registro na tabela.
     */
    public static function columns( $opts = [] )
    {
        $response   = [];
        $unsafe     = !empty( $opts[ 'unsafe' ] );
        $columns    = isset( $opts[ 'columns' ] )
            ? $opts[ 'columns' ]
            : null;

        foreach ( static::$columns as $column => $prop )
        {
            $col_hide   = in_array( $column, static::$hide );

            if ( !$unsafe && $col_hide )
            {
                continue;
            }

            if ( $columns && !in_array( $column, $columns ) )
            {
                continue;
            }

            $response[]  = $column;
        }

        return $response;
    }

    /**
     * Retorna um DAO para realizar consultas ao banco.
     */
    public static function dao()
    {
        return new DAO( get_called_class() );
    }

    /**
     * Retorna um DAO para realizar consultas ao banco.
     */
    public static function db()
    {
        return DB::instance( static::schema() );
    }

    /**
     * Pesquisa simples por um registro na tabela.
     */
    public static function dbOptions( $opts = [] )
    {
        return array_merge( $opts, [
            'class'         => get_class( new static ),
            'class_start'   => 'start',
        ]);
    }

    /**
     * Indica que o objeto foi iniciado.
     */
    public function start()
    {
        $this->_status = 'saved';
    }

    /**
     * Define um novo valor para uma propriedade do objeto.
     */
    public function set( $key, $value )
    {
        if ( !isset( $this->$key ) )
        {
            $this->$key             = null;
        }

        $old_value                  = $this->$key;
        $this->$key                 = $value;

        if ( $old_value === $value )
        {
            return $this;
        }

        $prop_changed               = array_key_exists( $key, $this->_changed );

        if ( $prop_changed && $this->_changed[ $key ] === $value )
        {
            unset( $this->_changed[ $key ] );
            return $this;
        }

        $record_is_saved            = $this->_status == 'saved';
        $column_exists              = array_key_exists( $key, static::$columns );

        if ( $record_is_saved && $column_exists )
        {
            $this->_changed[ $key ] = array_key_exists( $key, $this->_changed )
                ? $this->_changed[ $key ]
                : $old_value;
        }

        return $this;
    }

    /**
     * Retorna um novo objeto com os campos sensíveis removidos.
     */
    public function values( $unsafe = false )
    {
        $data       = (array) $this;
        unset( $data[ '_status' ] );
        unset( $data[ '_changed' ] );
        
        if ( $unsafe )
        {
            return $data;
        }

        $data   = array_intersect_key( $data, static::$columns );

        return (object) $data;
    }

    /**
     * Retorna um novo objeto com os campos sensíveis removidos.
     */
    public function filter()
    {
        $data   = (array) $this;
        $record = new static();
        
        array_walk( $data, function( $value, $key ) use ( $record )
        {
            if ( in_array( $key, static::$hide ) )
            {
                return;
            }

            if ( !array_key_exists( $key, static::$columns ) )
            {
                return;
            }

            $record->$key = $value;
        });

        return $record;
    }

    /**
     * Identifica se um registro sofreu alterações não salvas.
     */
    public function changed()
    {
        return (bool) $this->_changed;
    }

    /**
     * Identifica se houve alteração em um registro.
     */
    public function restore()
    {
        foreach ( $this->_changed as $key => $value )
        {
            $this->$key = $value;
            unset( $this->_changed[ $key ] );
        }

        $this->_status = 'saved';
    }

    /**
     * Salva o registro na tabela (cria um novo ou atualiza o registro existente).
     */
    public function save()
    {
        if ( !$this->changed() )
        {
            return false;
        }

        $key                = static::primaryKey();
        $count              = static::dao()
            ->update( $this->$key, $key, $this->values() )
            ->count();

        if ( $count )
        {
            $this->_status  = 'saved';
        }

        return $count;
    }

    /**
     * Identifica se houve alteração em um registro.
     */
    public function delete()
    {
        $key                = static::primaryKey();
        $count              = static::dao()
            ->delete( $this->$key, $key )
            ->count();

        if ( $count )
        {
            $this->_status  = 'deleted';
        }

        return $count;
    }

    /**
     * Atualiza os dados do registro atual com os dados do registro na tabela.
     */
    public function refresh()
    {
        $key    = static::primaryKey();
        $record = static::dao()->find( $this->$key, $key );

        array_walk( (array) $record, function( $value, $key ) {
            $this->$key = $value;
        });
    }
}
