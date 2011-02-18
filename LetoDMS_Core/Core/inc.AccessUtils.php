<?php
/**
 * Some definitions for access control
 *
 * @category   DMS
 * @package    LetoDMS_Core
 * @license    GPL 2
 * @version    @version@
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Used to indicate that a search should return all
 * results in the ACL table. See {@link LetoDMS_Core_Folder::getAccessList()}
 */
define("M_ANY", -1);

/**
 * No rights at all
 */
define("M_NONE", 1);

/**
 * Read access only
 */
define("M_READ", 2);

/**
 * Read and write access only
 */
define("M_READWRITE", 3);

/**
 * Unrestricted access
 */
define("M_ALL", 4);

define ("O_GTEQ", ">=");
define ("O_LTEQ", "<=");
define ("O_EQ", "=");

define("T_FOLDER", 1);		//TargetType = Folder
define("T_DOCUMENT", 2);	//    "      = Document

?>
