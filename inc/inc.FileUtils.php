<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005  Markus Westphal
//    Copyright (C) 2006-2008 Malcolm Cowe
//
//    This program is free software; you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation; either version 2 of the License, or
//    (at your option) any later version.
//
//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with this program; if not, write to the Free Software
//    Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.

function renameFile($old, $new)
{
	return rename($old, $new);
}

function removeFile($file)
{
	return unlink($file);
}

function copyFile($source, $target)
{
	return copy($source, $target);
}

function moveFile($source, $target)
{
	if (!copyFile($source, $target))
		return false;
	return removeFile($source);
}

function renameDir($old, $new)
{
	return rename($old, $new);
}

function makeDir($path)
{
	if (strncmp($path, DIRECTORY_SEPARATOR, 1) == 0) {
		$mkfolder = DIRECTORY_SEPARATOR;
	}
	else {
		$mkfolder = "";
	}
	$path = preg_split( "/[\\\\\/]/" , $path );
	for(  $i=0 ; isset( $path[$i] ) ; $i++ )
	{
		if(!strlen(trim($path[$i])))continue;
		$mkfolder .= $path[$i];
		if( !is_dir( $mkfolder ) ){
			$res=mkdir( "$mkfolder" ,  0777);
			if (!$res) return false;
		}
		$mkfolder .= DIRECTORY_SEPARATOR;
	}

	return true;
}

function removeDir($path)
{
	$handle = opendir($path);
	while ($entry = readdir($handle) )
	{
		if ($entry == ".." || $entry == ".")
			continue;
		else if (is_dir($path . $entry))
		{
			if (!removeDir($path . $entry . "/"))
				return false;
		}
		else
		{
			if (!unlink($path . $entry))
				return false;
		}
	}
	closedir($handle);
	return rmdir($path);
}

function copyDir($sourcePath, $targetPath)
{
	if (mkdir($targetPath, 0777))
	{
		$handle = opendir($sourcePath);
		while ($entry = readdir($handle) )
		{
			if ($entry == ".." || $entry == ".")
				continue;
			else if (is_dir($sourcePath . $entry))
			{
				if (!copyDir($sourcePath . $entry . "/", $targetPath . $entry . "/"))
					return false;
			}
			else
			{
				if (!copy($sourcePath . $entry, $targetPath . $entry))
					return false;
			}
		}
		closedir($handle);
	}
	else
		return false;
	
	return true;
}

function moveDir($sourcePath, $targetPath)
{
	if (!copyDir($sourcePath, $targetPath))
		return false;
	return removeDir($sourcePath);
}

//To-DO: fehler abfangen
function getSuitableDocumentDir()
{
	GLOBAL $settings;
	
	if (isset($settings->_useLegacyDir) && $settings->_useLegacyDir == false) {
		return _getSuitableDocumentDirNew();
	}
	else {
		return _getSuitableDocumentDirLegacy();
	}
}

function _getSuitableDocumentDirNew() {
	GLOBAL $db, $settings;

	// First, retrieve the current directory id and path.
	$resArr = $db->getResultArray("SELECT * FROM `tblPathList` ORDER BY `id` DESC LIMIT 1");
	if (is_bool($resArr)) {
		return false;
	}
	if (count($resArr)==0) {
		// No directories have been registered.
		$dirID = 0;
		$dirPath="";
		// Add the new parentPath into tblPathList.
		$res = $db->getResult("INSERT INTO `tblPathList` (`id`, `parentPath`) VALUES (NULL, '".$dirPath."')");
		if (!$res) {
			return false;
		}
	}
	else {
		$dirPath = $resArr[0]["parentPath"];
		// Get the current directory id.
		$resArr = $db->getResultArray("SELECT * FROM `tblDirPath` WHERE `tblDirPath`.`dirPath` = '".$dirPath."' ORDER BY `dirID` DESC LIMIT 1");
		if (is_bool($resArr)) {
			return false;
		}
		if (count($resArr)==0) {
			$dirID = 0;
		}
		else {
			$dirID = $resArr[0]["dirID"];
		}
	}

	if ($dirID >= $settings->_maxDirID) {
		//
		// Need to update the directory path.
		//
		if (strlen($dirPath)==0) {
			// Path has never been set before, so set it to the first legitimate
			// value for a subdirectory.
			$dirPath="0";
		}
		else {
			// Path already comprises at least one subdirectory.
			$tmp = split("/", $dirPath);
			$pathCT = count($tmp);
			// First, check the "value" of the leaf directory.
			$leaf = (integer)$tmp[$pathCT-1];
			if ($leaf >=$settings->_maxDirID) {
				// Need to examine the leaf's parent directory.
				if ($pathCT >1) {
					$parent = (integer)$tmp[$pathCT-2];
					if ($parent >= $settings->_maxDirID) {
						// Need to find the first ancestor that is < $settings->_maxDirID.
						// If not found, must append a new leaf directory to the array
						// and reset all the existing elements to 0;
						$found=false;
						$ct=$pathCT;
						while (!$found && --$ct>=0) {
							if ($tmp[$ct] < $settings->_maxDirID) {
								// Found a matching ancstor. Increase the ancestor's value by 1.
								$tmp[$ct]++;
								$ct++;
								$found=true;
								break;
							}
						}
						if (!$found) {
							$ct=0;
							$lastEl = $pathCT;
						}
						else {
							$lastEl = $pathCT-1;
						}
						for ($i=$ct; $i<=$lastEl; $i++) {
							$tmp[$i] = 0;
						}
					}
					else {
						$parent++;
						$tmp[$pathCT-2] = $parent;
						$tmp[$pathCT-1] = 0;
					}
				}
				else {
					$tmp = array(0,0);
				}
			}
			else {
				$leaf++;
				$tmp[$pathCT-1] = $leaf;
			}
			$dirPath=implode("/", $tmp);
		}
	
		// Add the new parentPath into tblPathList.
		$res = $db->getResult("INSERT INTO `tblPathList` (`id`, `parentPath`) VALUES (NULL, '".$dirPath."')");
		if (!$res) {
			return false;
		}
		$res = $db->getResult("DELETE FROM `tblPathList` WHERE `parentPath` != '".$dirPath."'");
	}
	// Now, insert a new row into the database using the newly created dirPath.
	// This will generate a new value for dirID, since that field is auto-
	// incremented. This new value is retrieved using the "get last insert id"
	// functionality from MySQL.
	//
	// If the insert is successful, delete all rows that do not match the one
	// currently inserted.
	//
	// Is it necessary to keep a record of previously used directories?
	//
	$res = $db->getResult("INSERT INTO `tblDirPath` (`dirID`, `dirPath`) VALUES (NULL, '".$dirPath."')");
	if (!$res) {
		return false;
	}
	$dirID = $db->getInsertID();
	$res = $db->getResult("DELETE FROM `tblDirPath` WHERE `dirID` < '".$dirID."' OR `dirPath` != '".$dirPath."'");

	return $settings->_contentOffsetDir."/".(strlen($dirPath) > 0 ? $dirPath."/" : "").$dirID."/";
}

function _getSuitableDocumentDirLegacy()
{
	GLOBAL $db, $settings;
	
	$maxVal = 0;
	
	$handle = opendir($settings->_contentDir);
	while ($entry = readdir($handle))
	{
		if ($entry == ".." || $entry == ".")
			continue;
		else if (is_dir($settings->_contentDir . $entry))
		{
			$num = intval($entry);
			if ($num >= $maxVal)
				$maxVal = $num+1;
		}
	}
	$name = "" . $maxVal . "";
	while (strlen($name) < 5)
		$name = "0" . $name;
	return $name . "/";
}
?>
