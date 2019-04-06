<div class="listing single-listing" data-post-name="<?=$listing['post_name']?>" data-address="<?=$listing['address']?>">

        <div class="photos" style="margin-top: 40px;">

            <div id="slideshow_listing-photos">
                <div class="button_previous"><div class="arrow">&#8678;</div></div>
                <div class="slideHolder">
                    <? $i = 0; ?>
                    <? foreach ($listing['photos'] as $photo) { ?>
                        <div class="slide" data-slideshow-index="<?=$i?>">
                            <div class="listing-photo" style="background-image:url('<?=$photo['url']?>');" data-url="<?=$photo['url']?>" data-location="<?=$photo['location']?>" data-photo-name="<?=$photo['name']?>"></div>
                        </div>
                        <? $i++; ?>
                    <? } ?>
                </div>
                <div class="button_next"><div class="arrow">&#8680;</div></div>
            </div>

            <div class="clear"></div>

            <ul class="list_thumbs">
            <? $i = 0; ?>
            <? foreach ($listing['photos'] as $photo) { ?>
                <li class="thumb" data-url-800="<?=$photo['url']?>" data-slideshow-index="<?=$i?>"><img src="<?=$photo['thumb']?>" /></li>
                <? $i++; ?>
            <? } ?>
            </ul>

        </div>


        <div class="location">
            <a href="<?=$listing['link']?>"><h1><?=$listing['address']?></h1></a>
            <h2>
            <? if ($api->isValidData($listing['subdivision'])) { ?>
            <?=$listing['subdivision']?> in
            <? } ?>
            <span class="nowrap"><?=$listing['city']?>, <?=$listing['state']?> <?=$listing['postalcode']?></span>
            </h2>
            <div>$<?=number_format($listing['data']['ListPrice'])?></div>
        </div>


        <div class="layout">
            <? if (
                ($api->isValidData($listing['data']['BedsTotal'])) &&
                ($api->isValidData($listing['data']['BathsTotal']))
            ) { ?>
            <h2><?=$listing['bedrooms']?> / <?=$listing['baths']?></h2>
            <? } ?>
            <? if ($listing['styles']) { ?>
            <h2><?=implode(', ', $listing['styles'])?></h2>
            <? } ?>
            <? if ($api->isValidData($listing['data']['BuildingAreaTotal'])) { ?>
            <div><?=number_format($listing['data']['BuildingAreaTotal'])?> Square Feet</div>
            <? } ?>
        </div>

        <div class="clear"></div>

        <style type="text/css">

            .list_details li.detail_built {
                background-image: url('<?=plugin_dir_url(__FILE__)?>../images/icons/year-built.png');
            }

            .list_details li.detail_styles {
                background-image: url('<?=plugin_dir_url(__FILE__)?>../images/icons/architecture-style.png');
            }

            .list_details li.detail_beds {
                background-image: url('<?=plugin_dir_url(__FILE__)?>../images/icons/number-of-bedrooms.png');
            }

            .list_details li.detail_baths {
                background-image: url('<?=plugin_dir_url(__FILE__)?>../images/icons/number-of-baths.png');
            }

            .list_details li.detail_footage {
                background-image: url('<?=plugin_dir_url(__FILE__)?>../images/icons/square-footage.png');
            }

            .list_details li.detail_elementaryschool,
            .list_details li.detail_middleschool,
            .list_details li.detail_highschool
            {
                background-image: url('<?=plugin_dir_url(__FILE__)?>../images/icons/school-nearby.png');
            }

            .list_details li.detail_pool {
                background-image: url('<?=plugin_dir_url(__FILE__)?>../images/icons/swimming-pool.png');
            }

            .list_details li.detail_garage {
                background-image: url('<?=plugin_dir_url(__FILE__)?>../images/icons/garage.png');
            }



        </style>

        <?


            $details = array();

            //$details['price'] = '$' . number_format($listing['data']['ListPrice']);

            if ($api->isValidData($listing['data']['YearBuilt'])) {
                $details['built'] = 'Built in ' . $listing['data']['YearBuilt'];
            }
            if (is_array($listing['styles'])) {
                $details['styles'] = implode(', ', $listing['styles']);
            }
            if ($api->isValidData($listing['data']['BedsTotal'])) {
                $details['beds'] = $listing['bedrooms'];
            }
            if ($api->isValidData($listing['data']['BathsTotal'])) {
                $details['baths'] = $listing['baths'];
            }
            if ($api->isValidData($listing['data']['BuildingAreaTotal'])) {
                $details['footage'] = number_format($listing['data']['BuildingAreaTotal']) . ' Square Feet';
            }
            if ($listing['data']['PoolYN'] == 'Y') {
                $details['pool'] = 'Swimming Pool';
            }

            if ($listing['data']['GarageYN'] == 'Y') {
                $details['garage'] = '';
                if ($api->isValidData($listing['data']['GarageSpaces'])) {
                    $details['garage'] .= $listing['data']['GarageSpaces'] . '-Car ';
                }
                $details['garage'] .= 'Garage';
            }

            if ($api->isValidData($listing['data']['ElementarySchool'])) {
                $details['elementaryschool'] = $listing['data']['ElementarySchool'] . ' <span class="fieldName">(Elementary)</span>';
            }
            if ($api->isValidData($listing['data']['MiddleOrJuniorSchool'])) {
                $details['middleschool'] = $listing['data']['MiddleOrJuniorSchool'] . ' <span class="fieldName">(Middle School)</span>';
            }
            if ($api->isValidData($listing['data']['HighSchool'])) {
                $details['highschool'] = $listing['data']['HighSchool'] . ' <span class="fieldName">(High School)</span>';
            }


        ?>

        <div class="details">
            <ul class="list_details">
            <? $i = 0; ?>
            <? foreach ($details as $class => $value) { ?>
                <li class="detail_<?=$class?>">
                    <?=$value?>
                </li>
                <? $i++; ?>
                <? if ($i == ceil(count($details)/2)) { ?>
                </ul>
                <ul class="list_details">
                <? } ?>
            <? } ?>

            </ul>
            <div class="clear"></div>
        </div>

        <div class="description">
            <p><?=$listing['data']['PublicRemarks']?></p>
        </div>

        <div class="listing-agent">
            Listing Agent: <?=$listing['data']['ListAgentName']?>
        </div>


        <style type="text/css">

            #googlemap {
                height: 400px;  /* The height is 400 pixels */
                width: 100%;  /* The width is the width of the web page */
               }

        </style>

        <script>

        function initializeGoogleMap() {

          var listing = {lat: <?=$listing['data']['Latitude']?>, lng: <?=$listing['data']['Longitude']?>};
          var map = new google.maps.Map(
              document.getElementById('googlemap'), {zoom: 14, center: listing});

          var marker = new google.maps.Marker({position: listing, map: map});
        }
            </script>
            <!--Load the API from the specified URL
            * The async attribute allows the browser to render the page while the API loads
            * The key parameter will contain your own API key (which is not needed for this tutorial)
            * The callback parameter executes the initMap() function
            -->
            <? //TODO: change key to sammys and add http:// restrictions ?>
            <script async defer
            src="https://maps.googleapis.com/maps/api/js?key=<?=GOOGLE_MAPS_API_KEY?>&callback=initializeGoogleMap">
            </script>

        <div id="googlemap"></div>


</div>