import React, {Component} from 'react';
import PropTypes from 'prop-types';
import styles from './AdminTable.module.scss';
import BaseComponent from '../BaseComponent/BaseComponent';
import {Table} from 'antd';

class AdminTable extends BaseComponent {

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
  };

  /**
   * @since 1.0.0
   */
  columns = () => {
  };

  /**
   * @since 1.0.0
   */
  source = () => {
    return [];
  };

  /**
   * @since 1.0.0
   */
  isLoading = () => {
    return this.state.loading;
  };

  /**
   * @since 1.0.0
   */
  table = () => {
    return <Table
        rowKey={'id'}
        columns={this.columns()}
        dataSource={this.source()}
        pagination={this.getPaginationConfig()}
        loading={this.isLoading()}
        {...this.tableProps()}
    />;
  };

  /**
   * @since 1.0.0
   */
  isExpandable = (record) => {
    return false;
  };

  /**
   * @since 1.0.0
   */
  expandableElem = (record) => {
    return undefined;
  };

  /**
   * @since 1.0.0
   */
  expandableProps = () => {
    return {};
  };

  /**
   * @since 1.0.0
   */
  tableProps = () => {
    return {};
  };

  /**
   * @since 1.0.0
   * @returns {*}
   */
  render() {
    return this.table();
  }
}

export default AdminTable;