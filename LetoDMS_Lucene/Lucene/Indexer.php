<?php
/**
 * Implementation of lucene index
 *
 * @category   DMS
 * @package    LetoDMS_Lucene
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010, Uwe Steinmann
 * @version    Release: @package_version@
 */


/**
 * Class for managing a lucene index.
 *
 * @category   DMS
 * @package    LetoDMS_Lucene
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2011, Uwe Steinmann
 * @version    Release: @package_version@
 */
class LetoDMS_Lucene_Indexer extends Zend_Search_Lucene {
	/**
	 * @var string $indexname name of lucene index
	 * @access protected
	 */
	protected $indexname;

	/**
	 * Create a new index
	 *
	 * @return object instance of LetoDMS_Lucene_Search
	 */
	function __construct() { /* {{{ */
		$this->version = '@package_version@';
		if($this->version[0] == '@')
			$this->version = '3.0.0';
	} /* }}} */


}
?>
