<?php
include_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Channel.php');
include_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'ChannelsApi.php');
include_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'GenresApi.php');


class OnlinefmApi {

    const API_VERSION = '0.3.1';

    private $_channelsApi = null;
    private $_genresApi = null;


    public function __construct(array $options = array()) {
        $this->_channelsApi = new ChannelsApi($options);
        $this->_channelsApi->setChannels();
        $this->_genresApi = new GenresApi($options);
    }

    public function getChannels() {
        return array_values($this->_channelsApi->getChannels());
    }

    public function setChannels($ids) {
        return $this->_channelsApi->setChannels($ids);
    }

    public function getGenres() {
        return $this->_genresApi->getGenres();
    }

    public function renderList() {
        return $this->_channelsApi->renderList();
    }

    public function renderChannelPlayingNow() {
        return $this->_channelsApi->renderChannelPlayingNow();
    }

    public function renderChannelPlaylist() {
        return $this->_channelsApi->renderChannelPlaylist();
    }

    public function renderGenres() {
        return $this->_genresApi->renderGenres();
    }

    public function renderCSS() {
        $html = $this->_channelsApi->renderCSS();
        $html .= "\n";
        $html .= $this->_genresApi->renderCSS();
        return $html;
    }

    public function renderListJS() {
        return $this->_channelsApi->renderListJS();
    }

    public function renderChannelJS() {
        return $this->_channelsApi->renderChannelJS();
    }

}
