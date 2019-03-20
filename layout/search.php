<?
    $listingsListID = rand(1000,9999);
?>



<form class="form_listing-options" action="<?=esc_url($_SERVER['REQUEST_URI'])?>" method="post" data-listings-list="<?=$listingsListID?>">

    <input type="hidden" class="hidden_listings-page" value="1" data-listings-list="<?=$listingsListID?>" />

    <? if (!$spark->attributes['hide-search']) { ?>
        <div>
            <input type="text" class="field_search" name="options[searchterms]" value="<?=$options['searchterms']?>" placeholder="Search by Address" />
        </div>
    <? } ?>

    <? if (!$spark->attributes['hide-filters']) { ?>
        <div class="float">

            <div class="float">
                <input type="text" class="field_priceMin" name="options[priceMin]" value="<?=$options['priceMin']?>" placeholder="Minimum Price" />
            </div>
            <div class="float" style="margin: 10px 10px;font-size: 14px;"> to </div>
            <div class="float" style="margin-right: 20px;">
                <input type="text" class="field_priceMax" name="options[priceMax]" value="<?=$options['priceMax']?>" placeholder="Max Price" />
            </div>
            <div class="clear"></div>

        </div>

        <div class="float" style="margin-right: 20px;">
            <!-- <label for="select_bedrooms">Bedrooms</label>-->
            <select class="select_bedrooms" name="options[bedrooms]" style="width: 130px;">
                <option value="1"<? selected($options['bedrooms'], 1) ?>>1+ Bedrooms</option>
                <option value="2"<? selected($options['bedrooms'], 2) ?>>2+ Bedrooms</option>
                <option value="3"<? selected($options['bedrooms'], 3) ?>>3+ Bedrooms</option>
                <option value="4"<? selected($options['bedrooms'], 4) ?>>4+ Bedrooms</option>
                <option value="5"<? selected($options['bedrooms'], 5) ?>>5+ Bedrooms</option>
            </select>
        </div>

        <div class="float" style="margin-right: 20px;">
            <!--<label for="select_baths">Baths</label> -->
            <select class="select_baths" name="options[baths]" style="width: 130px;">
                <option value="1"<? selected($options['baths'], 1) ?>>1+ Baths</option>
                <option value="2"<? selected($options['baths'], 2) ?>>2+ Baths</option>
                <option value="3"<? selected($options['baths'], 3) ?>>3+ Baths</option>
                <option value="4"<? selected($options['baths'], 4) ?>>4+ Baths</option>
                <option value="5"<? selected($options['baths'], 5) ?>>5+ Baths</option>
            </select>
        </div>

        <div class="clear"></div>


        <div class="float" style="margin-right: 20px;">
            <select class="select_city" name="options[city]">
            <? $cities = $spark->api->getCities(); ?>
            <option value="">Any City</option>
            <? foreach ($cities as $value => $name) { ?>
             <option value="<?=$value?>"<? selected(strtolower($options['city']), strtolower($value))?>><?=$name?></option>
            <? } ?>
            </select>
        </div>

        <div class="float" style="margin-right: 20px;">
            <select class="select_subdivision" name="options[subdivision]">
            <? $subdivisions = $spark->api->getSubdivisions(); ?>
            <option value="">Any Neighborhood</option>
            <? foreach ($subdivisions as $subdivision) { ?>
            <option value="<?=$subdivision['value']?>" data-city="<?=$subdivision['city']?>"<? selected(strtolower($options['subdivision']), strtolower($subdivision['value']))?>><?=$subdivision['name']?></option>
            <? } ?>
            </select>
        </div>


        <div class="float" style="margin-right: 20px;">
            <select class="select_style" name="options[style]" style="width:200px;">
            <? $styles = $spark->api->getArchitectureStyles(); ?>
            <option value="">Any Architectural Style</option>
            <? foreach ($styles as $value => $name) { ?>
            <option value="<?=$value?>"<? selected(strtolower($options['style']), strtolower($value))?>><?=$name?></option>
            <? } ?>
            </select>
        </div>


        <div class="float" style="margin-right: 20px;">
            <select class="select_type" name="options[type]">
            <? $types = $spark->api->getPropertyClasses(); ?>
            <option value="">Any Type</option>
            <? foreach ($types as $value => $name) { ?>
            <option value="<?=$value?>"<? selected(strtolower($options['type']), strtolower($value))?>><?=$name?></option>
            <? } ?>
            </select>
        </div>

        <div class="clear"></div>

        <div style="margin-top:8px;">
            <button type="submit" name="submitted" class="wpcf7-form-control wpcf7-submit" value="1" style="display:inline;width:auto;">Filter</button>
        </div>
    <? } ?>

</form>


<? if (!$spark->attributes['hide-listings']) { ?>

    <ul class="list_listings" data-listings-list="<?=$listingsListID?>">

    <? foreach($listings as $listing) { ?>
        <li class="listing" data-id="<?=$listing['id']?>" data-post-name="<?=$listing['post_name']?>" data-address="<?=$listing['address']?>">

            <div class="photos">
                <? if ($listing['photo']) { ?>
                    <a href="<?=$listing['link']?>"><img class="mainPhoto" src="<?=$listing['photo']['url']?>" /></a>
                <? } ?>
                <!--
                <ul class="list_thumbs">
                <? foreach ($listing['photos'] as $photo) { ?>
                    <li class="thumb" data-url-640="<?=$photo['url']?>"><img src="<?=$photo['thumb']?>" width="40" height="40" /></li>
                <? } ?>
                </ul>
                -->
            </div>



            <div class="location">
                <a href="<?=$listing['link']?>"><h1><?=$listing['address']?></h1></a>
                <h2>
                <? if ($api->isValidData($listing['subdivision'])) { ?>
                <?=$listing['subdivision']?> in
                <? } ?>
                <span class="nowrap"><?=$listing['city']?>, <?=$listing['data']['StateOrProvince']?></span>
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

            <? if ($listing['data']['ListingId']) { ?>
                <div style="font-size: 11px;color:#ccc;">
                    Listing ID: <?=$listing['data']['ListingId']?> | <?=$listing['data']['MlsStatus']?><br />
                    MLS ID: <?=$listing['data']['MlsId']?> | Listing Agent: <?=$listing['data']['ListAgentMlsId']?>
                </div>
            <? } ?>

        </li>

    <? } ?>


    </ul>

    <div class="clear"></div>

    <button type="button" class="button_load-more-listings wpcf7-form-control wpcf7-submit" data-listings-list="<?=$listingsListID?>">Load More Listings</button>

<? } ?>

