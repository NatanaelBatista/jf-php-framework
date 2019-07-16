<?php

namespace JF\HTML;

use JF\Exceptions\ErrorException;
use JF\FileSystem\Dir;
use JF\Messager;

/**
 * Trait da operação para montar a tag script.
 */
trait PageMakerPartial
{
    /**
     * Inclue um fragmento de arquivo HTML.
     */
    public function partial( $filename, $shared = false )
    {
        if ( !file_exists( DIR_PARTIALS ) )
        {
            Dir::makeDir( DIR_PARTIALS );
        }

        $filename       = strtolower( $filename );
        $filename       = str_replace( '\\', '/', $filename ) . '.php';
        $context_path   = $shared
            ? DIR_PARTIALS
            : DIR_VIEWS;

        $filename       = $shared
            ? $filename
            : $this->getRealPath( $filename, true );
        $file_path      = $context_path . '/' . $filename;
        $file_partial   = substr( $file_path, strlen( DIR_BASE ) + 1 );
        $file_caller    = DIR_VIEWS . '/' . $this->route . '/view.php';

        $this->testIfExistsFile( $file_path, $file_caller );
        $this->testIncludindPartialRecursivly( $file_path, $file_caller );

        $this->including[ $file_path ] = true;
        include $file_path;
        $this->depends[ $file_partial ] = filemtime( $file_path );
        unset( $this->including[ $file_path ] );
    }

    /**
     * Testa se o arquivo solicitado existe.
     */
    public function testIfExistsFile( $file_path, $file_caller )
    {
        if ( !file_exists( $file_path ) )
        {
            $msg        = Messager::get(
                'html',
                'file_partial_not_found',
                $file_caller,
                $file_path
            );
            throw new ErrorException( $msg );
        }
    }

    /**
     * Testa se está tentando chamar o fragmento de página recursivamente.
     */
    public function testIncludindPartialRecursivly( $file_path, $file_caller )
    {
        if ( isset( $this->including[ $file_path ] ) )
        {
            $msg        = Messager::get(
                'html',
                'recursive_request_in_partial',
                $file_caller,
                $file_path
            );
            $msg        = 
                "Erro ao interpretar o arquivo '{$file_caller}': " .
                "solicitação recursiva em '$file_path'!";
            throw new ErrorException( $msg );
        }
    }
}
