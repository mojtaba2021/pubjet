import React from 'react';
import AdminTable from "../../components/AdminTable/AdminTable";
import {getStoreKey, handleChangePlanCategory} from "./Actions";
import {pubjet__} from "../../../shared/scripts/utils";
import {connect} from 'trim-redux';
import SelectTerms from "../../components/SelectTerms/SelectTerms";

class PricingPlans extends AdminTable {

    state = {
        error  : false,
        loading: false,
    };

    /**
     * @since 1.0.0
     */
    columns = () => {
        return [
            {
                title : pubjet__('title'),
                render: (value, record, index) => {
                    return record.title;
                },
            },
            {
                title : pubjet__('category'),
                align : 'center',
                render: (value, record, rowIndex) => {
                    return <SelectTerms taxonomy={'category'} value={record.category} onChange={selected => {
                        handleChangePlanCategory(record.id, selected);
                    }}/>;
                },
            },
        ];
    };

    /**
     * @since 1.0.0
     */
    source = () => {
        return this.props.data;
    };

}

const mstp = (state) => ({
    data: state[getStoreKey()].pricingPlans,
});

export default connect(mstp)(PricingPlans);