<?php $title="List"; require 'header.php'; ?>

<?php

require '../bib_stuff.php';

$sort = extract_var_from_post_get("sort");
if (!$sort) $sort = "date";
switch ($sort) {
	case "date": $orderby = "year, month"; break;
	case "key": $orderby = "key"; break;
	case "type": $orderby = "type"; break;
	case "author": $orderby = "author"; break;
	case "title": $orderby = "title"; break;
}

// query database
$result = bib_db_query("SELECT * FROM bib_items");
if ($result) {
?>

<table cellpadding="0" cellspacing="0"><tr><td class="alt2">
<table cellpadding="0" cellspacing="2"><tr><td class="bg">
<table cellpadding="2" cellspacing="2">

<tr>
<td class="bg"><a href="list.php"><b>#</b></a></td>
<td class="bg"><a href="list.php?sort=key"><b>Key</b></a></td>
<td class="bg"><a href="list.php?sort=type"><b>Type</b></a></td>
<td class="bg"><a href="list.php?sort=author"><b>Author</b></a></td>
<td class="bg"><a href="list.php?sort=title"><b>Title</b></a></td>
<td class="bg" colspan="3"><a href="list.php"><b>Actions</b></a></td>
</tr>

<?php

// print table rows
$alt = 1;
$y = 0;
foreach ($result as $item){
	echo "<tr>";
	echo "<td class=\"alt$alt\">".($y+1)."</td>";
	echo "<td class=\"alt$alt\">".$item["key"]."</td>";
	echo "<td class=\"alt$alt\">".$item["type"]."</td>";
	echo "<td class=\"alt$alt\">".$item["author"]."</td>";
	echo "<td class=\"alt$alt\">".$item["title"]."</td>";
	echo "<td class=\"alt$alt\">[<a href=\"view.php?key=".$item["key"]."\">view</a>]</td>";
	echo "<td class=\"alt$alt\">[<a href=\"edit.php?key=".$item["key"]."\">edit</a>]</td>";
	echo "<td class=\"alt$alt\">[<a href=\"delete.php?key=".$item["key"]."\">delete</a>]</td>";
	echo "</tr>\n";
	$alt = 3-$alt;
	$y++;
}

?>

</table></td></tr></table></table>

<p>
Sort by:
[<a href="list.php?sort=date">date</a>]
[<a href="list.php?sort=key">key</a>]
[<a href="list.php?sort=type">type</a>]
[<a href="list.php?sort=author">author</a>]
[<a href="list.php?sort=title">title</a>]
</p>

<?php
}
?>

<?php require 'footer.php'; ?>
