<?php

/**
 * Classe simples para requisitar o feed público de usuários do Instagram
 * 
 * 
 */

class Boros_Instagram_Public_Feed {

    /**
     * Tempos de execução
     * 
     */
    protected $time_start     = '';
    protected $time_end       = '';
    protected $execution_time = '';
    protected $is_cached      = false;

    /**
     * Usuário a ser requisitado
     * 
     */
    protected $username = '';

    /**
     * Dados do usuário
     * 
     */
    protected $user_data = array(
        'username'  => '',
        'bio'       => '',
        'link'      => '',
        'avatar'    => '',
        'followers' => '',
        'business'  => '',
    );

    /**
     * Midias do profile
     * 
     */
    protected $user_medias = array();

    /**
     * Lista de erros
     * 
     */
    protected $errors = array();
    protected $error_message = array(
        'user-not-set'          => 'Usuário não definido',
        'instagram-unreachable' => 'Não foi possível conectar com o Instagram',
        'user-not-found'        => 'O perfil do Instagram não foi encontrado',
        'feed-not-found'        => 'Não foi possível recuperar as informações do perfil do Instagram',
        'private-profile'       => 'O perfil do Instagram é privado',
    );

    /**
     * Array do feed extraído do HTMl do profile
     * 
     */
    protected $profile_feed = array();

    /**
     * Construct
     * 
     */
    function __construct(){

    }

    public function init( $username, $cache = 60, $transient_name = false ){

        if( $transient_name == false ){
            $transient_name = "instagram_public_feed_{$username}";
        }

        if( $cache > 0 ){
            $cached = get_transient($transient_name);
            if( $cached != false ){
                $this->user_data   = $cached['user_data'];
                $this->user_medias = $cached['user_medias'];
                $this->is_cached   = true;
                return;
            }
        }

        $this->username = $username;

        // log início da execução
        $this->time_start = microtime(true);

        /**
         * Verificar conexão com o instagram
         * 
         */
        $connection = $this->check_connection();
        if( !$connection ){
            // retornar ao formulário inicial, alertando a falha de conexão
            $this->errors['user']['error'] = $this->error_message['instagram-unreachable'];
            return;
        }

        /**
         * Recuperar HTML do profile
         * 
         */
        $profile_html = $this->check_userpage( $this->username );

        if( !$profile_html ){
            $this->errors['user']['error'] = $this->error_message['user-not-found'];
            return;
        }
        else{
            $this->profile_feed = $this->extract_json($profile_html);

            if( $this->profile_feed ){

                $this->user_data = $this->set_user_data();

                // perfil privado
                if( $this->user_data['private'] == true ){
                    // retornar ao formulário inicial, alertando perfil privado
                    $this->errors['user']['error'] = $this->error_message['private-profile'];
                    return;
                }
    
                $this->user_medias = $this->set_user_medias();

                // salvar transient
                if( $cache > 0 ){
                    set_transient( $transient_name, array('user_data' => $this->user_data, 'user_medias' => $this->user_medias), $cache );
                }
            }
            else{
                // retornar ao formulário inicial, alertando a falha de requisição
                $this->errors['user']['error'] = $this->error_message['feed-not-found'];
                return;
            }
        }

        // fim da execução
        $this->time_end = microtime(true);
        $this->execution_time = ($this->time_end - $this->time_start);
    }

    /**
     * Verificar conexão com instagram
     * 
     */
    protected function check_connection( $host = 'www.instagram.com' ){
        // verificar DNS existente
        $dns = @dns_get_record($host);
        if( empty($dns) ){
            return false;
        }
        
        // verificar conexão direta com o site
        $connected = fsockopen( $host, '80', $errno, $errstr, 1 );
        if( !$connected && $errno == 0 ){
            return false;
        }
        return true;
    }

    /**
     * Requisição cURL
     * 
     */
    protected function request( $url ){
        $handle = curl_init($url);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($handle, CURLOPT_BINARYTRANSFER, true);

        /* Get the HTML or whatever is linked in $url. */
        $response = curl_exec($handle);

        /* Check for 404 (file not found). */
        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        if( $httpCode == 404 ){
            return false;
        }
        curl_close($handle);

        // retornar HTML
        return $response;
    }

    /**
     * Verificar usuário existente
     * 
     */
    protected function check_userpage( $username ){
        return $this->request("https://www.instagram.com/{$username}/");
    }

    /**
     * Extrair json do HTML
     * 
     */
    protected function extract_json( $html ){

        // iniciar PHPDOm
        $dom = new DOMDocument();

        // suprimir erros de tags HTML5
        libxml_use_internal_errors(true);

        // carregar HTML
        $dom->loadHTML($html);

        // buscar o script/json por xpath
        $xpath      = new DomXPath($dom);
        $script_tag = $xpath->query('/html/body/script[1]')->item(0);
        if( $script_tag ){
            $json = str_replace('window._sharedData = ', '', rtrim($script_tag->nodeValue, ';'));
            return json_decode( $json );
        }
        else{
            return '';
        }
    }

    /**
     * Criar array de dados do usuário
     * 
     */
    protected function set_user_data(){
        $user = $this->profile_feed->entry_data->ProfilePage[0]->graphql->user;
        return array(
            'username'  => $this->username,
            'bio'       => $user->biography,
            'link'      => $user->external_url,
            'avatar'    => $user->profile_pic_url,
            'followers' => $user->edge_followed_by->count,
            'business'  => $user->is_business_account,
            'private'   => $user->is_private,
        );
    }

    /**
     * Criar array de midias mais recentes
     * 
     * @todo remover bloco de testes de location
     * 
     */
    protected function set_user_medias(){
        $media_types = array(
            'GraphSidecar' => 'gallery',
            'GraphVideo'   => 'video',
            'GraphImage'   => 'image',
        );
        $medias = array();
        $feed_medias = $this->profile_feed->entry_data->ProfilePage[0]->graphql->user->edge_owner_to_timeline_media->edges;
        foreach( $feed_medias as $i => $m ){

            $thumbs = array(
                'default' => $m->node->thumbnail_src,
            );
            foreach( $m->node->thumbnail_resources as $t ){
                $thumbs[ $t->config_width ] = array(
                    'src'    => $t->src,
                    'width'  => $t->config_width,
                    'height' => $t->config_height,
                );
            }

            $medias[] = array(
                'id'          => $m->node->id,
                'shortcode'   => $m->node->shortcode,
                'url'         => "https://www.instagram.com/p/{$m->node->shortcode}/",
                'type'        => $media_types[$m->node->__typename],
                'is_video'    => $m->node->is_video,
                'comments'    => $m->node->edge_media_to_comment->count,
                'likes'       => $m->node->edge_liked_by->count,
                'caption'     => (!empty($m->node->edge_media_to_caption->edges)) ? $m->node->edge_media_to_caption->edges[0]->node->text : '',
                'date'        => $this->get_media_date( $m->node->taken_at_timestamp ),
                'location'    => $m->node->location,
                'display_url' => $m->node->display_url,
                'thumbnails'  => $thumbs,
            );
        }

        //$this->debug($medias);
        return $medias;
    }

    /**
     * Transformar timestamp em GMT
     * 
     */
    protected function get_media_date( $timestamp ){
        $datetime = new \DateTime();
        $datetime->setTimezone( new DateTimeZone('America/Sao_Paulo') );
        $datetime->setTimestamp( $timestamp );
        return $datetime->format('Y-m-d H:i:s');
    }

    /**
     * Retornar resultados
     * 
     */
    public function user_data(){
        return $this->user_data;
    }
    
    public function user_medias(){
        return $this->user_medias;
    }
    
    public function is_cached(){
        return $this->is_cached;
    }

    public function get_errors(){
        return $this->errors;
    }

}


