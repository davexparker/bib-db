<?php $title="Delete"; require 'header.php'; ?>

<?php

require '../bib_stuff.php';

// get key from post/get (if present)
$key = extract_key_from_post_get();

if ($key == "") {
	echo "<p><font color=\"#cc0000\">[Error: No key supplied]</font></p>\n";
}
else {
	$sure = extract_var_from_post_get("sure");
	if ($sure != "yes") {
		echo "<p>Are you sure you want to delete item $key? [<a href=\"delete.php?key=$key&sure=yes\">Yes</a>]</p>\n";
	} else {
		$error = bib_delete_item($key);
		if ($error) {
			echo "<p><font color=\"#cc0000\">[$error]</font></p>\n";
		} else {
			echo "<p>\n<font color=\"#00cc00\">[Item \"$key\" deleted successfully]</font>\n</p>\n";
		}
	}
} ?>

<?php require 'footer.php'; ?>
