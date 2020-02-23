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
     * Números da classe.
     */
    private $classNumbers = [];

    /**
     * Números dos métodos.
     */
    private $methodNumbers = [];

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
        print_r( $this );exit;
        $this->makeAnalyse();
    }

    /**
     * Obtém o nome do arquivo da documentação.
     */
    public function getAnalyseFile()
    {
        $classpath      = \JF\Autoloader::getClassFilename( $this->feature );
        $classpath      = dirname( $classpath );
        $this->docfile  = $classpath . '/.analyse-feature';
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
    }

    /**
     * Obtém os números dos métodos.
     */
    public function getMethodNumbers()
    {
        foreach ( $this->classMethods as $method )
        {
            $method_name = $method->getName();
            
            $this->methodNumbers[ $method_name ] = [
                'params'            => $method->getNumberOfParameters(),
                'lines'             => $method->getEndLine() - $method->getStartLine(),
            ];
        }
    }
}
