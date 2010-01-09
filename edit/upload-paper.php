<?php $title="Upload paper"; require 'header.php'; ?>

<?php

require '../bib_stuff.php';

?>

<?php
if (!array_key_exists("paperfile", $_FILES)) {
?>

<p>
Use this form to upload a paper to
<a href="http://qav.comlab.ox.ac.uk/papers/">this area</a> of the QAV server.
</p>

<p>
Please use lowercase filename of conference/journal, e.g. "cav06", "fmsd07".
</p>

<p>
Add a suffix to distinguish papers if necessary, e.g. "cav06symmetry".
</p>

<!-- The data encoding type, enctype, MUST be specified as below -->
<form enctype="multipart/form-data" action="upload-paper.php" method="POST">

    <!-- MAX_FILE_SIZE must precede the file input field -->
    <input type="hidden" name="MAX_FILE_SIZE" value="10000000">
    
	<table>
	<tr><td><b>Name (e.g. cav06.pdf): </b></td><td>
	<input type="text" size="20" name="name" value=""></td></tr>

	<tr><td><b>PDF (or PS) file to upload: </b></td><td>
    <!-- Name of input element determines name in $_FILES array -->
    <input name="paperfile" type="file">
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
		if (is_uploaded_file($_FILES['paperfile']['tmp_name'])) {
			echo "<p>Attempting to upload ".$_FILES['paperfile']['name']." to server...</p>\n";
			if (!move_uploaded_file($_FILES['paperfile']['tmp_name'], "/home/dxp/papers/".$name)) {
				echo "<p><font color=\"#cc0000\">[Error: Could not upload file (error code ".$_FILES['paperfile']['error'].")]</font></p>\n";
			}
			else {
				echo "<p>Upload successful. File is <a href=\"http://qav.comlab.ox.ac.uk/papers/$name\">here</a>.</p>";
			}
		}
		else {
			echo "<p><font color=\"#cc0000\">[Error: Could not upload file (error code ".$_FILES['paperfile']['error'].")]</font></p>\n";
		}
	}
}
?>

<?php require 'footer.php'; ?>
