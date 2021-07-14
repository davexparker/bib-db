<?php $title="Upload paper"; require 'header.php'; ?>

<?php

require '../bib_stuff.php';

?>


<?php
$name = extract_var_from_post_get("name");
$paperfile = extract_var_from_post_get("paperfile");
$overwrite = isset($_POST["overwrite"]) ? 1 : 0;
?>

<p>
Use this form to upload a paper to
<a href="$bib_files_dir/">this area</a> of the server.
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
    <input type="hidden" name="MAX_FILE_SIZE" value="50000000">
    
	<table>
	<tr><td><b>Name (e.g. cav06.pdf): </b></td><td>
	<input type="text" size="20" name="name" value="<?php echo $name;?>"></td></tr>

	<tr><td><b>PDF (or PS) file to upload: </b></td><td>
    <!-- Name of input element determines name in $_FILES array -->
    <input name="paperfile" type="file" value="<?php echo $paperfile;?>">
	</td></tr>

        <tr><td><b>Overwrite existing files?</b></td><td>
    <input name="overwrite" type="checkbox" <?php echo $overwrite===1?" checked":"";?>>
        </td></tr>

	</table><br>

    <input type="submit" value="Upload">
	
</form>

<?php
if (array_key_exists("paperfile", $_FILES)) {
	
	if (!isset($name) || preg_match("/^[A-Za-z0-9_\-+\.]+$/", $name) !== 1) {
		echo "<p><font color=\"#cc0000\">[Error: Invalid name \".$name.\"]</font></p>\n";
	} else {
		if (is_uploaded_file($_FILES['paperfile']['tmp_name']) !== 0) {
			echo "<p>Attempting to upload ".$_FILES['paperfile']['name']." to server...</p>\n";
			if (file_exists("$bib_files_dir/".$name) && $overwrite !== 1) {
                                echo "<p><font color=\"#cc0000\">[Error: File $name already exists. Tick the box above and retry.]</font></p>\n";
			} else {
				if (!move_uploaded_file($_FILES['paperfile']['tmp_name'], "$bib_files_dir/".$name)) {
					echo "<p><font color=\"#cc0000\">[Error: Could not upload file (error code ".$_FILES['paperfile']['error'].")]</font></p>\n";
					echo "<p>Error codes are <a href=\"http://php.net/manual/en/features.file-upload.errors.php\">here</a>.</p>\n";
				}
				else {
					echo "<p>Upload successful. File is <a href=\"$bib_files_url/$name\">here</a>.</p>";
				}
			}
		}
		else {
			echo "<p><font color=\"#cc0000\">[Error: Could not upload file (error code ".$_FILES['paperfile']['error'].")]</font></p>\n";
                        echo "<p>Error codes are <a href=\"http://php.net/manual/en/features.file-upload.errors.php\">here</a>.</p>\n";
		}
	}
}
?>

<?php require 'footer.php'; ?>

