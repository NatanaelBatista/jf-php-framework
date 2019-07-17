<?php

$this->set( 'module',   'Introdução' );
$this->set( 'title',    'Requisitos' );

$parser     = new \JF\Markdown\MDParser();
echo $parser->file( DIR_GUIDE . '/conceitos-basicos.md' );
?>
