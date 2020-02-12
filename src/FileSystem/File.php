<?php

namespace JF\FileSystem;

use JF\Exceptions\ErrorException;
use JF\Messager;

/**
 * Classe que manipula arquivos.
 */
class File
{
    /**
     * Modos de abertura de arquivo para leitura.
     */
    protected static $readModes = [ 'r', 'r+', 'w+', 'a+', 'x+' ];

    /**
     * Modos de abertura de arquivo para escrita.
     */
    protected static $writeModes = [ 'r+', 'w', 'w+', 'a', 'a+', 'x', 'x+' ];

    /**
     * Nome do arquivo.
     */
    protected $filename = null;

    /**
     * Instância do arquivo.
     */
    protected $file = null;

    /**
     * Modo no qual o arquivo foi aberto.
     */
    protected $mode = null;

    /**
     * Método para obter o tipo mime do arquivo.
     */
    public function __construct( $filename = null )
    {
        $this->setFilename( $filename );
    }
 
    /**
     * Método para definir o nome do arquivo a ser manipulado.
     */
    public static function humanizeFilesize( $size )
    {
        $size_kb    = 1024;
        $size_mb    = $size_kb * 1024;
        $size_gb    = $size_mb * 1024;
        $size_tb    = $size_gb * 1024;
        $size_pb    = $size_tb * 1024;

        if ( $size > $size_tb )
        {
            $human_size  = round( $size / $size_tb, 1 );
            return number_format( $human_size, 1, ',', '.' ) . ' Pb';
        }

        if ( $size > $size_tb )
        {
            $human_size  =  round( $size / $size_tb, 1 );
            return number_format( $human_size, 1, ',', '.' ) . ' Tb';
        }
        
        if ( $size > $size_gb )
        {
            $human_size  =  round( $size / $size_gb, 1 );
            return number_format( $human_size, 1, ',', '.' ) . ' Gb';
        }
        
        if ( $size > $size_mb )
        {
            $human_size  =  round( $size / $size_mb, 1 );
            return number_format( $human_size, 1, ',', '.' ) . ' Mb';
        }
        
        if ( $size > $size_kb )
        {
            $human_size  =  round( $size / $size_kb, 1 );
            return number_format( $human_size, 1, ',', '.' ) . ' Kb';
        }

        $meter      = 'byte';
        return $size . ' bytes';
    }
 
    /**
     * Método para definir o nome do arquivo a ser manipulado.
     */
    public function setFilename( $filename )
    {
        if ( !$filename || $this->filename === $filename )
        {
            return false;
        }
        
        if ( file_exists( $filename ) && !is_file( $filename ) )
        {
            return false;
        }

        $this->filename     = $filename;
        $this->file         = null;

        return true;
    }
    
    /**
     * Método para obter o tipo mime do arquivo.
     */
    public function mimeType()
    {
        // Se for uma imagem, pega o mime type da image
        $int_myme_type  = exif_imagetype( $this->filename );
        
        if ( $int_myme_type )
        {
            return image_type_to_mime_type( $int_myme_type );
        }
        
        $finfo          = new \Finfo( FILEINFO_MIME );
        $infos          = explode( ';', $finfo->file( $this->filename ) );
        $mime_type      = $infos[ 0 ];
        
        return $mime_type;
    }

    /**
     * Método para obter o arquivo a ser manipulado.
     */
    public function file( $mode = null )
    {
        if ( $this->file && ( !$mode || $this->mode === $mode ) )
        {
            return $this->file;
        }

        $valid_modes    = [ 'r', 'r+', 'w', 'w+', 'a', 'a+', 'x', 'x+' ];
        $mode_read      = in_array( $mode, self::$readModes );
        $mode_write     = in_array( $mode, self::$writeModes );
        $file_exists    = file_exists( $this->filename );
        
        if ( !$mode )
        {
            $msg        = Messager::get( 'file', 'empty_open_mode' );
            throw new ErrorException( $msg );
        }
        
        if ( !in_array( $mode, $valid_modes ) )
        {
            $msg        = Messager::get( 'file', 'invalid_open_mode' );
            throw new ErrorException( $msg );
        }

        if ( !$this->filename )
        {
            $msg        = Messager::get( 'file', 'empty_filename' );
            throw new ErrorException( $msg );
        }
        
        if ( !$file_exists && $mode === 'r' )
        {
            $msg        = Messager::get( 'file', 'file_not_found' );
            throw new ErrorException( $msg );
        }
        
        $is_not_readable    = !is_readable( $this->filename );
        $is_not_writable    = !is_writable( $this->filename );
        
        if ( $file_exists && $mode_read && $is_not_readable )
        {
            $msg            = Messager::get( 'file', 'file_not_radable' );
            throw new ErrorException( $msg );
        }
        
        if ( $file_exists && $mode_write && $is_not_writable )
        {
            $msg            = Messager::get( 'file', 'file_not_writable' );
            throw new ErrorException( $message );
        }

        $this->file         = new \SplFileObject( $this->filename, $mode );
        $this->mode         = $mode;
        return $this->file;
    }
}
