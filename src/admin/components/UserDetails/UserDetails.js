import React from 'react';
import {translate} from '../../../shared/scripts/utils';
import Mobile from '../Mobile/Mobile';
import DateTimeSpan from '../DateTimeSpan/DateTimeSpan';
import styles from './UserDetails.module.scss';
import ListItems from '../ListItems/ListItems';
import Online from '../Online/Online';

const UserDetails = props => {
  const items = [
    {value: props.id, label: translate('user-id')},
    {value: props.user_login, label: translate('user-login')},
    {value: props.display_name, label: translate('display-name')},
    {value: <Mobile>{props.mobile}</Mobile>, label: translate('mobile')},
    {value: <DateTimeSpan
          date={props.user_registered.date}
          time={props.user_registered.time}
      />, label: translate('register-date'),},
    {value: <Online isOnline={props.is_online} />, label: translate('status'),},
  ];
  return (
      <div className={styles.wrapper}>
        <ListItems items={items}/>
      </div>
  );
};

UserDetails.propTypes = {};

export default UserDetails;