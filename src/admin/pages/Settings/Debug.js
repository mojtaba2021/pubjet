import React from 'react';
import {
    copyText,
    findEndpointUrl,
    getAxios,
    getSecurityNonce,
    pubjet__,
    showErrorMessage,
    showSuccessMessage
} from "../../../shared/scripts/utils";
import {Button, Form, Space, Spin, Switch, Tooltip} from "antd";
import styles from "./Debug.module.scss";
import BaseComponent from "../../components/BaseComponent/BaseComponent";
import {CopyOutlined, DeleteOutlined, ReloadOutlined} from "@ant-design/icons";
import ConfirmDelete from "../../components/ConfirmDelete/ConfirmDelete";
import {changeInput, saveOptions} from "./Actions";
import {connect} from "trim-redux";

const axios = getAxios();

class Debug extends BaseComponent {

    state = {
        text   : '',
        error  : false,
        loading: false,
    };

    /**
     * @since 1.0.0
     */
    handleCopy = () => {
        copyText(this.state.text);
    };

    /**
     * @since 1.0.0
     */
    handleDelete = () => {
        this.setState({error: false, loading: true}, () => {
            axios.post(findEndpointUrl('delete-debug'), {
                security: getSecurityNonce(),
            }).then(response => {
                if (response.success) {
                    this.setState({text: ''});
                } else {
                    showErrorMessage(response.error);
                }
            }).catch(error => {
                showErrorMessage(error);
            }).finally(() => {
                this.setState({
                    loading: false,
                });
            });
        });
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
    input = () => {
        return <Form layout={'horizontal'} colon={false}>
            <Form.Item className={'pubjet-mb-1'}>
                {this.renderInput({
                    textarea : true,
                    name     : 'text',
                    readOnly : true,
                    rows     : 12,
                    className: styles.input
                })}
            </Form.Item>
        </Form>;
    };

    /**
     * @since 1.0.0
     */
    fetch = () => {
        this.setState({loading: true, error: false,}, () => {
            axios.get(findEndpointUrl('get-debug'), {
                params: {
                    security: getSecurityNonce(),
                },
            }).then(response => {
                if (response.success) {
                    this.setState({
                        text: response.payload.text,
                    });
                } else {
                    this.setState({error: response.error});
                }
            }).catch(error => {
                this.setState({error: true,});
            }).finally(() => {
                this.setState({loading: false,});
            });
        });
    };

    /**
     * @since 1.0.0
     */
    button = () => {
        const {text} = this.state;
        return <Space.Compact className={styles.buttonsWrapper}>
            <Tooltip title={pubjet__('copy')}>
                <Button disabled={!text} type={'default'} size={'small'} icon={<CopyOutlined/>}
                        onClick={this.handleCopy}></Button>
            </Tooltip>
            <Tooltip title={pubjet__('reload')}>
                <Button type={'default'} size={'small'} icon={<ReloadOutlined/>}
                        onClick={this.fetch}></Button>
            </Tooltip>
            <Tooltip title={pubjet__('delete-log')}>
                <ConfirmDelete title={''} description={pubjet__('delete-log-confirm')} cancelText={pubjet__('no')}
                               okText={pubjet__('yes')} onConfirm={this.handleDelete}>
                    <Button disabled={!text} type={'default'} size={'small'} icon={<DeleteOutlined/>}
                            buttonProps={{danger: true,}}></Button>
                </ConfirmDelete>
            </Tooltip>
        </Space.Compact>;
    };

    /**
     * @since 1.0.0
     */
    enable = () => {
        const {debug} = this.props.options;
        return <Form layout={'vertical'} colon={false}>
            <Form.Item label={pubjet__('enable')}>
                <Switch checked={debug} onChange={(checked) => {
                    changeInput('debug', checked);
                    showSuccessMessage(pubjet__('saved'));
                    saveOptions();
                }} size={'default'}/>
            </Form.Item>
        </Form>;
    }

    /**
     * @since 1.0.0
     * @returns {Element}
     */
    render() {
        const {debug} = this.props.options;
        const {loading} = this.state;
        return (
            <Spin spinning={loading}>
                <div className={styles.wrapper}>
                    {this.enable()}
                    {debug && this.input()}
                    {debug && this.button()}
                </div>
            </Spin>
        );
    }


}

const mstp = (state) => ({
    options: state.options,
});

export default connect(mstp)(Debug);