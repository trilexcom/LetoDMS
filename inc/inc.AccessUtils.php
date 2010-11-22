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

define("M_ANY", -1);		//Used to indicate that a search should return all
                    		// results in the ACL table. See Folder::getAccessList().
define("M_NONE", 1);		//Keine Rechte
define("M_READ", 2);		//Lese-Recht
define("M_READWRITE", 3);	//Schreib-Lese-Recht
define("M_ALL", 4);		//Unbeschränkte Rechte

define ("O_GTEQ", ">=");
define ("O_LTEQ", "<=");
define ("O_EQ", "=");

define("T_FOLDER", 1);		//TargetType = Folder
define("T_DOCUMENT", 2);	//    "      = Document

?>
