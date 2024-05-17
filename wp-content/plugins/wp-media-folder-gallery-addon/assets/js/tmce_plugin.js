/** WPLP Custom plugin for TinyME Editor v.0.1 **/
(function ($) {
    var wpmfglr_toolbarActive;
    tinymce.create('tinymce.plugins.wpmfglr', {

        init: function (ed, url) {
            var t = this;

            t.url = url;
            t.editor = ed;
            var widthmodal = $(window).width() - 100;
            var heightmodal = $(window).height() - 100;
            ed.addCommand('mcegallery', function (query) {
                ed.windowManager.open({
                    file: 'upload.php?page=media-folder-galleries&noheader=1&caninsert=1&' + query,
                    width: widthmodal,
                    height: heightmodal,
                    inline: 1,
                    title: 'Update Gallery'
                }, {
                    plugin_url: url
                });
            });

            ed.on('mousedown', function (event) {
                if (ed.dom.getParent(event.target, '#wpmfglr_toolbar')) {
                    if (tinymce.Env.ie) {
                        // Stop IE > 8 from making the wrapper resizable on mousedown
                        event.preventDefault();
                    }
                } else {
                    removeUydToolbar(ed);
                }
            });

            ed.on('mouseup', function (event) {
                var image,
                    node = event.target,
                    dom = ed.dom;

                // Don't trigger on right-click
                if (event.button && event.button > 1) {
                    return;
                }
                if (node.nodeName === 'DIV' && dom.getParent(node, '#wpmfglr_toolbar')) {
                    image = dom.select('img[data-wp-wpmfglrselect]')[0];

                    if (image) {
                        ed.selection.select(image);

                        if (dom.hasClass(node, 'remove')) {
                            removeUydToolbar(ed);
                            removeUydImage(image, ed);
                        } else if (dom.hasClass(node, 'edit')) {
                            var shortcode = ed.selection.getContent();
                            shortcode = shortcode.replace('</p>', '').replace('<p>', '').replace('[wpmfgallery ', '').replace('"]', '');
                            var query = encodeURIComponent(shortcode).split('%3D%22').join('=').split('%22%20').join('&');
                            removeUydToolbar(ed);
                            ed.execCommand('mcegallery', query);
                        }
                    }
                } else if (node.nodeName === 'IMG' && isUydPlaceholder(node, ed)) {
                    addUydToolbar(node, ed);
                } else if (node.nodeName !== 'IMG') {
                    removeUydToolbar(ed);
                }
            });

            ed.on('keydown', function (event) {
                var keyCode = event.keyCode;
                // Key presses will replace the image so we need to remove the toolbar
                if (wpmfglr_toolbarActive) {
                    if (event.ctrlKey || event.metaKey || event.altKey ||
                        (keyCode < 48 && keyCode > 90) || keyCode > 186) {
                        return;
                    }

                    removeUydToolbar(ed);
                }
            });

            ed.on('cut', function () {
                removeUydToolbar(ed);
            });

            ed.on('BeforeSetcontent', function (ed) {
                ed.content = t._do_wpmfglr(ed.content, t.url);
            });
            ed.on('PostProcess', function (ed) {
                if (ed.get)
                    ed.content = t._get_wpmfglr(ed.content);
            });
        },

        _do_wpmfglr: function (co) {
            return co.replace(/\[wpmfgallery([^\]]*)\]/g, function (a, b) {
                return '<img src="' + tinymce.baseURL + '/skins/lightgray/img/trans.gif" class="wpmfglred mceItem" title="WP Media Folder Gallery" data-mce-placeholder="1" ' + b + ' data-code="' + Base64.encode(b) + '" />';
            });
        },

        _get_wpmfglr: function (co) {
            function getAttr(s, n) {
                n = new RegExp(n + '=\"([^\"]+)\"', 'g').exec(s);
                return n ? n[1] : '';
            }
            return co.replace(/(?:<p[^>]*>)*(<img[^>]+>)(?:<\/p>)*/g, function (a, im) {
                var cls = getAttr(im, 'class');

                if (cls.indexOf('wpmfglred') !== -1)
                    return '<p>[wpmfgallery ' + tinymce.trim(Base64.decode(getAttr(im, 'data-code'))) + ']</p>';

                return a;
            });
        }
    });

    /** Registers the plugin. **/
    tinymce.PluginManager.add('wpmfglr', tinymce.plugins.wpmfglr);

    function removeUydImage(node, editor) {
        editor.dom.remove(node);
    }

    function addUydToolbar(node, editor) {
        var rectangle, toolbarHtml, toolbar, left,
            dom = editor.dom;

        removeUydToolbar(editor);

        // Don't add to placeholders
        if (!node || node.nodeName !== 'IMG' || !isUydPlaceholder(node, editor)) {
            return;
        }

        dom.setAttrib(node, 'data-wp-wpmfglrselect', 1);
        rectangle = dom.getRect(node);

        toolbarHtml = '<div class="dashicons dashicons-edit edit" data-mce-bogus="1"></div>' +
            '<div class="dashicons dashicons-no-alt remove" data-mce-bogus="1"></div>';

        toolbar = dom.create('div', {
            'id': 'wpmfglr_toolbar',
            'data-mce-bogus': '1',
            'contenteditable': false
        }, toolbarHtml);

        if (editor.rtl) {
            left = rectangle.x + rectangle.w - 82;
        } else {
            left = rectangle.x;
        }

        editor.getBody().appendChild(toolbar);
        dom.setStyles(toolbar, {
            top: rectangle.y,
            left: left
        });
        wpmfglr_toolbarActive = true;
    }

    function removeUydToolbar(editor) {
        var toolbar = editor.dom.get('wpmfglr_toolbar');

        if (toolbar) {
            editor.dom.remove(toolbar);
        }

        editor.dom.setAttrib(editor.dom.select('img[data-wp-wpmfglrselect]'), 'data-wp-wpmfglrselect', null);

        wpmfglr_toolbarActive = false;
    }

    function isUydPlaceholder(node, editor) {
        var dom = editor.dom;
        return !!dom.hasClass(node, 'wpmfglred');

    }

    /* Create Base64 Object */
    var Base64 = {
        _keyStr: "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=", encode: function (e) {
            var t = "";
            var n, r, i, s, o, u, a;
            var f = 0;
            e = Base64._utf8_encode(e);
            while (f < e.length) {
                n = e.charCodeAt(f++);
                r = e.charCodeAt(f++);
                i = e.charCodeAt(f++);
                s = n >> 2;
                o = (n & 3) << 4 | r >> 4;
                u = (r & 15) << 2 | i >> 6;
                a = i & 63;
                if (isNaN(r)) {
                    u = a = 64
                } else if (isNaN(i)) {
                    a = 64
                }
                t = t + this._keyStr.charAt(s) + this._keyStr.charAt(o) + this._keyStr.charAt(u) + this._keyStr.charAt(a)
            }
            return t
        }, decode: function (e) {
            var t = "";
            var n, r, i;
            var s, o, u, a;
            var f = 0;
            e = e.replace(/[^A-Za-z0-9\+\/\=]/g, "");
            while (f < e.length) {
                s = this._keyStr.indexOf(e.charAt(f++));
                o = this._keyStr.indexOf(e.charAt(f++));
                u = this._keyStr.indexOf(e.charAt(f++));
                a = this._keyStr.indexOf(e.charAt(f++));
                n = s << 2 | o >> 4;
                r = (o & 15) << 4 | u >> 2;
                i = (u & 3) << 6 | a;
                t = t + String.fromCharCode(n);
                if (parseInt(u) !== 64) {
                    t = t + String.fromCharCode(r)
                }
                if (parseInt(a) !== 64) {
                    t = t + String.fromCharCode(i)
                }
            }
            t = Base64._utf8_decode(t);
            return t
        }, _utf8_encode: function (e) {
            e = e.replace(/\r\n/g, "\n");
            var t = "";
            for (var n = 0; n < e.length; n++) {
                var r = e.charCodeAt(n);
                if (r < 128) {
                    t += String.fromCharCode(r)
                } else if (r > 127 && r < 2048) {
                    t += String.fromCharCode(r >> 6 | 192);
                    t += String.fromCharCode(r & 63 | 128)
                } else {
                    t += String.fromCharCode(r >> 12 | 224);
                    t += String.fromCharCode(r >> 6 & 63 | 128);
                    t += String.fromCharCode(r & 63 | 128)
                }
            }
            return t
        }, _utf8_decode: function (e) {
            var t = "";
            var n = 0;
            var r = c1 = c2 = 0;
            while (n < e.length) {
                r = e.charCodeAt(n);
                if (r < 128) {
                    t += String.fromCharCode(r);
                    n++
                } else if (r > 191 && r < 224) {
                    c2 = e.charCodeAt(n + 1);
                    t += String.fromCharCode((r & 31) << 6 | c2 & 63);
                    n += 2
                } else {
                    c2 = e.charCodeAt(n + 1);
                    c3 = e.charCodeAt(n + 2);
                    t += String.fromCharCode((r & 15) << 12 | (c2 & 63) << 6 | c3 & 63);
                    n += 3
                }
            }
            return t
        }
    }
})(jQuery);
