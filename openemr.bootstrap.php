<?php
/**
 * Bootstrap custom Patient Privacy module.
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Ken Chapple <ken@mi-squared.com>
 * @copyright Copyright (c) 2020 Ken Chapple <ken@mi-squared.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;


function oe_import_register_new_leaf(RegisterServices $event)
{
    $importerService = new \Mi2\NewLeafImport\NewLeafImporter();
    $event->getManager()->register($importerService);
    return $event;
}

// Listen for the menu update event so we can dynamically add our patient privacy menu item
$eventDispatcher->addListener(\Mi2\Import\Events\RegisterServices::REGISTER, 'oe_import_register_new_leaf');
