import React from 'react';
import PropTypes from 'prop-types';
import AdminTable from '../AdminTable/AdminTable';
import styles from './TableRoles.module.scss';
import {getAdminAjaxUrl, getAxios, pubjet__} from '../../../shared/scripts/utils';
import {Table} from 'antd';

const axios = getAxios();

class TableRoles extends AdminTable {

  /**
   * @since 1.0.0
   * @type {{roles: [], error: boolean, loading: boolean}}
   */
  state = {
    error  : false,
    roles  : [],
    loading: false,
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
      axios.get(getAdminAjaxUrl(), {
        params: {
          action: 'pubjet-wp-roles',
        },
      }).then(response => {
        if (response.success) {
          this.setState({roles: response.payload});
        } else {
          this.setState({error: true});
        }
      }).catch(error => {
        this.setState({error: true});
      }).finally(() => {
        this.setState({loading: false});
      });
    });
  };

  /**
   * @since 1.0.0
   */
  columns = () => {
    return [
      {
        title   : pubjet__('role-name'),
        ellipsis: true,
        render  : (value, record) => {
          return record.name;
        },
      },
      Table.EXPAND_COLUMN,
      {
        title   : pubjet__('count'),
        ellipsis: true,
        align   : 'center',
        render  : (value, record) => {
          return record.count;
        },
      },
    ];
  };

  /**
   * @since 1.0.0
   */
  source = () => {
    return this.state.roles;
  };

  /**
   * @since 1.0
   * @returns {{onChange: handleTableCheckboxChanged,
   *     selectedRowKeys}}
   */
  getTableRowSelectionConfig = () => {
    const {rowSelectionConfig} = this.props;
    return {
      type                   : 'checkbox',
      preserveSelectedRowKeys: true,
      selectedRowKeys        : this.props.checked,
      onChange               : this.handleTableCheckboxChanged,
      ...rowSelectionConfig,
    };
  };

  /**
   * @since 1.0
   * @param selectedRowKeys
   * @param selectedRowItems
   */
  handleTableCheckboxChanged = (selectedRowKeys, selectedRowItems) => {
    this.props.onChange(selectedRowKeys);
  };

  /**
   * @since 1.0.0
   * @param record
   * @returns {(function())|*}
   */
  handleRowClick = (record) => {
    const {checked} = this.props;
    let newState = {};
    if (checked.includes(record.id)) {
      newState = checked.filter(item => item !== record.id);
    } else {
      newState = [...checked, record.id,];
    }
    this.props.onChange(newState);
  };

  /**
   * @since 1.0.0
   */
  tableProps = () => {
    const {tableProps} = this.props;
    const {loading} = this.state;
    return {
      size        : 'small',
      loading     : loading,
      rowClassName: 'pubjet-cursor-pointer',
      rowSelection: this.getTableRowSelectionConfig(),
      onRow       : (record, rowIndex) => {
        return {
          onClick: (event) => {
            this.handleRowClick(record);
          },
        };
      },
      ...tableProps,
    };
  };

  /**
   * @since 1.0.0
   * @returns {JSX.Element}
   */
  render = () => {
    const {className} = this.props;
    return (
        <div className={`${styles.wrapper} ${className}`}>
          {this.table()}
        </div>
    );
  };
}

TableRoles.propTypes = {
  className         : PropTypes.string,
  tableProps        : PropTypes.object,
  onChange          : PropTypes.func,
  checked           : PropTypes.oneOfType([PropTypes.string, PropTypes.number, PropTypes.array]),
  rowSelectionConfig: PropTypes.oneOfType([PropTypes.object, PropTypes.bool]),
};

TableRoles.defaultProps = {
  className         : '',
  tableProps        : {},
  checked           : [],
  rowSelectionConfig: {},
  onChange          : (selectedRole) => {
  },
};

export default TableRoles;