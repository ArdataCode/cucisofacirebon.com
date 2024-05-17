<?php
    // phpcs:disable WordPress.WP.EnqueuedResources.NonEnqueuedScript, WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet --  not run on wordpress
?>
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>
<script src="<?php echo esc_html(WPMFAD_PLUGIN_URL) ?>/assets/js/mediaelement-and-player.js"></script>
<link rel="stylesheet" type="text/css"
      href="<?php echo esc_html(WPMFAD_PLUGIN_URL) ?>/assets/css/mediaelementplayer.min.css?v=<?php echo esc_html(time()) ?>">
<?php // phpcs:enable ?>
<div id="wpfdViewer">
    <?php if ($mediaType === 'image') { ?>
        <img src="<?php echo esc_html($downloadLink); ?>" alt="" title=""/>
    <?php } elseif ($mediaType === 'video') { ?>
        <video width="100%" height="100%" src="<?php echo esc_html($downloadLink); ?>" type="<?php echo esc_html($mimetype); ?>"
               class="mejs-player" data-mejsoptions='{"alwaysShowControls": true}'
               id="playerVid" controls="controls" preload="auto" autoplay="true">
            <source type="<?php echo esc_html($mimetype); ?>" src="<?php echo esc_html($downloadLink); ?>"/>
            Your browser does not support the <code>video</code> element.
        </video>
    <?php } elseif ($mediaType === 'audio') { ?>
        <audio src="<?php echo esc_html($downloadLink); ?>" type="<?php echo esc_html($mimetype); ?>"
               id="playerAud" controls="controls" preload="auto" autoplay="true"></audio>
    <?php } ?>
</div>
<script type="text/javascript">
    jQuery(document).ready(function ($) {
        var w = $('#wpfdViewer').width();
        var h = $('#wpfdViewer').height();
        var vid = document.getElementById("playerVid");
        var aud = document.getElementById("playerAud");
        if (vid !== null) {
            vid.onloadeddata = function () {
                // Browser has loaded the current frame
                var vW = $(vid).width();
                var vH = $(vid).height();

                if (vH > h) {
                    newH = h - 10;
                    newW = newH / vH * vW;
                    $(vid).attr('width', newW).attr('height', newH);
                    $(vid).width(newW);
                    $(vid).height(newH);

                    $(".mejs-video").width(newW);
                    $(".mejs-video").height(newH);

                    var barW = newW - 150;
                    $(".mejs-time-rail").width(barW).css('padding-right', '5px');
                    $(".mejs-time-total").width(barW);
                }

            };

        }

        $('video,audio').mediaelementplayer(/* Options */);

    });
</script>

<style>
    .wpfdviewer::before {
        content: none;
    }

    #wpfdViewer {
        text-align: center;
        position: absolute;
        top: 0;
        bottom: 0;
        left: 0;
        right: 0;
        height: 100%;
    }

    #wpfdViewer img {
        max-width: 100%;
        height: auto;
        max-height: 100%;
    }

    #wpfdViewer audio, #wpfdViewer video {
        display: inline-block;
    }

    #wpfdViewer .mejs-container {
        margin: 0 auto;
        max-width: 100%;
    }

    #wpfdViewer video {
        width: 100% !important;
        max-width: 100%;
        height: auto !important;
        max-height: 100% !important;
    }

    #wpfdViewer .mejs-container.mejs-video {
        margin: 0 auto;
    }

    #wpfdViewer .mejs-container.mejs-audio {
        top: 50%;
        margin-top: -15px;
    }

    .wpfdviewer #wpadminbar {
        display: none;
    }
</style>    