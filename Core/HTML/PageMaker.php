<?php

namespace JF\HTML;

use JF\Config;
use JF\FileSystem\Dir;
use JF\HTTP\Router;
use JF\Messager;
use JF\Exceptions\ErrorException;

/**
 * Monta páginas HTML.
 */
final class PageMaker
{
    use PageMakerCSS;
    use PageMakerJS;
    use PageMakerPartial;

    /**
     * Rota da página.
     */
    protected $route    = null;

    /**
     * Ação da rota.
     */
    protected $action   = null;

    /**
     * Conteúdo da página.
     */
    protected $html     = null;

    /**
     * Configurações da página.
     */
    protected $config   = [];

    /**
     * Documentação da página.
     */
    protected $doc      = [];

    /**
     * Dependências da view.
     */
    protected $depends  = [];

    /**
     * Inicia uma instância do objeto página.
     */
    public function __construct( $route )
    {
        $data               = [
            'route'         => $route,
            'url'           => [
                'base'      => URL_BASE,
                'ui'        => URL_UI,
                'pages'     => URL_PAGES,
                'route'     => URL_PAGES . '/' . $route,
            ],
        ];

        $this->data         = array_merge( $data, (array) Config::get( 'ui.data' ) );
        $this->route        = $route;
        $this->config       = [ 'layout' => Config::get( 'ui.default_layout' ) ];
    }
            
    /**
     * Monta uma página HTML.
     */
    public function makePage()
    {
        if ( !file_exists( DIR_LAYOUTS ) )
        {
            Dir::makeDir( DIR_LAYOUTS );
        }

        if ( !file_exists( DIR_VIEWS ) )
        {
            Dir::makeDir( DIR_VIEWS );
        }

        $view_path          = $this->getViewPath();
        $view_name          = substr( $view_path, strlen( DIR_BASE ) + 1 );

        if ( !file_exists( $view_path ) )
        {
            $msg            = Messager::get( 'html', 'page_not_created', $view_path );
            throw new ErrorException( $msg );
        }

        $this->depends[ $view_name ] = filemtime( $view_path );
        
        $view_ini           = DIR_VIEWS . '/' . $this->route . '/view.ini';
        
        if ( file_exists( $view_ini ) )
        {
            $ini            = parse_ini_file( $view_ini, true );
            $this->config   = isset( $ini[ 'CONFIG' ] )
                ? array_merge( $this->config, $ini[ 'CONFIG' ] )
                : $this->config;
            $this->data     = isset( $ini[ 'DATA' ] )
                ? array_merge( (array) $this->data, $ini[ 'DATA' ] )
                : $this->data;
        }

        $this->config       = json_decode( json_encode( $this->config ) );
        $this->data         = json_decode( json_encode( $this->data ) );

        ob_start();
        include $view_path;
        $this->html         = ob_get_clean();

        $layout_filename    = DIR_LAYOUTS . '/' . $this->config->layout . '.php';
        $layout_source      = substr( $layout_filename, strlen( DIR_BASE ) + 1 );

        if ( file_exists( $layout_filename ) )
        {
            $this->depends[ $layout_source ] = filemtime( $layout_filename );
            ob_start();
            include $layout_filename;
            $this->html     = ob_get_clean();
        }
        
        $result_components  = WebComponents\ComponentParser::parse( $this->html );
        $response           = [
            'depends'       => array_merge( $this->depends, $result_components->depends ),
            'doc'           => implode( ' ', $this->doc ),
            'html'          => $result_components->html,
        ];

        return (object) $response;
    }

    /**
     * Inclue o conteúdo da página.
     */
    public function content()
    {
        return $this->html;
    }
    
    
    /**
     * Cria o arquivo minificado e seu observador.
     */
    protected function makeMin( $type, $filename, $files, $file_monitor )
    {
        $filepath   = DIR_UI . '/design/css/' . $filename . '.min.css';
        $minified   = $type
            ? CSSMinifer::minify( $files )
            : JSMinifer::minify( $files );
        $content    = $minified->content;
        $updates    = Utils::var_export( $minified->updates, true );
        file_put_contents( $filepath, $minified->content );
        file_put_contents( $file_monitor, $updates );
    }

    /**
     * Inclue um script marcando o tempo de modificação do arquivo,
     * para forçar atualização pelo navegador do cliente.
     */
    public function ui( $filepath = '' )
    {
        $route      = explode( '/', Router::get( 'route' ) );
        $num_route  = count( $route );
        $server     = $_SERVER[ 'SERVER_NAME' ];
        
        return str_repeat( '../', $num_route ) . $filepath;
    }

    /**
     * Inclue os dados do controller na página.
     */
    public function data( $data_name )
    {
        $data = json_encode( $this->data );
        return "<script>var {$data_name} = {$data}</script>";
    }

    /**
     * Retorna o caminho para o arquivo de página.
     */
    public function getViewPath()
    {
        return DIR_VIEWS . "/{$this->route}/view.php";
    }
    
    /**
     * Define o valor de uma variável.
     */
    public function set( $key, $value )
    {
        $this->data->$key = $value;
    }
    
    /**
     * Define o layout da página.
     */
    public function setLayout( $layout )
    {
        $this->config->layout = $layout;
    }
    
    /**
     * Define o layout da página.
     */
    public function modelProps( $path, $unsafe = false )
    {
        $class_model    = 'App\\Domain\\' . str_replace( '.', '\\', $path );
        $file_model     = str_replace( '.', '/', $path );
        $file_model     = 'App/Domain/' . $file_model . '.php';
        $file_class     =  DIR_BASE . '/' . $file_model;
        $this->depends[ $file_model ] = filemtime( $file_class );

        return $class_model::getLayout( $unsafe );
    }
    
    /**
     * Obtém o caminho absoluto do arquivo.
     */
    private function getRealPath( $relative_path, $use_route_path )
    {
        $js_absolute_path   = preg_replace( '@\.\./+@', '', $relative_path );
        $diff_levels        = strlen( $relative_path ) - strlen( $js_absolute_path );
        $levels_up          = $diff_levels
            ? $diff_levels / 3
            : 0;

        $route              = $this->route
            ? $this->route . '/'
            : $this->route;

        if ( $levels_up )
        {
            $route_parts    = explode( '/', $this->route );
            $route_parts    = array_splice( $route_parts, 0, -$levels_up );
            $route          = implode( '/', $route_parts );
            $route         .= $route
                ? '/'
                : '';
        }

        $real_path          = $use_route_path
            ? $route . $js_absolute_path
            : $relative_path;
        
        return $real_path;
    }

    /**
     * Define o layout da página.
     */
    public function toDoc( $text )
    {
        $this->doc[] = $text;
    }
}
