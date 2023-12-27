import React from 'react';
import {connect} from "trim-redux";
import AntModal from "../../components/AntModal/AntModal";
import {ModalSizes} from "../../../shared/scripts/constants";
import {closeGlobalModal} from "../../store/Actions";
import Button from "../../components/Button/Button";
import styles from './ModalDebug.module.scss';
import {copyText, getAdminAjaxUrl, getAxios, showErrorMessage} from "../../../shared/scripts/utils";
import {CopyOutlined, DeleteOutlined, ReloadOutlined} from "@ant-design/icons";
import {Space, Spin, Tooltip} from "antd";
import ConfirmDelete from "../../components/ConfirmDelete/ConfirmDelete";

const axios = getAxios();

class ModalDebug extends AntModal {

    state = {
        text: '',
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
        this.setState({loading: true, error: false,}, () => {
            axios.get(getAdminAjaxUrl(), {
                params: {
                    action: 'pubjet-get-debug',
                    security: pubjet_params.nonce,
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
    title = () => {
        return 'لاگ درخواست ها';
    };

    /**
     * @since 1.0.0
     */
    isOpen = () => {
        return this.props.isOpen;
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
            axios.post(getAdminAjaxUrl(), {
                action: 'pubjet-delete-debug',
                security: pubjet_params.nonce,
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
    modalProps = () => {
        return {
            footer: null,
            size: ModalSizes.MEDIUM,
            destroyOnClose: true,
        };
    };

    /**
     * @since 1.0.0
     */
    handleCancel = () => {
        closeGlobalModal();
    };

    /**
     * @since 1.0.0
     */
    input = () => {
        return this.renderInput({
            textarea: true,
            name: 'text',
            readOnly: true,
            rows: 12,
            className: styles.input
        });
    };

    /**
     * @since 1.0.0
     */
    button = () => {
        const {text} = this.state;
        return <Space.Compact className={styles.buttonsWrapper}>
            <Tooltip title={'کپی کردن'}>
                <Button disabled={!text} type={'default'} size={'small'} icon={<CopyOutlined/>}
                        onClick={this.handleCopy}></Button>
            </Tooltip>
            <Tooltip title={'بارگذاری مجدد'}>
                <Button type={'default'} size={'small'} icon={<ReloadOutlined/>}
                        onClick={this.fetch}></Button>
            </Tooltip>
            <Tooltip title={'حذف لاگ'}>
                <ConfirmDelete title={''} description={'آیا از حذف فایل لاگ مطمئن هستید ؟'} cancelText={'خیر'} okText={'بله'}  onConfirm={this.handleDelete}>
                    <Button disabled={!text} type={'default'} size={'small'} icon={<DeleteOutlined/>}
                            buttonProps={{danger: true,}}></Button>
                </ConfirmDelete>
            </Tooltip>
        </Space.Compact>;
    };

    /**
     * @since 1.0.0
     * @returns {Element}
     */
    content = () => {
        const {loading} = this.state;
        return (
            <Spin spinning={loading}>
                <div className={styles.wrapper}>
                    {this.input()}
                    {this.button()}
                </div>
            </Spin>
        );
    }
}

const mstp = (state) => ({
    isOpen: 'debug' === state.modal,
});

export default connect(mstp)(ModalDebug);