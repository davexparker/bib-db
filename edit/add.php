<?php $title="Add"; require 'header.php'; ?>

<?php

require '../bib_stuff.php';

// get key from post/get (if present)
$key = extract_key_from_post_get();

if ($key == "") {?>
	
	<form method="post" action="add.php">
	
	<table>
	<tr><td><b>Key: </b></td><td>
	<input type="text" size="20" name="key" value=""></td></tr>
	<tr><td><b>Type: </b></td><td>
	<select name="type">
	<option value="inproceedings">inproceedings
	<option value="article">article
	<option value="book">book
	<option value="inbook">inbook
	<option value="phdthesis">phdthesis
	<option value="mastersthesis">mastersthesis
	<option value="techreport">techreport
	<option value="unpublished">unpublished
	<option value="proceedings">proceedings
	</td></tr>
	</table>
	<input type="submit" value="Add">
	
<?php } else {
	
	$type = extract_var_from_post_get("type");
	$error = bib_add_item($key, $type);
	if ($error) {
		echo "<p><font color=\"#cc0000\">[Error: $error.]</font></p>\n";
	} else {
		echo "<p>\n<font color=\"#00cc00\">[Item \"$key\" added successfully.]</font>\n";
		echo "[<a href=\"edit.php?key=$key\">Edit</a>]\n</p>\n";
	}
} ?>

<?php require 'footer.php'; ?>
