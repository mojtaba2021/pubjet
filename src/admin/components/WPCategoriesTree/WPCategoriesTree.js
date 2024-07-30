import React, {useEffect, useState} from 'react';
import PropTypes from 'prop-types';
import {Spin, Tree} from 'antd';
import {findEndpointUrl, getAdminAjaxUrl, getAxios, getSecurityNonce} from "../../../shared/scripts/utils";
import styles from './WPCategoriesTree.module.scss';

const axios = getAxios();

const WPCategoriesTree = (props) => {
    const [loading, setLoading] = useState(false);
    const {onCheck, checkedKeys, onLoad} = props;
    const [categories, setCategories] = useState([]);

    useEffect(() => {
        fetchCategories();
    }, []);

    const fetchCategories = async () => {
        setLoading(true);
        const response = await axios.get(getAdminAjaxUrl(), {
            params: {
                action  : 'pubjet-categories',
                security: getSecurityNonce(),
            },
        });
        setLoading(false);
        onLoad();
        const formattedData = formatCategories(response.payload);
        setCategories(formattedData);
    };

    const formatCategories = (categories) => {
        const categoryMap = {};
        categories.forEach(category => {
            categoryMap[category.id] = {
                key     : category.id,
                title   : category.name,
                children: []
            };
        });
        return Object.values(categoryMap);
    };

    const onSelect = (selectedKeys, info) => {
        const {node} = info;
        const key = node.key;

        // Check if the node is already checked
        const isChecked = checkedKeys.includes(key);

        // Update the checked keys based on whether the node was already checked or not
        const newChecked = isChecked
            ? checkedKeys.filter((item) => item !== key)
            : [...checkedKeys, key];

        onCheck(newChecked);
    };

    return (
        <div className={styles.mainWrapper}>
            <Spin spinning={loading} className={styles.spin}>
                <Tree
                    className={styles.tree}
                    checkable={true}
                    treeData={categories}
                    checkedKeys={checkedKeys.map(item => Number(item))}
                    onSelect={onSelect}
                    onCheck={onCheck}
                />
            </Spin>
        </div>
    );
};

WPCategoriesTree.defaultProps = {
    onCheck    : PropTypes.func,
    onLoad     : PropTypes.func,
    checkedKeys: PropTypes.array,
};

WPCategoriesTree.defaultProps = {
    checkedKeys: [],
    onCheck    : () => {
    },
    onLoad     : () => {
    },
};

export default WPCategoriesTree;