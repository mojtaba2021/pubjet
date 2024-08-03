import React from 'react';
import {ModalSizes} from "../../../shared/scripts/constants";
import {connect} from "trim-redux";
import AntModal from "../../components/AntModal/AntModal";
import {closeModals, getStoreKey, saveAuthorProps} from "./Actions";
import {getAdminAjaxUrl, getAxios, getSecurityNonce, pubjet__} from "../../../shared/scripts/utils";
import {Alert, Button, Spin, Table} from "antd";
import {SaveOutlined} from "@ant-design/icons";
import styles from './ModalReportageAuthor.module.scss';
import SavedAlert from "../../components/SaveAlert/SavedAlert";

const axios = getAxios();

class ModalReportageAuthor extends AntModal {

    state = {
        authors : [],
        saved   : false,
        error   : false,
        loading : false,
        saving  : false,
        authorId: false,
    };

    /**
     * @since 1.0.0
     */
    componentDidMount() {
        this.setState({authorId: this.props.authorId}, () => {
            this.fetchAuthors();
        });
    }

    /**
     * @since 1.0.0
     */
    fetchAuthors = () => {
        this.setState({error: false, loading: true,}, async () => {
            try {
                const response = await axios.get(getAdminAjaxUrl(), {
                    params: {
                        action  : 'pubjet-find-authors',
                        security: getSecurityNonce(),
                    }
                });
                if (response.success) {
                    this.setState({authors: response.payload});
                }
            } catch (err) {
                this.setState({error: err.message});
            } finally {
                this.setState({loading: false});
            }
        });
    };

    /**
     * @since 1.0.0
     */
    isOpen = () => {
        // return true;
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
        return pubjet__('select-rep-author');
    };

    /**
     * @since 1.0.0
     */
    columns = () => {
        return [
            {
                title : pubjet__('username'),
                render: (value, record) => {
                    return record.user_login;
                },
            },
            {
                title : pubjet__('displayname'),
                render: (value, record) => {
                    return record.display_name;
                },
            },
        ];
    };

    /**
     * @since 1.0.0
     */
    source = () => {
        return this.state.authors;
    };

    /**
     * @since 1.0.0
     */
    table = () => {
        const {loading, authorId} = this.state;
        return <Spin spinning={loading}>
            <Table
                rowKey={'ID'}
                columns={this.columns()}
                dataSource={this.source()}
                pagination={this.getPaginationConfig()}
                className={styles.table}
                rowClassName={styles.cursorPointer}
                rowSelection={{
                    type           : "radio",
                    selectedRowKeys: authorId ? [Number(authorId)] : [],
                    onChange       : (selectedRowKeys, selectedRows) => {
                        this.setState({authorId: selectedRowKeys[0]});
                    },
                }}
                onRow={(record) => ({
                    onClick: () => {
                        this.setState({authorId: record.ID});
                    }
                })}
            />
        </Spin>;
    };

    /**
     * @since 1.0.0
     */
    handleSave = () => {
        this.setState({error: false, saving: true,}, async () => {
            try {
                const response = await axios.post(getAdminAjaxUrl(), {
                    action  : 'pubjet-save-reportage-author',
                    authorId: this.state.authorId,
                    security: getSecurityNonce()
                });
                if (response.success) {
                    this.setState({saved: true,}, () => {
                        // Update store
                        saveAuthorProps('authorId', this.state.authorId);
                    });
                } else {
                    this.setState({error: response.error});
                }
            } catch (err) {
                this.setState({error: err.message});
            } finally {
                this.setState({saving: false,});
            }
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
            message={pubjet__('select-rep-author-hints')}
            className={styles.alert}
        />;
    };

    /**
     * @since 1.0.0
     */
    content = () => {
        const {saved} = this.state;
        return <div className={styles.wrapper}>
            {/*{this.alert()}*/}
            {this.table()}
            {saved && <SavedAlert className={styles.savedAlert}/>}
            {this.buttonSave()}
        </div>;
    };

}

const mstp = (state) => ({
    isOpen  : 'repauthor' === state[getStoreKey()].modal,
    authorId: state[getStoreKey()]?.repauthor?.authorId,
});

export default connect(mstp)(ModalReportageAuthor);