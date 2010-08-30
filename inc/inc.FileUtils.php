<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005  Markus Westphal
//    Copyright (C) 2006-2008 Malcolm Cowe
//    Copyright (C) 2010 Matteo Lucarelli
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
	return @rename($old, $new);
}

function removeFile($file)
{
	return @unlink($file);
}

function copyFile($source, $target)
{
	return @copy($source, $target);
}

function moveFile($source, $target)
{
	if (!@copyFile($source, $target))
		return false;
	return @removeFile($source);
}

function renameDir($old, $new)
{
	return @rename($old, $new);
}

function makeDir($path)
{
	/*
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
			$res=@mkdir( "$mkfolder" ,  0777);
			if (!$res) return false;
		}
		$mkfolder .= DIRECTORY_SEPARATOR;
	}

	return true;*/
	
	// patch from alekseynfor safe_mod or open_basedir 
	
	global $settings;
	$path = substr_replace ($path, "/", 0, strlen($settings->_contentDir));

	$mkfolder = $settings->_contentDir;
    
	$path = preg_split( "/[\\\\\/]/" , $path );
        
	for(  $i=0 ; isset( $path[$i] ) ; $i++ )
	{
		if(!strlen(trim($path[$i])))continue;
		$mkfolder .= $path[$i];
        
		if( !is_dir( $mkfolder ) ){
			$res= @mkdir( "$mkfolder" ,  0777);
			if (!$res) return false;    
		}
		$mkfolder .= DIRECTORY_SEPARATOR;
	}
    
	return true;
	
}

function removeDir($path)
{
	$handle = @opendir($path);
	while ($entry = @readdir($handle) )
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
			if (!@unlink($path . $entry))
				return false;
		}
	}
	@closedir($handle);
	return @rmdir($path);
}

function copyDir($sourcePath, $targetPath)
{
	if (mkdir($targetPath, 0777))
	{
		$handle = @opendir($sourcePath);
		while ($entry = @readdir($handle) )
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
				if (!@copy($sourcePath . $entry, $targetPath . $entry))
					return false;
			}
		}
		@closedir($handle);
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

// code by Kioob (php.net manual)
function gzcompressfile($source,$level=false)
{
	$dest=$source.'.gz';
	$mode='wb'.$level;
	$error=false;
	if($fp_out=@gzopen($dest,$mode)){
		if($fp_in=@fopen($source,'rb')){
			while(!feof($fp_in))
				@gzwrite($fp_out,fread($fp_in,1024*512));
			@fclose($fp_in);
		}
		else $error=true;
		@gzclose($fp_out);
	}
	else $error=true;
	
	if($error) return false;
	else return $dest;
} 

?>
