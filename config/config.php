<?php if (!defined('TL_ROOT')) die('You cannot access this file directly!');

/**
 * Contao Open Source CMS
 *
 * Copyright (C) 2005-2013 Leo Feyer
 *
 * @package   bdf
 * @author    Frank Hoppe
 * @license   GNU/LGPL
 * @copyright Frank Hoppe 2014
 */

/**
 * Backend-Bereich DSB anlegen, wenn noch nicht vorhanden
 */
$GLOBALS['BE_MOD']['content']['liveticker'] = array
(
	'tables'         => array('tl_liveticker', 'tl_liveticker_items'),
	'icon'           => 'system/modules/liveticker/assets/images/icon.png',
);

$GLOBALS['FE_MOD']['liveticker'] = array
(
	'liveticker' => 'Liveticker',
);  


?>