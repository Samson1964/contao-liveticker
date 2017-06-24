<?php
/**
 * Avatar for Contao Open Source CMS
 *
 * Copyright (C) 2013 Kirsten Roschanski
 * Copyright (C) 2013 Tristan Lins <http://bit3.de>
 *
 * @package    Avatar
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 */

/**
 * Add palette to tl_module
 */
$GLOBALS['TL_DCA']['tl_module']['palettes']['liveticker'] = '{title_legend},name,type;{options_legend},liveticker,liveticker_active,liveticker_reload';

$GLOBALS['TL_DCA']['tl_module']['fields']['liveticker'] = array
(
	'label'                => &$GLOBALS['TL_LANG']['tl_module']['liveticker'],
	'exclude'              => true,
	'options_callback'     => array('tl_module_liveticker', 'getLiveticker'),
	'inputType'            => 'select',
	'eval'                 => array
	(
		'mandatory'      => false, 
		'multiple'       => false, 
		'chosen'         => true,
		'submitOnChange' => true,
		'tl_class'       => 'long'
	),
	'sql'                  => "int(10) unsigned NOT NULL default '0'" 
);

$GLOBALS['TL_DCA']['tl_module']['fields']['liveticker_reload'] = array
(
	'label'            => &$GLOBALS['TL_LANG']['tl_module']['liveticker_reload'],
	'default'          => 30,
	'exclude'          => true,
	'inputType'        => 'text',
	'eval'             => array('tl_class'=>'w50', 'rgxp'=>'digit', 'maxlength'=>3),
	'sql'              => "varchar(3) NOT NULL default '30'"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['liveticker_active'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['liveticker_active'],
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'sql'                     => "char(1) NOT NULL default ''"
); 

		
/**
 * Class tl_module_fhcounter
 *
 * Provide miscellaneous methods that are used by the data configuration array.
 * @copyright  Leo Feyer 2005-2014
 * @author     Leo Feyer <https://contao.org>
 * @package    Calendar
 */
class tl_module_liveticker extends Backend
{

	/**
	 * Import the back end user object
	 */
	public function __construct()
	{
		parent::__construct();
		$this->import('BackendUser', 'User');
	}

	public function getLiveticker(DataContainer $dc)
	{
		$array = array();
		$objTicker = $this->Database->prepare("SELECT * FROM tl_liveticker ORDER BY title ASC")->execute();
		while($objTicker->next())
		{
			$array[$objTicker->id] = $objTicker->title;
		}
		return $array;

	}

}
