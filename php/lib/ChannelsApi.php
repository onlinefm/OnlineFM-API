<?php

include_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Channel.php');


class ChannelsApi {

    private $_listChannels = array();
    private $_listChannelIds = array();
    private $_channelId = null;
    private $_lang = array();
    private $_channelUri = null;
    private $_templatesDir = null;
    private $_scripts = array(
        'js_prefix' => null,
        'css_prefix' => null,
        'include_jquery' => null,
    );
    private $_options = array(
        'list_item_tag' => 'li',
        'list_item_html_attrs' => array('class' => 'channel-item'),
        'open_player_type' => 'popup',
        'lang' => 'ru',
    );

    public function __construct(array $options = array()) {
        if ($options) {
            $optionsKeys = array_keys($this->_options);
            foreach ($optionsKeys as $key) {
                if (isset($options[$key])) {
                    if ('class' == $options[$key]) {
                        //add custom class(es) and save original
                        $this->_options[$key] .= ' ' . $options[$key];
                    }
                    elseif ('id' == $options[$key]) {
                        //user can't change id
                        continue;
                    }
                    else {
                        $this->_options[$key] = $options[$key];
                    }
                }
            }
        }
        $config = include(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'config.php');
        $this->_scripts = $config['scripts'];
        $this->_channelUri = $config['routes']['channel'];
        $this->_templatesDir = $config['dirs']['templates'];
        $this->_lang = include(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'i18n' . DIRECTORY_SEPARATOR . $this->_options['lang'] . '.php');
    }

    public function setChannels($value = null) {
        if (!$value) {
            //all channels
            $this->_listChannels = Channel::getAllChannels($this->_options['lang']);
        }
        elseif (is_array($value)) {
            //selected by id channels
            $this->_listChannelIds = $value;
            if (!empty($value)) {
                $this->_channelId = $value[0];
            }
            $allChannels = Channel::getAllChannels($this->_options['lang']);
            $listChannels = array();
            foreach ($this->_listChannelIds as $id) {
                if (isset($allChannels[$id])) {
                    $listChannels[$id] = $allChannels[$id];
                }
            }
            $this->_listChannels = $listChannels;
        }
        elseif ((int)$value) {
            //single channel
            $this->_listChannelIds = array($value);
            $this->_channelId = (int)$value;
            $channel = new Channel($this->_channelId, $this->_options['lang']);
            $this->_listChannels = array($channel->id => $channel);
        }
    }

    public function renderList() {
        $html = '';
        foreach ($this->_listChannels as $channel) {
            if (!$channel->isReady()) {
                continue;
            }
            $values = array();
            $values['CHANNEL_ID'] = $channel->id;
            $values['CHANNEL_URL'] = sprintf($this->_channelUri, $channel->id);
            $values['CHANNEL_NAME'] = $channel->name;
            $values['CHANNEL_TITLE'] = $channel->name;
            $values['CURRENT_PLAYING_LABEL'] = $this->_lang['CURRENT_PLAYING_LABEL'];
            $values['NEXT_PLAYING_LABEL'] = $this->_lang['NEXT_PLAYING_LABEL'];

            $values['CURRENT_SONG_TITLE'] = '';
            $values['CURRENT_SONG_MDASH'] = '';
            $values['CURRENT_ARTIST_TITLE'] = '';
            $values['COVER_SRC'] = "http://st1.ofmimg.com/images/cover_empty__great.png";
            $values['COVER_ALT'] = $channel->name;
            $values['COVER_TITLE'] = $channel->name;
            if ($currentSong = $channel->getPlaylist('current')) {
                $currentSong = $currentSong[0];
                $values['CURRENT_SONG_TITLE'] = $currentSong->title;
                if ($currentSong->artist) {
                    $values['CURRENT_SONG_MDASH'] = "&mdash;";
                    $values['CURRENT_ARTIST_TITLE'] = $currentSong->artist;
                }
                if ($currentSong->albumTitle) {
                    $values['COVER_ALT'] = $currentSong->albumTitle;
                    $values['COVER_TITLE'] = $currentSong->albumTitle;
                }
                $values['COVER_SRC'] = $currentSong->cover;
            }

            $values['NEXT_SONG_TITLE'] = '';
            $values['NEXT_SONG_MDASH'] = '';
            $values['NEXT_ARTIST_TITLE'] = '';
            if ($nextSongs = $channel->getPlaylist('future')) {
                $nextSong = $nextSongs[count($nextSongs) - 1];
                $values['NEXT_SONG_TITLE'] = $nextSong->title;
                if ($nextSong->artist) {
                    $values['NEXT_SONG_MDASH'] = "&mdash;";
                    $values['NEXT_ARTIST_TITLE'] = $nextSong->artist;
                }
            }

            $values['PLAYER_LISTEN_LABEL'] = $this->_lang['PLAYER_LISTEN_LABEL'];
            $values['PLAYER_URL'] = sprintf("http://online.fm/player/%s/%d",
                                            $this->_lang['__CODE'],
                                            $channel->id);
            $values['PLAYER_TITLE'] = $channel->name;
            if ('popup' == $this->_options['open_player_type']) {
                $values['PLAYER_ONCLICK'] = sprintf("onclick=\"return startPlayer('%s', %d);\"",
                                                    $this->_lang['__CODE'],
                                                    $channel->id);
            }
            else {
                $values['PLAYER_ONCLICK'] = '';
            }

            $html .= $this->_assignTemplateListItem($values);
        }
        return $html;
    }

    public function renderListJS() {
        $html = '';
        if ($this->_scripts['include_jquery']) {
            $html .= sprintf("<script type='text/javascript' src='%sjquery.min.js'></script>\n",
                            $this->_scripts['js_prefix']);
        }
        $html .= sprintf("<script type='text/javascript' src='%schannel.js'></script>\n",
                        $this->_scripts['js_prefix']);

        $playlistUrl = sprintf("http://online.fm/%s/channels/playlist/", $this->_lang['__CODE']);
        $html .= "<script type='text/javascript'>\n" .
        "$(function() {" .
            "initChItems([" . implode(',', $this->_listChannelIds) . "], '" . $playlistUrl . "');" .
        "});\n" .
        "</script>\n";
        return $html;
    }

    public function renderCSS() {
        return sprintf("<link rel='stylesheet' type='text/css' media='screen' href='%sofmapi.css' />\n",
                       $this->_scripts['css_prefix']);
    }

    public function renderChannelPlayingNow() {
        $html = '';
        if ($this->_channelId) {
            if (count($this->_listChannels) && isset($this->_listChannels[$this->_channelId])) {
                $channel = $this->_listChannels[$this->_channelId];
            }
            else {
                $channel = new Channel($this->_channelId, $this->_options['lang']);
            }
            if (!$channel->isReady()) {
                return $html;
            }
            $values = array();
            $values['PLAYER_LISTEN_LABEL'] = $this->_lang['PLAYER_LISTEN_LABEL'];
            $values['PLAYER_URL'] = sprintf("http://online.fm/player/%s/%d",
                                            $this->_lang['__CODE'],
                                            $this->_channelId);
            $values['PLAYER_TITLE'] = $channel->name;
            if ('popup' == $this->_options['open_player_type']) {
                $values['PLAYER_ONCLICK'] = sprintf("onclick=\"return startPlayer('%s', %d);\"",
                                                    $this->_lang['__CODE'],
                                                    $this->_channelId);
            }
            else {
                $values['PLAYER_ONCLICK'] = '';
            }

            //prev
            $prevSong = null;
            if ($prevSong = $channel->getPlaylist('past')) {
                $prevSong = $prevSong[0];
            }
            $this->_assignPlayingNowData($prevSong, 'PREV', $values);
            //current
            $currentSong = null;
            if ($currentSong = $channel->getPlaylist('current')) {
                $currentSong = $currentSong[0];
            }
            $this->_assignPlayingNowData($currentSong, 'CURRENT', $values);
            //next
            $nextSong = null;
            if ($nextSongs = $channel->getPlaylist('future')) {
                $nextSong = $nextSongs[count($nextSongs) - 1];
            }
            $this->_assignPlayingNowData($nextSong, 'NEXT', $values);
            //next2
            $nextSong2 = null;
            if (count($nextSongs) > 1) {
                $nextSong2 = $nextSongs[count($nextSongs) - 2];
            }
            $this->_assignPlayingNowData($nextSong2, 'NEXT2', $values);

            $html .= $this->_assignTemplate($values, 'channel_playingnow.html');
        }
        return $html;
    }
    private function _assignPlayingNowData($song, $keyPrefix, &$values) {
        $values[$keyPrefix . '_PLAYING_LABEL'] = $this->_lang[$keyPrefix . '_PLAYING_LABEL'];
        $values[$keyPrefix . '_ALBUM_LABEL'] = $this->_lang[$keyPrefix . '_ALBUM_LABEL'];
        $values[$keyPrefix . '_SONG_TITLE'] = '';
        $values[$keyPrefix . '_SONG_MDASH'] = '';
        $values[$keyPrefix . '_ARTIST_TITLE'] = '';
        $values[$keyPrefix . '_COVER_SRC'] = "http://st1.ofmimg.com/images/cover_empty__great.png";
        $values[$keyPrefix . '_COVER_ALT'] = '';
        $values[$keyPrefix . '_COVER_TITLE'] = '';
        $values[$keyPrefix . '_ALBUM_TITLE'] = '';
        $values[$keyPrefix . '_ALBUM_YEAR'] = '';
        $values[$keyPrefix . '_ALBUM_COMMA'] = '';
        if ($song) {
            $values[$keyPrefix . '_SONG_TITLE'] = $song->title;
            if ($song->artist) {
                $values[$keyPrefix . '_SONG_MDASH'] = "&mdash;";
                $values[$keyPrefix . '_ARTIST_TITLE'] = $song->artist;
            }
            if ($song->albumTitle) {
                $values[$keyPrefix . '_COVER_ALT'] = $song->albumTitle;
                $values[$keyPrefix . '_COVER_TITLE'] = $song->albumTitle;
                $values[$keyPrefix . '_ALBUM_TITLE'] = $song->albumTitle;
            }
            if ($song->albumYear) {
                $values[$keyPrefix . '_ALBUM_YEAR'] = $song->albumYear;
            }
            if ($song->albumTitle && $song->albumYear) {
                $values[$keyPrefix . '_ALBUM_COMMA'] = ', ';
            }
            $values[$keyPrefix . '_COVER_SRC'] = $song->cover;
        }
    }


    private function _getHtmlPlaylistSongData($song, $rowClass, $legend = '') {
        $html = '';
        $values = array();
        $values['ROW_CLASS'] = $rowClass;
        $values['LEGEND'] = $legend;
        if ($song) {
            $values['SONG_TITLE'] = $song->title;
            $values['SONG_TIME'] = $song->lengthFormat;
            $values['ARTIST_TITLE'] = '';
            $values['ARTIST_MDASH'] = '';
            if ($song->artist) {
                $values['ARTIST_TITLE'] = $song->artist;
                $values['ARTIST_MDASH'] = '-';
            }
        }
        $html .= $this->_assignTemplate($values, 'channel_playlist_song.html');
        return $html;
    }

    public function renderChannelPlaylist() {
        $html = '';
        if ($this->_channelId) {
            if (count($this->_listChannels) && isset($this->_listChannels[$this->_channelId])) {
                $channel = $this->_listChannels[$this->_channelId];
            }
            else {
                $channel = new Channel($this->_channelId, $this->_options['lang']);
            }
            if (!$channel->isReady()) {
                return $html;
            }
            $values = array();
            $values['PLAYLIST_LABEL'] = $this->_lang['PLAYLIST_LABEL'];
            $values['PLAYLIST_PREV_SONGS_HTML'] = '';
            if ($pastSongs = $channel->getPlaylist('past')) {
                $cnt = count($pastSongs);
                for ($i = 0; $i < $cnt; ++$i) {
                    $song = $pastSongs[$cnt - $i - 1];
                    $values['PLAYLIST_PREV_SONGS_HTML'] .= $this->_getHtmlPlaylistSongData($song, 'prev');
                }
            }
            $values['PLAYLIST_CURRENT_SONG_HTML'] = '';
            if ($currentSong = $channel->getPlaylist('current')) {
                $values['PLAYLIST_CURRENT_SONG_HTML'] = $this->_getHtmlPlaylistSongData($currentSong[0],
                                                        'now',
                                                        $this->_lang['PLAYLIST_LEGEND_NOW']);
            }
            $values['PLAYLIST_NEXT_SONGS_HTML'] = '';
            if ($nextSongs = $channel->getPlaylist('future')) {
                $cnt = count($nextSongs);
                $values['PLAYLIST_NEXT_SONGS_HTML'] = $this->_getHtmlPlaylistSongData($nextSongs[$cnt - 1],
                                                      'next',
                                                      $this->_lang['PLAYLIST_LEGEND_NEXT']);
                for ($i = 1; $i < $cnt; ++$i) {
                    $song = $nextSongs[$cnt - $i - 1];
                    $values['PLAYLIST_NEXT_SONGS_HTML'] .= $this->_getHtmlPlaylistSongData($song, 'next');
                }
            }
            $html .= $this->_assignTemplate($values, 'channel_playlist.html');
        }
        return $html;
    }

    public function renderChannelJS() {
        if ($this->_channelId) {
            $html = '';
            if ($this->_scripts['include_jquery']) {
                $html .= sprintf("<script type='text/javascript' src='%sjquery.min.js'></script>\n",
                                $this->_scripts['js_prefix']);
            }
            $html .= sprintf("<script type='text/javascript' src='%schannel.js'></script>\n",
                            $this->_scripts['js_prefix']);

            $playlistUrl = sprintf("http://online.fm/%s/channels/playlist/%d",
                                   $this->_lang['__CODE'],
                                   $this->_channelId);
            $html .= "<script type='text/javascript'>\n" .
            "$(function() {" .
                "initChannel(" . $this->_channelId . ", '" . $playlistUrl . "');" .
            "});\n" .
            "</script>\n";
            return $html;
        }
        else {
            return null;
        }
    }

    public function getChannel() {
        if ($this->_channelId) {
            $channel = new Channel($this->_channelId, $this->_options['lang']);
            if ($channel->isReady()) {
                return $channel;
            }
        }
        return null;
    }

    public function getChannels() {
        return $this->_listChannels;
    }

    private function _assignTemplateListItem(array $values) {
        $resValues = array();
        $resValues[strtoupper('list_item_tag')] = $this->_options['list_item_tag'];
        $htmlAttrs = array();
        foreach ($this->_options['list_item_html_attrs'] as $attrName => $attrValue) {
            $htmlAttrs[] = sprintf("%s='%s'", $attrName, $attrValue);
        }
        $resValues[strtoupper('list_item_html_attrs')] = implode(' ', $htmlAttrs);

        return $this->_assignTemplate(array_merge($values, $resValues), 'list_item_channel.html');
    }

    private function _assignTemplate(array $values, $template) {
        $filePath = realpath(sprintf("%s/%s%s",
                                     dirname(__FILE__),
                                     $this->_templatesDir,
                                     $template));
        if (!$filePath || !$html = @file_get_contents($filePath)) {
            return null;
        }

        if ($values) {
            foreach ($values as $key => $value) {
                $html = str_replace('{#' . $key . '#}', $value, $html);
            }
        }
        return $html;
    }

}
