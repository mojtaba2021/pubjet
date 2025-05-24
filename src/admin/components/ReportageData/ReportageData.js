import React from 'react';
import {copyText, findEndpointUrl, getAxios, pubjet__, showSuccessMessage} from "../../../shared/scripts/utils";
import Button from "../Button/Button";
import BaseComponent from "../BaseComponent/BaseComponent";
import styles from './ReportageData.module.scss';
import {Spin} from "antd";
import {CopyOutlined} from "@ant-design/icons";

const axios = getAxios();

class ReportageData extends BaseComponent {

    state = {
        payload: {},
        error: false,
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
            axios.get(findEndpointUrl('find-reportage-panel-data'), {
                params: {
                    postId: postId,
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
            name: 'panelData',
            textarea: 'true',
            readOnly: true,
            rows: 10,
            className: styles.input,
            value: this.state.payload.data,
        });
    };

    /**
     * @since 1.0.0
     */
    handleCopy = () => {
        const {payload} = this.state;
        const textToCopy = payload?.data;

        if (textToCopy) {
            copyText(textToCopy);
            showSuccessMessage(pubjet__('copied'));
        }
    };

    /**
     * @since 1.0.0
     */
    button = () => {
        const isDisabled = !this.state.payload?.data;

        return <Button shape={'round'}
                       size={'small'}
                       onClick={this.handleCopy}
                       block={true}
                       disabled={isDisabled}
                       className={styles.copyButton}
                       icon={<CopyOutlined/>}
                >
            {pubjet__('copy')}
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