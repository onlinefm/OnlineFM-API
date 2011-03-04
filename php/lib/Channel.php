<?php

include_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'MemcacheWrapper.php');


class Channel {

    private $_id = null;
    private $_name = null;
    private $_slug = null;
    private $_text_uk = null;
    private $_text_ru = null;
    private $_text_en = null;
    private $_logo = null;
    private $_onlinefmUrl = null;
    private $_streamsHttp = null;
    private $_streamsRtmp = null;
    private $_lang = null;
    private $_channelsUrl = null;
    private $_playlistUrl = null;
    private $_playlist = null;
    private $_isReady = false;


    public function __construct($data, $lang) {
        $config = include(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'config.php');
        $this->_lang = $lang;

        if (is_int($data)) {
            $this->_id = $data;
            $this->_channelsUrl = sprintf($config['urls']['channels'],
                                          $config['token']);
            $this->_initChannel($config['memcache']['keys']['channels'],
                                $config['memcache']['lifetime']);
        }
        else {
            $this->_initChannelData($data);
        }
        $this->_playlistUrl = sprintf($config['urls']['playlist_single'],
                                      $config['token'],
                                      $this->_id);
        $this->_initPlaylist(sprintf("%s_%d",
                                     $config['memcache']['keys']['playlist'],
                                     $this->_id));
    }

    public static function getChannelsData() {
        $config = include(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'config.php');
        $channelsUrl = sprintf($config['urls']['channels'],
                               $config['token']);
        if ($channelsData = @file_get_contents($channelsUrl)) {
            $channelsData = json_decode($channelsData);
            if (200 == $channelsData->code) {
                $data = array();
                foreach ($channelsData->data as $key => $value) {
                    $data[(int)$key] = $value;
                }
                return $data;
            }
        }
        return null;
    }

    private function _initChannel($mcKey, $mcLifetime) {
        $this->_isReady = false;
        $channelsData = (array)MemcacheWrapper::getValue($mcKey,
                                                         $mcLifetime,
                                                         array('Channel', 'getChannelsData'));
        if (isset($channelsData[$this->_id])) {
            $channelData = $channelsData[$this->_id];
            $this->_name = @$channelData->name;
            $this->_slug = @$channelData->slug;
            $this->_text_uk = @$channelData->text_uk;
            $this->_text_ru = @$channelData->text_ru;
            $this->_text_en = @$channelData->text_en;
            $this->_logo = @$channelData->logo;
            $this->_onlinefmUrl = @$channelData->url;
            $this->_streamsHttp = @$channelData->streams_http;
            $this->_streamsRtmp = @$channelData->streams_rtmp;
            $this->_isReady = (bool)$this->_name && (bool)$this->_logo;
        }
    }
    private function _initChannelData($data) {
        $this->_id = @$data->id;
        $this->_name = @$data->name;
        $this->_slug = @$data->slug;
        $this->_text_uk = @$channelData->text_uk;
        $this->_text_ru = @$channelData->text_ru;
        $this->_text_en = @$channelData->text_en;
        $this->_logo = @$data->logo;
        $this->_onlinefmUrl = @$data->url;
        $this->_streamsHttp = @$data->streams_http;
        $this->_streamsRtmp = @$data->streams_rtmp;
        $this->_isReady = (bool)$this->_id && (bool)$this->_name && (bool)$this->_logo;
    }

    public function getPlaylistData() {
        if ($playlistData = @file_get_contents($this->_playlistUrl)) {
            $playlistData = json_decode($playlistData);
            if (200 == $playlistData->code) {
                return $playlistData->data;
            }
        }
        return null;
    }

    private function _initPlaylist($mcKey) {
        if ($this->isReady()) {
            if (!($playlistData = MemcacheWrapper::getValueFromCache($mcKey))) {
                if ($playlistData = $this->getPlaylistData()) {
                    $mcLifetime = (int)$playlistData->timeToUpdate;
                    MemcacheWrapper::setValueToCache($mcKey,
                                                     $playlistData,
                                                     $mcLifetime ? $mcLifetime : 60);
                }
            }
            //$playlistData = $this->getPlaylistData();
            $this->_playlist = $playlistData;//$this->getPlaylistData();
            $this->_isReady = !is_null($this->_playlist);
        }
    }


    public function isReady() { return $this->_isReady; }

    public function getPlaylist($time) {
        if (isset($this->_playlist->{$time}) && count($this->_playlist->{$time})) {
            return $this->_playlist->{$time};
        }
        return null;
    }

    public function __get($name) {
        if (in_array($name, array('id',
                                  'name',
                                  'slug',
                                  'logo',
                                  'onlinefmUrl',
                                  'streamsHttp',
                                  'streamsRtmp'))) {
            return $this->{'_' . $name};
        }
        if ('text' == $name) {
            return $this->{'_text_' . $this->_lang};
        }
        throw new Exception('Undefined property ' . $name);
    }

    public static function getAllChannels($lang) {
        $channels = array();
        $config = include(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'config.php');
        $mcKey = $config['memcache']['keys']['channels'];
        $mcLifetime = $config['memcache']['lifetime'];
        $channelsData = MemcacheWrapper::getValue($mcKey,
                                                  $mcLifetime,
                                                  array('Channel', 'getChannelsData'));
        $data = (array)$channelsData;
        if ($data) {
            foreach ($data as $chData) {
                $channels[$chData->id] = new Channel($chData, $lang);
            }
        }
        return $channels;
    }

    public static function updatePlaylists() {
        $config = include(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'config.php');
        $mcKey = $config['memcache']['keys']['channels'];
        $mcLifetime = $config['memcache']['lifetime'];
        //get list of channels
        $channelsData = MemcacheWrapper::getValue($mcKey,
                                                  $mcLifetime,
                                                  array('Channel', 'getChannelsData'));
        $channelsList = (array)$channelsData;
        foreach ($channelsList as $channel) {
            $playlistUrl = sprintf($config['urls']['playlist_single'],
                                   $config['token'],
                                   $channel->id);
            //for each channel retrieve playlist from server...
            if ($playlistData = @file_get_contents($playlistUrl)) {
                $playlistData = json_decode($playlistData);
                if (200 == $playlistData->code) {
                    $data = $playlistData->data;
                    $mcKey = sprintf("%s_%d",
                                     $config['memcache']['keys']['playlist'],
                                     $channel->id);
                    $mcLifetime = (int)$data->timeToUpdate;
                    //...and put it to memcache
                    MemcacheWrapper::setValueToCache($mcKey,
                                                     $data,
                                                     $mcLifetime ? $mcLifetime : 60);
                }
            }
        }
        return false;
    }

}
