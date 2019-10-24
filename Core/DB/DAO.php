<?php

namespace JF\DB;

use JF\DB\SQL\SQL;

/**
 * Data Access Object - Classe para acesso aos dados de uma tabela.
 */
class DAO
{
    /**
     * Classe DTO de referência.
     */
    protected $dto;

    /**
     * Pesquisa simples por um registro na tabela.
     */
    public function __construct( $dto )
    {
        $this->dto = $dto;
    }

    /**
     * Pesquisa simples por um registro na tabela.
     */
    public function find( $value, $search = null, $opts = [] )
    {
        $dto            = $this->dto;
        $new_opts       = [
            'class'         => get_class( new $dto ),
            'class_start'   => 'start',
        ];
        $columns        = $dto::columns( $opts );
        $search         = $search
            ? $search
            : $dto::primaryKey();
        $operator       = is_array( $value )
            ? 'IN'
            : '=';
        $method         = is_array( $value )
            ? 'all'
            : 'one';

        $sql            = SQL::select( $columns )
            ->from( $dto::table() )
            ->where( $search, $operator, $value );

        if ( !is_array( $value ) )
        {
            $sql->limit( 1 );
        }

        $sql            = $sql->sql();
        $result         = DB::instance( $dto::schema() )
            ->execute( $sql->sql, $sql->data )
            ->$method( $dto::dbOptions() );

        return $result;
    }

    /**
     * Pesquisa simples por um registro na tabela.
     */
    public function one( $opts = [] )
    {
        $dto            = $this->dto;
        $columns        = !empty( $opts[ 'columns' ] )
            ? $opts[ 'columns' ]
            : null;
        $columns        = $dto::columns( $opts );
        $sql            = SQL::select( $columns )
            ->from( $dto::table() )
            ->sql();
        $result         = DB::instance( $dto::schema() )
            ->execute( $sql->sql, $sql->data )
            ->one( $dto::dbOptions() );

        return $result;
    }

    /**
     * Pesquisa simples por um registro na tabela.
     */
    public function all( $opts = [] )
    {
        $dto            = $this->dto;
        $columns        = !empty( $opts[ 'columns' ] )
            ? $opts[ 'columns' ]
            : null;
        $pk             = $dto::primaryKey();
        $columns        = $dto::columns( $opts );
        $sql            = SQL::select( $columns )
            ->from( $dto::table() )
            ->sql();
        $result         = DB::instance( $dto::schema() )
            ->execute( $sql->sql, $sql->data )
            ->indexBy( $pk )
            ->all( $dto::dbOptions() );

        return $result;
    }

    /**
     * Pesquisa simples por um registro na tabela.
     */
    public function insert( $values, $opts = [] )
    {
        $dto        = $this->dto;
        
        return SQL::insert( $dto )->values( $values );
    }

    /**
     * Inicia uma consulta do tipo SELECT.
     */
    public function select( $columns = null, $opts = [] )
    {
        $dto            = $this->dto;
        
        return SQL::select( $columns, $dto );
    }

    /**
     * Inicia uma consulta do tipo UPDATE.
     */
    public function update( $value = null, $search = null, $values = [], $opts = [] )
    {
        $dto            = $this->dto;
        $sql            = SQL::update( $dto::table(), null, $dto );
        $search         = $search
            ? $search
            : $dto::primaryKey();

        if ( $value )
        {
            $operator   = is_array( $value )
                ? 'IN'
                : '=';
            $sql->where( $search, $operator, $value );
        }

        if ( $values )
        {
            $sql->set( $values );
        }
        
        return $sql;
    }

    /**
     * Inicia uma consulta do tipo DELETE.
     */
    public function delete( $value = null, $search = null, $opts = [] )
    {
        $dto            = $this->dto;
        $sql            = SQL::delete( $this->dto );
        $search         = $search
            ? $search
            : $dto::primaryKey();

        if ( $value )
        {
            $operator   = is_array( $value )
                ? 'IN'
                : '=';
            $sql->where( $search, $operator, $value );
        }
        
        return $sql;
    }

    /**
     * Limpa os dados da tabela.
     */
    public function truncate()
    {
        return static::db()->truncate( static::table() );
    }
}
