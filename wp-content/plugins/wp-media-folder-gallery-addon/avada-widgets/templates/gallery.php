<?php
/**
 * Underscore.js template.
 *
 * @package fusion-builder
 */
?>
<script type="text/template" id="fusion-builder-wpmf-gallery-addon-preview-template">
    <h4 class="fusion_module_title"><span class="fusion-module-icon {{ fusionAllElements[element_type].icon }}"></span>{{ fusionAllElements[element_type].name }}</h4>
    <#
    var gallery_title      = params.gallery_title;
    var preview   = '';

    if ( '' !== gallery_title ) {
    preview = jQuery( '<div></div>' ).html( gallery_title ).text();
    }
    #>

    <# if ( '' !== preview ) { #>
    <span style="font-weight: bold">Gallery Title: </span>
    <# } #>

    <span class="wpmf-gallery-addon-id" style="font-style: italic" data-id="{{params.gallery_id}}"> {{ preview }} </span>
</script>
