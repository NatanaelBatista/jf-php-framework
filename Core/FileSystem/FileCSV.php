<?php

namespace JF\FileSystem;

use JF\Exceptions\ErrorException;
use JF\Messager;

/**
 * Classe que manipula arquivos CSV.
 */
class FileCSV extends File
{
    /**
     * Nome dos campos do arquivo.
     */
    protected $labels       = null;

    /**
     * Mapa de renomeação de campos.
     */
    protected $layout       = null;

    /**
     * Caractere separador de campos.
     */
    protected $separator    = ';';

    /**
     * Caractere encapsulador de dados.
     */
    protected $enclosure    = '"';

    /**
     * Define um mapa de renomeação de campos.
     */
    public function setLayout( array $layout = array() )
    {
        $this->layout = (object) $layout;
        return $this;
    }

    /**
     * Define o separador de campos.
     */
    public function setSeparator( $separator )
    {
        $this->separator = $separator;
        return $this;
    }

    /**
     * Define o encapsulador de campos.
     */
    public function setEnclosure( $enclosure )
    {
        $this->enclosure = $enclosure;
        return $this;
    }

    /**
     * Obtém o layout dos dados.
     */
    public function labels()
    {
        if ( $this->labels )
        {
            return $this->labels;
        }

        $file               = $this->file( 'r' );
        $file->rewind();
        
        $labels             = $file->fgetcsv( $this->separator, $this->enclosure );

        if ( !$this->layout )
        {
            foreach ( $labels as &$label )
            {
                $label      = (object) array( 'label' => $label );
            }
            $this->labels   = $labels;
            return $this->labels;
        }

        $old_labels         = $labels;
        $labels             = array();

        foreach ( $this->layout as $name => $column )
        {
            $column = (object) $column;

            if ( empty( $column->regex ) )
            {
                $position   = array_search( $column->label, $old_labels );
            }
            else
            {
                $position   = false;
                
                foreach ( $old_labels as $pos => $old_label )
                {
                    preg_match( '/' . $column->label . '/', $old_label, $pos_match );

                    if ( $pos_match )
                    {
                        $position = $pos;
                        break;
                    }
                }
            }

            if ( $position === false )
            {
                continue;
            }

            $labels[ $position ]    = (object) [
                'label'             => $name,
            ];

            if ( isset( $column->filter ) )
            {
                $labels[ $position ]->filter = $column->filter;
            }
        }

        $this->labels       = $labels;
        
        return $this->labels;
    }

    /**
     * Obtém uma linha de dados em CSV.
     */
    public function record()
    {
        $file   = $this->file( 'r' );
        $labels = $this->labels();
        $row    = $file->fgetcsv( $this->separator, $this->enclosure );
        
        if ( !$row || count( $row ) === 1 && !$row[ 0 ] )
        {
            return null;
        }
        
        $record = $this->filteredRecord( $row, $labels );
        
        return $record;
    }

    /**
     * Obtém todas as linhas de dados em CSV.
     */
    public function records( $limit = 0 )
    {
        $file       = $this->file( 'r' );
        $labels     = $this->labels();
        $separator  = $this->separator;
        $enclosure  = $this->enclosure;
        
        $active_row = 0;
        $records    = array();

        while ( $row = $file->fgetcsv( $separator, $enclosure ) )
        {
            $record     = $this->filteredRecord( $row, $labels );
            $records[]  = $record;
            ++$active_row;
            
            if ( $limit > 0 && $active_row === $limit )
            {
                break;
            }
        }
        
        return $records;
    }
    
    /**
     * Obtém um dado de uma linha de dados CSV.
     */
    private function filteredRecord( $row, $labels )
    {
        $record = array();

        foreach ( $labels as $key => $column )
        {
            $label                  = $column->label;
            $value                  = $row[ $key ];
            $record[ $label ]       = $value;
            
            if ( !empty( $column->required ) && !$value )
            {
                return null;
            }
            
            if ( isset( $column->filter ) )
            {
                $class              = str_replace( '.', '\\', trim( $column->filter[ 0 ] ) );
                $method             = trim( $column->filter[ 1 ] );
                
                if ( !class_exists( $class ) )
                {
                    $msg = Messager::get( 'classes', 'class_not_found', $class );
                    throw new ErrorException( $msg );
                }

                if ( !method_exists( $class, $method ) )
                {
                    $msg = Messager::get( 'classes', 'method_not_found', $method, $class );
                    throw new ErrorException( $msg );
                }

                $record[ $label ]   = $class::$method( $value );
            }
        }
        
        return $record;
    }
}
