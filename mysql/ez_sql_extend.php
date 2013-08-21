<?php

  /* Extends ezSQL to allow SQL to be prepared. e.g. $ukcdb->get_var($ukcdb->prepare('SELECT name FROM users WHERE id = %d',$id));
   * get_row and get_results also defaults to ARRAY_A for output instead of OBJECT
   */

class ezSQL_mysql_extended extends ezSQL_mysql {
   	/**
     * Whether to use mysql_real_escape_string
     *
     * @since 2.8.0
     * @access public
     * @var bool
     */
    var $real_escape = false;

    /**
     * Weak escape, using addslashes()
     *
     * @see addslashes()
     * @since 2.8.0
     * @access private
     *
     * @param string $string
     * @return string
     */
    function _weak_escape( $string ) {
      return addslashes( $string );
    }
  
    /**
     * Real escape, using mysql_real_escape_string() or addslashes()
     *
     * @see mysql_real_escape_string()
     * @see addslashes()
     * @since 2.8.0
     * @access private
     *
     * @param  string $string to escape
     * @return string escaped
     */
    function _real_escape( $string ) {
      if ( $this->real_escape )
        return mysql_real_escape_string( $string );
      else
        return addslashes( $string );
    }
  
    /**
     * Escape data. Works on arrays.
     *
     * @uses wpdb::_escape()
     * @uses wpdb::_real_escape()
     * @since  2.8.0
     * @access private
     *
     * @param  string|array $data
     * @return string|array escaped
     */
    function _escape( $data ) {
      if ( is_array( $data ) ) {
        foreach ( (array) $data as $k => $v ) {
          if ( is_array($v) )
            $data[$k] = $this->_escape( $v );
          else
            $data[$k] = $this->_real_escape( $v );
        }
      } else {
        $data = $this->_real_escape( $data );
      }
  
      return $data;
    }
  
    /**
     * Escapes content for insertion into the database using addslashes(), for security.
     *
     * Works on arrays.
     *
     * @since 0.71
     * @param string|array $data to escape
     * @return string|array escaped as query safe string
     */
    function escape( $data ) {
      if ( is_array( $data ) ) {
        foreach ( (array) $data as $k => $v ) {
          if ( is_array( $v ) )
            $data[$k] = $this->escape( $v );
          else
            $data[$k] = $this->_weak_escape( $v );
        }
      } else {
        $data = $this->_weak_escape( $data );
      }
  
      return $data;
    }
  
    /**
     * Escapes content by reference for insertion into the database, for security
     *
     * @uses wpdb::_real_escape()
     * @since 2.3.0
     * @param string $string to escape
     * @return void
     */
    function escape_by_ref( &$string ) {
      $string = $this->_real_escape( $string );
    }
  
    /** 
     * Prepares a SQL query for safe execution. Uses sprintf()-like syntax.
     *
     * The following directives can be used in the query format string:
     *   %d (integer)
     *   %f (float)
     *   %s (string)
     *   %% (literal percentage sign - no argument needed)
     *
     * All of %d, %f, and %s are to be left unquoted in the query string and they need an argument passed for them.
     * Literals (%) as parts of the query must be properly written as %%.
     *
     * This function only supports a small subset of the sprintf syntax; it only supports %d (integer), %f (float), and %s (string).
     * Does not support sign, padding, alignment, width or precision specifiers.
     * Does not support argument numbering/swapping.
     *
     * May be called like {@link http://php.net/sprintf sprintf()} or like {@link http://php.net/vsprintf vsprintf()}.
     *
     * Both %d and %s should be left unquoted in the query string.
     *
     * <code>
     * wpdb::prepare( "SELECT * FROM `table` WHERE `column` = %s AND `field` = %d", 'foo', 1337 )
     * wpdb::prepare( "SELECT DATE_FORMAT(`field`, '%%c') FROM `table` WHERE `column` = %s", 'foo' );
     * </code>
     *
     * @link http://php.net/sprintf Description of syntax.
     * @since 2.3.0
     *
     * @param string $query Query statement with sprintf()-like placeholders
     * @param array|mixed $args The array of variables to substitute into the query's placeholders if being called like
     * 	{@link http://php.net/vsprintf vsprintf()}, or the first variable to substitute into the query's placeholders if
     * 	being called like {@link http://php.net/sprintf sprintf()}.
     * @param mixed $args,... further variables to substitute into the query's placeholders if being called like
     * 	{@link http://php.net/sprintf sprintf()}.
     * @return null|false|string Sanitized query string, null if there is no query, false if there is an error and string
     * 	if there was something to prepare
     */
    function prepare( $query = null ) { // ( $query, *$args )
      if ( is_null( $query ) )
        return;
  
      $args = func_get_args();
      array_shift( $args );
      // If args were passed as an array (as in vsprintf), move them up
      if ( isset( $args[0] ) && is_array($args[0]) )
        $args = $args[0];
      $query = str_replace( "'%s'", '%s', $query ); // in case someone mistakenly already singlequoted it
      $query = str_replace( '"%s"', '%s', $query ); // doublequote unquoting
      $query = preg_replace( '|(?<!%)%s|', "'%s'", $query ); // quote the strings, avoiding escaped strings like %%s
      array_walk( $args, array( &$this, 'escape_by_ref' ) );
      return @vsprintf( $query, $args );
    }
    
    function get_row($query=null,$output=ARRAY_A,$y=0) {
      return parent::get_row($query, $output, $y);
    }
    
    function get_results($query=null, $output = ARRAY_A)
		{
      return parent::get_results($query, $output);
		}
}
