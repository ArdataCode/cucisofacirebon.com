(function (wpI18n, wpBlocks, wpElement, wpEditor, wpComponents) {
    const {__} = wp.i18n;
    const {Component, Fragment} = wp.element;
    const {registerBlockType} = wpBlocks;
    const {BlockControls, BlockAlignmentToolbar} = wpEditor;
    const {Modal, FocusableIframe, IconButton, Toolbar} = wp.components;
    const $ = jQuery;

    class WpmfGoogleDrive extends Component {
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

            if (e.data.type !== 'wpmfgoogleinsert') {
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
                                    label={ wpmfblocks.l18n.remove }
                                    icon={ 'no' }
                                    onClick={ () => setAttributes( { hasfiles: false, 'html': '' } ) }
                                />
                            </Toolbar>
                        </BlockControls>
                    ) }

                    {(typeof cover === "undefined" && hasfiles) &&
                    renderHTML(html)
                    }
                    {typeof cover === "undefined" && !hasfiles &&
                    <button className="components-button is-button is-default is-primary is-large aligncenter"
                            onClick={this.openModal}>{wpmfblocks.l18n.btnopen}</button>}
                </Fragment>
            );
        }
    }

    const wpmfGoogleBlockIcon = (
        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="20" x="0px" y="0px"
             viewBox="0 0 512 512" >
            <polygon fill={'#FFC107'} points="341.344,352 512,352 341.344,32 170.656,32 "/>
            <polygon fill={'#2196F3'} points="158.464,352 85.344,480 432,480 512,352 "/>
            <polygon fill={'#4CAF50'} points="170.656,32 0,330.656 85.344,480 253.056,186.496 "/>
        </svg>
    );
    registerBlockType('wpmf/block-google-file', {
        title: wpmfblocks.l18n.google_drive,
        icon: wpmfGoogleBlockIcon,
        category: 'wp-media-folder',
        keywords: [
            __('google', 'wpmfAddon'),
            __('file', 'wpmfAddon'),
            __('attachment', 'wpmfAddon')
        ],
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
                attribute: 'src',
            }
        },
        edit: WpmfGoogleDrive,
        save: ({attributes}) => {

            const {
                align,
                html,
                hasfiles
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