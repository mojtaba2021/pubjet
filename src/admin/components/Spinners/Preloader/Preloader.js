import React from 'react';
import './Preloader.scss';

const Preloader = props => {
  return (
      <div id="preloader-wrapper">
        <div class="preloader">
          <div class="loader" />
        </div>
      </div>
  );
};

Preloader.propTypes = {};

export default Preloader;
