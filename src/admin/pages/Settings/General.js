import React from 'react';
import BaseComponent from "../../components/BaseComponent/BaseComponent";
import styles from './General.module.scss';
import {connect} from "trim-redux";
import {Button, Spin, Tooltip} from "antd";
import SaveAlert from "./SaveAlert";
import Form from "./Form";
import {SaveOutlined} from "@ant-design/icons";
import {saveOptions} from "./Actions";
import {pubjet__} from "../../../shared/scripts/utils";

class General extends BaseComponent {

    state = {
        token: '',
        debug: false,
        error: false,
        loading: false,
        saving: false,
        saved: false,
    };

    /**
     * Temporarily show the "saved" alert, then hide it after a short delay.
     * @since 1.0.0
     */
    toggleSavedAlert = () => {
        this.setState({saved: true});
        setTimeout(() => {
            this.setState({saved: false});
        }, 4000);
    };

    /**
     * @since 1.0.0
     */
    save = () => {
        this.setState({error: false, saving: true,}, () => {
            saveOptions().then(response => {
                this.toggleSavedAlert();
            }).catch(error => {
                this.setState({error: error, saved: false,});
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
        const {loading, error, saving, saved} = this.state;
        const {
            pricingPlans = [],
            checkToken,
            pricingPlansChanged = false
        } = this.props.options;

        const hasInvalidPlan = pricingPlans.some(
            plan => !plan.categories?.length
        );

        const shouldHighlightButton =
            pricingPlansChanged || hasInvalidPlan;

        const isSaveDisabled = hasInvalidPlan;


        const saveButton = (
            <Button
                className={`${styles.button} ${
                    shouldHighlightButton ? styles.changed : ''
                }`}
                type="primary"
                block
                size="large"
                loading={saving}
                disabled={isSaveDisabled}
                onClick={this.save}
                icon={<SaveOutlined />}
                shape="square"
            >
                {pubjet__('update-settings')}
            </Button>
        );

        return (
            <div className={styles.container}>
                <Spin spinning={loading}>
                    <div className={styles.formWrapper}>
                        <Form/>
                        {!error && <SaveAlert saved={saved}/>}
                        {(checkToken?.valid && pricingPlans.length > 0) && (
                            isSaveDisabled ? (
                                <Tooltip title="برای ذخیره تنظیمات، ابتدا دسته‌بندی پلن را مشخص کنید">
                                    <span>{saveButton}</span>
                                </Tooltip>
                            ) : (
                                saveButton
                            )
                        )}
                    </div>
                </Spin>
            </div>
        );
    }
}

const mstp = (state) => ({
    options: state.options,
});

export default connect(mstp)(General);