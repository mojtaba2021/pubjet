import React ,{useState}from 'react';
import PropTypes from 'prop-types';
import styles from './SelectTerms.module.scss';
import {findEndpointUrl, getAdminAjaxUrl, getAxios} from "../../../shared/scripts/utils";
import DebounceSelect from "../DebounceSelect/DebounceSelect";

const axios = getAxios();

const SelectTerms = props => {
    const {selectProps, taxonomy, placeholder, value, onChange, className , mode  ,maxCount, suffixIcon,required} = props;
    const [error, setError] = useState(false);
    const fetchPosts = (keyword = '') => {
        return axios.get(getAdminAjaxUrl(), {
            params: {
                action  : 'pubjet-find-terms',
                search  : keyword,
                taxonomy: taxonomy,
            },
        }).then(response => {
            return response.payload.map(option => ({
                ...option,
                disabled: value && Array.isArray(value) && value.length >= maxCount && !value.includes(option.value), // disable extra options
            }));
        });
    };
    const handleChange = (selected) => {
        onChange(selected);
        // if (required && (!selected || selected.length === 0)) {
        //     setError(true);
        // } else {
        //     setError(false);
        // }
    };

    return (
        <DebounceSelect
            mode={mode}
            showSearch={true}
            value={value ? value : undefined}
            placeholder={placeholder}
            onChange={handleChange}
            className={`${className} ${styles.select}`}
            fetchOptions={fetchPosts}
            allowClear={selectProps.allowClear || false}
            suffixIcon={suffixIcon}
            {...selectProps}
            status={ error && 'error'}
        />

    );
};

SelectTerms.propTypes = {
    value      : PropTypes.oneOfType([PropTypes.array, PropTypes.string, PropTypes.number]),
    onChange   : PropTypes.func,
    selectProps: PropTypes.object,
    placeholder: PropTypes.string,
    className  : PropTypes.string,
    taxonomy   : PropTypes.oneOfType([PropTypes.string, PropTypes.array]),
    mode       : PropTypes.string,
    maxCount   : PropTypes.number,
};

SelectTerms.defaultProps = {
    value      : false,
    placeholder: false,
    className  : '',
    selectProps: {},
    taxonomy   : '',
    onChange   : () => {
    },
    mode       : 'single',
    maxCount   : 10,
};

export default SelectTerms;