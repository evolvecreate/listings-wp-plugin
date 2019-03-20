<h1>Real Estate Listings</h1>
<h3>By Evolve, Create</h3>

<p>Use shortcode [real-estate-listings]</p>
<p>Options:</p>
<ul>
    <li>
        [real-estate-listings location-key="1392-4th-avenue"] to display a specific property
    </li>
    <li>
        [real-estate-listings listing-id="19-449"] to display a specific property
    </li>
    <li>
        [real-estate-listings bedrooms="3" baths="2"] to set default beds and baths in filters
    </li>
    <li>
        [real-estate-listings pricemin="300000" pricemax="500000"] to set default min and max price filters
    </li>
    <li>
        [real-estate-listings city="SANTA BARBARA" neighborhood="Mesa"] to set default location filters
    </li>
    <li>
        [real-estate-listings style="medit"] to set default style filters
    </li>
    <li>
        [real-estate-listings hide-search="true" hide-filters="true" hide-listings="true"] to hide specific elements
    </li>
    <li>
        [real-estate-listings limit="3"] to limit the results
    </li>
    <li>
        [real-estate-lead-form] to display lead form
    </li>
</ul>

<div id="panel_admin-settings">
    <form class="form_listing-settings" action="options.php" method="post">

        <?
            settings_fields('evolve-create-listings');
            do_settings_sections('evolve-create-listings');

        ?>



        <p>Select your photo quality</p>
        <select name="photo-res">
            <?
                $resolutions = array(640, 800, 1024, 1280, 1600, 2048);
                foreach( $resolutions as $res ) {
            ?>
                <option value="<?=$res?>" <?=selected($res, esc_attr(get_option('photo-res')))?>><?=$res?>dpi</option>
            <? } ?>
        </select>


        <h3>Default Listing Page</h3>
        <select name="listing-result-page">
            <?
                $pages = get_pages();
                foreach( $pages as $page ) {
            ?>
                <option value="<?=$page->ID?>" <?=selected($page->ID, esc_attr(get_option('listing-result-page')))?>><?=$page->post_title?></option>
            <? } ?>
        </select>

        <h3>City Listing Pages</h3>
        <? $cities = $spark->api->getCityKeys(); ?>
        <? foreach ($cities as $city => $key) { ?>
            <div class="fieldRow">
                <div class="fieldName float"><?=$city?></div>
                <div class="float">
                    <select name="<?=$key?>">
                    <option value="">Select One</option>
                        <?
                            $pages = get_pages();
                            foreach( $pages as $page ) {
                        ?>
                            <option value="<?=$page->ID?>" <?=selected($page->ID, esc_attr(get_option($key)))?>><?=$page->post_title?></option>
                        <? } ?>
                    </select>
                </div>
                <div class="clear"></div>
            </div>
        <? } ?>

        <h3>Neighborhood Listing Pages</h3>
        <? $subdivisions = $spark->api->getSubdivisionKeys(); ?>
        <? foreach ($subdivisions as $subdivision => $key) { ?>
            <div class="fieldRow">
                <div class="fieldName float"><?=$subdivision?></div>
                <div class="float">
                    <select name="<?=$key?>">
                    <option value="">Select One</option>
                        <?
                            $pages = get_pages();
                            foreach( $pages as $page ) {
                        ?>
                            <option value="<?=$page->ID?>" <?=selected($page->ID, esc_attr(get_option($key)))?>><?=$page->post_title?></option>
                        <? } ?>
                    </select>
                </div>
                <div class="clear"></div>
            </div>
        <? } ?>





        <? submit_button() ?>


    </form>
</div>