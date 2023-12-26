import React from 'react';
import PropTypes from 'prop-types';
import {Alert, Button} from 'antd';
import {getPluginsUrl} from '../../../shared/scripts/utils';

const UpdateAvailable = props => {
  const {version, className, alertProps} = props;
  return <React.Fragment>
    <Alert
        type={'warning'}
        showIcon={true}
        banner={false}
        message={'بروزرسانی جدید :: نسخه ' + version}
        description={<div>
          <p>{`نسخه ی ${version} از افزونه پیامک چاپار منتشر شده است.`}</p>
          <a href={getPluginsUrl()}>
            <Button type={'default'} block={true} size={'large'}>
              مشاهده و بروزرسانی
            </Button>
          </a>
        </div>}
        className={className}
        {...alertProps}
    />
  </React.Fragment>;
};

UpdateAvailable.propTypes = {
  className : PropTypes.string,
  version   : PropTypes.string.isRequired,
  alertProps: PropTypes.object,
  showButton: PropTypes.bool,
};

UpdateAvailable.defaultProps = {
  className : '',
  alertProps: {},
};

export default UpdateAvailable;
