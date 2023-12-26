import React, {Component as ReactComponent} from 'react';
import ErrorOccured from '../ErrorOccured/ErrorOccured';
import ConditionalWrap from '../ConditionalWrap/ConditionalWrap';
import {Alert, Button, Dropdown, Input, Menu, Modal, Switch} from 'antd';
import {EllipsisOutlined} from '@ant-design/icons';

const {TextArea} = Input;

class BaseComponent extends ReactComponent {

    /**
     * @param e
     */
    handleKeyChange = (e) => {
        this.setState({
            [e.target.name]: e.target.value,
        });
    };

    /**
     * @since 1.0.0
     */
    handleClearSmsText = () => {
        this.setState({text: ''});
    };

    /**
     * @since 1.0.0
     */
    handlePatternTemplate = () => {
        this.setState({
            text: `#patternId
GatewayParamA=ParamNameA
GatewayParamB=ParamNameB
GatewayParamC=ParamNameC
GatewayParamD=ParamNameD`,
        });
    };

    /**
     * @since 1.0.0
     * @param e
     */
    handleSendTypeChange = (e) => {
        this.setState({
            type: e.target.value,
        });
    };

    /**
     * @since 1.0.0
     * @returns {boolean}
     */
    isSendByPattern = () => {
        return 'pattern' === this.state.type;
    };

    /**
     * @since 1.0
     */
    renderInput = (args) => {
        const {textarea = false} = args;
        if (textarea) {
            return <TextArea onChange={this.handleKeyChange}
                             value={this.state[args.name]} {...args}/>;
        }
        return <Input
            onChange={this.handleKeyChange}
            value={this.state[args.name]}
            size={'large'}
            {...args}
        />;
    };

    /**
     * @since 1.0.0
     */
    getPaginationConfig = () => {
        const {currentPage, totalItems, perPage} = this.getPaginationSource();
        return {
            current: currentPage,
            defaultCurrent: currentPage,
            total: totalItems,
            simple: false,
            size: this.getPaginationSize(),
            hideOnSinglePage: true,
            defaultPageSize: perPage,
            pageSize: perPage,
            showSizeChanger: false,
            onChange: this.handlePageChanged,
        };
    };

    /**
     * @since 1.0.0
     */
    getPaginationSource = () => {
        return {
            currentPage: 1,
            totalItems: 1,
            perPage: 50,
        };
    };

    /**
     * @since 1.0.0
     */
    getPaginationSize = () => {
        return 'small';
    };

    /**
     * @since 1.0.0
     */
    handlePageChanged = (pageNumber, pageSize) => {
    };

    /**
     * @since 1.0.0
     * @param label
     * @returns {JSX.Element|boolean}
     */
    renderInputLabel = (label) => {
        if (!label) {
            return false;
        }
        return <label className={'pubjet-d-block pubjet-mb-2'}>{label}</label>;
    };

    /**
     * @since 1.0
     */
    renderSwitch = (args = {}) => {
        const {label} = args;
        return (
            <ConditionalWrap
                condition={label}
                wrap={(children) => (
                    <div className={'pubjet-d-flex pubjet-justify-content-between'}>
                        <label htmlFor={args.id} className={'pubjet-cursor-pointer'}>
                            {label}
                        </label>
                        {children}
                    </div>
                )}
            >
                <Switch
                    size={'large'}
                    onChange={(checked) => {
                        this.setState({[args.id]: checked});
                    }}
                    checked={!!this.state[args.id]}
                    {...args}
                />
            </ConditionalWrap>
        );
    };

    /**
     * @since 1.0.0
     */
    renderError = (className = '', props = {}) => {
        const {loading, error} = this.state;
        if (loading || !error) {
            return null;
        }
        return <ErrorOccured className={className} props={props}/>;
    };

    /**
     * @since 1.0
     * @param text
     * @param args
     * @returns {*}
     */
    renderButton = (text, args = {}) => {
        args.className = args.className || '';
        if (args.responsive) {
            args.className = `${args.className} pubjet-btn-responsive`;
        }
        return (
            <Button type={'primary'} size={'large'} {...args}>
                {text}
            </Button>
        );
    };

    /**
     * @since 1.0
     */
    renderAlert = (message, type = 'success', args = {}) => {
        return (
            <Alert
                type={type}
                message={message}
                className={type === 'gray' ? 'ant-alert-gray' : ''}
                {...args}
            />
        );
    };

    /**
     * @since 1.0
     */
    handleHideModal = () => {
        this.setState({modal: false, item: false});
    };

    /**
     * @since 1.0
     * @param name
     */
    handleShowModal = (name) => {
        this.setState({modal: name});
    };

    /**
     * @since 1.0
     * @param args
     */
    renderModal = (args) => {
        const {content} = args;
        return (
            <Modal
                cancelText={'انصراف'}
                onCancel={this.handleHideModal}
                centered
                {...args}
            >
                {content}
            </Modal>
        );
    };

    /**
     * @since 1.0
     * @param selectedRowKeys
     * @param selectedRowItems
     */
    handleTableCheckboxChanged = (selectedRowKeys, selectedRowItems) => {
        this.setState({selectedRowKeys, selectedRowItems});
    };

    /**
     * @since 1.0
     * @returns {{onChange: handleTableCheckboxChanged,
     *     selectedRowKeys}}
     */
    getTableRowSelectionConfig = () => {
        return {
            onChange: this.handleTableCheckboxChanged,
            selectedRowKeys: this.getSelectedRowKeys(),
            preserveSelectedRowKeys: true,
        };
    };

    /**
     * @since 1.0.0
     */
    getSelectedRowKeys = () => {
        return this.state.selectedRowKeys;
    };

    /**
     * @since 1.0.0
     */
    handleRowClickCheckbox = (recordId) => {
        const {selectedRowKeys} = this.state;
        const found = selectedRowKeys.filter(
            item => item === recordId)[0];
        if (found) {
            this.setState({
                selectedRowKeys: selectedRowKeys.filter(
                    item => item !== recordId),
            });
        } else {
            this.setState({
                selectedRowKeys: [
                    ...this.state.selectedRowKeys,
                    recordId,
                ],
            });
        }
    };

    /**
     * @since 1.0
     * @param stateKey
     */
    handleChangePhone = (stateKey) => {
        return (args) => {
            this.setState({
                [stateKey]: args,
            });
        };
    };

    /**
     * @param value
     * @param callback
     */
    handleToggleLoading = (value, callback) => {
        this.setState({loading: value}, () => {
            if (typeof callback === 'function') {
                callback();
            }
        });
    };

    /**
     * @since 1.0
     */
    handleCloseModals = () => {
        this.setState({
            modal: false,
        });
    };

    /**
     * @since 1.0
     * @param items
     * @param args
     * @returns {*}
     */
    renderTableDropdown = (items, args = {}) => {
        const menu = (
            <Menu style={{width: '150px'}}>
                {items.map((i) => {
                    const {title, onClick, hidden} = i;
                    if (hidden) {
                        return false;
                    }
                    return (
                        <Menu.Item key={uuid()} onClick={onClick}>
                            {title}
                        </Menu.Item>
                    );
                })}
            </Menu>
        );
        return (
            <Dropdown size={'large'} overlay={menu} trigger={['click']}>
                <a className="ant-dropdown-link" onClick={(e) => e.preventDefault()}>
                    <EllipsisOutlined/>
                </a>
            </Dropdown>
        );
    };
}

BaseComponent.propTypes = {};

export default BaseComponent;