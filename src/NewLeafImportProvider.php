<?php


namespace Mi2\SeeYourselfHealthImport;


use Mi2\Import\AbstractImportProvider;

class SeeYourselfHealthImportProvider extends AbstractImportProvider
{
    public function getKey()
    {
        return 'new-leaf-importer';
    }

    public function makeImporter()
    {
        return new SeeYourselfHealthImporter();
    }
}
