<?php
namespace Rdl\CumulusAPI;
/**
 * Created by PhpStorm.
 * User: jolf
 * Date: 11-11-18
 * Time: 18:50
 */

use AssertionError;

/**
 * Class CumulusRetriever
 * Handles the search and retrieval of images and metadata from Cumulus.
 */
class CumulusRetriever {

    /** @var The base URL for the Cumulus CIP.
     * Must be given as constructor argument, E.g. http://cumulus-core-test-01/CIP/
     */
    private $baseUrl;
    /** @var The name of the view for the Cumulus CIP.*/
    private $cipView;

    /** The base path to the search. */
    const PATH_METADATA_SEARCH = "metadata/search/";
    /** The base path to retrieving the metadata fields. */
    const PATH_METADATA_FIELDS = "metadata/getfieldvalues/";

    /** The base path for the thumbnails. */
    const PATH_THUMBNAIL = "preview/thumbnail/";
    /** The base path for the images. */
    const PATH_IMAGE = "preview/image/";

    /**
     * CumulusRetriever constructor.
     * @param $cumulusCipUrl The base URL for the Cumulus CIP Rest interface.
     * @param $view The name of the view.
     */
    function __construct($cumulusCipUrl, $view) {
        $this->baseUrl = $cumulusCipUrl;
        $this->cipView = $view;
    }

    /**
     * Validates the setup.
     * Ensures, that the catalogs respond to the right ID intervals.
     */
    public function setupValidation() {
        $this->validateCatalog('webbilleder',0,  4294967296-1);
        $this->validateCatalog('samlingsbilleder', 4294967296,  8589934592-1);
        $this->validateCatalog('billedarkivet', 8589934592,  12884901888-1);
        $this->validateCatalog('online master arkiv', 12884901888,  17179869184-1);
    }

    /**
     * Validates that the given catalog has right IDs.
     * It will throw an exception, if it finds no records, or if the first found ID is either
     * smaller than the lowerId bound, or larger than the upperId bound.
     * @param $catalogName The name of the catalog to validate.
     * @param $lowerId The lower bound of the IDs of the given catalog.
     * @param $upperId The upper bound of the IDs of the given catalog.
     */
    protected function validateCatalog($catalogName, $lowerId, $upperId) {
        $options = ['field' => 'Catalog Name',
            'maxreturned' => 1,
            'querystring' => "Catalog Name\tis\t" . $catalogName];
        $response = $this->search($options);

        try {
            $id = $response["items"][0]["id"];
        } catch (Exception $e) {
            throw new AssertionError("No IDs can be found for the catalog " . $catalogName);
        }

        if($id == null) {
            throw new AssertionError("No results found for catalog " . $catalogName);
        }
        if($id < $lowerId) {
           throw new AssertionError("The id " . $id . " for catalog " . $catalogName .
               " must be at least " . $lowerId);
        }
        if($id > $upperId) {
            throw new AssertionError("The id " . $id . " for catalog " . $catalogName .
                " must be less than " . $upperId);
        }
    }

    /**
     * Method for performing the quicksearch in
     * @param $searchString The string to search for.
     * @param string $sortBy The field to sort the results by. Default 'Asset Creation Date'.
     * @param string $maxReturned The maximum of results returned. Default 10.
     * @param string $startIndex The start index for paging through a large result set. Default 0.
     * @return mixed The results in JSON format.
     */
    public function quicksearch($searchString, $maxReturned = '10', $startIndex = '0', $sortBy = 'Asset Creation Date') {
        $options = ['sortby' => $sortBy,
            'maxreturned' => $maxReturned,
            'startindex' => $startIndex,
            'quicksearchstring' => $searchString,
            'field' => 'Catalog Name'];
        $json = $this->search($options);

        return $this->addImageUrlsToSearchResults($json);
    }

    /**
     * Retrieval of metadata for a
     * @param $id The ID of the record whose metadata should be retrieved.
     * @return mixed|null Will return the response in the JSON format, or null if it fails to retrieve from Cumulus.
     */
    public function getMetadata($id) {
        $ch = curl_init();
        $options = array(CURLOPT_URL => $this->getMetadataUrl($id),
            CURLOPT_POST => false,
            CURLOPT_RETURNTRANSFER => true
        );

        curl_setopt_array($ch, $options);

        $results = curl_exec($ch);
        $headerInfo = curl_getinfo($ch);
        print_r(array($headerInfo));

        if($headerInfo['http_code'] != 200) {
            return null;
        }
        curl_close($ch);

        return json_decode($results, true);
    }

    /**
     *
     * @param $json The JSON
     * @return array|null The new array with the URLs for the images, or null if the $json was badly formatted.
     */
    protected function addImageUrlsToSearchResults($json) {
        if(count($json) < 0 || !array_key_exists('items', $json)) {
            return null;
        }
        $res = array();
        for($i = 0; $i < count($json['items']); $i++) {
            $res[$i] = $json['items'][$i];
            $res[$i]['thumbnail'] = $this->getThumbnailUrl($res[$i]['id']);
            $res[$i]['image'] = $this->getImageUrl($res[$i]['id']);
        }

        return $res;
    }

    /**
     * @param $id The ID of the image, whose thumbnail should be retrieved.
     * @return string The URL for the thumbnail image.
     */
    protected function getThumbnailUrl($id) {
        return $this->baseUrl . CumulusRetriever::PATH_THUMBNAIL . $this->cipView . "/" . $id;
    }

    /**
     * @param $id The ID of the image, whose image should be retrieved.
     * @return string The URL for the image.
     */
    protected function getImageUrl($id) {
        return $this->baseUrl . CumulusRetriever::PATH_IMAGE . $this->cipView . "/" . $id;
    }

    /**
     * @param $id The ID of the image/Cumulus record, whose metadata should be retrieved.
     * @return string The URL for the metadata fields for the image/Cumulus record.
     */
    protected function getMetadataUrl($id) {
        //http://cumulus-core-test-01/CIP/metadata/getfieldvalues/samlingsbilleder/fields/315469
        return $this->baseUrl . CumulusRetriever::PATH_METADATA_FIELDS . $this->cipView . "/fields/" . $id;
    }

    /**
     * Perform the actual search.
     * This should not be used
     * @param $searchOptions
     * @return mixed
     */
    protected function search($searchOptions) {
        $searchUrl = $this->baseUrl . CumulusRetriever::PATH_METADATA_SEARCH . $this->cipView;

        $ch = curl_init();
        $options = array(CURLOPT_URL => $searchUrl,
            CURLOPT_POST => true,
            CURLOPT_HEADER => false,
            CURLOPT_POSTFIELDS => http_build_query($searchOptions),
            CURLOPT_RETURNTRANSFER => true
        );

        curl_setopt_array($ch, $options);

        $results = curl_exec($ch);
        $headerInfo = curl_getinfo($ch);
        // print_r(array($headerInfo));

        if($headerInfo['http_code'] != 200) {
            return null;
        }
        curl_close($ch);

        return json_decode($results, true);
    }
}
