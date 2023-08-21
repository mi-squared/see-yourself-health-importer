<?php
/**
 * Bootstrap custom New Leaf Importer module.
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Ken Chapple <ken@mi-squared.com>
 * @copyright Copyright (c) 2021 Ken Chapple <ken@mi-squared.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

// Initialize the import provider
$eventDispatcher = $GLOBALS['kernel']->getEventDispatcher();
(new \Mi2\SeeYourselfHealthImport\SeeYourselfHealthImportProvider($eventDispatcher));
