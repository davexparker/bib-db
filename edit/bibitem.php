<?php $title="View"; require 'header.php'; ?>

<?php require '../bib_stuff.php'; ?>

<?php
// get key from post/get
$key = extract_key_from_post_get();
if (!$key) return;
// get item from database
$item = bib_fetch_item($key);
if (!$item) return;
?>

<!-- ----------------------------------------------------------------------------- -->

<h1><?php echo $item["key"];?></h1>

<?php bib_display_item_detailed($item); ?>

<!-- ----------------------------------------------------------------------------- -->

<?php require 'footer.php'; ?>
