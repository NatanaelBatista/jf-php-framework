<?php

namespace JF\Domain;

/**
 * Classe de funcionalidades do domínio.
 */
abstract class Rule
{
    /**
     * Método construtor.
     */
    public function __construct( Feature $feature )
    {
        foreach ( $feature as $key => $value )
        {
            $this->$key = $value;
        }
    }

    /**
     * Aplica a regra de negócio.
     */
    abstract function execute();
}
