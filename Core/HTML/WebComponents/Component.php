<?php

namespace JF\HTML\WebComponents;

use JF\Autoloader;

/**
 * Classe modelo para os WebComponents.
 */
class Component
{
    /**
     * Pattern para identificar TAGs do framwork.
     */
    protected $props     = null;
    
    /**
     * Nome do arquivo de template.
     */
    protected $template  = 'template';
    
    /**
     * Dependências adquiridas na montagem do componente.
     */
    protected $_depends  = [];
    
    /**
     * Captura e renderiza os componentes do framework.
     */
    public function __construct( $props )
    {
        $this->props    = $props;
    }
    
    /**
     * Retorna as propriedades que não devem ser exibidas.
     */
    public function hideProps()
    {
        return [];
    }
    
    /**
     * Monta o componente.
     */
    public function mount()
    {
    }
    
    /**
     * Retorna as dependências adquiridas na montagem do componente.
     */
    public function depends()
    {
        return $this->_depends;
    }
    
    /**
     * Captura e renderiza os componentes do framework.
     */
    public function addDependencyClass( $class_name )
    {
        $filename       = Autoloader::getClassFilename( $class_name );
        $depend_name    = substr( $filename, strlen( DIR_BASE ) + 1 );

        $this->_depends[ $depend_name ] = filemtime( $filename );
    }
    
    /**
     * Captura e renderiza os componentes do framework.
     */
    public function render()
    {
        $reflection = new \ReflectionClass( get_called_class() );
        $filename   = $reflection->getFilename();
        $pathname   = str_replace( '\\', '/', dirname( $filename ) );
        $template   = "{$pathname}/{$this->template}.php";
        $fn         = function( $matches ) {
            $path   = $matches[ 1 ];
            return $this->$path;
        };

        ob_start();
        include $template;
        $content    = ob_get_clean();
        $content    = preg_replace_callback( '/\{@(.*)\}/', $fn, $content );
        
        return $content;
    }
    
    /**
     * Captura e renderiza os componentes do framework.
     */
    public function props( $prop = null, $name = null, $enclosure = '"' )
    {
        if ( $prop && !property_exists( $this->props, $prop ) )
        {
            return;
        }

        if ( $prop )
        {
            return $name
                ? $name . "=$enclosure" . $this->props->$prop . "$enclosure"
                : $this->props->$prop;
        }

        $props = [];

        foreach ( $this->props as $key => $prop )
        {
            if ( in_array( $key, static::hideProps() ) )
            {
                continue;
            }

            $props[] = $key . (!is_null( $prop ) ? '="' . $prop . '"' : '');
        }

        return implode( ' ', $props );
    }
}
