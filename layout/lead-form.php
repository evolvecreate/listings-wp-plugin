<?
    $api = new sparkAPI();
    $spark = new listings($api);
    $spark->init();
    $spark->notify_listing_lead();
?>

<form class="form_listing-lead" action="<?=esc_url($_SERVER['REQUEST_URI'])?>" method="post">

<?
    if ($_GET['rlid']) {
        $listing = $spark->api->getListingByPostName($_GET['rlid']);
?>
    <input type="hidden" name="listing_id" value="<?=$_GET['rlid']?>" />
    <h1>I'm interested in <?=$listing['address']?> in <?=$listing['city']?></h1>

<? } else { ?>

<? } ?>

<input type="text" class="placeholderNoLabel" name="contact_email" value="<?=$_POST['contact_email']?>" placeholder="Email*" />
<input type="text" class="placeholderNoLabel" name="contact_name" value="<?=$_POST['contact_name']?>" placeholder="Name" />

<textarea class="placeholderNoLabel"></textarea>

<button type="submit" value="submit">Send Me Info</button>

</form>
