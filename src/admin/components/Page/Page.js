import React, {Component} from 'react';
import PropTypes from 'prop-types';
import BaseComponent from '../BaseComponent/BaseComponent';

class Page extends BaseComponent {

  state = {
    data: [],
    pagination: {
      page: 1,
      totalPages: 1,
    },
    loading: false,
  };

  /**
   * ComponentDidMount
   *
   * @since 1.0
   */
  componentDidMount() {
    this.fetchData();
  }

  /**
   * Fetch data from server
   *
   * @since 1.0
   * @author Pishook
   */
  fetchData = () => {};

  /**
   * Handle page click
   *
   * @since 1.0
   * @author Pishook
   * @return void
   */
  handlePageChange = (event) => {
    this.setState({
      pagination: {
        page: event.selected + 1
      },
    }, () => {
      this.fetchData();
    });
  };

  /**
   * Render page content
   *
   * @since 1.0
   * @author Pishook
   * @returns {JSX.Element}
   */
  render() {
    return (
        <div></div>
    );
  }
}

Page.propTypes = {};

export default Page;
