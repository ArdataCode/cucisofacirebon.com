// External Dependencies
import React, {Component} from 'react';
import $ from 'jquery';
// Internal Dependencies
import './style.css';

class WpmfPdfEmbedDivi extends Component {

    static slug = 'wpmf_pdf_embed';

    constructor(props) {
        super(props);
        this.state = {
            html: '',
            loading: true,
            embed: true
        };
        this.wrap = React.createRef();
    }

    componentWillMount() {
        if (typeof this.props.url !== "undefined") {
            this.loadHtml(this.props);
        }
    }

    componentWillReceiveProps(nextProps) {
        if (this.props.url !== nextProps.url || this.props.embed !== nextProps.embed || this.props.target !== nextProps.target) {
            this.loadHtml(nextProps);
        }
    }

    loadHtml(props) {
        if (!this.state.loading) {
            this.setState({
                loading: true
            });
        }
        let width = '';
        let height = '';
        if (typeof props.width !== "undefined") {
            width = props.width;
        } else {
            if (typeof props.max_width !== "undefined") {
                width = props.max_width;
            }
        }

        if (typeof props.height !== "undefined") {
            height = props.height;
        } else {
            if (typeof props.max_height !== "undefined") {
                height = props.max_height;
            }
        }
        fetch(window.et_fb_options.ajaxurl + `?action=wpmf_divi_load_pdf_embed_html&url=${props.url}&embed=${props.embed}&target=${props.target}&width=${width}&height=${height}&et_admin_load_nonce=${window.et_fb_options.et_admin_load_nonce}`)
            .then(res => res.json())
            .then(
                (result) => {
                    this.setState({
                        html: result.html,
                        loading: false,
                        embed: false
                    });
                },
                // errors
                (error) => {
                    this.setState({
                        html: '',
                        loading: true
                    });
                }
            );
    }

    componentDidUpdate(prevProps, prevState) {
        if (this.props.embed === 'on' && !this.state.embed) {
            $(this.wrap.current).find(".wpmf-pdfemb-viewer").pdfEmbedder();
            this.setState({
                embed: true
            });
        }
    }

    render() {
        const loadingIcon = (
            <svg className={'wpfd-loading'} width="100%" height="100%" xmlns="http://www.w3.org/2000/svg"
                 viewBox="0 0 100 100" preserveAspectRatio="xMidYMid">
                <g transform="translate(25 50)">
                    <circle cx="0" cy="0" r="10" fill="#cfcfcf" transform="scale(0.590851 0.590851)">
                        <animateTransform attributeName="transform" type="scale" begin="-0.8666666666666667s"
                                          calcMode="spline"
                                          keySplines="0.3 0 0.7 1;0.3 0 0.7 1" values="0.5;1;0.5" keyTimes="0;0.5;1"
                                          dur="2.6s"
                                          repeatCount="indefinite"/>
                    </circle>
                </g>
                <g transform="translate(50 50)">
                    <circle cx="0" cy="0" r="10" fill="#cfcfcf" transform="scale(0.145187 0.145187)">
                        <animateTransform attributeName="transform" type="scale" begin="-0.43333333333333335s"
                                          calcMode="spline"
                                          keySplines="0.3 0 0.7 1;0.3 0 0.7 1" values="0.5;1;0.5" keyTimes="0;0.5;1"
                                          dur="2.6s"
                                          repeatCount="indefinite"/>
                    </circle>
                </g>
                <g transform="translate(75 50)">
                    <circle cx="0" cy="0" r="10" fill="#cfcfcf" transform="scale(0.0339143 0.0339143)">
                        <animateTransform attributeName="transform" type="scale" begin="0s" calcMode="spline"
                                          keySplines="0.3 0 0.7 1;0.3 0 0.7 1" values="0.5;1;0.5" keyTimes="0;0.5;1"
                                          dur="2.6s"
                                          repeatCount="indefinite"/>
                    </circle>
                </g>
            </svg>
        );

        if (typeof this.props.url === "undefined") {
            return (
                <div className="wpmf-divi-container">
                    <div id="divi-pdf-embed-placeholder" className="divi-pdf-embed-placeholder">
                        <span className="wpmf-divi-message">
                            {'Please select a PDF file to activate the preview'}
                        </span>
                    </div>
                </div>
            );
        }

        if (this.state.loading) {
            return (
                <div className="wpmf-divi-container">
                    <div className={'wpmf-loading-wrapper'}>
                        <i className={'wpmf-loading'}>{loadingIcon}</i>
                    </div>
                </div>
            );
        }

        if (!this.state.loading) {
            return (
                <div className="wpmf-pdf-embed-wrap" ref={this.wrap}
                     dangerouslySetInnerHTML={{__html: this.state.html}}/>
            );
        }
    }
}

export default WpmfPdfEmbedDivi;
