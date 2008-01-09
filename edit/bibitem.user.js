// ==UserScript==
// @name          Edit links for bibitem.php
// @include       *bibitem.php*
// ==/UserScript==

var edit_url = "http://www.prismmodelchecker.org/bib/edit.php?key=";

var nodes = document.evaluate("//span[@class='bib-key']", document, null, XPathResult.ORDERED_NODE_SNAPSHOT_TYPE, null);
if (nodes.snapshotLength > 0) {
	var key = nodes.snapshotItem(0).childNodes[0].data;
	key = key.substring(1, key.length-1);
	nodes = document.evaluate("//span[@class='bib-files']", document, null, XPathResult.ORDERED_NODE_SNAPSHOT_TYPE, null);
	if (nodes.snapshotLength > 0) {
		var node = nodes.snapshotItem(0);
		var parent = node.parentNode;
		var before = node.nextSibling;
		span = document.createElement('span');
		span.appendChild(document.createTextNode("["));
		link = document.createElement('a');
		link.href = edit_url+key;
		link.appendChild(document.createTextNode("edit"));
		span.appendChild(link);
		span.appendChild(document.createTextNode("]"));
		parent.insertBefore(span, before);
	}
}
