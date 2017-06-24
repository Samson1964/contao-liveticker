<?php 

// Melde alle Fehler außer E_NOTICE
error_reporting(E_ALL & ~E_NOTICE);

/** 
 * Initialize the system 
 */ 
define('TL_MODE', 'FE'); 
define('TL_FILES_URL', ''); 
require('../../../../initialize.php'); 

/** 
 * extends \System ?
 */ 
class LivetickerJob extends \PageRegular 
{ 

	var $onlinezeit = 90; // Anzahl Online-Sekunden, die für Besucher berücksichtigt wird
	
	protected $subTemplate = 'mod_liveticker_item';

	/** 
	 * Initialize the object (do not remove) 
	 */ 
	public function __construct() 
	{ 
		parent::__construct(); 
	} 
	
	/** 
	 * Ausführen 
	 */ 
	public function run($ticker_id, $last_id) 
	{ 

		$objDB = \Database::getInstance();
		//$objCTRL = new \Controller();
	
		// Liveticker-Stammdaten laden 
		$objTicker = $objDB->prepare("SELECT * FROM tl_liveticker WHERE id=?") 
						   ->execute($ticker_id); 

		// Liveticker-Einträge laden 
		$objTickerItems = $objDB->prepare("SELECT * FROM tl_liveticker_items WHERE pid=? AND id > ? AND (start = '' OR start < ?) AND (stop = '' OR stop > ?) AND published = 1 ORDER BY id DESC") 
								->execute($ticker_id, $last_id, time(), time()); 
		
		while ($objTickerItems->next()) 
		{ 
			// Eintrag anzeigen 
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
					\Controller::addImageToTemplate($objSubTemplate, $arrArticle); 
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
				$objDB->prepare("UPDATE tl_liveticker %s WHERE id=?")->set($arrSet)->execute($ticker_id);  
			}

			// Eintrag in das Subtemplate übertragen
			$objSubTemplate->id = $objTickerItems->id;
			$objSubTemplate->online = 0 + $besucher;
			$objSubTemplate->max_guests = $objTickerItems->max_guests;
			$objSubTemplate->max_guests_time = $objTickerItems->max_guests_tstamp;
			$objSubTemplate->authorname = $objAutor->name;
			$objSubTemplate->createtime = $objTickerItems->createtime;
			$objSubTemplate->headline = $objTickerItems->headline;
			$objSubTemplate->text = $objTickerItems->text;
			$objSubTemplate->text = str_replace("[nbsp]","&nbsp;",$objTickerItems->text);
			//if($objTickerItems->tag) $objSubTemplate->text .= $this->replaceInsertTags($objTickerItems->tag);
			if($objTickerItems->tag) $objSubTemplate->text .= $this->getElement($objTickerItems->tag);
			$objSubTemplate->display = 'display:none;';
			echo $objSubTemplate->parse();
		} 
	
	} 

	/**
	 * Generate a content element return it as HTML string
	 * @param integer
	 * @return string
	 */
	protected function getElement($intId)
	{
		if (!strlen($intId) || $intId < 1)
		{
			header('HTTP/1.1 412 Precondition Failed');
			return 'Missing content element ID';
		}

		$objElement = $this->Database->prepare("SELECT * FROM tl_content WHERE id=?")
									 ->limit(1)
									 ->execute($intId);

		if ($objElement->numRows < 1)
		{
			header('HTTP/1.1 404 Not Found');
			return 'Content element not found';
		}

		// Show to guests only
		if ($objElement->guests && FE_USER_LOGGED_IN && !BE_USER_LOGGED_IN && !$objElement->protected)
		{
			header('HTTP/1.1 403 Forbidden');
			return 'Forbidden';
		}

		// Protected element
		if ($objElement->protected && !BE_USER_LOGGED_IN)
		{
			if (!FE_USER_LOGGED_IN)
			{
				header('HTTP/1.1 403 Forbidden');
				return 'Forbidden';
			}

			$this->import('FrontendUser', 'User');
			$groups = deserialize($objElement->groups);

			if (!is_array($groups) || count($groups) < 1 || count(array_intersect($groups, $this->User->groups)) < 1)
			{
				header('HTTP/1.1 403 Forbidden');
				return 'Forbidden';
			}
		}

		$strClass = $this->findContentElement($objElement->type);

		// Return if the class does not exist
		if (!$this->classFileExists($strClass))
		{
			$this->log('Content element class "'.$strClass.'" (content element "'.$objElement->type.'") does not exist', 'Ajax getContentElement()', TL_ERROR);

			header('HTTP/1.1 404 Not Found');
			return 'Content element class does not exist';
		}

		$objElement->typePrefix = 'ce_';
		$objElement = new $strClass($objElement);

		if ($this->Input->get('g') == '1')
		{
			$strBuffer = $objElement->generate();

			// HOOK: add custom logic
			if (isset($GLOBALS['TL_HOOKS']['getContentElement']) && is_array($GLOBALS['TL_HOOKS']['getContentElement']))
			{
				foreach ($GLOBALS['TL_HOOKS']['getContentElement'] as $callback)
				{
					$this->import($callback[0]);
					$strBuffer = $this->$callback[0]->$callback[1]($objElement, $strBuffer);
				}
			}

			return $strBuffer;
		}
		else
		{
			//return $objElement->generateAjax();
			return $objElement->generate();
		}
	}


} 

/** 
 * Instantiate controller 
 */ 
$objLiveticker = new LivetickerJob(); 
$objLiveticker->run(\Input::get('id'), \Input::get('last'));  

?> 
