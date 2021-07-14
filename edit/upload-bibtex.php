<?php $title="Upload bibtex"; require 'header.php'; ?>

<?php

require '../bib_stuff.php';

?>

<?php
if (!array_key_exists("bibfile", $_FILES)) {
?>

<p>
Use this form to upload a bibtex file to
<a href="<?php echo "$bib_bibtex_url";?>/">this area</a> of the server.
</p>

<p>
The name of the bibtex file on the server will be based on its citation key, specified below.
</p>

<!-- The data encoding type, enctype, MUST be specified as below -->
<form enctype="multipart/form-data" action="upload-bibtex.php" method="POST">

    <!-- MAX_FILE_SIZE must precede the file input field -->
    <input type="hidden" name="MAX_FILE_SIZE" value="50000000">
    
	<table>
	<tr><td><b>Citation key (e.g. KNP06a): </b></td><td>
	<input type="text" size="20" name="name" value=""></td></tr>

	<tr><td><b>Bibtex file to upload: </b></td><td>
    <!-- Name of input element determines name in $_FILES array -->
    <input name="bibfile" type="file">
	</td></tr>
	</table>

    <input type="submit" value="Upload">
	
</form>

<?php
} else {
	$name = extract_var_from_post_get("name");
	if (!isset($name) || preg_match("/^[A-Za-z0-9_\-+\.]+$/", $name) !== 1) {
		echo "<p><font color=\"#cc0000\">[Error: Invalid name \".$name.\"]</font></p>\n";
	} else {
		if (is_uploaded_file($_FILES['bibfile']['tmp_name'])) {
			echo "<p>Attempting to upload ".$_FILES['bibfile']['name']." to server...</p>\n";
			if (!move_uploaded_file($_FILES['bibfile']['tmp_name'], "$bib_bibtex_dir/".$name.".bib")) {
				echo "<p><font color=\"#cc0000\">[Error: Could not upload file $bib_bibtex_dir/".$name.".bib (error code ".$_FILES['bibfile']['error'].")]</font></p>\n";
			}
			else {
				echo "<p>Upload successful. File is <a href=\"$bib_bibtex_url/$name.bib\">here</a>.</p>";
			}
		}
		else {
			echo "<p><font color=\"#cc0000\">[Error: Could not upload file (error code ".$_FILES['bibfile']['error'].")]</font></p>\n";
		}
	}
}
?>

<?php require 'footer.php'; ?>

