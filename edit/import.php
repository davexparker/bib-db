<?php $title="Import"; require 'header.php'; ?>

<?php

require '../bib_stuff.php';

?>

<?php
if (!array_key_exists("bibfile", $_FILES)) {
?>

<p>
Use this form to import to the database from bibtex.
</p>

<p>
See e.g.
<a href="http://qav.comlab.ox.ac.uk/bibtex/.Article">.Article</a> and
<a href="http://qav.comlab.ox.ac.uk/bibtex/.InProceedings">.InProceedings</a> for template bibtex files.
All existing bibtex files  are <a href="http://qav.comlab.ox.ac.uk/bibtex/">here</a>.
</p>

<p>
Please use bibtex "Alpha"-style citation keys (e.g. KNP09a)
and make sure that the key in the bibtex file matches the one you provide below.
</p>

<!-- The data encoding type, enctype, MUST be specified as below -->
<form enctype="multipart/form-data" action="import.php" method="POST">

    <!-- MAX_FILE_SIZE must precede the file input field -->
    <input type="hidden" name="MAX_FILE_SIZE" value="30000">
    
	<table>
	<tr><td><b>Key (e.g. KNP09a): </b></td><td>
	<input type="text" size="20" name="key" value=""></td></tr>

	<tr><td><b>Bibtex file to upload: </b></td><td>
    <!-- Name of input element determines name in $_FILES array -->
    <input name="bibfile" type="file">
	</td></tr>
	</table>

    <input type="submit" value="Upload">
	
</form>

<?php
} else {
	$key = extract_key_from_post_get($field = "key");
	if (!isset($key) || !is_key_valid($key)) {
		echo "<p><font color=\"#cc0000\">[Error: Invalid key]</font></p>\n";
	} else {
		if (is_uploaded_file($_FILES['bibfile']['tmp_name'])) {
			echo "<p>Attempting to add ".$_FILES['bibfile']['name']." to database...</p>\n";
			echo "<pre>\n";
			$res = exec("/home/dxp/bib-db/bib2postgresql/bib2postgresql < ".$_FILES['bibfile']['tmp_name']." 2>&1", $output);
			if ($res == 0) echo "<font color=\"#cc0000\">";
			else echo "<font color=\"#00cc00\">";
			foreach ($output as $line) {
				echo $line."\n";
			}
			echo "</pre>\n";
			echo "</font>\n";
			if ($res != 0) {
				if (!@move_uploaded_file($_FILES['bibfile']['tmp_name'], "/home/dxp/doc/bib/".$key.".bib")) {
					echo "<p>But there was en error uploading the actual bibtex file to the server.</p>\n";
				}
				echo "<p>You can now <a href=\"edit.php?key=$key\">edit</a> the database entry.</p>";
			}
		}
		else {
			echo "<p><font color=\"#cc0000\">[Error in file upload]</font></p>\n";
		}
	}
}
?>

<?php require 'footer.php'; ?>

