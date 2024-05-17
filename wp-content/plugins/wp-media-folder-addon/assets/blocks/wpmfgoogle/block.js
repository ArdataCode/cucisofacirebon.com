'use strict';

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

(function (wpI18n, wpBlocks, wpElement, wpEditor, wpComponents) {
    var __ = wp.i18n.__;
    var _wp$element = wp.element,
        Component = _wp$element.Component,
        Fragment = _wp$element.Fragment;
    var registerBlockType = wpBlocks.registerBlockType;
    var BlockControls = wpEditor.BlockControls,
        BlockAlignmentToolbar = wpEditor.BlockAlignmentToolbar;
    var _wp$components = wp.components,
        Modal = _wp$components.Modal,
        FocusableIframe = _wp$components.FocusableIframe,
        IconButton = _wp$components.IconButton,
        Toolbar = _wp$components.Toolbar;

    var $ = jQuery;

    var WpmfGoogleDrive = function (_Component) {
        _inherits(WpmfGoogleDrive, _Component);

        function WpmfGoogleDrive() {
            _classCallCheck(this, WpmfGoogleDrive);

            var _this = _possibleConstructorReturn(this, (WpmfGoogleDrive.__proto__ || Object.getPrototypeOf(WpmfGoogleDrive)).apply(this, arguments));

            _this.state = {
                isOpen: false
            };

            _this.openModal = _this.openModal.bind(_this);
            _this.closeModal = _this.closeModal.bind(_this);
            _this.addEventListener = _this.addEventListener.bind(_this);
            _this.componentDidMount = _this.componentDidMount.bind(_this);
            return _this;
        }

        _createClass(WpmfGoogleDrive, [{
            key: 'openModal',
            value: function openModal() {
                if (!this.state.isOpen) {
                    this.setState({ isOpen: true });
                }
            }
        }, {
            key: 'closeModal',
            value: function closeModal() {
                if (this.state.isOpen) {
                    this.setState({ isOpen: false });
                }
            }
        }, {
            key: 'addLoading',
            value: function addLoading() {
                var clientId = this.props.clientId;

                if ($('#block-' + clientId + ' [data-block="' + clientId + '"] img').length) {
                    if (!$('#block-' + clientId + ' .wpmf_loading_process').length) {
                        $('#block-' + clientId).prepend('<label class="wpmf_loading_process" style=" position: absolute; left: 45%; ">' + wpmfodvbusinessblocks.l18n.loading + '</label>');
                    }

                    $('#block-' + clientId + ' [data-block="' + clientId + '"] img').on('load', function () {
                        $('#block-' + clientId + ' .wpmf_loading_process').remove();
                    });
                }
            }
        }, {
            key: 'addEventListener',
            value: function addEventListener(e) {
                if (!e.data.hasfiles) {
                    return;
                }

                if (e.data.type !== 'wpmfgoogleinsert') {
                    return;
                }

                if (e.data.idblock !== this.props.clientId) {
                    return;
                }

                this.setState({
                    isOpen: false
                });

                var setAttributes = this.props.setAttributes;

                setAttributes({
                    html: e.data.html,
                    hasfiles: e.data.hasfiles
                });

                this.addLoading();
            }
        }, {
            key: 'componentDidMount',
            value: function componentDidMount() {
                this.addLoading();
                window.addEventListener("message", this.addEventListener, false);
            }
        }, {
            key: 'render',
            value: function render() {
                var _props = this.props,
                    attributes = _props.attributes,
                    setAttributes = _props.setAttributes;
                var align = attributes.align,
                    html = attributes.html,
                    hasfiles = attributes.hasfiles,
                    cover = attributes.cover;

                var renderHTML = function renderHTML(rawHTML) {
                    return React.createElement("div", { dangerouslySetInnerHTML: { __html: rawHTML } });
                };
                return React.createElement(
                    Fragment,
                    null,
                    typeof cover !== "undefined" && React.createElement(
                        'div',
                        { className: 'wpmf-cover' },
                        React.createElement('img', { src: cover })
                    ),
                    typeof cover === "undefined" && hasfiles && React.createElement(
                        BlockControls,
                        null,
                        React.createElement(BlockAlignmentToolbar, { value: align, onChange: function onChange(align) {
                                return setAttributes({ align: align });
                            } }),
                        React.createElement(
                            Toolbar,
                            null,
                            React.createElement(IconButton, {
                                className: 'components-toolbar__control',
                                label: wpmfblocks.l18n.remove,
                                icon: 'no',
                                onClick: function onClick() {
                                    return setAttributes({ hasfiles: false, 'html': '' });
                                }
                            })
                        )
                    ),
                    typeof cover === "undefined" && hasfiles && renderHTML(html),
                    typeof cover === "undefined" && !hasfiles && React.createElement(
                        'button',
                        { className: 'components-button is-button is-default is-primary is-large aligncenter',
                            onClick: this.openModal },
                        wpmfblocks.l18n.btnopen
                    )
                );
            }
        }]);

        return WpmfGoogleDrive;
    }(Component);

    var wpmfGoogleBlockIcon = React.createElement(
        'svg',
        { version: '1.1', xmlns: 'http://www.w3.org/2000/svg', width: '20', x: '0px', y: '0px',
            viewBox: '0 0 512 512' },
        React.createElement('polygon', { fill: '#FFC107', points: '341.344,352 512,352 341.344,32 170.656,32 ' }),
        React.createElement('polygon', { fill: '#2196F3', points: '158.464,352 85.344,480 432,480 512,352 ' }),
        React.createElement('polygon', { fill: '#4CAF50', points: '170.656,32 0,330.656 85.344,480 253.056,186.496 ' })
    );
    registerBlockType('wpmf/block-google-file', {
        title: wpmfblocks.l18n.google_drive,
        icon: wpmfGoogleBlockIcon,
        category: 'wp-media-folder',
        keywords: [__('google', 'wpmfAddon'), __('file', 'wpmfAddon'), __('attachment', 'wpmfAddon')],
        example: {
            attributes: {
                cover: wpmfblocks.vars.block_cover
            }
        },
        attributes: {
            hasfiles: {
                type: 'string',
                default: false
            },
            html: {
                type: 'string',
                default: ''
            },
            align: {
                type: 'string',
                default: 'center'
            },
            cover: {
                type: 'string',
                source: 'attribute',
                selector: 'img',
                attribute: 'src'
            }
        },
        edit: WpmfGoogleDrive,
        save: function save(_ref) {
            var attributes = _ref.attributes;
            var align = attributes.align,
                html = attributes.html,
                hasfiles = attributes.hasfiles;

            var renderHTML = function renderHTML(rawHTML) {
                return React.createElement("div", { dangerouslySetInnerHTML: { __html: rawHTML } });
            };
            return hasfiles && React.createElement(
                'div',
                { className: 'align' + align },
                renderHTML(html)
            );
        },
        getEditWrapperProps: function getEditWrapperProps(attributes) {
            var align = attributes.align;

            var props = { 'data-resized': true };

            if ('left' === align || 'right' === align || 'center' === align) {
                props['data-align'] = align;
            }

            return props;
        }
    });
})(wp.i18n, wp.blocks, wp.element, wp.editor, wp.components);
