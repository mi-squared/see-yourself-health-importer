<?php


namespace Mi2\NewLeafImport;


use Mi2\Import\AbstractImportProvider;

class NewLeafImportProvider extends AbstractImportProvider
{
    public function getKey()
    {
        return 'new-leaf-importer';
    }

    public function makeImporter()
    {
        return new NewLeafImporter();
    }
}
