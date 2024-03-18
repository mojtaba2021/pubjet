import React from 'react';
import styles from './RequiredPhpModules.module.scss';
import BaseComponent from '../../components/BaseComponent/BaseComponent';
import {findEndpointUrl, getAxios, getSecurityNonce, pubjet__} from '../../../shared/scripts/utils';
import {Alert, Badge, Table, Tooltip} from 'antd';
import {LoadingOutlined, ReloadOutlined} from '@ant-design/icons';
import Button from "../../components/Button/Button";

const axios = getAxios();

class RequiredPhpModules extends BaseComponent {

    state = {
        payload: {},
        error  : false,
        loading: false,
    };

    /**
     * @since 1.0.0
     */
    componentDidMount() {
        this.handleCheck();
    }

    /**
     * @since 1.0.0
     */
    handleCheck = () => {
        this.setState({error: false, loading: true}, () => {
            axios.get(findEndpointUrl('check-required-php-modules'), {
                params: {
                    security: getSecurityNonce(),
                },
            }).then(response => {
                if (response.success) {
                    this.setState({payload: response.payload});
                } else {
                    this.setState({error: response.error});
                }
            }).catch(error => {
                this.setState({error});
            }).finally(() => {
                this.setState({loading: false});
            });
        });
    };

    /**
     * @since 1.0.0
     */
    alert = () => {
        return <Alert
            type={'info'}
            banner={true}
            showIcon={false}
            description={pubjet__('required-modules-help')}
            className={styles.alert}
        />;
    };

    /**
     * @since 1.0.0
     */
    source = () => {
        const {payload} = this.state;
        return [
            {id: 'curl', label: pubjet__('curl-module'), status: payload['curl']},
            {id: 'openssl', label: pubjet__('openssl-module'), status: payload['openssl'], help: 'openssl_encrypt'},
            {id: 'openssl', label: 'php_soap', status: payload['php_soap']},
        ];
    };

    /**
     * @since 1.0.0
     */
    columns = () => {
        const {loading} = this.state;
        return [
            {
                title : pubjet__('name'),
                render: (value, record) => {
                    return <Tooltip title={record.help}>
            <span className={`${loading ? '' : (record.status ? styles.enable : styles.disable)}`}>
            {record.label}
          </span>
                    </Tooltip>;
                },
            },
            Table.EXPAND_COLUMN,
            {
                title   : pubjet__('status'),
                ellipsis: true,
                align   : 'center',
                render  : (value, record) => {
                    if (loading) {
                        return <LoadingOutlined spin={true}/>;
                    }
                    return <Tooltip title={record.status ? pubjet__('active') : pubjet__('deactive')}>
                        <Badge color={record.status ? 'green' : 'red'}/>
                    </Tooltip>;
                },
            },
        ];
    };

    /**
     * @since 1.0.0
     */
    table = () => {
        return <Table
            rowKey={'id'}
            columns={this.columns()}
            dataSource={this.source()}
            pagination={{hideOnSinglePage: true}}
            className={styles.table}
            showHeader={false}
        />;
    };

    /**
     * @since 1.0.0
     */
    button = () => {
        const {loading} = this.state;
        return <Button
            className={styles.button}
            loading={loading}
            onClick={this.handleCheck}
            icon={<ReloadOutlined/>}
            type={'default'}
        >
            {pubjet__('check-now')}
        </Button>
    };

    /**
     * @since 1.0.0
     * @returns {JSX.Element}
     */
    render() {
        const {loading} = this.state;
        return (
            <div className={styles.wrapper}>
                {this.alert()}
                {this.table()}
                {this.button()}
            </div>
        );
    }
}

export default RequiredPhpModules;