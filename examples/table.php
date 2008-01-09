<?php require '../bib_stuff.php'; ?>

<?php
$css_file = extract_var_from_post_get("css_file");
if (!$css_file) $css_file = "table1.css";
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 //EN" "http://www.w3.org/TR/html4/loose.dtd">

<html>
<head>
<title></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link rel="stylesheet" type="text/css" href="base.css">
<link rel="stylesheet" type="text/css" href="<?php echo $css_file; ?>">
</head>
<body bgcolor="#99cccc">

<div style="background-color: #ffffff; margin: 20px; border: solid 2px #333366; padding: 30px;">

<p><span style="color:#999999">[ Full PHP source is in: <span style="color:#009900"><?php echo __FILE__; ?></span>]</span></p>

<h1>CSS code</h1>

<div style="background-color:#ffffcc; border:dotted 2px #cccc99; margin:10px; padding:10px;">

<xmp>
<?php readfile($css_file) ?>
</xmp>

</div>

<h1>Resulting list</h1>


<?php

bib_new_list();
bib_add_all();
bib_sort_by("year");
bib_sort_by("month");
bib_display_list_table("../bib.php?key=%k");

?>

</div>

</body></html>
