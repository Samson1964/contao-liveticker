<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 */


/**
 * Register the classes
 */
ClassLoader::addClasses(array
(
	// Classes
	'Liveticker'    => 'system/modules/liveticker/classes/Liveticker.php',
));

/**
 * Register the templates
 */
TemplateLoader::addFiles(array
(
	'mod_liveticker_item'       => 'system/modules/liveticker/templates',
	'mod_liveticker_refresh'    => 'system/modules/liveticker/templates',
	'be_liveticker'             => 'system/modules/liveticker/templates',
)); 
