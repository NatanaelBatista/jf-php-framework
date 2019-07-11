<?php

namespace JF\HTML\Tags;

/**
 * Tag modelo.
 */
class Tag
{
    /**
     * Propriedades da tag.
     */
    protected $props = [];
    
    /**
     * Conteúdo da tag.
     */
    protected $content = [];
    
    /**
     * Desabilita a adição de conteúdo.
     */
    protected static $disableAddContent = true;

    /**
     * Cria uma instância da TAG.
     */
    public function create()
    {
        return new static();
    }
    
    /**
     * Adiciona uma propriedade à TAG.
     */
    public function tag()
    {
        $class          = get_called_class();
        $class_parts    = explode( '\\', $class );
        $class_name     = array_pop( $class_parts );
        $tag_name       = substr( $class_name, 0, -4 );

        return $tag_name;
    }
    
    /**
     * Adiciona uma propriedade à TAG.
     */
    public function prop( $prop )
    {
        $prop_content   = isset( $this->props[ $prop ] )
            ? $this->props[ $prop ]
            : null;

        return $prop_content;
    }
    
    /**
     * Adiciona uma propriedade à TAG.
     */
    public function addProp( $prop, $val )
    {
        $prop = strtolower( $prop );
        $this->props[ $prop ] = $val;

        return $this;
    }
    
    /**
     * Adiciona um conteúdo à TAG.
     */
    public function addContent( $content )
    {
        if ( static::$disableAddContent )
        {
            throw new ErrorException( 'add_content_disabled', $this->tag() );
        }

        if ( $this->accept( $content ) )
        {
            $this->content[] = $content;
        }

        return $this;
    }
    
    /**
     * Retorna se o conteúdo é aceito pela TAG.
     */
    public function accept( $content )
    {
        return true;
    }
    
    /**
     * Retorna as propriedades da TAG.
     */
    public function props()
    {
        return $this->props;
    }
    
    /**
     * Retorna o conteúdo da TAG.
     */
    public function content()
    {
        return $this->content;
    }
    
    /**
     * Exporta o conteúdo da TAG.
     */
    public function show()
    {
        $props      = self::propsToHTML( $this->props() );
        $content    = self::contentToHTML( $this->content() );
        $html       = "<{$this->tag}{$props}>{$content}</{$this->tag}>";
        
        return $html;
    }
    
    /**
     * Formata as propriedades para o formato HTML.
     */
    public static function propsToHTML( $props )
    {
        $props  = [];

        foreach ( $this->props as $prop_name => $prop_content ) {
            $props[] = !is_null( $prop_content )
                ? "{$prop_name}=\"{$prop_content}\""
                : $prop_name;
        }

        $props  = $props
            ? ' ' . implode( ' ', $props )
            : '';

        return $props;
    }
    
    /**
     * Prepara o conteúdo para ser exibido em HTML.
     */
    public static function contentToHTML( $content )
    {
        $response   = [];

        foreach ( $content as $item ) {
            if ( is_null( $item ) )
            {
                continue;
            }

            if ( is_scalar( $item ) )
            {
                $response[] = $item;
                continue;
            }

            if ( self::isTag( $item ) )
            {
                $response[] = $item->show();
            }
        }

        $response   = $response
            ? implode( ' ', $response )
            : '';

        return $response;
    }
    
    /**
     * Prepara o conteúdo para ser exibido em HTML.
     */
    public static function isTag( $content )
    {
        if ( !$content )
        {
            return false;
        }

        if ( is_scalar( $content ) )
        {
            return false;
        }

        if ( !is_subclass_of( $content, __CLASS__ ) )
        {
            return false;
        }

        return true;
    }
}
