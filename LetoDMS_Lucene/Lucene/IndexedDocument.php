<?php
/**
 * Implementation of an indexed document
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
 * Class for managing an indexed document.
 *
 * @category   DMS
 * @package    LetoDMS_Lucene
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2011, Uwe Steinmann
 * @version    Release: @package_version@
 */
class LetoDMS_Lucene_IndexedDocument extends Zend_Search_Lucene_Document {
	/**
	 * Constructor. Creates our indexable document and adds all
	 * necessary fields to it using the passed in document
	 */
	public function __construct($dms, $document) {
		$version = $document->getLatestContent();
		$this->addField(Zend_Search_Lucene_Field::Keyword('document_id', $document->getID()));
		$this->addField(Zend_Search_Lucene_Field::Keyword('mimetype', $version->getMimeType()));
		$this->addField(Zend_Search_Lucene_Field::UnIndexed('created', $version->getDate()));
		$this->addField(Zend_Search_Lucene_Field::Text('title', $document->getName()));
		if($categories = $document->getCategories()) {
			$names = array();
			foreach($categories as $cat) {
				$names[] = $cat->getName();
			}
			$this->addField(Zend_Search_Lucene_Field::Text('category', implode(' ', $names)));
		}
		$owner = $document->getOwner();
		$this->addField(Zend_Search_Lucene_Field::Text('owner', $owner->getLogin()));
		if($keywords = $document->getKeywords()) {
			$this->addField(Zend_Search_Lucene_Field::Text('keywords', $keywords));
		}
		if($comment = $document->getComment()) {
			$this->addField(Zend_Search_Lucene_Field::Text('comment', $comment));
		}
		$path = $dms->contentDir . $version->getPath();
		$content = '';
		$fp = null;
		switch($version->getMimeType()) {
			case "application/pdf":
				$fp = popen('pdftotext -nopgbrk '.$path.' - |sed -e \'s/ [a-zA-Z0-9.]\{1\} / /g\' -e \'s/[0-9.]//g\'', 'r');
				break;
			case "application/msword":
				$fp = popen('catdoc '.$path, 'r');
				break;
			case "application/vnd.ms-excel":
				$fp = popen('ssconvert -T Gnumeric_stf:stf_csv -S '.$path.' fd://1', 'r');
				break;
			case "audio/mpeg":
				if(function_exists('id3_get_tag')) {
					echo "lasjfdl";
				}
				break;
			case "text/plain":
				$fp = popen('cat '.$path, 'r');
				break;
		}
		if($fp) {
			$content = '';
			while(!feof($fp)) {
				$content .= fread($fp, 2048);
			}
			pclose($fp);
		}
		if($content) {
			$this->addField(Zend_Search_Lucene_Field::UnStored('content', $content, 'utf-8'));
		}
	}
}
?>
