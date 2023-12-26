import React from 'react';
import PropTypes from 'prop-types';
import {v4 as uuid} from 'uuid';
import './WPTable.scss';
import TableNav from './TableNav';
import Button from '../Button/Button';
import CircleSpinner from '../Spinners/Circle/CircleSpinner';
import ErrorOccured from '../ErrorOccured/ErrorOccured';
import ReactPaginate from 'react-paginate';

const WPTable = props => {

  const {
    id,
    data,
    cols,
    showTopNav,
    showBottomTav,
    topBulkActions,
    bottomBulkActions,
    loading,
    error,
    onRefreshDataClick,
    pagination,
    onPageChange,
    paginationProps,
  } = props;

  return (
      <React.Fragment>
        {showTopNav && <TableNav actions={topBulkActions}/>}
        <div className={`pubjet-table-responsive`}>
          <table className={`pubjet-table wp-list-table widefat striped table-view-list`}>
            <thead>
            <tr>
              {cols.map(col => {
                return <th
                    id={col.id ? col.id : false}
                    scope="col"
                    className={`${typeof col.onClick === 'function'
                        ? 'cursor-pointer'
                        : ''}`}
                    key={uuid()}
                >
                  {col.title}
                </th>;
              })}
            </tr>
            </thead>
            <tbody id="the-list" data-wp-lists={`list:${id}`}>
            {
              error
              &&
              <tr>
                <td className={'pubjet-text-center'} colSpan={cols.length}>
                  <ErrorOccured onButtonClick={onRefreshDataClick}/>
                </td>
              </tr>
            }
            {loading ?
                <tr>
                  <td className={`pubjet-text-center`} colSpan={cols.length}>
                    <CircleSpinner width={'45px'} height={'45px'}/>
                  </td>
                </tr>
                :
                data.map(item => {
                  return <tr key={uuid()}>
                    {cols.map(col => {
                      return <td className={`${col.id ? col.id : ''} ${col.classes
                          ? col.classes
                          : ''} ${col.id ? `column-${col.id}` : ''}`}
                                 key={uuid()}>
                        {col.render(item)}
                      </td>;
                    })}
                  </tr>;
                })
            }
            </tbody>
          </table>
        </div>
        {showBottomTav && <TableNav actions={bottomBulkActions}/>}
        {(pagination && !loading && pagination.totalPages > 1) && <ReactPaginate
            breakLabel={'...'}
            nextLabel={'>'}
            onPageChange={onPageChange}
            pageRangeDisplayed={0}
            pageCount={pagination.totalPages}
            previousLabel={`<`}
            renderOnZeroPageCount={null}
            pageLinkClassName={'button'}
            nextLinkClassName={'button'}
            previousLinkClassName={'button'}
            containerClassName={`pubjet-pagination-wrapper`}
            disableInitialCallback={true}
            forcePage={pagination.page - 1}
            {...paginationProps}
        />}
      </React.Fragment>
  );

};

WPTable.propTypes = {
  id: PropTypes.string.isRequired,
  cols: PropTypes.array.isRequired,
  data: PropTypes.array.isRequired,
  showTopNav: PropTypes.bool,
  showBottomTav: PropTypes.bool,
  topBulkActions: PropTypes.oneOfType([PropTypes.array, PropTypes.bool]),
  bottomBulkActions: PropTypes.oneOfType([PropTypes.array, PropTypes.bool]),
  pagination: PropTypes.object,
  loading: PropTypes.bool,
  error: PropTypes.bool,
  onRefreshDataClick: PropTypes.func,
  onPageChange: PropTypes.func,
  paginationProps: PropTypes.object,
};

WPTable.defaultProps = {
  loading: false,
  error: false,
  showTopNav: false,
  showBottomNav: false,
  topBulkActions: [],
  bottomBulkActions: [],
  paginationProps: {},
  onRefreshDataClick: () => {},
  onPageChange: (event) => {},
};

export default WPTable;