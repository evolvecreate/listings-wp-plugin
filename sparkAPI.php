<?php

    require_once plugin_dir_path(__FILE__) . 'sparkAPI-config.php';
    require_once plugin_dir_path(__FILE__) . 'curlRequest.php';

    class sparkAPI extends curlRequest {

        protected $apiURL = 'https://sparkapi.com/v1/';
        protected $authType = 'access token'; // can also be 'API auth' and 'open id' (not coded for yet)
        private $authEndpoint = 'session';
        private $authToken;

        public function __construct() {}

        private function getApiKey() {

            return SPARK_API_KEY;
        }

        private function getApiSecret() {

            return SPARK_API_SECRET;
        }

        private function getBearer() {

            return SPARK_API_BEARER;
        }




        /* listing calls */

        public function getListing($id) {

            $data['_expand'] = 'Photos';
            $data['_select'] = implode(',', $this->getDefaultListingSelections());

            $listings = $this->sparkRequest('get', 'listings/' . $id, $data);
            if (count($listings) > 0) {

                $transformed = $this->transformListing($listings[0]);
                $transformed = $this->getPhotosAsListingAttachments($transformed);
                $transformed['photo'] = $transformed['photos'][0];

                return $transformed;

            } else {

                return null;
            }
        }

        public function getListings($options = array()) {

            $defaultOptions = array(
                'limit'             => 9,
                'expand'            => array('Photos'),
                'select'            => $this->getDefaultListingSelections(),
                'coverPhotoOnly'    => true,
            );
            $options = array_merge($defaultOptions, $options);

            $maxLimit = 25;
            if ($options['limit'] > $maxLimit) {
                $options['limit'] = $maxLimit;
            }
            $data['_limit'] = $options['limit'];

            if ($options['page']) {
                $data['_page'] = $options['page'];
            }

            if ($options['pagination']) {
                $data['_pagination'] = 1;
            }


            $data['_expand'] = implode(',', $options['expand']);
            $data['_select'] = implode(',', $options['select']); // TODO: uncomment once we get all fields

            $data['_filter'] = $this->getFiltersFromOptions($options);
            $data['_orderby'] = '-ModificationTimestamp,ListPrice';

            if ($options['pagination']) {
                $response = $this->sparkRequest('get', 'listings', $data, true);
                $listings = $response['data'];
            } else {
                $listings = $this->sparkRequest('get', 'listings', $data);
            }


            if ($listings) {
                foreach ($listings as &$listing) {
                    $listing = $this->transformListing($listing, $options);
                    if (($options['coverPhotoOnly']) && (count($listing['photos']) > 0)) {
                        $listing['photos'] = array($listing['photos'][0]); // reduce to one photo
                    }
                }

                $this->createListingPosts($listings);

                foreach ($listings as &$listing) {
                    $listing = $this->getPhotosAsListingAttachments($listing);
                    $listing['photo'] = $listing['photos'][0];
                }

                if ($options['pagination']) {
                    return array(
                        'listings'      => $listings,
                        'pagination'    => $response['raw']['Pagination']
                    );

                } else {

                    return $listings;
                }

            } else {

                return [];
            }
        }

        public function getListingByPostName($postName) {

            $id = $this->getListingIDByPostName($postName);
            $listing = $this->getListing($id);

            return $listing;
        }

        public function getListingByMlsID($mlsid) {

            $options = array(
                'mlsid' => $mlsid
            );

            $listings = $this->getListings($options);
            if (count($listings) == 1) {
                return $listings[0];
            } else {
                return $listings[0];
            }
        }

        public function getListingByListingID($listingID) {

             $options = array(
                'listing-id'        => $listingID,
                'coverPhotoOnly'    => false
            );

            $listings = $this->getListings($options);
            if (count($listings) == 1) {
                return $listings[0];
            } else {
                return $listings[0];
            }
        }

        public function getListingPostByPostName($postName) {

            if (!$postName) {
                global $post;
                $postName = $post->post_name;
            }

            $args = array(
              'name'        => $postName,
              'post_type'   => 'listing',
              'post_status' => 'publish',
              'numberposts' => 1
            );

            $posts = get_posts($args);
            if (count($posts) == 1) {
                return $posts[0];

            } else {
                return null;
            }
        }

        public function getListingIDByPostName($postName = null) {

            $post = $this->getListingPostByPostName($postName);
            if ($post) {
                $listingID = get_post_meta($post->ID, 'listingID', true);
                return $listingID;
            }
            return null;
        }

        private function getFiltersFromOptions($options) {

            // single listing
            if ($options['listing-id']) {
                $filters = "ListingId Eq '" . $options['listing-id'] . "'";
                return $filters;
            }

            // single listing
            if ($options['mlsid']) {
                $filters = "MlsId Eq '" . $options['mlsid'] . "'";
                return $filters;
            }

            $filters = "MlsStatus Eq 'Active'";
            $filters .= " AND City Ne 'Out Of Area'";

            if ($options['searchterms']) {
                $filters .= " AND tolower(UnparsedAddress) Eq contains('" . strtolower($options['searchterms']) . "')";
            }

            if (($options['bedrooms']) && (!in_array($options['type'], array('Land', 'Commercial', 'MultiFamily')))) {
                $filters .= " AND BedsTotal Ge " . $options['bedrooms'];
            }

            if (($options['baths']) && (!in_array($options['type'], array('Land', 'Commercial', 'MultiFamily')))) {
                $filters .= " AND BathsTotal Ge " . $options['baths'];
            }

            if ($options['city']) {
                $filters .= " AND City Eq '" . $options['city'] . "'";
            }

            if ($options['subdivision']) {
                $filters .= " AND SubdivisionName Eq '" . $options['subdivision'] . "'";
            }

            if (($options['style']) && (!in_array($options['type'], array('Land', 'Commercial', 'MultiFamily')))) {
                $filters .= " AND ArchitecturalStyle Eq '" . $options['style'] . "'";
            }

            if ($options['type']) {
                $filters .= " AND PropertyClass Eq '" . $options['type'] . "'";
            }

            if (($options['priceMin']) || ($options['priceMax'])) {
                if ($options['priceMin']) {
                    $priceMin = preg_replace('/[^0-9]+/', '', $options['priceMin']);
                }

                if ($options['priceMax']) {
                    $priceMax = preg_replace('/[^0-9]+/', '', $options['priceMax']);
                }

                if (($priceMin) && ($priceMax)) {
                    $filters .= " AND ListPrice Bt " . $priceMin . ',' . $priceMax;
                } else if ($options['priceMin']) {
                    $filters .= " AND ListPrice Ge " . $priceMin;
                } else if ($options['priceMin']) {
                    $filters .= " AND ListPrice Le " . $priceMax;
                }
            }


            return $filters;

        }

        private function transformListing($listing, $options = array()) {

            $transformed = array();
            $transformed['id'] = $listing['Id'];
            $transformed['data'] = $listing['StandardFields'];
            $transformed['photos'] = $this->transformPhotos($listing['StandardFields']['Photos']);

            $transformed['photo'] = $transformed['photos'][0];

            $transformed['address'] = $this->generateListingAddress($transformed['data']);
            $transformed['city'] = $this->formatListingCity($transformed['data']['City']);
            $transformed['state'] = $transformed['data']['StateOrProvince'];
            $transformed['postalcode'] = $transformed['data']['PostalCode'];

            if (
                ($this->isValidData($transformed['data']['SubdivisionName'])) &&
                ($transformed['data']['SubdivisionName'] != $transformed['city'])
            ) {
                $transformed['subdivision'] = $this->formatSubdivision($transformed['data']['SubdivisionName']);
            }
            $transformed['bedrooms'] = $this->formatPlural($transformed['data']['BedsTotal'], 'Bedroom');
            $transformed['baths'] = $this->formatPlural($transformed['data']['BathsTotal'], 'Bath');

            if (is_array($transformed['data']['ArchitecturalStyle'])) {
                $keys = array_keys($transformed['data']['ArchitecturalStyle']);
                $styles = array();
                foreach ($keys as $style) {
                    $styles[] = $this->formatArchitecturalStyle($style);
                }
                $transformed['styles'] = $styles;
            }

            $transformed['post_name'] = $this->getPostName($transformed, true); // need $transformed data
            $transformed['link'] =  $this->getListingPostLink($transformed, $options);

            return $transformed;
        }

        private function getPostName($listing, $sanitize = false) {

            $titleOptions = array(
                'includeSubdivisionName'    => true,
                'includeCity'               => true
            );

            $title = $this->generateListingAddress($listing['data'], $titleOptions);

            $title .= ' ' . $listing['beds'];
            $title .= ' ' . $listing['baths'];

            if ($sanitize) {
                return sanitize_title($title);
            } else {
                return $title;
            }
        }

        private function getListingPostLink($listing, $options = array()) {

            $url = get_option('siteurl');
            $url .= '/';

            $id = get_option('listing-page-subdivision-' . $this->convertValueToKey($listing['subdivision']));

            if (!$id) {
                $id = get_option('listing-page-city-' . $this->convertValueToKey($listing['city']));
            }

            if (!$id) {
                $id = get_option('listing-result-page');
            }

            $resultPage = get_post($id);
            $url .= $resultPage->post_name;
            $url .= '/';

            $url .= $listing['post_name'];
            $url .= '/';

            return $url;
        }

        private function generateListingAddress($data, $options = array()) {

            $defaultOptions = array(
                'includeSubdivision' => false,
                'includeCity'        => false,
                'includeState'        => false,
            );

            $options = array_merge($defaultOptions, $options);

            $address = $data['StreetNumber'] . ' ' . $data['StreetName'];
            if ($this->isValidData($data['StreetSuffix'])) {
                $address .= ' ' . $data['StreetSuffix'];
            }

            if (($data['SubdivisionName']) && ($options['includeSubidivision'])) {
                $address .= ' in ' . $data['SubdivisionName'];
            }

            if ($options['includeCity']) {
                if ($options['includeSubidivision']) {
                    $address .= ',';
                }
                $address .= ' ' . $data['City'];
            }

            if ($options['includeState']) {
                if (($options['includeSubidivision']) || ($options['includeCity'])) {
                    $address .= ',';
                }
                $address .= ' ' . $data['StateOrProvince'];
            }

            return $address;
        }

        private function createListingPosts($listings) {

            foreach($listings as $listing) {

                $listingID = $this->getListingIDByPostName($listing['post_name']);
                if (!$listingID) {
                    $this->createListingPost($listing);
                } else {
                    // update photos only
                }
            }
        }

        private function createListingPost($listing) {

            $title = $this->getPostName($listing, false);

            $url = get_option('siteurl');
            $id = get_option('listing-result-page');
            $guid = $url . '?page_id=' . $id . '&rlid=' . $listing['id'];

            $post = array(
                'post_title' => $title,
                'post_content' => '',
                'post_status' => 'publish',
                'post_date' => date('Y-m-d H:i:s'),
                'post_author' => '',
                'post_type' => 'listing',
                'post_category' => array(0),
                'guid'  => $guid
            );

            $postID = wp_insert_post($post);
            add_post_meta($postID, 'listingID', $listing['id']);

        }

        private function getDefaultListingSelections() {

            $selections = array(
                'ListPrice',  'PublicRemarks',
                'StreetNumber', 'StreetName', 'StreetSuffix', 'UnitNumber',
                'City', 'SubdivisionName', 'StateOrProvince', 'PostalCode',
                'BuildingAreaTotal', 'BedsTotal', 'BathsTotal',
                'ModificationTimestamp', 'YearBuilt',
                'Longitude', 'Latitude',
                'PoolYN', 'GarageYN', 'GarageSpaces',
                'ElementarySchool', 'MiddleOrJuniorSchool', 'HighSchool',
                'VirtualTourURLBranded', 'VirtualTourURLUnbranded',
                'ListAgentName'
            );

            if (is_super_admin()) {
                $adminSelections = array(
                    'ListingId',
                    'MlsStatus', 'MlsId', 'ListAgentMlsId'
                );
                $selections = array_merge($selections, $adminSelections);

            }

            return $selections;
        }






        /* cities and subdivisions */

        public function getCities() {

            $meta = $this->getStandardFieldMeta('City');
            $cities = $meta[0]['City']['FieldList'];

            $approvedCities = array(
                'Carpinteria',
                'Montecito',
                'Santa Barbara',
                'Goleta',
                'Santa Ynez',
                'Solvang',
                'Lompoc',
                'Santa Maria',
                'Ventura',
                'Oxnard',
                'Studio City',
                'Ojai'
            );

            $formatted = array();
            foreach ($cities as $city) {
                $name = $this->formatListingCity($city['Value']);
                if (in_array($name, $approvedCities)) {
                    $formatted[$city['Value']] = $name;
                }
            }

            return $formatted;
        }

        public function getSubdivisions() {

            $meta = $this->getStandardFieldMeta('SubdivisionName');
            $subdivisions = $meta[0]['SubdivisionName']['FieldList'];

            $formatted = array();
            foreach ($subdivisions as $subdivision) {
                $name = $this->formatSubdivision($subdivision['Value']);
                $formatted[] = array(
                    'value' => $subdivision['Value'],
                    'name'  => $name,
                    'city'  => $this->getCityFromSubdivision($name)
                );
            }

            return $formatted;
        }

        public function getCityKeys() {

            $keys = array();
            $cities = $this->getCities();
            foreach ($cities as $city) {
                $keys[$city] = 'listing-page-city-' .$this->convertValueToKey($city);
            }

            return $keys;
        }

        public function getSubdivisionKeys() {

            $keys = array();
            $subdivisions = $this->getSubdivisions();
            foreach ($subdivisions as $subdivision) {
                $keys[$subdivision['name']] = 'listing-page-subdivision-' .$this->convertValueToKey($subdivision['name']);
            }

            return $keys;
        }

       /**
         * TODO: take care of duplicates
         *
         * @param $subdivisionName
         * @return string
         */
        private function getCityFromSubdivision($subdivisionName) {

            switch ($subdivisionName) {
                case 'Concha Loma':
                case 'Padaro':
                case 'Sandyland Cove':
                case 'Faria Beach':
                case 'Summerland':
                    $city = 'Carpinteria';
                    break;

                case 'Birnam Wood':
                case 'Ennisbrook':
                case 'Fernald Point':
                case 'Hedgerow':
                case 'Hidden Valley':
                    $city = 'Montecito';
                    break;

                case 'East Beach':
                case 'Eucalyptus Hill':
                case 'Las Canoas/El Cielito':
                case 'Lower Eastside':
                case 'Mission Canyon':
                case 'Riviera/Lower':
                case 'Riviera/Upper':
                case 'San Roque':
                case 'San Roque/Above Foothil':
                case 'Upper Eastside':
                case 'Downtown':
                case 'Bel Air Knolls':
                case 'Campanil/Yankee Farm':
                case 'Hidden Valley':
                case 'Mesa':
                case 'Oak Park':
                case 'Samarkand':
                case 'West Beach':
                case 'Westside':
                case 'Hope Ranch':
                case 'Hope Ranch Annex':
                case 'More Mesa':
                case 'Vieja Gardens':
                case 'Forte Ranch':
                case 'Rancho San Antonio':
                case 'Park Highlands':
                case 'Rancho Del Ciervo':
                case 'University Circle':
                    $city = 'Santa Barbara';
                    break;


                case 'Ellwood/Santa Barbara Shores':
                case 'Storke Ranch':
                case 'The Bluffs':
                case 'University Village':
                case 'Crown Collection':
                case 'University Circle':
                case 'El Encanto Heights':
                case 'Forte Ranch':
                case 'Hollister Ranch':
                case 'Lake Los Carneros':
                case 'Mountain View':
                case 'Paradise Canyon':
                case 'Park Highlands':
                case 'Rancho Del Ciervo':
                case 'Rancho Embarcadero':
                case 'Winchester Canyon':
                case 'Winchester Commons':
                    $city = 'Goleta';
                    break;


                case 'Ballard':
                case 'Equestria':
                case 'Oak Trail Estates':
                case 'Santa Ynez':
                case 'Santa Ynez Township':
                case 'Skyline Park':
                    $city = 'Santa Ynez';
                    break;

                case 'Fredensborg Canyon':
                case 'Hill Haven Private Road':
                case 'Rosenborg Estates':
                case 'Solvang Glen':
                    $city = 'Solvang';
                    break;

                case 'Oak Hills Estates':
                case 'Happy Hill':
                case 'Lakeview Estates':
                case 'Meadow Ridge Estates':
                case 'University Park':
                case 'Vandenberg Village':
                case 'Village Country Club':
                    $city = 'Lompoc';
                    break;

                case 'Government Center':
                    $city = 'Santa Maria';
                    break;

                case 'Faria Beach':
                case 'Ventura':
                    $city = 'Ventura';
                    break;

                default:
                    //$city = 'Santa Barbara';
                    break;

            }

            return strtolower($city);
        }







        /* standard fields and meta data calls */

        public function getArchitectureStyles() {

            $meta = $this->getStandardFieldMeta('ArchitecturalStyle');
            $styles = $meta[0]['ArchitecturalStyle']['FieldList'];

            $formatted = array();
            foreach ($styles as $style) {
                if ($style['Value'] != 'Other') {
                    $formatted[$style['Value']] = $this->formatArchitecturalStyle($style['Value']);
                }
            }

            return $formatted;
        }

        public function getPropertyClasses() {

            $meta = $this->getStandardFieldMeta('PropertyClass');
            $types = $meta[0]['PropertyClass']['FieldList'];

            $formatted = array();
            foreach ($types as $type) {
                if ($type['Value'] != 'Rental') {
                    $formatted[$type['Value']] = $type['Value'];
                }
            }

            return $formatted;
        }

        public function getStandardFieldMeta($field) {

            $data = $this->sparkRequest('get', 'standardfields/' . $field);

            return $data;

        }

        public function getPropertyTypes() {

            $types = $this->sparkRequest('get', 'propertytypes/all');

            return $types;
        }







        /* formatting */

        /**
         *  formats city because sometimes it's returned in all caps
         *
         * @param $city
         * @return string
         */
        private function formatListingCity($city) {
            return ucwords(strtolower($city));
        }

        /**
         * strips "10 - " and "15 or 20 - "
         *
         * @param $subdivision
         * @return mixed
         */
        private function formatSubdivision($subdivision) {

            $subdivision = preg_replace('/^[0-9]+( or [0-9]+)? \- /', '', $subdivision);

            return $subdivision;
        }

        /**
         * gives human readable names
         *
         * @param $style
         * @return string
         */
        private function formatArchitecturalStyle($style) {

            switch($style) {
                case 'Apt. Style':
                    $formatted = 'Apartment Style';
                    break;
                case 'Cal. Cottage':
                case 'Cal Cottage':
                    $formatted = 'California Cottage';
                    break;
                case 'Cox-E':
                    $formatted = 'Cox';
                    break;
                case 'FR Normand':
                    $formatted = 'French Normandy';
                    break;
                case 'Medit':
                    $formatted = 'Mediterranean';
                    break;
                default:
                    $formatted = $style;
                    break;
            }

            return $formatted;
        }

        /**
         * opposite of formatArchitecturalStyle()
         *
         * TODO: could probably be done via an associated array except cal cottage has two
         * might actually be better using regex
         *
         * @param $formatted
         */
        private function translateStyleOption($userProvidedTerm) {

            $userTerm = strtolower($userProvidedTerm);
            switch($userTerm) {
                case 'apt style':
                case 'apt':
                case 'apartment':
                case 'apartment style':
                    $apiTerm = 'Apt. Style';
                    break;
                case 'cal cottage':
                case 'cal. cottage':
                case 'california cottage':
                    $apiTerm = 'Cal Cottage';
                    // $apiTerm = 'Cal. Cottage';
                    break;
                case 'cox-e':
                case 'cox':
                    $apiTerm = 'Cox-E'; // TODO
                    break;
                case 'fr normand':
                case 'french normandy':
                    $apiTerm = 'FR Normand';
                    break;
                case 'medit':
                case 'mediterranean':
                    $apiTerm = 'Medit';
                    break;
                default:
                    $apiTerm = $userProvidedTerm; // should be capitalized
                    break;
            }

            return $apiTerm;
        }

        /**
         * works for shortcode mispellings
         *
         * @param $options
         * @return mixed
         */
        public function translateUserOptions($options) {

            if ($options['style']) {
                $options['style'] = $this->translateStyleOption($options['style']);
            }

            return $options;
        }





        /* photo saving and manipulation */

        private function transformPhotos($photos) {

            $res = get_option('photo-res');
            if (!$res) {
                $res = 1024;
            }

            $key = 'Uri' . $res;
            $thumbKey = 'UriThumb';

            foreach($photos as &$photo) {

                $id = substr($photo[$key], strrpos($photo[$key], '/') + 1);
                $id = sanitize_title($id);

                $photo['id'] = $id;
                $photo['url'] = $photo[$key];
                $photo['thumb']  = $photo[$thumbKey];
            }

            return $photos;
        }

        /**
         * must be called AFTER post is made to get postID
         *
         * @param $listing
         * @return mixed
         */
        private function getPhotosAsListingAttachments($listing) {

            $transformed = array();

            $listingPost = $this->getListingPostByPostName($listing['post_name']);
            $currentAttachments = $this->getAttachmentsByListingPostID($listingPost->id); // // TODO: test


            $firstPhoto = true; // e.g. main photo
            foreach ($listing['photos'] as $photo) {
                if ($currentAttachments[$photo['id']]) {
                    $transformedPhoto = $this->transformPhotoFromAttachment($currentAttachments[$photo['id']]);
                } else {

                    if ($firstPhoto) {
                        $transformedPhoto = $this->saveListingPhotoAsAttachment($listing, $photo);
                    } else {
                        $transformedPhoto = $this->transformUnsavedPhoto($listing, $photo);
                    }
                }
                if ($firstPhoto) { $firstPhoto = false; }

                $transformed[] = $transformedPhoto;
            }

            $listing['photos'] = $transformed;

            return $listing;
        }

        /**
         * TODO: get upload folder instead of this hardcoded
         *
         * @param $attachment
         * @return array
         */
        private function transformPhotoFromAttachment($attachment) {

            $url = get_site_url() . '/wp-content/uploads/';
            $url .= get_post_meta($attachment->ID, '_wp_attached_file', true);

            $transformed = array(
                'url'           => $url,
                'thumb'         => $url,
                'title'         => $attachment->post_title,
                //'size'        => 1024,
                'location'      => 'local'
            );

            return $transformed;
        }

        private function transformUnsavedPhoto($listing, $photo) {

            $listingPost = $this->getListingPostByPostName($listing['post_name']);

            $photoTitle = $this->getPhotoTitle($listing, $photo);

            // get url, title, caption?
            $transformed = array(
                'url'            => $photo['url'], // original url
                'thumb'          => $photo['thumb'], // original thumb
                'title'          => $photoTitle,
                //'size'         => 1024,
                'location'       => 'api',
                'name'           => $photo['Name']
            );

            return $transformed;
        }

        public function saveListingPhotoAsAttachment($listing, $photo) {

            $listingPost = $this->getListingPostByPostName($listing['post_name']);
            $parentID = $listingPost->ID;

            $photoTitle = $this->getPhotoTitle($listing, $photo);

            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');

            $attachmentID = media_sideload_image($photo['url'], $parentID, $photoTitle, 'id');

            $fileURL = get_attached_file($attachmentID);
            $path = pathinfo($fileURL);

            $newFilename = sanitize_file_name(strtolower($photoTitle));
            $newFilePath = $path['dirname'] . '/' . $newFilename . '.' . $path['extension'];

            $listingPhotoFilename = $path['filename'] . '. ' . $path['extension'];


            rename($fileURL, $newFilePath);
            update_attached_file( $attachmentID, $newFilePath);

            $url = $this->transformPhotoPathToUrl($newFilePath);

            $guid = $url;
            $attachment = array(
              'ID'           => $attachmentID,
              'post_name'   => $listingPhotoFilename,
              'post_parent' => $parentID,
              'guid'         => $guid // TODO: guid doesn't update
            );

            wp_update_post($attachment);


            $transformedPhoto = array(
                'url'       => $url,
                'thumb'     => $url, // TODO
                'title'     => $photoTitle,
                //'size'    => 1024,
                'location'  => 'local'

            );

            return $transformedPhoto;

        }

        private function transformPhotoPathToUrl($newFilePath) {

            $relativePath = strstr($newFilePath, '/wp-content');
            $url = get_site_url() . $relativePath;

            return $url;
        }

        /**
         * // TODO: use caption if possible
         * // TODO: check if photo name is already a post
         *
         * @param $listing
         * @param $photo
         * @return string
         */
        private function getPhotoTitle($listing, $photo) {

            return $listing['address'] . '-photo-' . $photo['Name'];
        }

        public function getAttachmentsByListingPostID($listingPostID) {

            if (!$listingPostID) {
                global $post;
                $listingPostID = $post->id;
            }

            $args = array(
              'post_type'   => 'attachment',
              'post_status' => 'inherit',
              'post_parent' => $listingPostID,
              'numberposts' => -1, // get all
            );

            $posts = get_posts($args);

            $attachments = array();
            foreach ($posts as $post) {
                $listingPhotoID = $post->post_name;
                $attachments[$listingPhotoID] = $post;
            }

            return $attachments;
        }






        /* AJAX endpoints */

        /**
         * used in AJAX call to saveListingPhotoAsAttachment
         * needs $_POST['listing'] needs postID, address
         * needs $_POST['photo'] needs url and Name (capitalized)
         */
        public function saveApiPhotoFromPost() {

            $savedPhoto = $this->saveListingPhotoAsAttachment($_POST['listing'], $_POST['photo']);

            echo $savedPhoto['url'];

            die();
        }

        /**
         * used in AJAX call to loadListings
        */
        public function getListingsAsync() {

            $options = $this->translateUserOptions($_POST['options']);
            $options['pagination'] = 1;
            $listings = $this->getListings($options);

            echo json_encode($listings);

            die();
        }







        /* utilities */

        public function formatPlural($count, $object, $plural = 's', $pluralFull = null) {

            if ($count == 1) {
                return $count . ' ' . $object;
            } else {
                if ($pluralFull) {
                    return $count . ' ' . $pluralFull;
                } else {
                    return $count . ' ' . $object . $plural;
                }
            }
        }

        public function isValidData($value, $options = array()) {

            $defaultOptions = array(
                'other'     => true,
                'hidden'    => true,
                'null'      => true,
            );

            $options = array_merge($defaultOptions, $options);



            if (($options['other']) && (strtolower($value) == 'other')) {
                return false;
            }

            if (($options['hidden']) && ($value == $this->maskedDataString())) {
                return false;
            }

            if (($options['null']) && (!$value)) {
                return false;
            }

            return true;
        }

        public function maskedDataString() {
            return '********'; // TODO: should this be a var/property? probably
        }

        private function convertValueToKey($value) {

            return str_replace(' ', '_', strtolower($value));
        }







        /* API and Auth */

        private function sparkRequest($method, $endpoint, $data = array(), $returnFullResponse = false, $options = array())
        {

            if (($this->authType == 'API auth') && (!$this->authToken)) {
                $this->setAuthToken();
            }


            if (
                    ($this->authType == 'access token') ||
                    (($this->authType == 'API auth') && ($this->authToken))
            ) {

                if ($this->authType == 'API auth') {

                    // must calculate signature BEFORE adding AuthToken and ApiSig
                    $apiSignature = $this->getApiSignature($endpoint, $this->authToken, $data);

                    $data['AuthToken'] = $this->authToken;
                    $data['ApiSig'] = $apiSignature;
                }

                // headers
                if ($options['headers']) {
                    $options['headers'] = array_merge($this->getDefaultRequestHeaders(), $options['headers']);
                } else {
                    $options['headers'] = $this->getDefaultRequestHeaders();
                }

                $rawResponse = $this->request($method, $endpoint, $data, $options);
                $response = $this->extractResponse($rawResponse, $returnFullResponse);

                return $response;


            } else {

                return false;
            }

        }

        public function getAuthToken() {

            $endpoint = $this->authEndpoint;
            $data = array(
                'ApiKey'    => $this->getApiKey(),
                'ApiSig'    => $this->getAuthApiSignature()
            );


            $method = 'post'; // spark only accepts post method for auth

            $options['headers'] = $this->getDefaultRequestHeaders();
            $rawResponse = $this->request($method, $endpoint, $data, $options);

            $response = $this->extractResponse($rawResponse, false);

            $authToken = $response[0]['AuthToken'];

            return $authToken;
        }

        private function setAuthToken() {
            $this->authToken = $this->getAuthToken();
        }

        private function getDefaultRequestHeaders() {

            return array(
                'X-SparkApi-User-Agent'   => 'EvolveCreate Listings WP Plugin/1.0',
                'Authorization'           => 'Bearer ' . $this->getBearer()
            );
        }

        /**
         * POST methods will have AuthToken and ApiSig as $_POST values, but GET needs to put these in manually
         *
         * @param $endpoint
         * @param $method
         * @param $data
         * @return string
         */
        protected function createApiUrl($endpoint, $method, $data) {

            $url = $this->apiURL . $endpoint;
            if ($method == 'get') {

               $url .= '?';
               $i = 0;
               foreach ($data as $key => $value) {
                   if ($i > 0) {
                       $url .= '&';
                   }
                   $url .= $key . '=' . str_replace(' ', '%20', $value);
                   $i++;
               }
            }

            return $url;
         }

        private function extractResponse($rawResponse, $returnFullResponse) {

            if ($returnFullResponse) {

                $response = array(
                    'data'      => $rawResponse['data']['D']['Results'],
                    'raw'      => $rawResponse['data']['D']
                );

                return $response;

            } else {
                return $rawResponse['data']['D']['Results'];
            }
        }

        private function getApiSignature($endpoint, $authToken, $data) {

            $md5String = $this->getApiSecret() . 'ApiKey' . $this->getApiKey();
            $md5String .= 'ServicePath/v1/' . $endpoint;
            $md5String .= 'AuthToken' . $authToken;
            $md5String .= $this->translateDataToStringForSignature($data);

            return md5($md5String);
        }

        private function translateDataToStringForSignature($data) {

            $dataString = '';
            ksort($data);
            foreach ($data as $key => $value) {
                $dataString .= $key;
                $dataString .= $value;
            }

            return $dataString;

        }

        private function getAuthApiSignature() {

            $md5String = $this->getApiSecret() . 'ApiKey' . $this->getApiKey();

            return md5($md5String);
        }

    }