import React from 'react';
import AntModal from '../AntModal/AntModal';
import {ModalSizes} from '../../../shared/scripts/constants';
import styles from './ModalExport.module.scss';
import {Button, Table} from 'antd';
import {v4 as uuid} from 'uuid';
import {translate} from '../../../shared/scripts/utils';
import {FileTextOutlined, FileExcelOutlined} from '@ant-design/icons';

class ModalExport extends AntModal {

  /**
   * @since 1.0.0
   */
  title = () => {
    return translate('export');
  };

  /**
   * @since 1.0.0
   */
  modalProps = () => {
    return {
      footer: null,
      width : ModalSizes.MEDIUM,
    };
  };

  /**
   * @since 1.0.0
   */
  handleExportExcel = () => {
  };

  /**
   * @since 1.0.0
   */
  handleExportCsv = () => {
  };

  /**
   * @since 1.0.0
   */
  columns = () => {
    return [
      {
        title : translate('data'),
        render: (value, record) => {
          return record.label;
        },
      },
    ];
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
  table = () => {
    return <Table
        rowKey={'id'}
        columns={this.columns()}
        dataSource={this.source()}
        rowSelection={this.getTableRowSelectionConfig()}
        pagination={this.getPaginationConfig()}
        size={'small'}
    />;
  };

  /**
   * @since 1.0.0
   */
  buttons = () => {
    const {loading, error} = this.state;
    const types = [
      // {
      //   id     : 'excel',
      //   label  : translate('export-excel'),
      //   onClick: this.handleExportExcel,
      //   icon   : <FileExcelOutlined />,
      // },
      {
        id     : 'csv',
        icon   : <FileTextOutlined/>,
        label  : translate('export-csv'),
        type   : 'primary',
        onClick: this.handleExportCsv,
        loading: loading,
      },
    ];
    return (
        <div className={styles.wrapper}>
          {types.map(item => {
            const {onClick, label, icon, type, loading} = item;
            return <Button
                key={uuid()}
                onClick={onClick}
                type={type ? type : 'default'}
                block={true}
                size={'large'}
                className={styles.button}
                icon={icon}
                loading={loading}
            >
              {label}
            </Button>;
          })}
        </div>
    );
  };

  /**
   * @since 1.0.0
   */
  image = () => {
    return <div className={styles.imageWrapper}>
      <img
          src={`${pubjet_params.images_url}/analysis.png`}
          width={170}
          height={170}
      />
    </div>;
  };

  /**
   * @returns {JSX.Element}
   */
  content = () => {
    return <React.Fragment>
      {this.image()}
      {this.buttons()}
    </React.Fragment>;
  };
}

ModalExport.propTypes = {};

export default ModalExport;