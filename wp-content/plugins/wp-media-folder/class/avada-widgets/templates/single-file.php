<?php
/**
 * Underscore.js template.
 *
 * @package fusion-builder
 */
?>
<script type="text/template" id="fusion-builder-wpmf-single-file-preview-template">
    <h4 class="fusion_module_title"><span class="fusion-module-icon {{ fusionAllElements[element_type].icon }}"></span>{{ fusionAllElements[element_type].name }}</h4>
    <#
    var url      = params.url;
    var preview   = '';

    if ( '' !== url ) {
    preview = jQuery( '<div></div>' ).html( url ).text();
    }
    #>

    <# if ( '' !== params.url ) { #>
    <span style="font-weight: bold">File URL: </span>
    <# } #>

    <span class="file-url" style="font-style: italic"> {{{ preview }}} </span>
</script>
