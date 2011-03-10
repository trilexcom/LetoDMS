<?php
//    LetoDMS. Document Management System
//    Copyright (C) 2011 Uwe Steinmann
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

/**
 * @uses Zend_Search_Lucene
 */
require_once('Zend/Search/Lucene.php');

/**
 * @uses Zend_Search_Lucene_Analysis_TokenFilter_Stopwords
 */
require_once("Zend/Search/Lucene/Analysis/TokenFilter/StopWords.php");

/**
 * @uses LetoDMS_Lucene_Indexer
 */
require_once('Lucene/Indexer.php');

/**
 * @uses LetoDMS_Lucene_Search
 */
require_once('Lucene/Search.php');

/**
 * @uses LetoDMS_Lucene_IndexedDocument
 */
require_once('Lucene/IndexedDocument.php');

?>
