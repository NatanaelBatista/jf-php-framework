<?php

namespace JF\Domain;

/**
 * Analisa a qualidade do código de uma funcionalidade.
 */
class FeatureCodeAnalyser
{
    /**
     * Nome da feature.
     */
    public $feature;

    /**
     * Números dos métodos.
     */
    private $errors         = [];

    /**
     * Números da classe.
     */
    private $classNumbers   = [];

    /**
     * Números dos métodos.
     */
    private $methodNumbers  = [];

    /**
     * Método construtor.
     */
    public function __construct( $feature )
    {
        $this->feature      = $feature;
    }

    /**
     * Invoca o metodo construtor.
     */
    public static function instance( $feature )
    {
        return new self( $feature );
    }

    /**
     * Solicita a análise do código da funcionalidade.
     */
    public function analyse()
    {
        $this->getAnalyseFile();
        $this->getClassNumbers();
        $this->getMethodNumbers();
        $this->makeAnalyse();
    }

    /**
     * Obtém o nome do arquivo da documentação.
     */
    public function getAnalyseFile()
    {
        $classpath      = \JF\Autoloader::getClassFilename( $this->feature );
        $source         = file_get_contents( $classpath );
        $this->source   = explode( PHP_EOL, $source );
        $this->docfile  = dirname( $classpath ) . '/.analyse-feature';
    }

    /**
     * Obtém os números da classe.
     */
    public function getClassNumbers()
    {
        $this->classReflection  = new \ReflectionClass( $this->feature );
        $this->classMethods     = [];

        $class_methods          = $this->classReflection->getMethods();
        $use_service            = in_array( 'JF\\HTTP\\HTTP_Service_Trait', class_uses( $this->feature ) );

        foreach ( $class_methods as $method )
        {
            $method_name        = $method->getName();
            
            if ( $method->getDeclaringClass()->getName() != $this->feature )
                continue;
             
            if ( $use_service && method_exists( 'JF\\HTTP\\HTTP_Service_Trait', $method_name ) )
                continue;
            
            $this->classMethods[] = $method;
        }

        $this->classNumbers     = [
            'lines'             => $this->classReflection->getEndLine(),
            'numMethods'        => count( $this->classMethods ),
        ];

        if ( $this->classReflection->getEndLine() > 200 )
            $this->errors[]     = '<Feature> Classe com mais de 200 linhas';

        if ( count( $this->classMethods ) > 30 )
            $this->errors[]     = '<Feature> Classe com mais de 30 métodos';
    }

    /**
     * Obtém os números dos métodos.
     */
    public function getMethodNumbers()
    {
        foreach ( $this->classMethods as $method )
        {
            $method_name    = $method->getName();
            $docblock       = preg_replace( '@/\*\*[\s\t]*(.*)[\s\t]*\*/@', '$1', $method->getDocComment() );
            $lines          = $this->getMethodLines( $method );
            $max_idents     = 0;
            $max_cols       = 0;

            foreach ( $lines as $i => $line )
            {
                $line       = preg_replace( '@(?:)(\t|\s{4})@', '    ', $line );
                preg_match( '@^\s*@', $line, $match );
                $tabs       = $match[ 0 ]
                    ? strlen( $match[ 0 ] ) / 4
                    : 0;
                $max_idents = max( $max_idents, $tabs );
                $max_cols   = max( $max_cols, strlen( $line ) );
            }

            if ( !isset( $method_name[ 7 ] ) )
                $this->errors[]     = "[$method_name] Método com menos de 7 caracteres";

            if ( isset( $method_name[ 25 ] ) )
                $this->errors[]     = "[$method_name] Método com mais de 25 caracteres";

            if ( !$docblock )
                $this->errors[]     = "[$method_name] Método sem DockBlock";

            if ( isset( $lines[ 20 ] ) )
                $this->errors[]     = "[$method_name] Método tem mais de 20 linhas";

            if ( $max_cols > 100 )
                $this->errors[]     = "[$method_name] Método tem linha com mais de 100 colunas";

            if ( $max_idents - 1 > 3 )
                $this->errors[]     = "[$method_name] Método tem mais de 3 níveis de identação";

            $this->methodNumbers[ $method_name ] = [
                'lenName'           => strlen( $method_name ),
                'totalParams'       => $method->getNumberOfParameters(),
                'totalLines'        => count( $lines ),
                'hasDocBlock'       => !!$docblock,
                'maxIdents'         => $max_idents,
                'maxCols'           => $max_cols,
            ];
        }
    }

    /**
     * Obtém as linhas de um método.
     */
    public function getMethodLines( $method )
    {
        $len_lines      = $method->getEndLine() - $method->getStartLine() + 1;
        $lines          = array_slice( $this->source, $method->getStartLine() - 1, $len_lines );

        foreach ( $lines as $i => $line )
        {
            if ( !preg_match( '@{@', $line ) )
                continue;

            $line       = trim( preg_replace( '@({.*?)(//.*|/\*\*.*)@', '$1', $line ) );
            $lines      = $line == '{'
                ? array_slice( $lines, $i + 1 )
                : array_slice( $lines, $i );
            break;
        }

        if ( trim( $lines[ count( $lines ) - 1 ] )[0] == '}' )
            array_pop( $lines );

        return $lines;
    }

    /**
     * Grava os erros encontrados na qualidade do código.
     */
    public function makeAnalyse()
    {
        $data = implode( PHP_EOL, $this->errors );
        file_put_contents( $this->docfile, $data );
    }
}
