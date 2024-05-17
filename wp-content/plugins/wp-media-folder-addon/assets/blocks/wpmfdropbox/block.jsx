(function (wpI18n, wpBlocks, wpElement, wpEditor, wpComponents) {
    const {__} = wp.i18n;
    const {Component, Fragment} = wp.element;
    const {registerBlockType} = wpBlocks;
    const {BlockControls, BlockAlignmentToolbar} = wpEditor;
    const {Modal, FocusableIframe, IconButton, Toolbar} = wp.components;
    const $ = jQuery;

    class WpmfDropboxDrive extends Component {
        constructor() {
            super(...arguments);
            this.state = {
                isOpen: false
            };

            this.openModal = this.openModal.bind(this);
            this.closeModal = this.closeModal.bind(this);
            this.addEventListener = this.addEventListener.bind(this);
            this.componentDidMount = this.componentDidMount.bind(this);
        }

        openModal() {
            if (!this.state.isOpen) {
                this.setState({isOpen: true});
            }
        }

        closeModal() {
            if (this.state.isOpen) {
                this.setState({isOpen: false});
            }
        }

        addLoading() {
            const {clientId} = this.props;
            if ($('#block-' + clientId + ' [data-block="'+ clientId +'"] img').length) {
                if (!$('#block-' + clientId + ' .wpmf_loading_process').length) {
                    $('#block-' + clientId).prepend(`<label class="wpmf_loading_process" style=" position: absolute; left: 45%; ">${wpmfodvbusinessblocks.l18n.loading}</label>`);
                }

                $('#block-' + clientId + ' [data-block="'+ clientId +'"] img').on('load', function () {
                    $('#block-' + clientId + ' .wpmf_loading_process').remove();
                });
            }
        }

        addEventListener(e) {
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

            const {setAttributes} = this.props;
            setAttributes({
                html: e.data.html,
                hasfiles: e.data.hasfiles
            });

            this.addLoading();
        }

        componentDidMount() {
            this.addLoading();
            window.addEventListener("message", this.addEventListener, false);
        }

        render() {
            const {attributes, setAttributes} = this.props;
            const {
                align,
                html,
                hasfiles,
                cover
            } = attributes;

            const renderHTML = (rawHTML: string) => React.createElement("div", { dangerouslySetInnerHTML: { __html: rawHTML } });
            return (
                <Fragment>
                    {
                        typeof cover !== "undefined" && <div className="wpmf-cover"><img src={cover} /></div>
                    }

                    {typeof cover === "undefined" && hasfiles && (
                        <BlockControls>
                            <BlockAlignmentToolbar value={ align } onChange={ ( align ) => setAttributes( { align: align } ) } />

                            <Toolbar>
                                <IconButton
                                    className="components-toolbar__control"
                                    label={ wpmfdbxblocks.l18n.remove }
                                    icon={ 'no' }
                                    onClick={ () => setAttributes( { hasfiles: false, html: '' } ) }
                                />
                            </Toolbar>
                        </BlockControls>
                    ) }

                    {(typeof cover === "undefined" && hasfiles) &&
                        renderHTML(html)
                    }
                    {typeof cover === "undefined" && !hasfiles &&
                    <button className="components-button is-button is-default is-primary is-large aligncenter"
                            onClick={this.openModal}>{wpmfdbxblocks.l18n.btnopen}</button>}
                </Fragment>
            );
        }
    }

    const wpmfDropboxBlockIcon = (
        <svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" width="20" x="0px" y="0px"
             viewBox="0 0 447.232 447.232">
            <path fill={'#1587EA'} d="M207.527,251.676L92.903,177.758c-3.72-2.399-8.559-2.145-12.007,0.63L3.833,240.403
                c-5.458,4.392-5.015,12.839,0.873,16.636l114.624,73.918c3.72,2.399,8.559,2.145,12.007-0.63l77.063-62.014
                C213.858,263.92,213.415,255.473,207.527,251.676z"/>
            <path fill={'#1587EA'} d="M238.833,268.312l77.063,62.014c3.449,2.775,8.287,3.029,12.007,0.63l114.624-73.918
                c5.888-3.797,6.331-12.244,0.873-16.636l-77.063-62.014c-3.449-2.775-8.287-3.029-12.007-0.63l-114.624,73.918
                C233.819,255.473,233.375,263.92,238.833,268.312z"/>
            <path fill={'#1587EA'} d="M208.4,74.196l-77.063-62.014c-3.449-2.775-8.287-3.029-12.007-0.63L4.706,85.47
                c-5.888,3.797-6.331,12.244-0.873,16.636l77.063,62.014c3.449,2.775,8.287,3.029,12.007,0.63l114.624-73.918
                C213.415,87.035,213.858,78.588,208.4,74.196z"/>
            <path fill={'#1587EA'} d="M442.527,85.47L327.903,11.552c-3.72-2.399-8.559-2.145-12.007,0.63l-77.063,62.014
                c-5.458,4.392-5.015,12.839,0.873,16.636l114.625,73.918c3.72,2.399,8.559,2.145,12.007-0.63l77.063-62.014
                C448.858,97.713,448.415,89.266,442.527,85.47z"/>
            <path fill={'#1587EA'} d="M218,279.2l-86.3,68.841c-3.128,2.495-7.499,2.715-10.861,0.547L99.568,334.87
                c-6.201-3.999-14.368,0.453-14.368,7.831v7.416c0,3.258,1.702,6.28,4.488,7.969l128.481,77.884c2.969,1.8,6.692,1.8,9.661,0
                l128.481-77.884c2.786-1.689,4.488-4.71,4.488-7.969v-6.619c0-7.378-8.168-11.83-14.368-7.831l-20.024,12.913
                c-3.368,2.172-7.748,1.947-10.876-0.559l-85.893-68.809C226.238,276.489,221.405,276.484,218,279.2z"/>
        </svg>
    );
    registerBlockType('wpmf/block-dropbox-file', {
        title: wpmfdbxblocks.l18n.dropbox_drive,
        icon: wpmfDropboxBlockIcon,
        category: 'wp-media-folder',
        keywords: [
            __('dropbox', 'wpmfAddon'),
            __('file', 'wpmfAddon'),
            __('attachment', 'wpmfAddon')
        ],
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
                attribute: 'src',
            }
        },
        edit: WpmfDropboxDrive,
        save: ({attributes}) => {

            const {
                align,
                html,
                hasfiles,
            } = attributes;

            const renderHTML = (rawHTML: string) => React.createElement("div", { dangerouslySetInnerHTML: { __html: rawHTML } });
            return (
                (hasfiles) &&
                <div className={ `align${align}` }>
                    {renderHTML(html)}
                </div>
            );
        },
        getEditWrapperProps( attributes ) {
            const { align } = attributes;
            const props = { 'data-resized': true };

            if ( 'left' === align || 'right' === align || 'center' === align ) {
                props[ 'data-align' ] = align;
            }

            return props;
        }
    });
})(wp.i18n, wp.blocks, wp.element, wp.editor, wp.components);