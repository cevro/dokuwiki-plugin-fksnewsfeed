<?php

/**
 * DokuWiki Plugin fksnewsfeed (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Michal Červeňák <miso@fykos.cz>
 */
if(!defined('DOKU_INC')){
    die();
}

/** $INPUT 
 * @news_do add/edit/
 * @news_id no news
 * @news_strem name of stream
 * @id news with path same as doku @ID
 * @news_feed how many newsfeed need display
 * @news_view how many news is display
 */
class action_plugin_fksnewsfeed_token extends DokuWiki_Action_Plugin {

    private $helper;
    private $token = array('show' => false,'id' => null);

    /**
     * Registers a callback function for a given event
     *
     * @param Doku_Event_Handler $controller DokuWiki's event controller object
     * @return void
     */
    public function __construct() {
        $this->helper = $this->loadHelper('fksnewsfeed');
    }

    /**
     * 
     * @param Doku_Event_Handler $controller
     */
    public function register(Doku_Event_Handler $controller) {
        /**
         * to render by token
         */
        $controller->register_hook('TPL_ACT_RENDER','BEFORE',$this,'ACTRenderByTocen');
        $controller->register_hook('ACTION_ACT_PREPROCESS','BEFORE',$this,'EncriptToken');
        $controller->register_hook('TPL_METAHEADER_OUTPUT','BEFORE',$this,'AddFBmeta');
    }

    public function AddFBmeta(Doku_Event &$event) {
        

        if($this->token['show']){
            $news = $this->helper->LoadSimpleNews($this->token['id']);

            $event->data['meta'][] = array('property' => 'og:title','content' => $news['name']);
            //$event->data['meta'][]=array('property'=>'og:url','content'=>$news['name']);
          
            $event->data['meta'][] = array('property' => 'og:url','content' => DOKU_URL.'?'.$_SERVER['QUERY_STRING']);
            $text = p_render('text',p_get_instructions($news['text']),$info);
            
            $event->data['meta'][] = array('property' => 'og:description','content' => $text);
            $event->data['meta'][] = array('property' => 'og:title','content' => $news['name']);
            if($news['image'] != ""){

                $event->data['meta'][] = array('property' => 'og:image','content' => ml($news['image'],array('w'=>400,'h'=>400),true,'&',true));
            }
            $event->data['meta'][] = array('property' => 'article:author','content' => $news['author']);
        }
    }

    /**
     * 
     * @param Doku_Event $event
     * @param type $param
     */
    public function ACTRenderByTocen(Doku_Event &$event) {

        if($this->token['show']){
            $e = $this->helper->_is_even($this->token['id']);
            //$event->preventDefault();
            $info = array();
            echo '<div class="FKS_newsfeed">';
            $n = str_replace(array('@id@','@even@','@edited@','@stream@'),array($this->token['id'],$e,'false',' '),$this->helper->simple_tpl);
            //var_dump($n);
            echo p_render('xhtml',p_get_instructions($n),$info);
            echo'</div>';
            $event->advise_after();
        }
    }

    /**
     * 
     * @global type $INPUT
     * @global string $ACT
     * @global type $TEXT
     * @global type $ID
     * @global type $INFO
     * @param Doku_Event $event
     * @param type $param
     */
    public function EncriptToken() {
        global $ACT;
        global $INPUT;
        global $ID;
        if($ACT != 'fksnewsfeed_token'){
            return;
        }

        $token = $INPUT->str('token');
        $this->token['id'] = self::_EncriptToken($token,$this->getConf('no_pref'),$this->getConf('hash_no'));
        $this->token['show'] = true;
        $ACT = 'show';
        $ID = 'start';
    }

    /**
     * 
     * @param type $hash
     * @param type $l
     * @param type $hash_no
     * @return type
     */
    private static function _EncriptToken($hash,$l,$hash_no) {
        $enc_hex = substr($hash,$l,-$l);
        $enc_dec = hexdec($enc_hex);
        $id = ($enc_dec - $hash_no) / 2;
        return (int) $id;
    }

}
