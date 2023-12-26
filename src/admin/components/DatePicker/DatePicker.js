import React from 'react';
import PropTypes from 'prop-types';
import ReactDatePicker from "react-multi-date-picker"
import perCalendar from "react-date-object/calendars/persian";
import greCalendar from "react-date-object/calendars/gregorian";
import persian_fa from "react-date-object/locales/persian_fa"

const DatePicker = props => {
  const {label, value, onChange, wrapperClassName ,wrapperProps, datePickerProps, labelProps} = props;
  const isIran = pubjet_params.locale === 'fa_IR';
  return (
      <div className={`pubjet-d-flex pubjet-flex-column ${wrapperClassName}`} {...wrapperProps}>
          {label && <label className={'pubjet-mb-2'} {...labelProps}>{label}</label>}
          <ReactDatePicker
              value={value}
              onChange={onChange}
              onOpenPickNewDate={false}
              style={{
                width: '100%',
                marginTop: '5px',
              }}
              calendar={isIran ? perCalendar : greCalendar}
              locale={isIran ? persian_fa : false}
              {...datePickerProps}
          />
      </div>
  );
};

DatePicker.propTypes = {
  label: PropTypes.string,
  value: PropTypes.oneOfType([PropTypes.string, PropTypes.object,]),
  onChange: PropTypes.func,
  wrapperClassName: PropTypes.string,
  wrapperProps: PropTypes.object,
  datePickerProps: PropTypes.object,
  labelProps: PropTypes.object,
};

DatePicker.defualtProps = {
  value: '',
  onChange: () => {},
  wrapperClassName: '',
  wrapperProps: {},
  datePickerProps: {},
  labelProps: {},
};

export default DatePicker;