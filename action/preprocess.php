<?php

use dokuwiki\Extension\ActionPlugin;
use dokuwiki\Extension\Event;
use dokuwiki\Extension\EventHandler;
use FYKOS\dokuwiki\Extenstion\PluginNewsFeed\Model\Priority;
use FYKOS\dokuwiki\Extenstion\PluginNewsFeed\Model\News;
use FYKOS\dokuwiki\Extenstion\PluginNewsFeed\Model\Stream;

/**
 * Class action_plugin_newsfeed_preprocess
 * @author Michal Červeňák <miso@fykos.cz>
 */
class action_plugin_newsfeed_preprocess extends ActionPlugin {

    private helper_plugin_newsfeed $helper;

    public function __construct() {
        $this->helper = $this->loadHelper('newsfeed');
    }

    public function register(EventHandler $controller): void {
        $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, 'actPreprocess');
    }

    public function actPreprocess(Event $event): void {
        global $INPUT;
        if ($event->data !== helper_plugin_newsfeed::FORM_TARGET) {
            return;
        }
        if (auth_quickaclcheck('start') < AUTH_EDIT) {
            return;
        }
        $event->preventDefault();
        $event->stopPropagation();
        switch ($INPUT->param('news')['do']) {
            case 'create':
            case 'edit':
            default:
                return;
            case'save':
                $this->saveNews();
                return;
            case'priority':
                $this->savePriority();
                return;
            case'delete':
                $this->saveDelete();
                return;
            case'purge':
                $this->deleteCache();
                return;
        }
    }

    private function saveNews(): void {
        global $INPUT;

        $file = News::getCacheFileById($INPUT->param('news')['id']);
        $cache = new cache($file, '');
        $cache->removeCache();

        $data = [];
        foreach (helper_plugin_newsfeed::$fields as $field) {
            if ($field === 'text') {
                $data[$field] = cleanText($INPUT->str('text'));
            } else {
                $data[$field] = $INPUT->param($field);
            }
        }
        $news = new News($this->helper->sqlite);
        $news->setTitle($data['title']);
        $news->setAuthorName($data['authorName']);
        $news->setAuthorEmail($data['authorEmail']);
        $news->setText($data['text']);
        $news->setNewsDate($data['newsDate']);
        $news->setImage($data['image']);
        $news->setCategory($data['category']);
        $news->setLinkHref($data['linkHref']);
        $news->setLinkTitle($data['linkTitle']);
        if ($INPUT->param('news')['id'] == 0) {
            $newsId = $news->create();
            $this->saveIntoStreams($newsId);
        } else {
            $news->setNewsId($INPUT->param('news')['id']);
            $news->update();
        }
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit();
    }

    private function saveIntoStreams($newsId) {
        global $INPUT;
        $stream = new Stream($this->helper->sqlite, null);
        $stream->findByName($INPUT->param('news')['stream']);
        $streamId = $stream->getStreamId();

        $streams = [$streamId];
        $this->helper->fullParentDependence($streamId, $streams);
        foreach ($streams as $stream) {
            $priority = new Priority($this->helper->sqlite, null, $newsId, $stream);
            $priority->create();
        }
    }

    private function savePriority(): void {
        global $INPUT;
        $file = News::getCacheFileById($INPUT->param('news')['id']);

        $cache = new cache($file, '');
        $cache->removeCache();
        $stream = new Stream($this->helper->sqlite, null);
        $stream->findByName($INPUT->param('news')['stream']);
        $streamId = $stream->getStreamId();

        $priority = new Priority($this->helper->sqlite, null, $INPUT->param('news')['id'], $streamId);
        $data = $INPUT->param('priority');
        $priority->setPriorityFrom($data['from']);
        $priority->setPriorityTo($data['to']);
        $priority->setPriorityValue($data['value']);
        $priority->checkValidity();
        if ($priority->update()) {
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit();
        }
    }

    private function saveDelete(): void {
        global $INPUT;
        $stream = new Stream($this->helper->sqlite, null);
        $stream->findByName($INPUT->param('news')['stream']);
        $streamId = $stream->getStreamId();
        $priority = new Priority($this->helper->sqlite, null, $INPUT->param('news')['id'], $streamId);
        $priority->delete();
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit();
    }

    private function deleteCache(): void {
        global $INPUT;
        if (!$INPUT->param('news')['id']) {
            $news = $this->helper->allNewsFeed();
            foreach ($news as $new) {
                $f = $new->getCacheFile();
                $cache = new cache($f, '');
                $cache->removeCache();
            }
        } else {
            $f = News::getCacheFileById($INPUT->param('news')['id']);
            $cache = new cache($f, '');
            $cache->removeCache();
        }
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit();
    }
}
