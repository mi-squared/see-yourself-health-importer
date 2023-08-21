<?php


namespace Mi2\SeeYourselfImport;


use Mi2\Import\AbstractImportProvider;

class SeeYourselfImportProvider extends AbstractImportProvider
{
    public function getKey()
    {
        return 'new-leaf-importer';
    }

    public function makeImporter()
    {
        return new SeeYourselfImporter();
    }
}
