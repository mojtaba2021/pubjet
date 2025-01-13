import React from 'react';
import {ModalSizes} from "../../../shared/scripts/constants";
import {connect} from "trim-redux";
import AntModal from "../../components/AntModal/AntModal";
import {addMetakey, changeMetakey, closeModals, deleteAuthorCategory, getStoreKey, saveAuthorProps} from "./Actions";
import {getAdminAjaxUrl, getAxios, getSecurityNonce, pubjet__} from "../../../shared/scripts/utils";
import {Alert, Button, Spin, Table, Select , Divider} from "antd";
import {PlusOutlined, SaveOutlined} from "@ant-design/icons";
import styles from './ModalReportageAuthor.module.scss';
import SavedAlert from "../../components/SaveAlert/SavedAlert";
import {v4 as uuid} from "uuid";
import Span from "../../components/Span/Span";

const axios = getAxios();

class ModalReportageAuthor extends AntModal {

    state = {
        authors : [],
        authorCategory: [],
        categories :[],
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
        this.setState({authorId: this.props.authorId || false}, () => {
            this.fetchAuthors();
        });
        this.setState({authorCategory: this.props.authorCategory || [] }, () => {
            this.fetchCategories();
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
    fetchCategories = () => {
        this.setState({error: false, loading: true,}, async () => {
            try {
                const response = await axios.get(getAdminAjaxUrl(), {
                    params: {
                        action  : 'pubjet-categories',
                        security: getSecurityNonce(),
                    }
                });
                if (response.success) {
                    this.setState({categories: response.payload});
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
        const { authorId, authors } = this.state;
        return [
            {
                title: pubjet__('displayname'),
                render: (value, record,index) => {
                    return (
                        <Select
                            style={{
                                width: '100%',
                            }}
                            value={record.display_name}
                            allowClear
                            placeholder="select a username"
                            options={
                                authors.map((author) => ({
                                    value: author.ID,
                                    label: author.display_name,
                                    key: author.ID,
                                }))
                            }
                            // onChange={(value) => this.handleAuthorCategoryChange(index, 'author', value)}
                        />
                    )
                }

            }
            // {
            //     title : pubjet__('username'),
            //     render: (value, record) => {
            //         return record.user_login;
            //     },
            // },
            // {
            //     title : pubjet__('displayname'),
            //     render: (value, record) => {
            //         return record.display_name;
            //     },
            // },
        ];
    };

    /**
     * @since 1.0.0
     */
    // handleAuthorCategoryChange = (index, key, value) => {
    //     const updatedAuthorCategory = [...this.state.authorCategory];
    //     updatedAuthorCategory[index][key] = value;
    //     this.setState({ authorCategory: updatedAuthorCategory });
    // };
    handleAuthorCategoryChange = (index, key, value) => {
        const currentAuthorCategory = this.state.authorCategory || [];
        const updatedAuthorCategory = [...currentAuthorCategory];
        updatedAuthorCategory[index] = {
            ...updatedAuthorCategory[index],
            [key]: value
        };
        this.setState({ authorCategory: updatedAuthorCategory });
    };
    /**
     * @since 1.0.0
     */
    handleDelete = (record) => {
        return () => {
            deleteAuthorCategory(record, (newAuthorCategory) => {
                this.setState({ authorCategory: newAuthorCategory });
            });
        };
    };

    /**
     * @since 1.0.0
     */
    categoryColumns = () => {
        const { categories, authors } = this.state;

        return [
            {
                align : 'center',
                title : pubjet__('actions'),
                render: (value, record,index) => {
                    return <Button danger={true} block={true} onClick={this.handleDelete(record.id)}>
                        {pubjet__('delete')}
                    </Button>;
                },
            },
            {
                align: 'center',
                title: pubjet__('category'),
                render: (value, record,index) => {
                    return (
                        <Select
                            style={{
                                width: '100%',
                            }}
                            value={record.category}
                            allowClear
                            placeholder="select a category"
                            options={
                                categories.map((category) => ({
                                    value: category.id,
                                    label: category.name,
                                    key: category.id,
                                }))
                            }
                            onChange={(value) => this.handleAuthorCategoryChange(index, 'category', value)}

                        />
                    );
                },
            },
            {
                align: 'center',
                title: pubjet__('username'),
                render: (value, record,index) => {
                    return (
                        <Select
                            style={{
                                width: '100%',
                            }}
                            value={record.author}
                            allowClear
                            placeholder="select a username"
                            options={
                                authors.map((author) => ({
                                    value: author.ID,
                                    label: author.display_name,
                                    key: author.ID,
                                }))
                            }
                            onChange={(value) => this.handleAuthorCategoryChange(index, 'author', value)}
                        />
                    );
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
    categorySource = () => {
        return this.state.authorCategory;
    };

    /**
     * @since 1.0.0
     */
    // table = () => {
    //     const {loading, authorId} = this.state;
    //     return <Spin spinning={loading}>
    //         <Table
    //             rowKey={'ID'}
    //             columns={this.columns()}
    //             dataSource={this.source()}
    //             pagination={this.getPaginationConfig()}
    //             className={styles.table}
    //             rowClassName={styles.cursorPointer}
    //             // rowSelection={{
    //             //     type           : "radio",
    //             //     selectedRowKeys: authorId ? [Number(authorId)] : [],
    //             //     onChange       : (selectedRowKeys, selectedRows) => {
    //             //         this.setState({authorId: selectedRowKeys[0]});
    //             //     },
    //             // }}
    //             onRow={(record) => ({
    //                 onClick: () => {
    //                     this.setState({authorId: record.ID});
    //                 }
    //             })}
    //         />
    //     </Spin>;
    // };

    /**
     * @since 1.0.0
     */
    categoryTable = () => {
        const {loading, authorId} = this.state;
        return <Spin spinning={loading}>
            <Table
                rowKey={(record) => record.id}
                columns={this.categoryColumns()}
                dataSource={this.categorySource()}
                pagination={this.getPaginationConfig()}
                className={styles.table}
                rowClassName={styles.cursorPointer}
            />
        </Spin>;
    };

    /**
     * @since 1.0.0
     */
    handleSave = () => {

        const { authorId, authorCategory } = this.state;

        // validate author and category before save
        const validAuthorCategory = authorCategory.filter(item =>
            item.author && item.category
        );
        this.setState({error: false, saving: true,}, async () => {
            try {
                const response = await axios.post(getAdminAjaxUrl(), {
                    action  : 'pubjet-save-reportage-author',
                    authorId: authorId,
                    authorCategory: JSON.stringify(validAuthorCategory),
                    security: getSecurityNonce()
                });
                if (response.success) {
                    this.setState({saved: true,}, () => {
                        // Update store
                        saveAuthorProps('authorId', authorId);
                        saveAuthorProps('authorCategory', validAuthorCategory);
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
    handleAdd = () => {
        this.setState((prevState) => ({
            authorCategory: [
                ...prevState.authorCategory,
                { id: uuid(),author: '', category: '' },
            ],
        }));
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
            {pubjet__('add-authorCategory')}
        </Button>
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

    handleAuthorChange = (value) => {
        this.setState({authorId : value})
    }
    /**
     * @since 1.0.0
     */
    defaultAuthor = () => {
        const {loading, authorId, authors} = this.state;
        const selectedAuthor = authors.find((author) => author.ID == authorId);


        return <Spin spinning={loading}>

            <Select
                // showSearch
                allowClear
                style={{ width: '100%', height: '40px',margin: '8px 0 20px 0' }}
                value={selectedAuthor ? { value: selectedAuthor.ID, label: selectedAuthor.display_name } : null}
                placeholder="نویسنده پیش فرض را انتخاب کنید"
                optionFilterProp="label"
                filterSort={(optionA, optionB) =>
                    (optionA?.label ?? '').toLowerCase().localeCompare((optionB?.label ?? '').toLowerCase())
                }
                options={
                    authors.map((author) => ({
                        value: author.ID,
                        label: author.display_name ,
                        key: author.ID,
                    }))
                }
                onChange={(value) => this.handleAuthorChange(value)}
            />
        </Spin>;
    };
    /**
     * @since 1.0.0
     */
    alert = () => {
        return (<>
            <Divider plain>انتخاب نویسنده پیش فرض</Divider>
            <Alert
                type={'info'}
                message={pubjet__('select-rep-author-hints')}
                className={styles.alert}
            />
        </>);
    };

    /**
     * @since 1.0.0
     */
    authorCategoryAlert = () => {
        return(<>            <Divider plain>انتخاب نویسنده به ازای هر دسته بندی </Divider>
         <Alert
            type={'info'}
            message={pubjet__('add-authorCategory-hints')}
            className={styles.alert}
        /></>);
    };

    /**
     * @since 1.0.0
     */
    content = () => {
        const {saved} = this.state;
        return <div className={styles.wrapper}>
            {this.alert()}
            {/*{this.table()}*/}
            {this.defaultAuthor()}
            {this.authorCategoryAlert()}
            {this.buttonAdd()}
            {this.categoryTable()}
            {saved && <SavedAlert className={styles.savedAlert}/>}
            {this.buttonSave()}
        </div>;
    };

}


const mstp = (state) => ({
    isOpen  : 'repauthor' === state[getStoreKey()].modal,
    authorId: state[getStoreKey()]?.repauthor?.authorId,
    authorCategory: state[getStoreKey()]?.repauthor?.authorCategory,

});

export default connect(mstp)(ModalReportageAuthor);