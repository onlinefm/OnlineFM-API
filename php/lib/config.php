<?php

return $config = array(
    'token' => '4d7106ed1d85c',
    'urls' => array(
        'channels' => 'http://online.fm/api/channels.json?token=%s',
        'genres_channels' => 'http://online.fm/api/genres_channels.json?token=%s',
        'playlist_list' => 'http://online.fm/api/playlist/list.json?token=%s',
        'playlist_single' => 'http://online.fm/api/playlist/single.json?token=%s&id=%d',
        'playlist_currentsong' => 'http://online.fm/api/playlist/currentsong.json?token=%s&id=%d',
    ),
    'memcache' => array(
        'use' => true,
        'servers' => array( array('host' => '127.0.0.1', 'port' => 11211), ),
        'lifetime' => 86400,
        'key_prefix' => 'ofmapi031',
        'keys' => array(
            'channels' => 'channels',
            'genres' => 'genres',
            'playlist' => 'playlist',
        ),
    ),
    'scripts' => array(
        'include_jquery' => true,
        'js_prefix' => '/js/', // with trailing slash!
        'css_prefix' => '/css/', // with trailing slash!
    ),
    'routes' => array(
        'channel' => '/examples/channel.php?id=%d', //with %d - is used for channel's id
        'genre' => '/examples/list.php?genre=%s', //with %s - is used for genre's slug
    ),
    'dirs' => array(
        'templates' => 'templates/', //relative path from libs/ directory; with trailing slash
    ),
);

