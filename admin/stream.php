<?php

use \dokuwiki\Form\Form;
use \PluginNewsFeed\Model\Stream;

// TODO refract

class admin_plugin_fksnewsfeed_stream extends \DokuWiki_Admin_Plugin {
    /**
     * @var helper_plugin_fksnewsfeed
     */
    private $helper;

    public function __construct() {
        $this->helper = $this->loadHelper('fksnewsfeed');
    }

    public function getMenuSort() {
        return 290;
    }

    public function forAdminOnly() {
        return false;
    }

    public function getMenuText($lang) {
        return $this->getLang('stream_menu');
    }

    public function handle() {
        global $INPUT;
        $streamName = $INPUT->str('stream_name');
        if (trim($streamName) == '') {
            return;
        }

        $stream = new Stream($this->helper->sqlite, $streamName);
        $stream->findByName($streamName);
        if (!$stream->getName()) {
            $stream->fill(['name' => $streamName]);
            $stream->create();
            msg('Stream has been created', 1);
        } else {
            msg('Stream already exist', -1);
        }
    }

    public function html() {
        echo '<h1>' . $this->getLang('stream_menu') . '</h1>';
        echo '<h2>' . $this->getLang('stream_create') . '</h2>';
        echo $this->getNewStreamForm()->toHTML();
        $streams = $this->helper->getAllStreams();
        echo '<h2 id="stream_list">' . $this->getLang('stream_list') . '</h2>';
        echo('<ul>');
        foreach ($streams as $stream) {
            echo '<li class="form-group row"><span class="col-3">' . $stream->getName() . '</span>';
            echo '<input type="text" class="col-9 form-control" value="' .
                hsc('{{news-stream>stream="' . $stream->getName() . '" feed="5"}}') . '" />';
            echo '</li>';
        }
        echo '</ul>';
        echo '</div>';
    }

    private function getNewStreamForm() {
        global $lang;
        $form = new Form();
        $form->setHiddenField('news_do', 'stream_add');
        $form->addTextInput('stream_name', $this->getLang('stream'));
        $form->addButton('submit', $lang['btn_save']);
        return $form;
    }
}
