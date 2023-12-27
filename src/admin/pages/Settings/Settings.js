import React from 'react';
import styles from './Settings.module.scss';
import {getAxios, getImagesUrl} from "../../../shared/scripts/utils";
import {Alert, Button, Form, Select, Space, Spin, Switch, Tooltip} from 'antd';
import BaseComponent from "../../components/BaseComponent/BaseComponent";
import {EyeOutlined, ReloadOutlined, SaveOutlined} from "@ant-design/icons";
import {changeInput, loadOptions, saveOptions} from "./Actions";

import {connect} from "trim-redux";
import {openGlobalModal} from "../../store/Actions";

const axios = getAxios();

class Settings extends BaseComponent {

    state = {
        token: '',
        debug: false,
        category: '',
        categories: [],
        error: false,
        loading: false,
        saving: false,
        saved: false,
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
        this.setState({error: false, loading: true}, () => {
            loadOptions().catch(err => {
                this.setState({error: true,});
            }).finally(() => {
                this.setState({loading: false,});
            });
        });
    };

    /**
     * @since 1.0.0
     */
    save = () => {
        this.setState({error: false, saving: true,}, () => {
            saveOptions().then(response => {
                this.setState({saved: true,});
            }).catch(error => {
                this.setState({error: true, saved: false,});
            }).finally(() => {
                this.setState({saving: false,});
            });
        });
    };

    /**
     * @since 1.0.0
     */
    header = () => {
        return <div className={styles.header}>
            <div className={styles.logo}>
                <img className={styles.image} src={`${getImagesUrl()}logo.png`}/>
            </div>
        </div>;
    };

    /**
     * @since 1.0.0
     */
    alert = () => {
        const {saved} = this.state;
        if (!saved) {
            return;
        }
        return <Alert
            showIcon={true}
            type={'success'}
            message={'تنظیمات با موفقیت ذخیره شد'}
            className={styles.alert}
        />;
    };

    /**
     * @since 1.0.0
     */
    form = () => {
        const {token, debug, category, categories} = this.props.options;
        return <form>
            <Form className={styles.form} layout={`vertical`} autoComplete="off">
                <Form.Item label={'کلید دسترسی تریبون'}>
                    {this.renderInput({
                        name: 'token',
                        value: token,
                        className: styles.input,
                        onChange: (e) => {
                            changeInput('token', e.target.value);
                        },
                    })}
                </Form.Item>
                <Form.Item label={'دسته بندی پیشفرض انتشار'}>
                    <Select
                        className={`${styles.input} ${styles.select}`}
                        options={categories}
                        value={category}
                        size={'large'}
                        showSearch={true}
                        labelInValue={true}
                        onChange={value => {
                            changeInput('category', value);
                        }}
                    />
                </Form.Item>
                <Form.Item label={'حالت اشکال زدایی'}>
                    <Space>
                        <Switch checked={debug} onChange={(checked) => {
                            changeInput('debug', checked);
                        }} size={'default'}/>
                        {debug && <Tooltip title={'مشاهده لاگ درخواست ها'}>
                            <EyeOutlined
                                className={styles.viewDebug} style={{fontSize: '18px'}}
                                onClick={() => {
                                    openGlobalModal('debug');
                                }}
                            />
                        </Tooltip>}
                    </Space>
                </Form.Item>
            </Form>
        </form>;
    };

    /**
     * @since 1.0.0
     */
    button = () => {
        const {saving, loading} = this.state;
        return <Button
            className={styles.button}
            type={'primary'}
            block={true}
            size={'large'}
            loading={saving}
            onClick={this.save}
            icon={<SaveOutlined />}
        >
            ذخیره تغییرات
        </Button>
    };

    /**
     * @since 1.0.0
     */
    refreshIcon = () => {
        return <Tooltip title={'بارگذاری مجدد'}>
            <ReloadOutlined className={styles.refresh} onClick={this.fetch}/>
        </Tooltip>
    };

    /**
     * @since 1.0.0
     * @returns {Element}
     */
    render() {
        const {loading} = this.state;
        return (
            <div className={styles.container}>
                {this.header()}
                {this.refreshIcon()}
                <Spin spinning={loading}>
                    <div className={styles.formWrapper}>
                        {this.form()}
                        {this.alert()}
                        {this.button()}
                    </div>
                </Spin>
            </div>
        );
    }
}

const mstp = (state) => ({
    options: state.options,
});

export default connect(mstp)(Settings);