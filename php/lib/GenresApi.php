<?php

include_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'MemcacheWrapper.php');


class GenresApi {

    private $_dataUrl = null;
    private $_isReady = false;
    private $_genres = null;
    private $_genreUri = null;
    private $_templatesDir = null;
    private $_scripts = array(
        'css_prefix' => null,
    );
    private $_options = array(
        'list_item_class' => 'genre',
        'lang' => 'ru',
    );


    public function __construct($options = array()) {
        if ($options) {
            $optionsKeys = array_keys($this->_options);
            foreach ($optionsKeys as $key) {
                if (isset($options[$key])) {
                    $this->_options[$key] = $options[$key];
                }
            }
        }
        $config = include(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'config.php');
        $this->_scripts['css_prefix'] = $config['scripts']['css_prefix'];
        $this->_genreUri = $config['routes']['genre'];
        $this->_templatesDir = $config['dirs']['templates'];
        $this->_dataUrl = sprintf($config['urls']['genres_channels'],
                                  $config['token']);
        $this->_init($config['memcache']['keys']['genres'],
                     $config['memcache']['lifetime']);
    }

    public function getGenresData() {
        if ($genresData = @file_get_contents($this->_dataUrl)) {
            $genresData = json_decode($genresData);
            if (200 == $genresData->code) {
                $items = $genresData->data;
                $res = array();
                foreach ($items as $item) {
                    $res[$item->slug] = new Genre($item, $this->_options['lang']);
                }
                return $res;
            }
        }
        return null;
    }

    private function _init($mcKey, $mcLifetime) {
        $this->_isReady = false;
        $genres = MemcacheWrapper::getValue($mcKey,
                                            $mcLifetime,
                                            array($this, 'getGenresData'));
        if ($genres) {
            $this->_genres = $genres;
        }
        $this->_isReady = (bool)$genres;
    }

    public function getGenres() {
        return $this->_genres;
    }

    public function renderGenres() {
        if (!$this->_isReady) {
            return '';
        }

        $html = '';
        foreach ($this->_genres as $genre) {
            $values = array();
            $values['GENRE_NAME'] = $genre->name;
            $values['URL'] = sprintf($this->_genreUri, $genre->slug);
            $values['ITEM_CLASS'] = $this->_options['list_item_class'];
            $html .= $this->_assignTemplate($values, 'genre_item.html');
        }
        return $html;
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

    public function renderCSS() {
        return sprintf("<link rel='stylesheet' type='text/css' media='screen' href='%sofmapi_genres.css' />\n",
                       $this->_scripts['css_prefix']);
    }

}


class Genre {

    private $_data = null;
    private $_lang = null;

    public function __construct($data, $lang) {
        $this->_data = (array)$data;
        $this->_lang = $lang;
    }

    public function __get($name) {
        if (in_array($name, array('id', 'slug', 'channels'))) {
            return $this->_data[$name];
        }
        if ('name' == $name) {
            return $this->_data['name_' . $this->_lang];
        }
        throw new Exception('Undefined property ' . $name);
    }

}
