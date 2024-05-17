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

    var WpmfDropboxDrive = function (_Component) {
        _inherits(WpmfDropboxDrive, _Component);

        function WpmfDropboxDrive() {
            _classCallCheck(this, WpmfDropboxDrive);

            var _this = _possibleConstructorReturn(this, (WpmfDropboxDrive.__proto__ || Object.getPrototypeOf(WpmfDropboxDrive)).apply(this, arguments));

            _this.state = {
                isOpen: false
            };

            _this.openModal = _this.openModal.bind(_this);
            _this.closeModal = _this.closeModal.bind(_this);
            _this.addEventListener = _this.addEventListener.bind(_this);
            _this.componentDidMount = _this.componentDidMount.bind(_this);
            return _this;
        }

        _createClass(WpmfDropboxDrive, [{
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

                if (e.data.type !== 'wpmfdropboxinsert') {
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
                                label: wpmfdbxblocks.l18n.remove,
                                icon: 'no',
                                onClick: function onClick() {
                                    return setAttributes({ hasfiles: false, html: '' });
                                }
                            })
                        )
                    ),
                    typeof cover === "undefined" && hasfiles && renderHTML(html),
                    typeof cover === "undefined" && !hasfiles && React.createElement(
                        'button',
                        { className: 'components-button is-button is-default is-primary is-large aligncenter',
                            onClick: this.openModal },
                        wpmfdbxblocks.l18n.btnopen
                    )
                );
            }
        }]);

        return WpmfDropboxDrive;
    }(Component);

    var wpmfDropboxBlockIcon = React.createElement(
        'svg',
        { version: '1.1', id: 'Layer_1', xmlns: 'http://www.w3.org/2000/svg', width: '20', x: '0px', y: '0px',
            viewBox: '0 0 447.232 447.232' },
        React.createElement('path', { fill: '#1587EA', d: 'M207.527,251.676L92.903,177.758c-3.72-2.399-8.559-2.145-12.007,0.63L3.833,240.403 c-5.458,4.392-5.015,12.839,0.873,16.636l114.624,73.918c3.72,2.399,8.559,2.145,12.007-0.63l77.063-62.014 C213.858,263.92,213.415,255.473,207.527,251.676z' }),
        React.createElement('path', { fill: '#1587EA', d: 'M238.833,268.312l77.063,62.014c3.449,2.775,8.287,3.029,12.007,0.63l114.624-73.918 c5.888-3.797,6.331-12.244,0.873-16.636l-77.063-62.014c-3.449-2.775-8.287-3.029-12.007-0.63l-114.624,73.918 C233.819,255.473,233.375,263.92,238.833,268.312z' }),
        React.createElement('path', { fill: '#1587EA', d: 'M208.4,74.196l-77.063-62.014c-3.449-2.775-8.287-3.029-12.007-0.63L4.706,85.47 c-5.888,3.797-6.331,12.244-0.873,16.636l77.063,62.014c3.449,2.775,8.287,3.029,12.007,0.63l114.624-73.918 C213.415,87.035,213.858,78.588,208.4,74.196z' }),
        React.createElement('path', { fill: '#1587EA', d: 'M442.527,85.47L327.903,11.552c-3.72-2.399-8.559-2.145-12.007,0.63l-77.063,62.014 c-5.458,4.392-5.015,12.839,0.873,16.636l114.625,73.918c3.72,2.399,8.559,2.145,12.007-0.63l77.063-62.014 C448.858,97.713,448.415,89.266,442.527,85.47z' }),
        React.createElement('path', { fill: '#1587EA', d: 'M218,279.2l-86.3,68.841c-3.128,2.495-7.499,2.715-10.861,0.547L99.568,334.87 c-6.201-3.999-14.368,0.453-14.368,7.831v7.416c0,3.258,1.702,6.28,4.488,7.969l128.481,77.884c2.969,1.8,6.692,1.8,9.661,0 l128.481-77.884c2.786-1.689,4.488-4.71,4.488-7.969v-6.619c0-7.378-8.168-11.83-14.368-7.831l-20.024,12.913 c-3.368,2.172-7.748,1.947-10.876-0.559l-85.893-68.809C226.238,276.489,221.405,276.484,218,279.2z' })
    );
    registerBlockType('wpmf/block-dropbox-file', {
        title: wpmfdbxblocks.l18n.dropbox_drive,
        icon: wpmfDropboxBlockIcon,
        category: 'wp-media-folder',
        keywords: [__('dropbox', 'wpmfAddon'), __('file', 'wpmfAddon'), __('attachment', 'wpmfAddon')],
        example: {
            attributes: {
                cover: wpmfdbxblocks.vars.block_cover
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
        edit: WpmfDropboxDrive,
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
