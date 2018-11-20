<?php
use PHPUnit\Framework\TestCase;

use Rdl\CumulusAPI\CumulusRetriever;

class CumulusRetrieverTest extends TestCase {

    public function testStuff() {
        $cumulus = new CumulusRetriever("http://cumulus-core-test-01/CIP/", "cms");
        $cumulus->setupValidation();
        var_dump($cumulus->quicksearch("floradanica"));
        var_dump($cumulus->getMetadata('4295282753'));
    }

}
