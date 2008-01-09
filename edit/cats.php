<?php $title="Categories"; require 'header.php'; ?>

<?php

require '../bib_stuff.php';

// determine action required
$action = extract_var_from_post_get("action");

if ($action == "update") {
	$keys = extract_var_from_post_get("keys");
	$cats = extract_var_from_post_get("cats");
	$num_errors = 0;
	foreach ($keys as $key) {
		$item = array();
		$item["key"] = $key;
		$item["cats"] = (array_key_exists($key, $cats)) ? $cats[$key] : array();
		$error = bib_update_item($item);
		if ($error) {
			echo "<p><font color=\"#cc0000\">[$error]</font></p>\n";
			$num_errors++;
		}
	}
	if ($num_errors == 0) {
		echo "<p><font color=\"#00cc00\">[Update was successful]</font></p>\n";
	}
}

$sort = extract_var_from_post_get("sort");
if (!$sort) $sort = "date";
switch ($sort) {
	case "date": $orderby = "year, month"; break;
	case "key": $orderby = "key"; break;
	case "author": $orderby = "author"; break;
	case "title": $orderby = "title"; break;
}

// filter
$where = extract_var_from_post_get("where");
echo "<form method=\"get\" action=\"cats.php\">\n";
echo "<input type=\"text\" size=\"50\" name=\"where\"".($where?" value=\"".$where."\"":"").">\n";
echo "<input type=\"submit\" value=\"Filter\">\n";
echo "</form>\n";

// connect to database
$dbconn = bib_connect_to_db();
if (!$dbconn) {
	echo "Error: Couldn't connect to database.<br>\n";
	exit;
}

// query database
if (!$where) $where = "true";
$query = "SELECT key FROM bib_items WHERE $where ORDER BY $orderby";
$result = @pg_query($dbconn, $query);
if (!$result) {
	echo "Error: Couldn't get data from database.<br>\n";
	exit;
}

$cat_names = bib_get_cat_names();

?>

<form method="post" action="cats.php">
<input type="hidden" name="action" value="update">

<input type="submit" value="Submit changes">

<br><br>

<table cellpadding="0" cellspacing="0"><tr><td class="alt2">
<table cellpadding="0" cellspacing="2"><tr><td class="bg">
<table cellpadding="2" cellspacing="2">

<tr>
<td class="bg"><a href="cats.php"><b>#</b></a></td>
<td class="bg"><a href="cats.php?sort=key"><b>Key</b></a></td>
<td class="bg"><a href="cats.php?sort=author"><b>Author</b></a></td>
<td class="bg"><a href="cats.php?sort=title"><b>Title</b></a></td>
<td class="bg" colspan="<?php echo count($cat_names)?>"><a href="cats.php"><b>Categories</b></a></td>
</tr>

<?php

// print table rows
$alt = 1;
$y = 0;
while($row = pg_fetch_array($result)){
	$key = $row["key"];
	// get item from database
	$item = bib_fetch_item($key);
	if (!$item) return;
	
	echo "<tr>\n";
	echo "<td class=\"alt$alt\">".($y+1)."</td>\n";
	echo "<td class=\"alt$alt\"><a href=\"edit.php?key=".$item["key"]."\">".$item["key"]."</a></td>\n";
	echo "<td class=\"alt$alt\">".$item["author"]."</td>\n";
	echo "<td class=\"alt$alt\">".$item["title"]."</td>\n";
	
	foreach ($cat_names as $cat) {
		echo "<td class=\"alt$alt\">";
		echo "<input type=\"checkbox\" name=\"cats[$key][]\" value=\"".$cat["name"]."\"";
		if ($action == "update" && in_array($key, $keys)) {
			if (array_key_exists($key, $cats)) if ($cats[$key]) if (in_array($cat["name"], $cats[$key])) echo " checked";
		} else {
			if ($item["cats"]) if (in_array($cat["name"], $item["cats"])) echo " checked";
		}
		echo "><br>".$cat["name"];
		echo "</td>\n";
	}
	
	echo "<input type=\"hidden\" name=\"keys[]\" value=\"$key\">\n";
	
	echo "</tr>\n\n";
	$alt = 3-$alt;
	$y++;
}
?>

</table></dt></tr></table></table>

<p>
Sort by:
[<a href="cats.php?sort=date">date</a>]
[<a href="cats.php?sort=key">key</a>]
[<a href="cats.php?sort=author">author</a>]
[<a href="cats.php?sort=title">title</a>]
</p>

<input type="submit" value="Submit changes">

<?php

// disconnect from database
bib_disconnect_db($dbconn)
 
?>

<?php require 'footer.php'; ?>
