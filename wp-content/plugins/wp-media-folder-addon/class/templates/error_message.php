<div id="robotsmessage" class="error">
    <p>
        <strong><?php echo esc_html($message); ?></strong>
        <a href='<?php echo esc_html($link_setting) ?>' target='<?php echo ($open_new === true) ? '_blank' : '' ?>' class='button'
           style="background: #008ec2;border-color: #006799;color: #fff;"><?php esc_html_e('Configure now', 'wpmfAddon'); ?></a>
        <a href="<?php echo esc_html($link_document) ?>" target="_blank"><?php esc_html_e('View documentation', 'wpmfAddon'); ?></a>
    </p>
</div>