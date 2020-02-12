<?php

namespace JF\Domain;

use JF\Config;
use JF\Exceptions\ErrorException;
use JF\Exceptions\WarningException;
use JF\FileSystem\Dir;
use JF\Reflection\DocBlockParser;
use JF\User;

/**
 * Classe de funcionalidades do domínio.
 */
class Feature
{
    /**
     * Indica se a funcionalidade exige permissão de acesso.
     */
    protected static $requirePermission = false;

    /**
     * Etapas da execução.
     */
    protected $_steps = [];

    /**
     * Mensagem de retorno.
     */
    protected $msg = '';

    /**
     * Método construtor.
     */
    public function __construct()
    {
        $this->setSteps();
    }

    /**
     * Define as etapas da funcionalidade.
     */
    protected function setSteps()
    {
    }

    /**
     * Define as etapas da funcionalidade.
     */
    public function getSteps()
    {
        $steps = $this->_steps;

        foreach ( $steps as &$step )
        {
            $reflection = new \ReflectionMethod( $this, $step );
            $comment    = $reflection->getDocComment();
            $step       = DocBlockParser::parse( $comment )->getDescription();
        }

        return $steps;
    }

    /**
     * Implementa a execução da funcionalidade.
     */
    protected function execution()
    {

    }

    /**
     * Cria uma nova instância da entidade.
     */
    public static function instance( $props = [] )
    {
        $instance = new static();
        
        foreach ( $props as $key => $value )
        {
            $instance->set( $key, $value );
        }

        return $instance;
    }

    /**
     * Cria uma instância da operação e executa.
     */
    public static function run()
    {
        return static::instance()->execute();
    }

    /**
     * Define um valor para a funcionalidade.
     */
    public function set( $key, $value )
    {
        $this->$key = $value;

        return $this;
    }

    /**
     * Exporta as propriedades da funcionalidade.
     */
    public static function export()
    {
        $reflection     = new \ReflectionClass( get_called_class() );
        $props          = $reflection->getProperties();
        $response       = (object) [];

        foreach ( $props as $prop )
        {
            if ( !$prop->isPublic() || $prop->isStatic() )
            {
                continue;
            }

            $comment            = $prop->getDocComment();
            $name               = $prop->getName();
            $response->$name    = DocBlockParser::parse( $comment )->getDescription();
        }

        return $response;
    }

    /**
     * Define uma etapa da execução.
     */
    public function step( $step )
    {
        if ( !method_exists( $this, $step ) )
        {
            $reflection = new \ReflectionClass( $this );
            $comment    = $reflection->getDocComment();
            $desc       = DocBlockParser::parse( $comment )->getDescription();
            $msg        = "Método $step não encontrado na funcionalidade \"$desc\".";
            
            throw new ErrorException( $msg );
        }

        $this->_steps[ $step ] = $step;
    }

    /**
     * Aplica as regras de negócio da funcionalidade.
     */
    public function execute()
    {
        $req_permission = static::$requirePermission;

        if ( $req_permission && !User::get() )
        {
            $msg        = 'Usuário identificado.';
            throw new ErrorException( $msg );
        }

        if ( $req_permission && !User::hasPermission( get_called_class() ) )
        {
            $msg        = 'Usuário sem permissão para executar a operação.';
            throw new ErrorException( $msg );
        }

        $entities       = [];
        $reflection     = new \ReflectionClass( get_called_class() );
        $props          = $reflection->getProperties();

        foreach ( $props as $prop )
        {
            if ( !$prop->isPublic() || $prop->isStatic() )
            {
                continue;
            }

            $comment    = $prop->getDocComment();
            $tags       = DocBlockParser::parse( $comment )->getTags();

            if ( empty( $tags[ 'entity' ] ) )
            {
                continue;
            }
            
            $entities[] = $prop->name;
        }

        foreach ( $entities as $entity )
        {
            $this->$entity->validate();
        }

        foreach ( $this->_steps as $step )
        {
            $this->$step();
        }

        return $this->execution();
    }

    /**
     * Aplica as regras de negócio da operação.
     */
    public function applyRules( $context = null )
    {
        $classname      = get_called_class();
        $reflection     = new \ReflectionClass( $classname );
        $rule_ns        = $reflection->getNamespaceName() . '\\Rules';
        $rule_ns       .= $context
            ? '\\' . $context
            : '';
        $dir_rules      = 1;

        $namespaces     = Config::get( 'namespaces' );
        $rules_path     = $rule_ns;

        foreach ( $namespaces as $ns => $path )
        {
            if ( strpos( $rule_ns, $ns ) === 0 )
            {
                $rules_path = $path . substr( $rule_ns, strlen( $ns ) );
                break;
            }
        }

        $rules_path     = str_replace( '\\', '/', $rules_path );
        $rules_path     = DIR_BASE . '/' . $rules_path;

        if ( !file_exists( $rules_path ) )
        {
            Dir::makeDir( $rules_path );
        }

        $dir_rules      = new \FilesystemIterator( $rules_path );
        $rulemodel      = 'JF\\Domain\\Rule';

        foreach ( $dir_rules as $item )
        {
            if ( !$item->isFile() )
            {
                continue;
            }

            $filename   = $item->getFileName();
            $ruleclass  = $rule_ns . '\\' . substr( $filename, 0, -4 );

            if ( !class_exists( $ruleclass ) )
            {
                $msg    = "Regra de negócio {$ruleclass} não encontrada para a operação {$classname}.";
                throw new ErrorException( $msg );
            }

            if ( !is_subclass_of( $ruleclass, $rulemodel ) )
            {
                $msg    = "{$ruleclass} não estende à classe $rulemodel.";
                throw new ErrorException( $msg );
            }

            $rule_obj   = new $ruleclass( $this );
            $rule_obj->execute();
        }
    }

    /**
     * Aplica as regras de negócio da operação.
     */
    public function applyRule( $rule )
    {
        $rule       = str_replace( '.', '\\', $rule );
        $classname  = get_called_class();
        $reflection = new \ReflectionClass( $classname );
        $namespace  = $reflection->getNamespaceName();
        $ruleclass  = $namespace . '\\Rules\\' . $rule . '__Rule';
        $rulemodel  = 'JF\\Domain\\Rule';

        if ( !class_exists( $ruleclass ) )
        {
            $msg    = "Regra de negócio {$ruleclass} não encontrada para a operação {$classname}.";
            throw new ErrorException( $msg );
        }

        if ( !is_subclass_of( $ruleclass, $rulemodel ) )
        {
            $msg    = "{$ruleclass} não estende à classe $rulemodel.";
            throw new ErrorException( $msg );
        }

        $rule_obj   = new $ruleclass( $this );
        $rule_obj->execute();
    }

    /**
     * Retorna a mensagem de resposta da operação.
     */
    public function msg()
    {
        return $this->msg;
    }
}
