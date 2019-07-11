<?php

namespace JF\HTML\WebComponents;

/**
 * Classe para montar WebComponents.
 */
class ComponentParser
{
    /**
     * Pattern para identificar TAGs do framwork.
     */
    protected static $patternTag = '[A-Za-z0-9]+';
    
    /**
     * Pattern para identificar TAGs do framwork.
     */
    protected static $patternProps = '[\s\t\r\n]+(.*?)';
    
    /**
     * Captura e renderiza os componentes do framework.
     */
    public static function parse( $html )
    {
        $depends                    = [];
        $fn                         = function( $matches ) use ( &$depends ) {
            libxml_use_internal_errors( true );

            $dom                    = new \DOMDocument();
            $content                = preg_replace( '/[\s\t\n\r]+@([A-Za-z])/', " ::$1", $matches[ 0 ] );
            $dom->loadHTML( $content );
            $html                   = $dom->childNodes->item( 1 );
            $body                   = $html->childNodes->item( 0 );
            $tag                    = $body->childNodes->item( 0 );
            $props                  = [];
            
            foreach ( $tag->attributes as $attr )
            {
                $name               = preg_replace( '/::/', '@', $attr->name );
                $props[ $name ]     = $attr->value === ''
                    ? null
                    : trim( utf8_decode( $attr->value ) );
            }

            $tag                    = $matches[ 1 ];
            $tag_ns                 = ucfirst( $tag );
            $tag_ns                 = preg_replace_callback( '@-(.)@', function( $matches ) {
                return strtoupper( $matches[ 1 ] );
            }, $tag_ns );
            $class_component        = "WebComponents\\$tag_ns\\Component";

            if ( !class_exists( $class_component ) )
            {
                $msg                = "Componente $class_component não encontrado.";
                die( $msg );
            }

            $class_name             = str_replace( '\\', '/', "templates/html/$class_component.php" );
            $depends[ $class_name ] = filemtime( DIR_BASE . '/' . $class_name );
            $component              = new $class_component( (object) $props );

            $component->mount();
            $depends                = array_merge( $depends, $component->depends() );

            return $component->render();
        };

        $patternTag         = self::$patternTag;
        $patternProps       = self::$patternProps;
        $pattern            = "@<jf-($patternTag)($patternProps)?></jf-\\1>@s";
        $html               = preg_replace_callback( $pattern, $fn, $html );
        $response           = (object) [
            'html'          => $html,
            'depends'       => $depends,
        ];

        return $response;
    }
}
