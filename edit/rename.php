<?php $title="Rename"; require 'header.php'; ?>

<?php

require '../bib_stuff.php';

// get keys from post/get (if present)
$key = extract_key_from_post_get();
$newkey = extract_key_from_post_get("newkey");

if ($key == "" || $newkey == "") {?>
	
	<form method="post" action="rename.php">
	
	<table>
	<tr><td><b>Old key: </b></td><td>
	<input type="text" size="20" name="key" value=""></td></tr>
	<tr><td><b>New key: </b></td><td>
	<input type="text" size="20" name="newkey" value=""></td></tr>
	</table>
	<input type="submit" value="Rename">
	
<?php } else {
	
	$error = bib_rename_item($key, $newkey);
	if ($error) {
		echo "<p><font color=\"#cc0000\">[Error: $error.]</font></p>\n";
	} else {
		echo "<p>\n<font color=\"#00cc00\">[Item \"$key\" renamed successfully to \"$newkey\".]</font>\n";
		echo "[<a href=\"edit.php?key=$newkey\">Edit</a>]\n</p>\n";
		echo "<p>Don't forget to rename/update your bibtex files.</p>\n";
	}
} ?>

<?php require 'footer.php'; ?>
