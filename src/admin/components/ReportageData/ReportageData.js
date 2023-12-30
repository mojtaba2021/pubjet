import React from 'react';
import {
    copyText,
    getAdminAjaxUrl,
    getAxios,
    getSecurityNonce,
    pubjet__,
    showSuccessMessage
} from "../../../shared/scripts/utils";
import Button from "../Button/Button";
import BaseComponent from "../BaseComponent/BaseComponent";
import styles from './ReportageData.module.scss';
import {Spin} from "antd";

const axios = getAxios();

class ReportageData extends BaseComponent {

    state = {
        payload: {},
        error  : false,
        loading: false,
    };

    /**
     * @since 1.0.0
     */
    componentDidMount() {
        this.fetch();
    }

    /**
     * @since 1.0.0
     */
    fetch = () => {
        this.setState({error: false, loading: true,}, () => {
            const postId = jQuery('#pubjet-reportage-panel-data').data('postid');
            axios.get(getAdminAjaxUrl(), {
                params: {
                    action  : 'pubjet-find-reportage-panel-data',
                    postId  : postId,
                    security: getSecurityNonce(),
                },
            }).then(response => {
                if (response.success) {
                    const {payload} = response;
                    this.setState({payload});
                } else {
                    this.setState({error: true,});
                }
            }).catch(err => {
                this.setState({error: true,});
            }).finally(() => {
                this.setState({loading: false,});
            });
        });
    };

    /**
     * @since 1.0.0
     */
    input = () => {
        return this.renderInput({
            name     : 'panelData',
            textarea : true,
            readOnly : true,
            rows     : 10,
            className: styles.input,
            value    : this.state.payload.data,
        });
    };

    /**
     * @since 1.0.0
     */
    handleCopy = () => {
        copyText('siavsah ebrahimi');
        showSuccessMessage(pubjet__('copied'));
    };

    /**
     * @since 1.0.0
     */
    button = () => {
        return <Button shape={'round'}
                       size={'small'}
                       onClick={this.handleCopy}
                       block={true}
                       className={styles.copyButton}
        >
            کپی کردن
        </Button>;
    };

    /**
     * @since 1.0.0
     * @returns {Element}
     */
    render() {
        return (
            <div className={styles.wrapper}>
                <Spin spinning={this.state.loading}>
                    {this.input()}
                    {this.button()}
                </Spin>
            </div>
        );
    }
}

export default ReportageData;