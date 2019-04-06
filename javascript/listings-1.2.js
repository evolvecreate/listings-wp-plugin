
(function($) {


    $(document).ready(function(){

        initializeThumbs();
        initializePriceRangeInputs();
        initializeSlideshow();

        initializeLoadMoreButtons();
        initializeCityToNeighborhoodFilter();
    });

    function initializeLoadMoreButtons() {

        $('.button_load-more-listings').click(function(){
            var $button = $(this);
            var listID = $button.data('listings-list');

            loadListings(listID);
        });

    }

    function loadListings(listID) {

        var options = getFilters();
        var $page = $('.hidden_listings-page[data-listings-list="' + listID + '"]');
        var currentPage = Number($page.val());
        var nextPage = currentPage + 1;


        options.page = nextPage;
        options.limit = 27;

        $page.val(nextPage);

        $('.button_load-more-listings[data-listings-list="' + listID + '"]').hide();

        $.ajax({
            url: listingsajax.ajax_url,
            type: 'post',
            data: {
                action: 'get_listings',
                options: options
            },
            success: function(json) {

                var response = JSON.parse(json);
                $.each(response.listings, function(){
                    var listing = this;
                    var $listing = createListingSummary(listing);

                    var $list = $('.list_listings[data-listings-list="' + listID + '"]');
                    $list.append($listing);

                    if (response.pagination) {
                        var totalDisplayed = options.page * options.limit;
                        if (response.pagination.TotalRows > totalDisplayed) {
                            $('.button_load-more-listings[data-listings-list="' + listID + '"]').show();
                        }
                    }

                });
            }
        });


    }

    function getFilters() {

        var options = {};

        options.searchterms = $('.field_search').val();
        options.priceMin = $('.field_priceMin').val();
        options.priceMax = $('.field_priceMax').val();
        options.bedrooms = $('.select_bedrooms').val();
        options.baths = $('.select_baths').val();
        options.city = $('.select_city').val();
        options.subdivision = $('.select_subdivision').val();
        options.style = $('.select_style').val();
        options.type = $('.select_type').val();

        return options;

    }

    function createListingSummary(listing) {

        var $listing = $(document.createElement('li')).addClass('listing');
        $listing.data('id', listing.id).attr('data-id', listing.id);
        $listing.data('post-name', listing.post_name).attr('data-post-name', listing.post_name);
        $listing.data('address', listing.address).attr('data-address', listing.address);

        var $photos = createListingSummaryPhotos(listing);
        $listing.append($photos);

        var $location = createListingSummaryLocation(listing);
        $listing.append($location);

        var $layout = createListingSummaryLayout(listing);
        $listing.append($layout);

        var $clear = $(document.createElement('div')).addClass('clear');
        $listing.append($clear);

        // TODO: add is_super_admin part?

        if (listing.data['ListingId']) {
            var $adminInfo = $(document.createElement('div')).css('font-size', '11px').css('color', '#ccc');
            $adminInfo.append('Listing ID: ' + listing.data['ListingId']);
            $adminInfo.append(' | ');
            $adminInfo.append(listing.data['MlsStatus']);
            $adminInfo.append('<br />');
            $adminInfo.append('MLS ID: ' + listing.data['MlsId']);
            $adminInfo.append(' | ');
            $adminInfo.append('Listing Agent: ' + listing.data['ListAgentMlsId']);
            $listing.append($adminInfo);
        }

        /*
            <? if (is_super_admin()) { ?>
                <div style="font-size: 11px;color:#ccc;">
                    <?=$listing['data']['MlsStatus']?> | MLS ID: <?=$listing['data']['MlsId']?> | Listing Agent: <?=$listing['data']['ListAgentMlsId']?>
                </div>
            <? } ?>
        */

        return $listing;

    }

    function createListingSummaryPhotos(listing) {

        var $photos = $(document.createElement('div')).addClass('photos');
        if (listing.photo) {
            var $photoLink = $(document.createElement('a'));
            $photoLink.attr('href', listing.link);
            var $photo = $(document.createElement('img')).addClass('mainPhoto');
            $photo.attr('src', listing.photo.url);
            $photoLink.append($photo);
            $photos.append($photoLink);
        }

        return $photos;
    }

    function createListingSummaryLocation(listing) {



        var $location = $(document.createElement('div')).addClass('location');

        var $addressLink = $(document.createElement('a'));
        $addressLink.attr('href', listing.link);
        var $addressHeading = $(document.createElement('h1')).html(listing.address);
        $addressLink.append($addressHeading);
        $location.append($addressLink);


        var $cityStateSubdivision = $(document.createElement('h2'));
        if (listing.subdivision) { // TODO: use isValidData()
            $cityStateSubdivision.append(listing.subdivision + ' in ');
        }
        var $cityState = $(document.createElement('span'));
        $cityState.addClass('nowrap').text(listing.city + ', ' + listing.data['StateOrProvince']);
        $cityStateSubdivision.append($cityState);
        $location.append($cityStateSubdivision);

        var $price = $(document.createElement('div')).text(formatCurrency(listing.data['ListPrice']));
        $location.append($price);

        return $location;
    }

    function createListingSummaryLayout(listing) {

        var $layout = $(document.createElement('div')).addClass('layout');
        if (
            (listing.data['BedsTotal']) && // TODO: use isValidData
            (listing.data['BathsTotal'])
        ) {
            var $bedsBaths = $(document.createElement('h2'));
            $bedsBaths.text(listing.bedrooms + ' / ' + listing.baths);
            $layout.append($bedsBaths);
        }

        if (listing.styles) {
            var $styles = $(document.createElement('h2'));
            $styles.text(listing.styles.join(', '));
            $layout.append($styles);
        }

        if (listing.data['BuildingAreaTotal']) { // TODO: use isValidData
            var $footage = $(document.createElement('div'));
            $footage.text(numberFormat(listing.data['BuildingAreaTotal']) + ' Square Feet');
            $layout.append($footage);
        }

        return $layout;

    }



    function initializeThumbs() {

        $('.list_thumbs > li.thumb:first-child').addClass('active');
        saveApiPhotos();
    }

    function saveApiPhotos() {
        var $photos = $('.listing-photo[data-location="api"]');
        $photos.each(function(){
            saveApiPhoto($(this));
        });
    }

    function saveApiPhoto($photo) {

        var $listing = $photo.closest('.listing');

        var listing = {
            post_name: $listing.data('post-name'),
            address: $listing.data('address')
        };


        var photo = {
            url: $photo.data('url'),
            Name: $photo.data('photo-name') // key must be capitalized
        };

        $.ajax({
            url: listingsajax.ajax_url,
            type: 'post',
            data: {
                action: 'save_api_photo',
                listing: listing,
                photo: photo
            },
            success: function(url) {
                $photo.css('background-image', 'url(\'' + url + '\')');
                var index = $photo.closest('.slide').data('slideshow-index');
                $('.thumb[data-slideshow-index="' + index + '"]').find('img').attr('src', url);
            }
        })
    }


    function initializeCityToNeighborhoodFilter() {

        var $citySelector = $('.select_city');

        $citySelector.change(function(){
            filterCityToNeighborhood();
        });

        if ($citySelector.val()) {
            filterCityToNeighborhood();
        }
    }

    function filterCityToNeighborhood() {

        var $neighborhoods = $('.select_subdivision option');
        var $citySelector = $('.select_city');

        var city = $citySelector.val().toLowerCase();
        if (city) {
            var $matchingNeighborhoods = $('.select_subdivision option[data-city="' + city + '"]');
            if ($matchingNeighborhoods.length > 0) {
                $neighborhoods.hide();
                $matchingNeighborhoods.show();
            } else {
                $neighborhoods.show();
            }

        } else {
            $neighborhoods.show();
        }
    }



    function initializePriceRangeInputs() {

        $('.field_priceMin').each(function(){
            var $field = $(this);
            if ($field.val()) {
                var formatted = formatCurrency($field.val(), true);
               $field.val(formatted);
            }
        });

        $('.field_priceMax').each(function(){
            var $field = $(this);
            if ($field.val()) {
                var formatted = formatCurrency($field.val(), true);
               $field.val(formatted);
            }
        });


        $('.field_priceMin,.field_priceMax').keyup(function() {
            var formatted = formatCurrency($(this).val(), true);
            $(this).val(formatted);
        });
    }

    function initializeSlideshow() {

        var $listingSlideshow = $('#slideshow_listing-photos');  // TODO: pull from options

        if ($listingSlideshow.length > 0) {
            $listingSlideshow.slideshow({autoPlay: false, fadeButtons: false});
        }

        var thumbs = $('.list_thumbs .thumb');
        thumbs.click(function(){
            var slideshowIndex = $(this).data('slideshow-index');
            moveToSlide($listingSlideshow, slideshowIndex);
            activateThumbForCurrentSlide();
        });

        $listingSlideshow.find('.slide,.button_previous,.button_next').click(function(){
            activateThumbForCurrentSlide();
        });
    }

    function moveToSlide(slideshow, slideNumber){

        slideshow.slideshow('moveToSlide', slideNumber);
    }

    function activateThumbForCurrentSlide() {

        var $listingSlideshow = $('#slideshow_listing-photos');  // TODO: pull from options

        var currentSlide = $($listingSlideshow.data('currentSlide'));
        var index = currentSlide.data('slideshow-index');
        activateThumb(index);
    }

    function activateThumb(index) {
        $('.list_thumbs > li.thumb.active').removeClass('active');
        $('.list_thumbs > li.thumb[data-slideshow-index="' + index + '"]').addClass('active');
    }


    function formatCurrency(value, clearZero) {
        if (typeof(value) != 'string') {
            value = value.toString();
        }
        var numbers = Number(value.replace(/[^0-9]+/g, ''));
        if ((clearZero) && (numbers == 0)) {
            return '';
        }
        var formatted = numbers.toLocaleString('en-US', {style: 'currency', currency: 'USD', minimumFractionDigits: 0, maximumFractionDigits: 0});

        return formatted;
    }

    function numberFormat(value) {

        if (typeof(value) != 'string') {
            value = value.toString();
        }

        var formatted = value.toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 0});
        return formatted;
    }

})(jQuery);


