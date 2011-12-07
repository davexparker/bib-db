<?php $title="Report"; require 'header.php'; ?>

<?php require '../bib_stuff.php'; ?>

<?php $bib_list_item_extra_links = array("view" => "bibitem.php?key=%k", "edit" => "edit.php?key=%k"); ?>

<h3>Possible missing 'author' categories:</h3>

<?php
bib_new_list();
bib_add_sql("(bib_items.type != 'techreport') AND ((position('M. Kwiatkowska' in author)>0) AND ((position('|marta|' in cats)=0) OR (cats IS NULL)))");
bib_add_sql("(bib_items.type != 'techreport') AND ((position('G. Norman' in author)>0) AND ((position('|gethin|' in cats)=0) OR (cats IS NULL)))");
bib_add_sql("(bib_items.type != 'techreport') AND ((position('D. Parker' in author)>0) AND ((position('|dave|' in cats)=0) OR (cats IS NULL)))");
bib_display_list_ul("view.php?key=%k");
?>

<h3>Local papers with no 'filename' (or 'url'):</h3>

<?php
// papers with some category (that is not 'prismbib')
$sql = "length(cats)>0 AND ((position('|prismbib|' in cats)=0)";
// and that are not conference proceedings, books or msc theses
$sql = "(".$sql.") AND (bib_items.type != 'proceedings' AND bib_items.type != 'book' AND bib_items.type != 'mastersthesis')";
// and with missing filename/url
$sql = "(".$sql.") AND (filename IS NULL OR filename = '') AND (url IS NULL OR url = '')";
bib_new_list();
bib_add_sql($sql);
bib_sort_by("year", "desc");
bib_display_list_ul("view.php?key=%k");
?>

<h3>Papers with 'filename' but no files:</h3>

<?php
$items = bib_db_query("SELECT key,filename,author,title FROM bib_items WHERE NOT(filename IS NULL OR filename = '')");
echo "<ul>\n";
foreach ($items as $item) {
	$files = bib_get_item_files($item, false);
	if (count($files) == 0) {
		echo "<li> <b>[".$item["key"]."]</b> ".$item["author"];
		echo ". <a href=\"view.php?key=".$item["key"]."\">".$item["title"]."</a>";
		echo ". [".$item["filename"]."]\n";
		echo " [<a href=\"edit.php?key=".$item["key"]."\">edit</a>]\n";
	}
}
echo "</ul>\n";
?>

<h3>Marta/Gethin/Dave papers with no 'abstract':</h3>

<?php
$authors = array("marta", "gethin", "dave");
$sql = implode(" OR ", preg_replace("/.+/", "(position('|$0|' in cats)>0)", $authors));
// and that are not conference proceedings, books
$sql = "(".$sql.") AND (bib_items.type != 'proceedings' AND bib_items.type != 'book')";
// with no abstract...
$sql = "(".$sql.") AND (abstract IS NULL OR abstract = '')";
bib_new_list();
bib_add_sql($sql);
bib_sort_by("year", "desc");
bib_display_list_ul("view.php?key=%k");
?>

<?php require 'footer.php'; ?>
