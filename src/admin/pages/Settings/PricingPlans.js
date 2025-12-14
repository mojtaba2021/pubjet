import React from 'react';
import AdminTable from "../../components/AdminTable/AdminTable";
import {getStoreKey, handleChangePlanCategory, changeInput} from "./Actions";
import {pubjet__} from "../../../shared/scripts/utils";
import {connect} from 'trim-redux';
import SelectTerms from "../../components/SelectTerms/SelectTerms";
import {DownOutlined} from '@ant-design/icons';
import styles from './PricingPlans.module.scss';

class PricingPlans extends AdminTable {

    state = {
        error: false,
        loading: false,
    };
    MAX_COUNT = 10;

    componentDidMount() {
        const plans = this.props.data || [];
        const updatedPricingPlans = plans.map(plan => {
            if (!plan.categories || plan.categories.length === 0) {
                plan.categories = (plan.relative_categories || []).map(cat => cat.unique_name);
            }
            return plan;
        });

        this.setState({
            pricingPlans: updatedPricingPlans,
        });
    }

    /**
     * @since 1.0.0
     */
    columns = () => {

        return [
            {
                title: pubjet__('title'),
                render: (value, record, index) => {
                    return (
                        <div>
                            <div style={{fontWeight: 'bold'}}>{record.title}</div>
                            <div style={{color: 'gray', fontSize: '12px'}}>{record.archive_position_fa}</div>
                        </div>
                    );
                },
            },
            {
                title: pubjet__('category'),
                align: 'center',
                render: (value, record, rowIndex) => {
                    // Determine selection mode based on archive_position
                    const selectionMode = record.archive_position === 'relative' ? 'multiple' : 'single';

                    let currentCategories;

                    if (record.categories !== undefined && record.categories !== null) {
                        currentCategories = record.categories;
                    } else {
                        currentCategories = (record.relative_categories || []).map(cat => cat.unique_name);
                    }

                    const hasError = !currentCategories || currentCategories.length === 0;

                    return (
                        <div>


                            <SelectTerms
                                mode={selectionMode}
                                placeholder=''
                                allowClear={true}
                                taxonomy='category'
                                maxCount={selectionMode === 'multiple' ? 10 : undefined}
                                maxTagCount={selectionMode === 'multiple' ? 'responsive' : undefined}
                                required={true}
                                onChange={selected => {
                                    handleChangePlanCategory(record.id, selected);
                                }}
                                value={currentCategories}
                                optionRender={selectionMode === 'multiple' ? (option) =>
                                    <span>{option.label}</span> : undefined}
                                suffixIcon={selectionMode === 'multiple' ? this.suffixIcon(currentCategories) : undefined}
                            />
                            {hasError && (
                                <div className={styles.errorText}>
                                    این پلن باید حداقل یک دسته‌بندی داشته باشد
                                </div>
                            )}
                        </div>
                    );
                },
            },
        ];
    };

    suffixIcon = (categories) => (
        <>
            <span>
                {(Array.isArray(categories) ? categories.length : (categories ? 1 : 0))} / {this.MAX_COUNT}
            </span>
            <DownOutlined/>
        </>
    )

    /**
     * @since 1.0.0
     */
    source = () => {
        return this.props.data;
    };

}

const mstp = (state) => ({
    data: state[getStoreKey()].pricingPlans,
    options: state[getStoreKey()], // This gives access to the entire options object including categories
});

export default connect(mstp)(PricingPlans);