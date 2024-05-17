<?php
global $post;
$file  = get_attached_file($post->ID, true);
$infos = get_post_meta($post->ID, 'wpmf_awsS3_info', true);
?>
<div>
    <?php if (!$infos) : ?>
        <div class="misc-pub-section">
            <?php esc_html_e('This file has not been store on amazon', 'wpmfAddon'); ?>
        </div>
    <?php else : ?>
        <div class="misc-pub-section">
            <div class="s3-key"><?php esc_html_e('Storage Provider', 'wpmfAddon') ?>:</div>
            <input type="text" id="as3cf-provider" class="widefat" readonly="readonly"
                   value="Amazon S3">
        </div>

        <?php if (isset($infos['Bucket']) && $infos['Bucket']) : ?>
            <div class="misc-pub-section">
                <div class="s3-key"><?php esc_html_e('Bucket', 'wpmfAddon') ?>:</div>
                <input type="text" id="as3cf-bucket" class="widefat" readonly="readonly"
                       value="<?php echo esc_attr($infos['Bucket']); ?>">
            </div>
        <?php endif; ?>

        <?php if (isset($infos['Key']) && $infos['Key']) : ?>
            <div class="misc-pub-section">
                <div><?php esc_html_e('Key', 'wpmfAddon') ?>:</div>
                <input type="text" class="widefat" readonly="readonly"
                       value="<?php echo esc_attr($infos['Key']); ?>">
            </div>
        <?php endif; ?>
        <?php if (isset($infos['Region']) && $infos['Region']) : ?>
            <div class="misc-pub-section">
                <div><?php esc_html_e('Region', 'wpmfAddon') ?>:
                    <strong><?php echo esc_html($infos['Region']); ?></strong></div>
            </div>
        <?php endif; ?>
        <?php if (isset($infos['Acl']) && $infos['Acl']) : ?>
            <div class="misc-pub-section">
                <div><?php esc_html_e('Access', 'wpmfAddon') ?>:
                    <strong><?php echo esc_html($infos['Acl']) ?></strong></div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    <div class="clear"></div>
</div>