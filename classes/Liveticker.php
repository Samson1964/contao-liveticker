<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @package   fh-counter
 * @author    Frank Hoppe
 * @license   GNU/LGPL
 * @copyright Frank Hoppe 2014
 */

/**
 * Class CounterRegister
 *
 * @copyright  Frank Hoppe 2014
 * @author     Frank Hoppe
 *
 * Basisklasse vom FH-Counter
 * Erledigt die Zählung der jeweiligen Contenttypen und schreibt die Zählerwerte in $GLOBALS
 */
class Liveticker extends \Module
{

	/**
	 * Template
	 * @var string
	 */
	protected $masterTemplate = 'mod_liveticker_refresh';
	protected $subTemplate = 'mod_liveticker_item';
	var $onlinezeit = 90; // Anzahl Online-Sekunden, die für Besucher berücksichtigt wird

	/**
	 * Display a wildcard in the back end
	 * @return string
	 */
	public function generate()
	{
		if (TL_MODE == 'BE')
		{
			$objTemplate = new \BackendTemplate('be_liveticker');

			$objTemplate->wildcard = '### LIVETICKER ###';
			$objTemplate->title = $this->title;
			$objTemplate->id = $this->id;

			return $objTemplate->parse();
		}

		return parent::generate(); // Weitermachen mit dem Modul
	}

	/**
	 * Generate the module
	 */
	protected function compile()
	{
		$objDB = \Database::getInstance();
	
		// Liveticker-Stammdaten laden 
		$objTicker = $objDB->prepare("SELECT * FROM tl_liveticker WHERE id=?") 
						   ->execute($this->liveticker); 

		$last = 0; // Letzte ID merken
		$content = '';

		// Liveticker-Einträge laden 
		$objTickerItems = $objDB->prepare("SELECT * FROM tl_liveticker_items WHERE pid=? AND id > ? AND (start = '' OR start < ?) AND (stop = '' OR stop > ?) AND published = 1 ORDER BY id DESC") 
								->execute($this->liveticker, $last, time(), time()); 
		
		$parsedEntries = array(); // Einträge 
		
		while ($objTickerItems->next()) 
		{ 
			// ID merken
			if($last < $objTickerItems->id) $last = $objTickerItems->id;
			
			$objSubTemplate = new \FrontendTemplate($this->subTemplate);
			
			// Autorname laden
			$objAutor = $objDB->prepare("SELECT name FROM tl_user WHERE id=?") 
							  ->execute($objTickerItems->author); 

			$objSubTemplate->addImage = false;
			
			// Add an image
			if ($objTickerItems->addImage && $objTickerItems->singleSRC != '')
			{
				$objModel = \FilesModel::findByUuid($objTickerItems->singleSRC);
        	
				if ($objModel === null)
				{
					if (!\Validator::isUuid($objArticle->singleSRC))
					{
						$objSubTemplate->text = '<p class="error">'.$GLOBALS['TL_LANG']['ERR']['version2format'].'</p>';
					}
				} 
				elseif (is_file(TL_ROOT . '/' . $objModel->path))
				{
					// Bilddaten in Array schreiben
					$arrArticle['imagemargin'] = $objTickerItems->imagemargin;
					$arrArticle['fullsize'] = $objTickerItems->fullsize;
					$arrArticle['floating'] = $objTickerItems->floating;
					$arrArticle['imageUrl'] = $objTickerItems->imageUrl;
					$arrArticle['id'] = $objTickerItems->id;
					$arrArticle['size'] = $objTickerItems->size;
					$arrArticle['alt'] = $objTickerItems->alt;
					$arrArticle['title'] = $objTickerItems->title;
					$arrArticle['caption'] = $objTickerItems->caption;
					$arrArticle['singleSRC'] = $objModel->path;
					$this->addImageToTemplate($objSubTemplate, $arrArticle); 
				}
			} 
					
			/***************************************************
			 * Besucherzähler für diesen Datensatz aktualisieren
			 ***************************************************/
			// Älteste Sperrzeit festlegen
			$sperrzeitende = time() - $this->onlinezeit;
			$iparray = deserialize($objTickerItems->last_iparray);
			// Aktuellen Besucher aktualisieren/eintragen
			$iparray[$_SERVER['REMOTE_ADDR']] = time();
			// Besucher entfernen, deren Sperrzeit abgelaufen ist
			if($iparray)
			{
				foreach($iparray as $key => $value)
				{
					if($value < $sperrzeitende) unset($iparray[$key]);
				}
			}
			$besucher = count($iparray);
			$arrSet = array();
			// Besucheranzahl für diesen Datensatz höher geworden?
			if($besucher > $objTickerItems->max_guests)
			{
				$arrSet['max_guests'] = $besucher;
				$arrSet['max_guests_tstamp'] = time();
				$objTickerItems->max_guests = $arrSet['max_guests'];
				$objTickerItems->max_guests_tstamp = $arrSet['max_guests_tstamp'];
			}

			// Datensatz aktualisieren
			$arrSet['last_iparray'] = serialize($iparray);
			$objDB->prepare("UPDATE tl_liveticker_items %s WHERE id=?")->set($arrSet)->execute($objTickerItems->id);  

			$arrSet = array();
			// Besucheranzahl für diesen Ticker höher geworden?
			if($besucher > $objTicker->max_guests)
			{
				$arrSet['max_guests'] = $besucher;
				$arrSet['max_guests_tstamp'] = time();
				$objTicker->max_guests = $arrSet['max_guests'];
				$objTicker->max_guests_tstamp = $arrSet['max_guests_tstamp'];
				// Ticker aktualisieren
				$objDB->prepare("UPDATE tl_liveticker %s WHERE id=?")->set($arrSet)->execute($this->liveticker);  
			}

			// Eintrag in das Subtemplate übertragen
			$objSubTemplate->id = $objTickerItems->id;
			$objSubTemplate->online = 0 + $besucher;
			$objSubTemplate->max_guests = $objTickerItems->max_guests;
			$objSubTemplate->max_guests_time = $objTickerItems->max_guests_tstamp;
			$objSubTemplate->authorname = $objAutor->name;
			$objSubTemplate->createtime = $objTickerItems->date;
			$objSubTemplate->headline = $objTickerItems->headline;
			$objSubTemplate->text = $objTickerItems->text . $objTickerItems->tag;
			$parsedEntries[] = $objSubTemplate->parse();
			
		} 

		$this->Template = new FrontendTemplate($this->masterTemplate);

		// Restliche Variablen zuweisen
		$this->Template->id = $this->liveticker;
		$this->Template->max_guests = $objTicker->max_guests;
		$this->Template->max_guests_time = $objTicker->max_guests_tstamp;
		$this->Template->active = $this->liveticker_active;
		$this->Template->seconds = $this->liveticker_reload;
		$this->Template->reload = $this->liveticker_reload * 1000;
		$this->Template->last = $last;
		$this->Template->content = $parsedEntries;

	}


}
