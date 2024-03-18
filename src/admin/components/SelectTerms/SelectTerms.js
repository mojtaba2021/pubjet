import React from 'react';
import PropTypes from 'prop-types';
import styles from './SelectTerms.module.scss';
import {findEndpointUrl, getAxios} from "../../../shared/scripts/utils";
import DebounceSelect from "../DebounceSelect/DebounceSelect";

const axios = getAxios();

const SelectTerms = props => {
    const {selectProps, taxonomy, placeholder, value, onChange, className} = props;

    const fetchPosts = (keyword = '') => {
        return axios.get(findEndpointUrl('find-terms'), {
            params: {
                search  : keyword,
                taxonomy: taxonomy,
            },
        }).then(response => response.payload);
    };

    return (
        <DebounceSelect
            mode={'single'}
            showSearch={true}
            value={value ? value : undefined}
            placeholder={placeholder}
            onChange={onChange}
            className={`${className} ${styles.select}`}
            fetchOptions={fetchPosts}
            {...selectProps}
        />
    );
};

SelectTerms.propTypes = {
    value      : PropTypes.oneOfType([PropTypes.array, PropTypes.string,]),
    onChange   : PropTypes.func,
    selectProps: PropTypes.object,
    placeholder: PropTypes.string,
    className  : PropTypes.string,
    taxonomy   : PropTypes.oneOfType([PropTypes.string, PropTypes.array]),
};

SelectTerms.defaultProps = {
    value      : false,
    placeholder: false,
    className  : '',
    selectProps: {},
    taxonomy   : '',
    onChange   : () => {
    },
};

export default SelectTerms;