import React from 'react';
import styles from './Settings.module.scss';

import {connect} from "trim-redux";
import Header from "./Header";
import General from "./General";
import {Tabs, Tooltip} from "antd";
import Debug from "./Debug";
import {pubjet__} from "../../../shared/scripts/utils";
import Misc from "./Misc";
import RequiredPhpModules from "./RequiredPhpModules";
import ModalMetakeys from "./ModalMetakeys";
// import SyncCategories from "./SyncCategories";
import ModalReportageAuthor from "./ModalReportageAuthor";


const Settings = props => {
    const allHaveRelativeCategories =  props.options?.pricingPlans?.every(plan =>
        Array.isArray(plan?.relative_categories) && plan?.relative_categories?.length > 0
    );
    const hasInvalidToken = !props.options.checkToken?.valid;
    const hasIncompletePlans = !allHaveRelativeCategories;
    const shouldDisableMiscTab = hasInvalidToken || hasIncompletePlans;

    return <div className={styles.container}>
        <Header/>
        <Tabs
            defaultActiveKey="general"
            rootClassName={styles.tabs}
            items={[
                {
                    key: 'general',
                    label: pubjet__('general'),
                    children: <General/>,
                },
                {
                    key: 'debug',
                    label: pubjet__('debug'),
                    children: <Debug/>,
                },
                {
                    key: 'required-php-modules',
                    label: pubjet__('modules'),
                    children: <RequiredPhpModules/>,
                },
                {
                    key: 'misc',
                    label: shouldDisableMiscTab ? (
                        <Tooltip placement="top" title={'لطفا بروزرسانی تنظیمات را انجام دهید'} color='#ff4d4f'>
                            <span>{pubjet__('advanced')}</span>
                        </Tooltip>
                    ) : pubjet__('advanced'),
                    disabled: shouldDisableMiscTab,
                    children: <Misc/>,
                },
            ]}
        />
        <ModalMetakeys/>
        <ModalReportageAuthor/>
    </div>
};

const mstp = (state) => ({
    options: state.options,
});

export default connect(mstp)(Settings);