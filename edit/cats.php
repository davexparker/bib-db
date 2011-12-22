<?php $title="Report"; require 'header.php'; ?>

<?php require '../bib_stuff.php'; ?>

<?php

// get action from post/get
$action = "";
$action = extract_var_from_post_get("action");

if ($action == "delete") {
	$sure = extract_var_from_post_get("sure");
	if ($sure != "yes") {
		echo "<p>Are you sure you want to delete category '$name'? [<a href=\"cats.php?action=delete&name=$name&sure=yes\">Yes</a>]</p>\n";
	} else {
		$name = extract_var_from_post_get("name");
		if ($name) {
			$name = pg_escape_string($name);
			$changed = bib_db_update_query("DELETE from bib_cats WHERE name='".$name."'");
			if ($changed == 0) {
				echo "<p><font color=\"#cc0000\">[Delete failed]</font></p>\n";
			} else {
				echo "<p><font color=\"#00cc00\">[Deleted category '".$name."']</font></p>\n";
			}
		}
	}
}

if ($action == "add") {
	$name = extract_var_from_post_get("name");
	$group = extract_var_from_post_get("group");
	if ($name && $group) {
		$name = pg_escape_string($name);
		$group = pg_escape_string($group);
		$changed = bib_db_update_query("INSERT INTO bib_cats (name, cat_group, description) values ('".$name."', '".$group."', '');");
		if ($changed != 1) {
			echo "<p><font color=\"#cc0000\">[Add failed]</font></p>\n";
		} else {
			echo "<p><font color=\"#00cc00\">[Added category '".$name."']</font></p>\n";
		}
	}
}
?>

<p>
These are the categories that show up in the "edit" page.
</p>

<p>
Delete with the links in the table, or add using the form below.
</p>

<table border="1"><tr><td><b>Group</b></td><td><b>Name</b></td><td><b>Delete?</b></td></tr>

<?php 
$result = bib_db_query("SELECT name,cat_group FROM bib_cats ORDER BY cat_group,name");
foreach ($result as $cat) {
echo "<tr>";
echo "<td>".$cat["cat_group"]."</td>";
echo "<td>".$cat["name"]."</td>";
echo "<td>[<a href=\"".$_SERVER["PHP_SELF"]."?action=delete&name=".$cat["name"]."\">delete</a>]</td>";
echo "</tr>\n";
}
?>

</table>

<p>
Add new categories here:
</p>

<form method="post" action="cats.php">
<input type="hidden" name="action" value="add">
<table>
<tr><td><b>Group: </b></td><td><input type="text" size="30" name="group"></td></tr>
<tr><td><b>Name: </b></td><td><input type="text" size="30" name="name""></td></tr>
</table>
<input type="submit" value="Add">
</form>

<?php require 'footer.php'; ?>
