<?php

include_once(realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR .'..' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'OnlinefmApi.php'));

$api = new OnlinefmApi;
$api->setChannels($_GET['id']);
$channels = $api->getChannels();
$channel = $channels[0];
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <title><?php if ($channel): echo $channel->name; endif; ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <?php echo $api->renderCSS(); ?>
    <?php echo $api->renderChannelJS(); ?>
</head>
<body>

<ul id="genres">
    <?php echo $api->renderGenres(); ?>
    <li class="genre">
        <h3><a title="Все" href="/examples/list.php">Все</a></h3>
    </li>
</ul>

    <div class="logo">
    <?php if ($channel):
//var_dump($channel)        ;
        ?>
    <img src="<?php echo $channel->logo; ?>" />
    <p><?php echo $channel->name; ?></p>
    <?php endif; ?>
</div>

<div class="playing-now">
<?php echo $api->renderChannelPlayingNow(); ?>
</div>

<div class="text">
<?php echo $channel->text; ?>
</div>

<div class="playlist">
<?php echo $api->renderChannelPlaylist(); ?>
</div>

<?php
    include_once(realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR .'..' . DIRECTORY_SEPARATOR .
                    'lib/templates' . DIRECTORY_SEPARATOR . 'copyright.html'));
?>
</body>
</html>
