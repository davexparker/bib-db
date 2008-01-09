<?php $title="Edit All"; require 'header.php'; ?>

<?php

require '../bib_stuff.php';

// get key from post/get
$key = extract_key_from_post_get();
if (!$key) return;

// determine action required
$action = extract_var_from_post_get("action");

if ($action == "update") {
	$item["key"] = $key;
	extract_vars_from_post_get($item, $bib_item_fields);
	$item["cats"] = extract_var_from_post_get("cats");
	$error = bib_update_item($item);
	if ($error) {
		echo "<p><font color=\"#cc0000\">[$error]</font></p>\n";
	} else {
		echo "<p><font color=\"#00cc00\">[Update was successful]</font></p>\n";
	}
}

else {
	// get item from database
	$item = bib_fetch_item($key);
	if (!$item) return;
}

?>

<form method="post" action="editall.php?key=<?php echo $item["key"]; ?>">
<input type="hidden" name="action" value="update">

<input type="submit" value="Submit changes">

<br><br>

<table>
<tr><td><b>Key:</b></td><td><b><?php echo $item["key"]; ?></b></td></tr>

<tr><td>&nbsp;</td><td></td></tr>

<?php
foreach ($bib_item_fields as $field) {
	echo "<tr><td><b>$field: </b></td><td><input type=\"text\" size=\"100\" name=\"$field\" value=\"".$item["$field"]."\"></td></tr>\n";
}
?>

<tr><td>&nbsp;</td><td></td></tr>

<?php
	echo "<tr><td valign=\"top\"><b>Categories: </b></td><td>\n";
	foreach (bib_get_cat_names() as $cat) {
		echo "<input type=\"checkbox\" name=\"cats[]\" value=\"$cat\"";
		if ($item["cats"]) if (in_array($cat, $item["cats"])) echo " checked";
		echo "> $cat<br>\n";
	}
	echo "</td></tr>\n";
?>

</table>

<br>

<input type="submit" value="Submit changes">

<?php require 'footer.php'; ?>
