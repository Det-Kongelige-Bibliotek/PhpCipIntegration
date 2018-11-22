<?php namespace Rdl\CumulusAPI;
use PHPUnit\Framework\TestCase;

class CumulusRetrieverTest extends TestCase {

    public function testInstantiation() {
        $cumulus = new CumulusRetriever("http://cumulus-core-test-01/CIP/", "cms");
        $this->assertInstanceOf(CumulusRetriever::class, $cumulus);
        unset($cumulus);
    }

    public function testStuff() {
//        $this->markTestSkipped("Not finished with the test");
        $cumulus = new CumulusRetriever("http://cumulus-core-test-01/CIP/", "cms");
        $cumulus->setupValidation();

        $quickSearch = $cumulus->quicksearch("floradanica");

        $this->assertNotNull($quickSearch);
        $this->assertEquals(10, count($quickSearch));
        foreach($quickSearch as $element) {
            $this->assertTrue(array_key_exists('id', $element));
            $this->assertTrue(array_key_exists('Catalog Name', $element));
            $this->assertTrue(array_key_exists('name', $element));
            $this->assertTrue(array_key_exists('thumbnail', $element));
            $this->assertTrue(array_key_exists('image', $element));
        }
//        foreach ($quickSearch['items'] as $element) {
//            var_dump($element);
//            $element['thumbnail'] = "GNU";
//        }
        //var_dump($quickSearch['items']);

//        var_dump($quickSearch);
        print_r($quickSearch);


        unset($cumulus);
        unset($quickSearch);
//        var_dump($cumulus->getMetadata('4295282753'));
    }

    public function testAddImageUrlsToSearchResults() {
        $this->markTestSkipped("Have not yet found out how to access protected methods.");
        $cumulus = new CumulusRetriever("http://cumulus-core-test-01/CIP/", "cms");

//        $cumulus->getMetadata()
//        $cumulus->addImageUrlsToSearchResults
    }

}
