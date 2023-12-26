import React, {useState} from 'react';
import PropTypes from 'prop-types';

const Excerpt = props => {
  const {excerpt, text} = props;
  const [show, setShow] = useState(false);
  return (
      <div className={'cursor-pointer'} onClick={() => {setShow(!show);}}>
        {show ? text : excerpt}
      </div>
  );
};

Excerpt.propTypes = {
  text: PropTypes.string.isRequired,
  excerpt: PropTypes.string.isRequired,
};

export default Excerpt;
