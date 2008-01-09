<?php require '../bib_stuff.php'; ?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 //EN" "http://www.w3.org/TR/html4/loose.dtd">

<html>
<head>
<title></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link rel="stylesheet" type="text/css" href="base.css">
<link rel="stylesheet" type="text/css" href="list1.css">
</head>
<body bgcolor="#99cccc">

<div style="background-color: #ffffff; margin: 20px; border: solid 2px #333366; padding: 30px;">

<p><span style="color:#999999">[ Full PHP source is in: <span style="color:#009900"><?php echo __FILE__; ?></span>]</span></p>

<h1>PHP code</h1>

<div style="background-color:#ffffcc; border:dotted 2px #cccc99; margin:10px; padding:10px;">

<xmp>
bib_new_list();

bib_new_section("<h3>Book</h3>");

bib_add("cat", "dave");
bib_filter("type", "book");

bib_new_section("<h3>Ph.D. Thesis</h3>");

bib_add("cat", "dave");
bib_filter("type", "phdthesis");

bib_new_section();

bib_add("cat", "dave");
bib_filter("type", "!", "book", "phdthesis");
bib_sort_by("year", "desc", "<h3>Papers (%s)</h3>");
bib_sort_by("month", "desc");

bib_display_list_ul();
</xmp>

</div>

<h1>Resulting list</h1>

<?php

bib_new_list();

bib_new_section("<h3>Book</h3>");

bib_add("cat", "dave");
bib_filter("type", "book");

bib_new_section("<h3>Ph.D. Thesis</h3>");

bib_add("cat", "dave");
bib_filter("type", "phdthesis");

bib_new_section();

bib_add("cat", "dave");
bib_filter("type", "!", "book", "phdthesis");
bib_sort_by("year", "desc", "<h3>Papers (%s)</h3>");
bib_sort_by("month", "desc");

bib_display_list_ul();

?>

</div>

</body></html>
