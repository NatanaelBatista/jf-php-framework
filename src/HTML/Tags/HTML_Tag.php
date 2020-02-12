<?php

namespace JF\HTML\Tags;

/**
 * Tag HTML.
 */
class HTML_Tag extends Tag
{
    /**
     * Propriedades da tag.
     */
    protected $props = ['lang' => 'pt-br'];
    
    /**
     * Retorna se o conteúdo é aceito pela TAG.
     */
    public function accept( $content )
    {
        if ( !self::isTag( $content ) )
        {
            return false;
        }
        
        $accept_tags = ['head', 'body'];
        
        return in_array( $content->tag, $accept_tags );
    }
}