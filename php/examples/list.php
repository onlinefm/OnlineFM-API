<?php

include_once(realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR .'..' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'OnlinefmApi.php'));

$options = array(
    'genre_uri' => '/examples/list.php?genre=%s',
    'channel_uri' => '/examples/channel.php?id=%d',
);
$api = new OnlinefmApi($options);
$genres = $api->getGenres();


$showAll = true;
if (isset($_GET['genre']) && isset($genres[$_GET['genre']])) {
    $api->setChannels($genres[$_GET['genre']]->channels);
}
else {
//    $api->setChannels();
    $showAll = false;
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <?php echo $api->renderCSS(); ?>
    <?php echo $api->renderListJS(); ?>
</head>
<body>

    <ul id="genres">

        <?php echo $api->renderGenres(); ?>

        <?php if ($showAll): ?>
        <li class="genre">
            <h3><a title="Все" href="/examples/list.php">Все</a></h3>
        </li>
        <?php endif; ?>
    </ul>
    
    <h1>Радиоканалы Online.FM</h1>

    <ul id="channels">
        <?php echo $api->renderList(); ?>

        <?php if ($showAll): ?>
        <li class="channel-item more-block more-channel-item">
            <a title="Все радиоканалы" href="/examples/list.php">
                <span>Все радиоканалы</span></a>
        </li>
        <?php endif; ?>
    </ul>

    <?php
        include_once(realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR .'..' . DIRECTORY_SEPARATOR .
                    'lib/templates' . DIRECTORY_SEPARATOR . 'copyright.html'));
    ?>
</body>
</html>
