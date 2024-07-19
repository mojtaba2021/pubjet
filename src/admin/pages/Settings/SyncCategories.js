import React from 'react';
import BaseComponent from "../../components/BaseComponent/BaseComponent";
import {connect} from "trim-redux";
import {findEndpointUrl, getAxios, pubjet__} from "../../../shared/scripts/utils";
import {Alert, Button} from "antd";
import styles from './SyncCategories.module.scss';
import WPCategoriesTree from "../../components/WPCategoriesTree/WPCategoriesTree";
import {SyncOutlined} from "@ant-design/icons";
import SaveAlert from "./SaveAlert";

const axios = getAxios();

class SyncCategories extends BaseComponent {

    state = {
        error     : false,
        loaded    : false,
        syncing   : false,
        categories: [],
        saved     : false,
    };

    /**
     * @since 1.0.0
     */
    componentDidMount() {
        let {categories = []} = this.props.options;
        categories = Array.isArray(categories) ? categories : categories.split(',');
        this.setState({categories,});
    }

    /**
     * @since 1.0.0
     */
    alert = () => {
        return <Alert
            type={'info'}
            banner={false}
            showIcon={false}
            description={pubjet__('select-categories-hints')}
            className={styles.alert}
        />;
    };

    /**
     * @since 1.0.0
     */
    handleSync = () => {
        const {categories} = this.state;
        this.setState({error: false, syncing: true}, () => {
            axios.post(findEndpointUrl('sync-categories'), {
                categories: Array.isArray(categories) ? categories.join(',') : '',
            }).then(response => {
                if (response.success) {
                    this.setState({saved: true,}, () => {
                        setTimeout(() => {
                            this.setState({saved: false});
                        }, 2500);
                    });
                } else {
                    this.setState({error: response.error});
                }
            }).catch(err => {
                this.setState({error: err.message});
            }).finally(() => {
                this.setState({syncing: false});
            });
        });
    };

    /**
     * @since 1.0.0
     * @returns {Element}
     */
    render() {
        const {loaded, syncing, categories, saved} = this.state;
        return (
            <div className={styles.wrapper}>
                {this.alert()}
                <WPCategoriesTree
                    checkedKeys={categories}
                    onLoad={() => {
                        this.setState({loaded: true});
                    }}
                    onCheck={checkedKeys => {
                        this.setState({categories: checkedKeys});
                    }}
                />
                <SaveAlert saved={saved}/>
                {loaded && <Button
                    type={'primary'}
                    size={'large'}
                    onClick={this.handleSync}
                    icon={<SyncOutlined/>}
                    className={styles.button}
                    loading={syncing}
                >
                    {pubjet__('sync-categories')}
                </Button>}
            </div>
        );
    }
}

const mstp = (state) => ({
    options: state.options,
});

export default connect(mstp)(SyncCategories);