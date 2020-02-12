<?php

namespace JF\HTML\Tags;

/**
 * Tag HTML.
 */
class Head_Tag extends Tag
{
    /**
     * Propriedades da tag.
     */
    protected $props    = [
        'title'         => '',
        'charset'       => 'UTF-8',
        'viewport'      => 'width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, minimal-ui',
    ];
    
    /**
     * Retorna se o conteúdo é aceito pela TAG.
     */
    public function accept( $content )
    {
        if ( !self::isTag( $content ) )
        {
            return false;
        }
        
        $accept_tags = ['meta', 'link', 'script'];
        
        return in_array( $content->tag, $accept_tags );
    }
    
    /**
     * Define o título da página.
     */
    public function setTitle( $title )
    {
        return $this->addProp( 'title', $title );
    }
    
    /**
     * Define a codificação de caracteres da página.
     */
    public function setCharset( $charset )
    {
        return $this->addProp( 'charset', $charset );
    }
    
    /**
     * Define o modo de exibição da página.
     */
    public function setViewport( $viewport )
    {
        return $this->addProp( 'viewport', $viewport );
    }
    
    /**
     * Retorna as propriedades da TAG.
     */
    public function props()
    {
        return [];
    }
    
    /**
     * Retorna o conteúdo da TAG.
     */
    public function content()
    {
        $content = [];

        foreach ( $this->props as $prop_name => $prop_content ) {
            $content[] = $prop_name === 'title'
                ? "<title>{$prop_content}</title>"
                : "<meta name=\"{$prop_name}\" content=\"{$prop_content}\">";
        }

        $content = implode( $content );

        return $content;
    }
}