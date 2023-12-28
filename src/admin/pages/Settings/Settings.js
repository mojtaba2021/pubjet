import React from 'react';
import styles from './Settings.module.scss';
import {Button, Spin} from 'antd';
import BaseComponent from "../../components/BaseComponent/BaseComponent";
import {SaveOutlined} from "@ant-design/icons";
import {loadOptions, saveOptions} from "./Actions";

import {connect} from "trim-redux";
import Header from "./Header";
import SaveAlert from "./SaveAlert";
import Form from "./Form";
import RefreshIcon from "../../components/RefreshIcon/RefreshIcon";

class Settings extends BaseComponent {

    state = {
        token     : '',
        debug     : false,
        category  : '',
        categories: [],
        error     : false,
        loading   : false,
        saving    : false,
        saved     : false,
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
     * @returns {Element}
     */
    render() {
        const {loading} = this.state;
        return (
            <div className={styles.container}>
                <Header/>
                <RefreshIcon onClick={this.fetch}/>
                <Spin spinning={loading}>
                    <div className={styles.formWrapper}>
                        <Form/>
                        <SaveAlert saved={this.state.saved}/>
                        <Button
                            className={styles.button}
                            type={'primary'}
                            block={true}
                            size={'large'}
                            loading={this.state.saving}
                            onClick={this.save}
                            icon={<SaveOutlined/>}
                        >
                            ذخیره تغییرات
                        </Button>
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