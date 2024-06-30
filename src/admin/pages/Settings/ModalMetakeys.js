import React from 'react';
import {ModalSizes} from "../../../shared/scripts/constants";
import {connect} from "trim-redux";
import AntModal from "../../components/AntModal/AntModal";
import {addMetakey, changeMetakey, closeModals, deleteMetakey, getStoreKey, saveOptions} from "./Actions";
import {pubjet__} from "../../../shared/scripts/utils";
import {Alert, Button, Table} from "antd";
import {PlusOutlined, SaveOutlined} from "@ant-design/icons";
import styles from './ModalMetakeys.module.scss';
import SavedAlert from "../../components/SaveAlert/SavedAlert";

class ModalMetakeys extends AntModal {

    state = {
        saved  : false,
        error  : false,
        loading: false,
        saving : false,
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
    handleCancel = () => {
        closeModals();
    };

    /**
     * @since 1.0.0
     */
    modalProps = () => {
        return {
            footer: null,
            width : ModalSizes.MEDIUM,
        };
    };

    /**
     * @since 1.0.0
     */
    title = () => {
        return pubjet__('metakeys');
    };

    /**
     * @since 1.0.0
     */
    handleAdd = () => {
        addMetakey();
    };

    /**
     * @since 1.0.0
     */
    buttonAdd = () => {
        return <Button
            type={'default'}
            size={'large'}
            icon={<PlusOutlined/>}
            onClick={this.handleAdd}
        >
            {pubjet__('add-metakey')}
        </Button>
    };

    /**
     * @since 1.0.0
     */
    handleDelete = (record) => {
        return () => {
            deleteMetakey(record._id);
        };
    };

    /**
     * @since 1.0.0
     */
    columns = () => {
        return [
            {
                align : 'center',
                title : pubjet__('actions'),
                render: (value, record) => {
                    return <Button danger={true} block={true} onClick={this.handleDelete(record)}>
                        {pubjet__('delete')}
                    </Button>;
                },
            },
            {
                align : 'center',
                title : pubjet__('keyvalue'),
                render: (value, record) => {
                    return <input
                        value={record.value}
                        className={styles.input}
                        onChange={e => {
                            changeMetakey(record._id, 'value', e.target.value);
                        }}
                    />;
                },
            },
            {
                align : 'center',
                title : pubjet__('keyname'),
                render: (value, record) => {
                    return <input
                        value={record.name}
                        className={styles.input}
                        onChange={e => {
                            changeMetakey(record._id, 'name', e.target.value);
                        }}
                    />;
                },
            },
        ];
    };

    /**
     * @since 1.0.0
     */
    source = () => {
        return this.props.items;
    };

    /**
     * @since 1.0.0
     */
    table = () => {
        return <Table
            rowKey={'id'}
            columns={this.columns()}
            dataSource={this.source()}
            pagination={this.getPaginationConfig()}
            className={styles.table}
        />;
    };

    /**
     * @since 1.0.0
     */
    handleSave = () => {
        this.setState({error: false, saving: true,}, () => {
            saveOptions().then(response => {
                this.setState({saved: true}, () => {
                    setTimeout(() => {
                        this.setState({saved: false});
                    }, 1500);
                });
            }).finally(() => {
                this.setState({saving: false,})
            });
        });
    };

    /**
     * @since 1.0.0
     */
    buttonSave = () => {
        const {saving} = this.state;
        return <Button
            type={'primary'}
            size={'large'}
            icon={<SaveOutlined/>}
            block={true}
            className={styles.saveButton}
            onClick={this.handleSave}
            loading={saving}
        >
            {pubjet__('save')}
        </Button>
    };

    /**
     * @since 1.0.0
     */
    alert = () => {
        return <Alert
            type={'info'}
            message={pubjet__('pmk-hints')}
            className={styles.alert}
        />;
    };

    /**
     * @since 1.0.0
     */
    content = () => {
        const {saved} = this.state;
        return <div className={styles.wrapper}>
            {this.alert()}
            {this.buttonAdd()}
            {this.table()}
            {saved && <SavedAlert className={styles.savedAlert}/>}
            {this.buttonSave()}
        </div>;
    };

}

const mstp = (state) => ({
    isOpen: 'metakeys' === state[getStoreKey()].modal,
    items : state[getStoreKey()]?.metakeys?.items,
});

export default connect(mstp)(ModalMetakeys);