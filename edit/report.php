<?php $title="Report"; require 'header.php'; ?>

<?php require '../bib_stuff.php'; ?>

<?php $bib_list_item_extra_links = array("view" => "bibitem.php?key=%k", "edit" => "edit.php?key=%k"); ?>

<h3>Marta papers with no author tag:</h3>

<?php
$keys = bib_db_query("SELECT key FROM bib_items WHERE ((position('Kwiatkowska' in author)>0) AND ((position('|marta|' in cats)=0) OR (cats IS NULL)))");
if (count($keys)>0) {
	echo "<ul>\n";
	foreach ($keys as $key) {
		$item = bib_fetch_item($key);
		echo "<li>\n";
		bib_display_item($item);
	}
	echo "</ul>\n";
} else echo "&lt;none&gt;\n";
?>

<h3>Marta/Gethin/Dave papers with no 'filename':</h3>

<?php
$keys = bib_db_query("SELECT key FROM bib_items WHERE ((position('|dave|' in cats)>0 OR position('|gethin|' in cats)>0 OR position('|marta|' in cats)>0) AND (type != 'book') AND (filename IS NULL OR filename = ''))");
if (count($keys)>0) {
	echo "<ul>\n";
	foreach ($keys as $key) {
		$item = bib_fetch_item($key);
		echo "<li>\n";
		bib_display_item($item);
	}
	echo "</ul>\n";
} else echo "&lt;none&gt;\n";
?>

<h3>Marta/Gethin/Dave papers with no 'abstract':</h3>

<?php
$keys = bib_db_query("SELECT key FROM bib_items WHERE ((position('|dave|' in cats)>0 OR position('|gethin|' in cats)>0 OR position('|marta|' in cats)>0) AND (type != 'book') AND (abstract IS NULL OR abstract = ''))");
if (count($keys)>0) {
	echo "<ul>\n";
	foreach ($keys as $key) {
		$item = bib_fetch_item($key);
		echo "<li>\n";
		bib_display_item($item);
	}
	echo "</ul>\n";
} else echo "&lt;none&gt;\n";
?>

<h3>Papers with 'filename' but no files:</h3>

<?php
$keys = bib_db_query("SELECT key FROM bib_items WHERE NOT(filename IS NULL OR filename = '')");
if (count($keys)>0) {
	echo "<ul>\n";
	foreach ($keys as $key) {
		$item = bib_fetch_item($key);
		$files = bib_get_item_files($item);
		if (count($files) == 0 || (count($files) == 1 && $files[0]["ext"]="bib")) {
			echo "<li>\n";
			bib_display_item($item);
			echo "[".$item["filename"]."]\n";
		}
	}
	echo "</ul>\n";
} else echo "&lt;none&gt;\n";
?>

<?php require 'footer.php'; ?>
