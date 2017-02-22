<?php

/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Michal Červeňák <miso@fykos.cz>
 */
// must be run within Dokuwiki

if (!defined('DOKU_INC')) {
    die();
}
if (!defined('DOKU_PLUGIN')) {
    define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');
}
require_once(DOKU_PLUGIN . 'syntax.php');

class syntax_plugin_fksnewsfeed_fksnewsfeedstream extends DokuWiki_Syntax_Plugin {

    /**
     * @var helper_plugin_fksnewsfeed
     */
    private $helper;

    public function __construct() {
        $this->helper = $this->loadHelper('fksnewsfeed');
    }

    public function getType() {
        return 'substition';
    }

    public function getPType() {
        return 'block';
    }

    public function getAllowedTypes() {
        return [];
    }

    public function getSort() {
        return 3;
    }

    public function connectTo($mode) {
        $this->Lexer->addSpecialPattern('\{\{fksnewsfeed-stream>.+?\}\}', $mode, 'plugin_fksnewsfeed_fksnewsfeedstream');
    }

    public function handle($match, $state) {
        $param = helper_plugin_fkshelper::extractParamtext(substr($match, 21, -2));
        return [$state, [$param]];
    }

    public function render($mode, Doku_Renderer &$renderer, $data) {
        if ($mode !== 'xhtml') {
            return true;
        }
        list(, $match) = $data;
        list($param) = $match;
        $atr = [];
        foreach ($param as $key => $value) {
            $atr['data-' . $key] = $value;
        }

        $renderer->doc .= '<noscript>
<div class="error">
<h1>Asi máte vypnutý JavasScript</h1>
            <p>Pro správne fungovaní téjto stránky je potřebné mít zapnutý 
                <a href="http://en.wikipedia.org/wiki/JavaScript">JavaScript</a>.</p>
            <p>Pokud chcete zobrazit tento web plnohodnotně 
                <a href="https://www.google.cz/search?q=how+to+turn+on+javascript">
                    zapněte si JavaScript</a>!</p></div></noscript>';
        $renderer->doc .= '<div class="FKS_newsfeed"><div class="stream" ' . buildAttributes($atr) . '></div></div>';
        return false;
    }

}
