<?php $title="Edit All"; require 'header.php'; ?>

<?php

require '../bib_stuff.php';

// get key from post/get
$key = extract_key_from_post_get();
if (!$key) return;

?>

<table><tr><td valign="top">

<?php

// print contents sidebar
$keylist = bib_db_query("SELECT key FROM bib_items ORDER BY key");
$n = count($keylist);
$k = -1;
for ($i = 0; $i < $n; $i++) if ($keylist[$i] == $key) $k = $i;
if ($k >= 0) {
	echo "------<br>\n";
	$i1 = max(0, $k-5);
	$i2 = min($k+5, $n-1);
	if ($i1 > 0) echo "[<a href=\"edit.php?key=".$keylist[0]."\">".$keylist[0]."</a>]<br>\n...<br>\n";
	for ($i = $i1; $i < $k; $i++) {
		echo "[<a href=\"edit.php?key=".$keylist[$i]."\">".$keylist[$i]."</a>]<br>\n";
	}
	echo "[<b>".$key."</b>]<br>\n";
	for ($i = $k+1; $i <= $i2; $i++) {
		echo "[<a href=\"edit.php?key=".$keylist[$i]."\">".$keylist[$i]."</a>]<br>\n";
	}
	if ($i2 < $n-1) echo "...<br>\n[<a href=\"edit.php?key=".$keylist[$n-1]."\">".$keylist[$n-1]."</a>]<br>\n";
	echo "------<br>\n";
}

?>

</td><td>&nbsp;</td><td class="alt2">&nbsp;</td><td>&nbsp;</td><td valign="top">

<?php

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

<br>

<h3>Bibtex fields:</h3>

<table>
<tr><td><b>Key:</b></td><td><b><?php echo $item["key"]; ?></b></td></tr>

<tr><td>&nbsp;</td><td></td></tr>

<?php
foreach ($bib_item_fields_bibtex as $field) {
	if ($field == "type") {
		echo "<tr><td><b>Type: </b></td><td><select name=\"type\">";
		foreach ($bib_item_types as $type_index => $type_name) {
			echo "<option value=\"$type_index\"";
			if ($item["type"] == $type_index) echo " selected";
			echo ">$type_name";
		}
		echo "</select></td></tr>\n";
	} else if ($field == "month") {
		echo "<tr><td><b>Month: </b></td><td><select name=\"month\">";
		foreach ($bib_month_names as $month_index => $month_name) {
			echo "<option value=\"$month_index\"";
			if ($item["month"] == $month_index) echo " selected";
			echo ">$month_name";
		}
		echo "</select></td></tr>\n";
	} else {
		echo "<tr><td><b>$field: </b></td><td><input type=\"text\" size=\"100\" name=\"$field\" value=\"".$item["$field"]."\"></td></tr>\n";
	}	
}
?>

<tr><td>&nbsp;</td><td></td></tr>
<tr><td><input type="submit" value="Submit changes"></td><td></td></tr>
<tr><td>&nbsp;</td><td></td></tr>

</table>

<h3>Other database fields:</h3>

<table>

<tr><td colspan="2">For local publications, enter the name of the PDF/PS file (without the extension):<br>
Use e.g. <em>cav06</em> or <em>cav06symm</em> (for conferences), <em>fmsd-games</em> (for journals) or <em>RR-11-11</em> (for CS tech reports)</td></tr>
<tr><td><b>Filename: </b></td><td><input type="text" size="100" name="filename" value="<?php echo $item["filename"]; ?>"></td></tr>
<tr><td colspan="2">For other people's publications, enter the URL where it can be found:</td></tr>
<tr><td><b>URL: </b></td><td><input type="text" size="100" name="url" value="<?php echo $item["url"]; ?>"></td></tr>

<tr><td>&nbsp;</td></tr>

<tr><td valign="top"><b>Abstract: </b><br>(html)</td><td><textarea rows="10" cols="70" name="abstract"><?php echo $item["abstract"]; ?></textarea></td></tr>
<tr><td valign="top"><b>Notes: </b><br>(html)</td><td><textarea rows="5" cols="70" name="links"><?php echo $item["links"]; ?></textarea><br>
Use e.g. "See [KNP06c]" to automatically get a link to another paper in the database.</td></tr>
<tr><td valign="top"><b>Synopsis: </b></td><td><textarea rows="5" cols="70" name="synopsis"><?php echo $item["synopsis"]; ?></textarea><br>

<tr><td>&nbsp;</td><td></td></tr>

<?php
	echo "<tr><td valign=\"top\"><b>Categories: </b></td><td>\n";
	echo "<table border=\"1\">\n";
	foreach (bib_get_cat_groups() as $group) {
		echo "<tr><td valign=\"top\">".$group.":</td><td>\n";
		foreach (bib_get_cat_names_for_group($group) as $cat) {
			echo "<input type=\"checkbox\" name=\"cats[]\" value=\"".$cat["name"]."\"";
			if ($item["cats"]) if (in_array($cat["name"], $item["cats"])) echo " checked";
			echo "> ".$cat["name"]."&nbsp;\n";
		}
		echo "</td></tr>\n";
	}
	echo "</table>\n";
	echo "</td></tr>\n";
?>

</table>

<br>

<input type="submit" value="Submit changes">

</form>

</td></tr></table>

<?php require 'footer.php'; ?>
