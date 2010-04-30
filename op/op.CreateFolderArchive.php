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

include("../inc/inc.Settings.php");
include("../inc/inc.AccessUtils.php");
include("../inc/inc.ClassAccess.php");
include("../inc/inc.ClassDocument.php");
include("../inc/inc.ClassEmail.php");
include("../inc/inc.ClassFolder.php");
include("../inc/inc.ClassGroup.php");
include("../inc/inc.ClassUser.php");
include("../inc/inc.DBAccess.php");
include("../inc/inc.FileUtils.php");
include("../inc/inc.Language.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.Authentication.php");

// TODO: test if zLib is available

if (!$user->isAdmin()) {
	UI::exitError(getMLText("admin_tools"),getMLText("access_denied"));
}



// Adds file header to the tar file, it is used before adding file content.
// f: file resource (provided by eg. fopen)
// phisfn: path to file
// archfn: path to file in archive, directory names must be followed by '/'
// code by calmarius at nospam dot atw dot hu
function TarAddHeader($f,$phisfn,$archfn)
{
    $info=stat($phisfn);
    $ouid=sprintf("%6s ", decoct($info[4]));
    $ogid=sprintf("%6s ", decoct($info[5]));
    $omode=sprintf("%6s ", decoct(fileperms($phisfn)));
    $omtime=sprintf("%11s", decoct(filemtime($phisfn)));
    if (@is_dir($phisfn))
    {
         $type="5";
         $osize=sprintf("%11s ", decoct(0));
    }
    else
    {
         $type='';
         $osize=sprintf("%11s ", decoct(filesize($phisfn)));
         clearstatcache();
    }
    $dmajor = '';
    $dminor = '';
    $gname = '';
    $linkname = '';
    $magic = '';
    $prefix = '';
    $uname = '';
    $version = '';
    $chunkbeforeCS=pack("a100a8a8a8a12A12",$archfn, $omode, $ouid, $ogid, $osize, $omtime);
    $chunkafterCS=pack("a1a100a6a2a32a32a8a8a155a12", $type, $linkname, $magic, $version, $uname, $gname, $dmajor, $dminor ,$prefix,'');

    $checksum = 0;
    for ($i=0; $i<148; $i++) $checksum+=ord(substr($chunkbeforeCS,$i,1));
    for ($i=148; $i<156; $i++) $checksum+=ord(' ');
    for ($i=156, $j=0; $i<512; $i++, $j++)    $checksum+=ord(substr($chunkafterCS,$j,1));

    fwrite($f,$chunkbeforeCS,148);
    $checksum=sprintf("%6s ",decoct($checksum));
    $bdchecksum=pack("a8", $checksum);
    fwrite($f,$bdchecksum,8);
    fwrite($f,$chunkafterCS,356);
    return true;
}

// Writes file content to the tar file must be called after a TarAddHeader
// f:file resource provided by fopen
// phisfn: path to file
// code by calmarius at nospam dot atw dot hu
function TarWriteContents($f,$phisfn)
{
    if (@is_dir($phisfn))
    {
        return;
    }
    else
    {
        $size=filesize($phisfn);
        $padding=$size % 512 ? 512-$size%512 : 0;
        $f2=fopen($phisfn,"rb");
        while (!feof($f2)) fwrite($f,fread($f2,1024*1024));
        $pstr=sprintf("a%d",$padding);
        fwrite($f,pack($pstr,''));
    }
}

// Adds 1024 byte footer at the end of the tar file
// f: file resource
// code by calmarius at nospam dot atw dot hu
function TarAddFooter($f)
{
    fwrite($f,pack('a1024',''));
}

function createFolderTar($folder,$ark)
{
	global $settings;

	$documents=$folder->getDocuments();
	foreach ($documents as $document){
		
		if (file_exists($settings->_contentDir.$document->getDir())){

			$handle = opendir($settings->_contentDir.$document->getDir());
			while ($entry = readdir($handle) )
			{
				if (!is_dir($settings->_contentDir.$document->getDir().$entry)){
				
					TarAddHeader($ark,$settings->_contentDir.$document->getDir().$entry,$document->getDir().$entry);
					TarWriteContents($ark,$settings->_contentDir.$document->getDir().$entry);
				}

			}
			closedir($handle);
		}	
	}

	$subFolders=$folder->getSubfolders();
	foreach ($subFolders as $folder)
		if (!createFolderTar($folder,$ark))
			return false;
	
	return true;
}


if (!isset($_POST["folderid"]) || !is_numeric($_POST["folderid"]) || intval($_POST["folderid"])<1) {
	UI::exitError(getMLText("admin_tools"),getMLText("invalid_folder_id"));
}
$folderid = $_POST["folderid"];
$folder = getFolder($folderid);

if (!is_object($folder)) {
	UI::exitError(getMLText("admin_tools"),getMLText("invalid_folder_id"));
}

$ark_name = $settings->_contentDir.time()."_".$folderid.".tar";

$ark = fopen($ark_name,"w");

if (!createFolderTar($folder,$ark)) {
	fclose($ark);
	unlink($ark_name);
	UI::exitError(getMLText("admin_tools"),getMLText("error_occured"));
}

TarAddFooter($ark);
fclose($ark);

// compression
$fp = fopen($ark_name, "r");
$data = fread ($fp, filesize($ark_name));
fclose($fp);

$zp = gzopen($ark_name.".gz", "w9");
gzwrite($zp, $data);
gzclose($zp);
unlink($ark_name);

header("Location:../out/out.BackupTools.php");

?>
