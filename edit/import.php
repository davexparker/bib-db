<?php $title="Import"; require 'header.php'; ?>

<?php require '../bib_stuff.php'; ?>

To import from bibtex:

<ul>
<li> upload .bib file to <tt>~dxp/doc/bib</tt> on <tt>qav</tt>
<li> (there are template .bib files (e.g. <tt>.InProceedings</tt>, <tt>.Book</tt>) in <tt>~dxp/doc/bib</tt>)
<br><br>
<li> <tt>ssh qav</tt>
<li> <tt>~dxp/bib-db/bib2postgresql/bib2postgresql < ~dxp/doc/bib/newfile.bib</tt>
<br><br>
<li> now <a href="list.php">edit</a> the new database entry (to set categories etc.)
</ul>

<?php require 'footer.php'; ?>
