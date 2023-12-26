import React from 'react';
import styles from './Settings.module.scss';
import {getAdminAjaxUrl, getAxios, getImagesUrl} from "../../../shared/scripts/utils";
import {Alert, Button, Form, Select, Spin, Switch} from 'antd';
import BaseComponent from "../../components/BaseComponent/BaseComponent";

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
            axios.get(getAdminAjaxUrl(), {
                params: {
                    action: 'pubjet-get-options',
                    security: pubjet_params.nonce,
                },
            }).then(response => {
                if (response.success) {
                    const {token, debug, category, categories} = response.payload;
                    this.setState({
                        token,
                        debug,
                        category,
                        categories,
                    }, () => {
                        if (this.state.category) {
                            const found = this.state.categories.find(item => item.value == this.state.category);
                            if (found) {
                                this.setState({category: found});
                            }
                        }
                    });
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
    save = () => {
        this.setState({error: false, saving: true,}, () => {
            axios.post(getAdminAjaxUrl(), {
                action: 'pubjet-save-options',
                token: this.state.token,
                debug: this.state.debug,
                category: this.state.category ? this.state.category.value : '',
                security: pubjet_params.nonce,
            }).then(response => {
                if (response.success) {
                    this.setState({saved: true,});
                } else {
                    this.setState({error: true,});
                }
            }).catch(err => {
                this.setState({error: true,});
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
        return <form>
            <Form className={styles.form} layout={`vertical`} autoComplete="off">
                <Form.Item label={'کلید دسترسی تریبون'}>
                    {this.renderInput({name: 'token', className: styles.input})}
                </Form.Item>
                <Form.Item label={'دسته بندی پیشفرض انتشار'}>
                    <Select
                        className={styles.input}
                        options={this.state.categories}
                        value={this.state.category}
                        size={'large'}
                        showSearch={true}
                        labelInValue={true}
                        onChange={value => {
                            this.setState({
                                category: value,
                            });
                        }}
                    />
                </Form.Item>
                <Form.Item label={'حالت اشکال زدایی'}>
                    <Switch checked={this.state.debug} onChange={(checked) => {
                        this.setState({
                            debug: checked,
                        });
                    }} size={'default'}/>
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
        >
            ذخیره تغییرات
        </Button>
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

export default Settings;