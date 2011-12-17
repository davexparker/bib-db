<?php $title="List"; require 'header.php'; ?>

<?php

require '../bib_stuff.php';

$bib_list_item_extra_links = array("view" => "view.php?key=%k", "edit" => "edit.php?key=%k", "delete" => "delete.php?key=%k");

$search = bib_search_box();

?>
<p>
Sort by: 
[<a href="<?php echo $_SERVER["PHP_SELF"]; ?>?search=<?php echo $search; ?>&sort=date">Date</a>]
[<a href="<?php echo $_SERVER["PHP_SELF"]; ?>?search=<?php echo $search; ?>&sort=key">Key</a>]
[<a href="<?php echo $_SERVER["PHP_SELF"]; ?>?search=<?php echo $search; ?>&sort=type">Type</a>]
[<a href="<?php echo $_SERVER["PHP_SELF"]; ?>?search=<?php echo $search; ?>&sort=author">Author</a>]
[<a href="<?php echo $_SERVER["PHP_SELF"]; ?>?search=<?php echo $search; ?>&sort=title">Title</a>]
</p>
<?

echo "<p></p>\n\n";

bib_new_list($search);

bib_add_all();

$sort = extract_var_from_post_get("sort");
if (!$sort) $sort = "date";
switch ($sort) {
	case "date": bib_sort_by("year", "desc", "<h3>%s</h3>"); bib_sort_by("month", "desc"); break;
	case "key": bib_sort_by("key", "asc"); break;
	case "type": bib_sort_by("type", "asc", "<h3>%s</h3>"); break;
	case "author": bib_sort_by("author", "asc"); break;
	case "title": bib_sort_by("title", "asc"); break;
}

bib_display_list_table("view.php?key=%k");

/*
<tr>
<td class="bg"><a href="list.php"><b>#</b></a></td>
<td class="bg"><a href="list.php?sort=key"><b>Key</b></a></td>
<td class="bg"><a href="list.php?sort=type"><b>Type</b></a></td>
<td class="bg"><a href="list.php?sort=author"><b>Author</b></a></td>
<td class="bg"><a href="list.php?sort=title"><b>Title</b></a></td>
<td class="bg" colspan="3"><a href="list.php"><b>Actions</b></a></td>
</tr>
*/

?>

<?php require 'footer.php'; ?>
