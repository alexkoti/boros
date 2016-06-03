<?php
/**
 * ==================================================
 * ADMIN OPTIONS PAGES ==============================
 * ==================================================
 * 
 * 
 * 
 */

class Boros_Admin_Pages {
    
    /**
     * Configuração das páginas, fornecido pelo plugin
     * 
     */
    private $pages = array();
    
    /**
     * Registro dos hooks de cada página adicionada
     * 
     */
    private $hooks = array();
    
    /**
     * Core pages/sections
     * 
     */
    private $core_pages = array(
        'index.php',                // Dashboard
        'edit.php',                 // Posts
        'upload.php',               // Media
        'edit.php?post_type=page',  // Pages
        'edit-comments.php',        // Comments
        'themes.php',               // Appearance
        'plugins.php',              // Plugins
        'users.php',                // Users
        'tools.php',                // Tools
        'options-general.php',      // Settings
    );
    
    private $path;
    
    private $url;
    
    private $current_page;
    
    private $current_page_config;
    
        private $views = array();
        
        private $not_founds = array();
    
    // ===================
    
    /**
     * 
     * 
     * 1* a prioridade 9 é necessária ao 'admin_menu' para as situações onde um post_type ficará como sub-menu de uma options-page. Assim 
     * a page será registrada antes do post_type, fazendo com que o primeiro level do menu aponte para a option-page, caso contrário será 
     * apontado para o post_type.
     */
    public function __construct( $config ){
        //$this->debug( 'Boros_Admin_Pages::__construct()' );
        //$this->debug( $config, '$config ORIGINAL' );
        
        // Configurações
        $this->pages = $config['pages'];
        $this->path = $config['path'];
        $this->url = $config['url'];
        
        // Normalizar configuração
        $this->normalize_config();
        
        // Definir as permissões personalizadas
        $this->set_pages_capabilities();
        
        //$this->debug( $this, '$config PROCESSADO' );
        
        // Registrar as páginas
        add_action( 'admin_menu', array($this, 'register_pages'), 9 ); // ver 1*
        
        // Adicionar hooks para a página corrente: registrar elements, enqueues
        
        
        // debug
        add_action( 'admin_footer', array($this, 'admin_footer') );
    }
    
    /**
     * Normalizar as configurações de páginas
     * 
     */
    function normalize_config(){
        foreach( $this->pages as $page_name => $attr ){
            if( !in_array( $page_name, $this->core_pages ) ){
                $this->pages[$page_name]['page_title']     = $attr['page_title'];
                $this->pages[$page_name]['menu_title']     = $attr['menu_title']; 
                $this->pages[$page_name]['capability']     = isset($attr['capability']) ? $attr['capability'] : 'manage_options';
                $this->pages[$page_name]['menu_slug']      = apply_filters('boros_menu_page_slug', $page_name, $attr);
                $this->pages[$page_name]['icon_url']       = isset($attr['icon_url']) ? $attr['icon_url'] : '';
                $this->pages[$page_name]['position']       = isset($attr['position']) ? $attr['position'] : null;
                
                // Caso existam subpages registradas, adicionar, herdando a 'capability', caso não declarada.
                // Caso seja uma subpage do core, declarar o $capability
                if( isset( $attr['subpages'] ) ){
                    foreach( $attr['subpages'] as $subpage_name => $subattr ){
                        $capability = isset($subattr['capability']) ? $subattr['capability'] : $attr['capability'];
                        $this->pages[$page_name]['subpages'][$subpage_name] = array(
                            'parent_slug'    => $page_name, 
                            'page_title'     => $subattr['page_title'], 
                            'menu_title'     => $subattr['menu_title'], 
                            'capability'     => $capability, 
                            'menu_slug'      => apply_filters('boros_menu_page_slug', $subpage_name, $subattr), 
                        );
                    }
                }
            }
        }
    }
    
    /**
     * Aplica as capabilities de cada página para que se possa aplicar os filtros adequados, ja que a capability padrão de admin_pages é 'manage_options'
     * 
     * @todo: testar a aplicação correta dos filtros de permissão
     * 
     */
    function set_pages_capabilities(){
        foreach( $this->pages as $page_name => $attr ){
            $this->set_page_capability( $page_name, $attr );
            
            if( isset($attr['subpages']) ){
                foreach( $attr['subpages'] as $subpage_name => $sub_options ){
                    $this->set_page_capability( $subpage_name, $sub_options );
                }
            }
            
            if( isset($attr['tabs']) ){
                foreach( array_slice( $attr['tabs'], 1 ) as $tab => $title ){
                    $attr['page_title'] = $attr['menu_title'] = $title;
                    $this->set_page_capability( "{$page_name}_{$tab}", $attr );
                }
            }
        }
    }
    
    /**
     * Adicionar filtro para aceitar o capability declarado corretamente. Ver arquivo wp-admin/options.php, filtro "option_page_capability_{$option_page}"
     * Pular caso seja uma página do core.
     * 
     * @link http://wordpress.org/support/topic/wordpress-settings-api-cheatin-uh-error#post-2219995
     */
    function set_page_capability( $page_name, $options ){
        if( !in_array( $page_name, $this->core_pages ) ){
            if( isset($options['capability']) ){
                add_filter( "option_page_capability_{$page_name}", create_function(NULL, "return '{$options['capability']}';")  );
            }
        }
    }
    
    /**
     * Registrar as páginas no menu do admin
     * 
     */
    public function register_pages(){
        foreach( $this->pages as $page_name => $attr ){
            if( !in_array( $page_name, $this->core_pages ) ){
                
                // Registrar página no menu, e adicionar o hook no array $hooks
                $page_hook = add_menu_page(
                    $page_title     = $attr['page_title'], 
                    $menu_title     = $attr['menu_title'], 
                    $capability     = $attr['capability'], 
                    $menu_slug      = $attr['menu_slug'], 
                    $function       = array( $this, 'output' ),
                    $icon_url       = $attr['icon_url'],
                    $position       = $attr['position']
                );
                $this->hooks[$page_name] = $page_hook;
                
                // Adicionar hook de load
                add_action( "load-{$page_hook}", array($this, 'load') );
                
                // Adicionar css/js
                add_action( 'admin_enqueue_scripts', array($this, 'enqueues') );
                
                // @todo Adicionar help nativo
                //add_action( 'load-'.$admin_page, array( $this, 'add_help' ) );
                
                // Adicionar subpages
                if( isset( $attr['subpages'] ) ){
                    foreach( $attr['subpages'] as $subpage_name => $subattr ){
                        $subpage_hook = add_submenu_page(
                            $parent_slug    = $page_name, 
                            $page_title     = $subattr['page_title'], 
                            $menu_title     = $subattr['menu_title'], 
                            $capability     = $subattr['capability'], 
                            $menu_slug      = $subattr['menu_slug'], 
                            $function       = array( $this, 'output' )
                        );
                        $this->hooks[$subpage_name] = $subpage_hook;
                        add_action( "load-{$subpage_hook}", array($this, 'load') );
                    }
                }
            }
        }
    }
    
    /**
     * Adiciona css/js
     * 
     */
    public function enqueues( $hook ){
        if ( $this->current_hook != $hook ) {
            return;
        }
        
        // Enqueue JS
        if( isset($this->current_page_config['enqueues']['js']) ){
            foreach( $this->current_page_config['enqueues']['js'] as $js ){
                // Verificar 4 opções: array, registered, absolute, relative
                
                // array de configuração completo
                if( is_array($js) ){
                    wp_enqueue_script( $js[0], $js[1], $js[2], $js[3], $js[4] );
                }
                // absolute
                elseif( filter_var($js, FILTER_VALIDATE_URL) ){
                    $pathinfo = pathinfo($js);
                    $this->debug( $pathinfo['filename'], 'absolute script: ' );
                    wp_enqueue_script( $pathinfo['filename'], $js, false, false, false );
                }
                // relative ou registered
                else{
                    // registered?
                    $registered = wp_script_is($js, 'registered');
                    if( $registered === true ){
                        $this->debug( $js, 'registered script: ' );
                        wp_enqueue_script( $js );
                        continue;
                    }
                    
                    // relative
                    $file = $this->path . $js;
                    if( file_exists($file) ){
                        $pathinfo = pathinfo($file);
                        $this->debug($pathinfo, 'relative script: ');
                        wp_enqueue_script( $pathinfo['filename'], $file );
                    }
                }
            }
        }
        
        // Enqueue CSS
        if( isset($this->current_page_config['enqueues']['css']) ){
        foreach( $this->current_page_config['enqueues']['css'] as $css ){
                // Verificar 4 opções: array, registered, absoluto, relativo
                
                // array de configuração completo
                if( is_array($css) ){
                    wp_enqueue_style( $css[0], $css[1], $css[2], $css[3], $css[4] );
                }
                // absolute
                elseif( filter_var($js, FILTER_VALIDATE_URL) ){
                    $pathinfo = pathinfo($css);
                    $this->debug( $pathinfo['filename'], 'absolute style: ' );
                    wp_enqueue_style( $pathinfo['filename'], $css );
                }
                // relative ou registered
                else{
                    // registered?
                    $registered = wp_style_is($css, 'registered');
                    if( $registered === true ){
                        $this->debug( $css, 'registered style: ' );
                        wp_enqueue_style( $css );
                        continue;
                    }
                    
                    // relative
                    $file = $this->path . $css;
                    if( file_exists($file) ){
                        $pathinfo = pathinfo($file);
                        $this->debug($pathinfo, 'relative style: ');
                        wp_enqueue_style( $pathinfo['filename'], $this->url . $css );
                    }
                }
            }
        }
    }
    
    public function load(){
        global $hook_suffix, $plugin_page;
        $this->debug('LOAD');
        $this->debug($hook_suffix, 'hook_suffix: ');
        $this->debug($plugin_page, 'plugin_page: ');
        
        $this->current_hook = $hook_suffix;
        $this->current_page = $plugin_page;
        $this->current_page_config = $this->get_page_config( $plugin_page );
        $this->debug($config, '$config: ');
        
        $action = current_filter();
        $this->debug($action, 'current_filter: ');
    }
    
    /**
     * 
     * 
     */
    private function get_page_config( $required ){
        foreach( $this->pages as $page_name => $attr ){
            if( $page_name == $required ){
                return $attr;
            }
            if( isset($attr['subpages']) ){
                foreach( $attr['subpages'] as $subpage_name => $subattr ){
                    if( $subpage_name == $required ){
                        return $subattr;
                    }
                }
            }
        }
    }
    
    /**
     * Exibir página de admin
     * 
     */
    public function output(){
        $action = current_filter();
        $this->debug($action, 'current_filter: ');
        
    }
    
    private function debug( $var, $label = '' ){
        if( is_array($var) or is_object($var) ){
            echo "<!-- DEBUG:: {$label} \n";
            print_r($var);
            echo " --> \n";
        }
        else{
            $label = str_pad($label, 30, ' ', STR_PAD_LEFT);
            $var = str_pad($var, 150);
            echo "<!-- DEBUG:: {$label}{$var} --> \n";
        }
    }
    
    public function admin_footer(){
        echo '<div id="debug_admin_vars">';
        pre($this, 'Boros_Admin_Pages');
        echo '</div>';
    }
    
    
}



