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
import ModalReportageAuthor from "./ModalReportageAuthor";
import BacklinkPlans from "./BacklinkPlans";


const Settings = props => {
    // const allHaveRelativeCategories =  props.options?.pricingPlans?.every(plan =>
    //     Array.isArray(plan?.relative_categories) && plan?.relative_categories?.length > 0
    // );
    // const hasIncompletePlans = !allHaveRelativeCategories;
    const pricingPlans = props.options?.pricingPlans || [];
    const allPlansHaveCategories =
        pricingPlans.length > 0 &&
        pricingPlans.every(plan =>
            Array.isArray(plan?.categories) && plan.categories.length > 0
        );

    const hasInvalidToken = !props.options.checkToken?.valid;

    const hasIncompletePlans = !allPlansHaveCategories;
    const shouldDisableMiscTab = hasInvalidToken || hasIncompletePlans;

    console.log(pricingPlans);
    console.log(shouldDisableMiscTab);

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
                    key: 'backlink-plans',
                    label: pubjet__('backlink-plans'),
                    children: <BacklinkPlans/>,
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
                        <Tooltip placement="top" title={pubjet__('misc-disable-tooltip')} color='#ff4d4f'>
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