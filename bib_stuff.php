<?php

//-----------------------------------------------------------------------------
// Includes
//-----------------------------------------------------------------------------

require "config.php";
require $bib_connect_file;

//-----------------------------------------------------------------------------
// Global variables
//-----------------------------------------------------------------------------

// Month names
// NB: There are 2 options for "undefined" corresponding to indices 0 and 13.
//     Only matters if you care where an item appears when sorting by month.
$bib_month_names = array("0"=>"", "1"=>"January", "2"=>"February", "3"=>"March", "4"=>"April", "5"=>"May", "6"=>"June", "7"=>"July", "8"=>"August", "9"=>"September", "10"=>"October", "11"=>"November", "12"=>"December", "13"=>"");

// Main database fields for bibliography items (excludes "key", "cats")
$bib_item_fields = array("type", "address", "author", "booktitle", "chapter", "edition", "editor", "institution", "journal", "month", "note", "number", "organization", "pages", "publisher", "school", "series", "title", "type2", "volume", "year", "url", "filename", "abstract", "links", "synopsis");

// Subset of database fields (from $bib_item_fields above) that are standard bibtex fields
$bib_item_fields_bibtex = array("type", "address", "author", "booktitle", "chapter", "edition", "editor", "institution", "journal", "month", "note", "number", "organization", "pages", "publisher", "school", "series", "title", "type2", "volume", "year");

// Bibliography item types (à la bibtex)
$bib_item_types = array("inproceedings","article","book","inbook","phdthesis","mastersthesis","techreport","unpublished","proceedings");

// Default database fields to use for searching
$bib_list_search_fields_default = array("key", "address", "author", "booktitle", "chapter", "editor", "institution", "journal", "month", "note", "number", "organization", "pages", "publisher", "school", "series", "title", "type2", "volume", "year", "synopsis");

// Storage for info about a bib item list
$bib_list_item_link = "";
$bib_list_item_start = "";
$bib_list_item_end = "";
$bib_list_block_start = "";
$bib_list_block_end = "";
$bib_list_sections = NULL;
$bib_list_search_enabled = false;
$bib_list_search_string = NULL;
$bib_list_search_fields = NULL;
$bib_list_search_words = NULL;
$bib_list_search_preg = NULL;
$bib_list_search_sql = NULL;
$bib_list_counter = 1;
$bib_list_alt = 1;
$bib_list_item_extra_links = NULL;

//-----------------------------------------------------------------------------
// General utiltity functions
//-----------------------------------------------------------------------------

// Extract a variable from POST/GET

function extract_var_from_post_get($var)
{
	$res = "";
	if (isset($_POST[$var])) {
		$res = $_POST[$var];
	}
	else if (isset($_GET[$var])) {
                $res = $_GET[$var];
        }
	if (is_string($res)) {
		$res = stripslashes($res);
	}
	return $res;
}

//-----------------------------------------------------------------------------

// Extract multiple variables from POST/GET

function extract_vars_from_post_get(&$res, $arr)
{
	foreach ($arr as $var) {
		$res[$var] = "";
		if (isset($_POST[$var])) {
			$res[$var] = $_POST[$var];
		}
		else if (isset($_GET[$var])) {
                        $res[$var] = $_GET[$var];
                }
		if (is_string($res[$var])) {
			$res[$var] = stripslashes($res[$var]);
		}
	}
	return $res;
}

//-----------------------------------------------------------------------------

// Extract "key" variable from POST/GET

function extract_key_from_post_get($field = "key")
{
	// get variable
	$key = extract_var_from_post_get($field);
	// replace ' 's with '+'s because they get mangled in the url
	$key = preg_replace("/ /", "+", $key);
	
	return $key;
}

//-----------------------------------------------------------------------------

// Check validity of a key

function is_key_valid($key)
{
	return (preg_match("/^[A-Za-z0-9_\-+]+$/", $key) === 1);
}

//-----------------------------------------------------------------------------

// Log an error message to the error log.
// If $dbconn is provided, last DB error will also be logged.

function bib_log_error($err_msg, $dbconn = NULL)
{
	global $bib_error_file;
	// open log file
	$fout = fopen($bib_error_file, "a");
	if ($fout) {
		// write date/time
		fwrite($fout, "\nTime: ".date("D M j G:i:s T Y")."\n");
		// write error message
		fwrite($fout, "Error: ".$err_msg.".\n");
		// if a db error, write db error message too
		if ($dbconn) fwrite($fout, "Postgresql error: ".pg_last_error($dbconn)."\n");
		// write calling url
		fwrite($fout, "URL: ".$_SERVER["PHP_SELF"]);
		if (array_key_exists("QUERY_STRING", $_SERVER)) if ($_SERVER["QUERY_STRING"]) fwrite($fout, "?".$_SERVER["QUERY_STRING"]);
		fwrite($fout, "\n");
		// close log file
		fclose($fout);
	}
}

//-----------------------------------------------------------------------------

// Execute an arbitrary SQL query and return results in an array.
// Result is NULL in case of error.
// By default array is 2D but if there is only one column it is 1D.
// Existing DB connection can be passed in to be reused.

function bib_db_query($query, $existing_dbconn = NULL)
{
	// connect to database
	$dbconn = $existing_dbconn ? $existing_dbconn : bib_connect_to_db();
	if (!$dbconn) { bib_log_error("Couldn't connect to database"); return NULL; }
	
	// query database
	$result = @pg_query($dbconn, $query);
	if (!$result) { bib_log_error("Couldn't execute query \"$query\"", $dbconn); return NULL; }
	
	// initialise result array
	$res = array();
	
	// for a single column of results, build a 1D array
	if (pg_num_fields($result) <= 1) {
		while($row = pg_fetch_array($result)) {
			// store single row item in array, de-escaping if necessary
			$res[] = (is_string($row[0])) ? stripslashes($row[0]) : $row[0];
		}
	}
	// for multiple columns, build a 2D array
	else {
		$res = array();
		while($row = pg_fetch_array($result)) {
			// de-escape any strings
			foreach ($row as $key => $val) {
				if (is_string($val))
					$row[$key] = stripslashes($val);
			}
			// store row in array
			$res[] = $row;
		}
	}
	
	// disconnect from database
	if (!$existing_dbconn) bib_disconnect_db($dbconn);
	
	// return result
	return $res;
}

//-----------------------------------------------------------------------------

// Execute an arbitrary SQL update query and return number of affected rows.
// Result is NULL in case of error.
// Existing DB connection can be passed in to be reused.

function bib_db_update_query($query, $existing_dbconn = NULL)
{
	// connect to database
	$dbconn = $existing_dbconn ? $existing_dbconn : bib_connect_to_db();
	if (!$dbconn) { bib_log_error("Couldn't connect to database"); return NULL; }
	
	// query database
	$result = @pg_query($dbconn, $query);
	if (!$result) { bib_log_error("Couldn't execute query \"$query\"", $dbconn); return NULL; }
	
	// disconnect from database
	if (!$existing_dbconn) bib_disconnect_db($dbconn);
	
	// return result
	return pg_affected_rows($result);
}

//-----------------------------------------------------------------------------

// Get array of category names.
// If anything goes wrong, just return empty array.

function bib_get_cat_names()
{
	$result = bib_db_query("SELECT name,description FROM bib_cats ORDER BY cat_group, name");
	if (!$result) $result = array();
	return $result;
}

// Get array of category names for one group.
// If anything goes wrong, just return empty array.

function bib_get_cat_names_for_group($group)
{
	$group = pg_escape_string($group);
	$result = bib_db_query("SELECT name,description FROM bib_cats WHERE cat_group='$group' ORDER BY name");
	if (!$result) $result = array();
	return $result;
}

// Get array of category groups.
// If anything goes wrong, just return empty array.

function bib_get_cat_groups()
{
	$result = bib_db_query("SELECT DISTINCT cat_group FROM bib_cats");
	if (!$result) $result = array();
	return $result;
}

//-----------------------------------------------------------------------------

// Construct an SQL clause with which to select bib items
// First argument is field.
// Subsequent arguments are possible values for field, e.g.:
//   bib_build_sql_clause("type", "article", "inproceedings").
// If first argument is "!", clause is negated, e.g.:
//   bib_build_sql_clause("type", "!", "techreport", "unpublished").

function bib_build_sql_clause($args)
{
	global $bib_item_fields;
	
	// check arguments for errors
	if (count($args) == 0) {
		bib_log_error("No field provided to construct SQL clause");
		return "";
	}
	$field = $args[0];
	if (!(in_array($field, $bib_item_fields) || $field == "cat" || $field == "key")) {
		bib_log_error("No such field \"".$field."\"");
		return "";
	}
	if (count($args) == 1) {
		bib_log_error("Incomplete information to construct SQL clause");
		return "";
	}
	// check for negation
	if ($args[1] == "!") {
		$negate = true;
		$start = 2;
	} else {
		$negate = false;
		$start = 1;
	}
	// collate elements of list to build clause
	$n = count($args);
	$clauses = array();
	for ($i = $start; $i < $n; $i++) {
		if ($field != "cat") {
			$clauses []= "bib_items.$field='".pg_escape_string($args[$i])."'";
		} else {
			$clauses []= "position('|".pg_escape_string($args[$i])."|' in cats)>0";
		}
	}
	// compose elements
	$clause =  implode(" OR ", $clauses);
	if ($negate) $clause = "NOT(".$clause.")";
	
	return $clause;
}

//-----------------------------------------------------------------------------

// Do some processing of the search string

function bib_build_search_info($search, $fields)
{
	global $bib_item_fields;
	global $bib_month_names;
	global $bib_list_search_enabled;
	global $bib_list_search_string;
	global $bib_list_search_fields;
	global $bib_list_search_words;
	global $bib_list_search_preg;
	global $bib_list_search_sql;
	
	$search = trim($search);
	
	if ($search == NULL || $search == "") {
		$bib_list_enabled = false;
		$bib_list_search_string = NULL;
		$bib_list_search_fields = NULL;
		$bib_list_search_words = NULL;
		$bib_list_search_preg = NULL;
		$bib_list_search_sql = NULL;
	} else {
		// set enabled flag
		$bib_list_search_enabled = true;
		// store search string
		$bib_list_search_string = trim($search);
		// store search fields
		$bib_list_search_fields = $fields;
		// separate search string into words - note more complex if there are "s
		if (!strstr($bib_list_search_string, "\"")) {
			$bib_list_search_words = explode(" ", $bib_list_search_string);
		}
		else {
			// split up into blocks in/out-side quotes
			$tmp = split("\"", trim($bib_list_search_string));
			$n = count($tmp);
			$bib_list_search_words = array();
			for ($i = 0; $i <= ((int)($n/2)); $i++) {
				// split odd elements of list - i.e. stuff outside quotes
				$tmp2 = explode(" ", trim($tmp[2*$i]));
				foreach ($tmp2 as $tmp3) if ($tmp3 != "") $bib_list_search_words[] = $tmp3;
				// don't split even elements of list - i.e. stuff inside quotes
				if (2*$i+1 < $n) if ($tmp[2*$i+1] != "") $bib_list_search_words[] = $tmp[2*$i+1];
			}
		}
		// build regexp to search for *any* of these words (in html)
		$bib_list_search_preg = "";
		foreach ($bib_list_search_words as $word)
			$bib_list_search_preg = ($bib_list_search_preg==""?"":$bib_list_search_preg."|").preg_quote(htmlentities($word, ENT_COMPAT, "UTF-8"), "/");
		// build sql query to search for *all* of these words
		$bib_list_search_sql = "";
		foreach ($bib_list_search_words as $word) {
			$clause = "key ~* '".pg_escape_string(preg_quote($word, "/"))."'";
			foreach ($bib_item_fields as $field) {
				if (in_array($field, $bib_list_search_fields)) {
					if ($field == "month") {
						$months = array();
						foreach ($bib_month_names as $i => $month) if (stristr($month, $word)) $months[] = "month=$i";
						if ($months) $clause = $clause." OR ".implode(" OR ", $months);
					} else if ($field == "year") {
						$clause = $clause." OR CAST($field AS TEXT) ~* '".pg_escape_string(preg_quote($word, "/"))."'";
					} else {
						$clause = $clause." OR $field ~* '".pg_escape_string(preg_quote($word, "/"))."'";
					}
				}
			}
			$bib_list_search_sql = ($bib_list_search_sql==""?"":$bib_list_search_sql." AND ")."(".$clause.")";
		}
	}
}

//-----------------------------------------------------------------------------
// Functions to access/modify bibliography items
//-----------------------------------------------------------------------------

// Fetch bibliography item.
// Returns array of fields.
// If anything goes wrong, return NULL.

function bib_fetch_item($key)
{
	// check key is valid
	if (!is_key_valid($key)) {
		bib_log_error("Cannot fetch item \"$key\" - key is invalid");
		return NULL;
	}
	
	// query database
	$key = pg_escape_string($key);
	$result = bib_db_query("SELECT * FROM bib_items WHERE key='$key';");
	if (!$result) return NULL;
	$item = $result[0];
	
	// expand category information
	$item["cats"] = explode("|", $item["cats"]);
	
	return $item;
}

//-----------------------------------------------------------------------------

// Add a new bibliography item.
// Returns any error message, empty return denotes success.

function bib_add_item($key, $type)
{
	global $bib_item_types;
	
	// check key is valid
	if (!is_key_valid($key)) {
		return "Key \"$key\" is invalid";
	}
	
	// check type is present/valid
	if (!in_array($type, $bib_item_types)) {
		return "Invalid type \"$type\"";
	}
	
	// update db
	$key = pg_escape_string($key);
	$type = pg_escape_string($type);
	$result = bib_db_update_query("INSERT INTO bib_items (key, type, month) VALUES ('$key', '$type', '13')");
	if ($result != 1) {
		return "Could not add item \"$key\" - duplicate key perhaps";
	}
}

//-----------------------------------------------------------------------------

// Delete a bibliography item.
// Returns any error message, empty return denotes success.

function bib_delete_item($key)
{
	// check key is valid
	if (!is_key_valid($key)) {
		return "Key \"$key\" is invalid";
	}
	
	// update db
	$key = pg_escape_string($key);
	$result = bib_db_update_query("DELETE FROM bib_items WHERE key='$key'");
	if ($result != 1) {
		return "Could not delete item \"$key\"";
	}
}

// Rename a new bibliography item.
// Returns any error message, empty return denotes success.

function bib_rename_item($key, $newkey)
{
	// check keys are valid
	if (!is_key_valid($key)) {
		return "Key \"$key\" is invalid";
	}
	if (!is_key_valid($newkey)) {
		return "New key \"$newkey\" is invalid";
	}
	
	// update db
	$key = pg_escape_string($key);
	$newkey = pg_escape_string($newkey);
	$result = bib_db_update_query("UPDATE bib_items SET key='$newkey' WHERE key='$key'");
	if ($result != 1) {
		return "Could not rename item \"$key\" to \"$newkey\" - duplicate key perhaps";
	}
}

//-----------------------------------------------------------------------------

// Update bibliography item.
// Takes array of fields $item.
// This should also include an index "key" with the key.
// Category information can also be included as an array under index "cats".
// Returns any error message, empty return denotes success.

function bib_update_item($item)
{
	global $bib_item_fields;
	
	// check key is present and valid
	if (!array_key_exists("key", $item)) {
		return "No key supplied";
	}
	if (!is_key_valid($item["key"])) {
		return "Key \"".$item["key"]."\" is invalid";
	}
	
	// do some checks on fields supplied
	if (array_key_exists("year", $item)) if ($item["year"]) if (!is_numeric($item["year"])) return "Invalid year";
	if (array_key_exists("month", $item)) if ($item["month"]) if (!is_numeric($item["month"])) return "Invalid month";
	
	// build update query
	$set = "";
	// go through all the normal fields
	foreach ($item as $item_key => $item_val) {
		if (in_array($item_key, $bib_item_fields)) {
			if ($set != "") $set .= ", ";
			if (is_string($item_val)) $item_val = pg_escape_string($item_val);
			if (($item_key == "month" || $item_key == "year") && $item_val == "") {
				$set .= "$item_key=NULL";
			} else {
				$set .= "$item_key='$item_val'";
			}
		}
	}
	// then add category info
	if (array_key_exists("cats", $item)) {
		if ($set != "") $set .= ", ";
		if ($item["cats"]) {
			foreach ($item["cats"] as $i => $cat) if (is_string($cat)) $item["cats"][$i] = pg_escape_string($cat);
			$set .= "cats='|".implode("|", $item["cats"])."|'";
		} else {
			$set .= "cats=''";
		}
	}
	// build/execute whole query
	if (strlen($set) > 0) {
		$item["key"] = pg_escape_string($item["key"]);
		$result = bib_db_update_query("UPDATE bib_items SET $set WHERE key='".$item["key"]."'");
		if ($result != 1) {
			return "Could not update item \"$key\"";
		}
	}
}

//-----------------------------------------------------------------------------

// Gets title of bibliography item.
// We only really need a function for this because of "inbook"'s use of the "title" field

function bib_get_title($item)
{
	if (!$item) return "";
	return ($item["type"] != "inbook") ? $item["title"] : $item["chapter"];
}

//-----------------------------------------------------------------------------

// Display bibliography item.
// Item details passed in in array $item.
// Link for each item (optional) is given by $link, where %k expands to the key.

function bib_display_item($item, $link = NULL, $indent = 0)
{
	global $bib_month_names;
	global $bib_list_search_enabled;
	global $bib_list_search_fields;
	global $bib_list_search_preg;
	global $bib_list_item_extra_links;
	$tabs = str_repeat("\t", $indent);
	
	// html-ise everything
	// (note: some fields, e.g. abstract, links, are html anyway and shouldn't be html-ised again,
	//        but none of those are displayed by this function)
	// also convert months to month names
	foreach ($item as $index => $val) {
		if ($index !== "month") {
			if (is_string($val)) $item[$index] = htmlentities($val, ENT_COMPAT, "UTF-8");
		} else {
			$item[$index] = htmlentities($bib_month_names[$val], ENT_COMPAT, "UTF-8");
		}
	}
	
	// remove any trailing full stop on "note" (will add anyway)
	if (strlen($item["note"]) > 0) if (substr($item["note"], strlen($item["note"])-1) == ".") $item["note"] = substr($item["note"], 0, strlen($item["note"])-1);
	
	// create search-highlighted version of $item
	// (nb: don't just overwrite because for some things, e.g. anchors, links, we don't want it)
	if ($bib_list_search_enabled) {
		foreach ($item as $item_index => $item_val) {
			if (in_array($item_index, $bib_list_search_fields))
				$item_hi[$item_index] = preg_replace("/$bib_list_search_preg/i", "<span class=\"bib-highlight\">\\0</span>", $item_val);
			else
				$item_hi[$item_index] = $item_val;
		}
	}
	else {
		$item_hi = $item;
	}
	
	// key
	echo "$tabs<a name=\"".$item["key"]."\"></a>\n";
	echo "$tabs<span class=\"bib-key\">[".$item_hi["key"]."]</span>\n";
	// author
	echo "$tabs<span class=\"bib-auth\">";
	if ($item_hi["author"])
		echo $item_hi["author"].".";
	if (($item["type"] == "book" || $item["type"] == "proceedings") && $item_hi["editor"]) {
		if ($item_hi["author"]) echo " ";
		echo $item_hi["editor"]." (editor";
		if (strstr($item_hi["editor"], " and ")) echo "s";
		echo ")";
	}
	echo "</span>\n";
	// title
	$title = ($item["type"] != "inbook") ? $item_hi["title"] : $item_hi["chapter"];
	if ($link) {
		$link = preg_replace("/%k/", $item["key"], $link);
		echo "$tabs<span class=\"bib-title\"><a href=\"$link\">".$title."</a>.</span>\n";
	} else {
		echo "$tabs<span class=\"bib-title\">".$title.".</span>\n";
	}
	// src
	$src = "";
	switch ($item["type"]) {
	
	case "inproceedings":
		$src .= "In ";
		if ($item_hi["editor"]) {
			$src .= $item_hi["editor"]." (editor";
			if (strstr($item_hi["editor"], " and ")) $src .= "s";
			$src .= ") ";
		}
		$src .= "<em>".$item_hi["booktitle"]."</em>";
		if ($item_hi["volume"] && $item_hi["series"]) $src .= ", volume ".$item_hi["volume"]." of ".$item_hi["series"];
		if ($item_hi["pages"]) $src .= ", pages ".$item_hi["pages"];
		if ($item_hi["organization"]) $src .= ", ".$item_hi["organization"];
		if ($item_hi["publisher"]) $src .= ", ".$item_hi["publisher"];
		if ($item_hi["note"]) $src .= ". ".$item_hi["note"];
		break;
		
	case "article":
		$src .= "<em>".$item_hi["journal"]."</em>";
		if ($item_hi["volume"]) $src .= ", ".$item_hi["volume"];
		if ($item_hi["number"]) $src .= "(".$item_hi["number"].")";
		if ($item_hi["pages"]) $src .= ", pages ".$item_hi["pages"];
		if ($item_hi["publisher"]) $src .= ", ".$item_hi["publisher"];
		if ($item_hi["note"]) $src .= ". ".$item_hi["note"];
		break;
		
	case "book":
	case "proceedings":
		if ($item_hi["volume"] && $item_hi["series"]) $src .= "Volume ".$item_hi["volume"]." of ".$item_hi["series"].". ";
		if ($item_hi["publisher"]) $src .= $item_hi["publisher"];
		if ($item_hi["note"]) $src .= ". ".$item_hi["note"];
		break;
		
	case "inbook":
		$src .= "In ";
		if ($item_hi["editor"]) {
			$src .= $item_hi["editor"]." (editor";
			if (strstr($item_hi["editor"], " and ")) $src .= "s";
			$src .= ") ";
		}
		$src .= "<em>".$item_hi["title"]."</em>";
		if ($item_hi["volume"] && $item_hi["series"]) $src .= ", volume ".$item_hi["volume"]." of ".$item_hi["series"];
		if ($item_hi["pages"]) $src .= ", pages ".$item_hi["pages"];
		if ($item_hi["organization"]) $src .= ", ".$item_hi["organization"];
		if ($item_hi["publisher"]) $src .= ", ".$item_hi["publisher"];
		if ($item_hi["note"]) $src .= ". ".$item_hi["note"];
		break;
		
	case "phdthesis":
		$src .= ($item_hi["type2"] ? $item_hi["type2"] : "Ph.D. thesis").", ".$item_hi["school"];
		if ($item_hi["note"]) $src .= ". ".$item_hi["note"];
		break;
		
	case "mastersthesis":
		$src .= ($item_hi["type2"] ? $item_hi["type2"] : "Masters thesis").", ".$item_hi["school"];
		if ($item_hi["note"]) $src .= ". ".$item_hi["note"];
		break;
		
	case "techreport":
		$src .= ($item_hi["type2"] ? $item_hi["type2"] : "Technical report")." ".$item_hi["number"].", ".$item_hi["institution"];
		if ($item_hi["note"]) $src .= ". ".$item_hi["note"];
		break;
		
	case "unpublished":
		if ($item_hi["note"]) $src .= $item_hi["note"];
		break;
		
	default:
		bib_log_error("Unknown bibliography item type \"".$item["type"]."\"");
		break;
	}
	if ($src) echo "$tabs<span class=\"bib-src\">$src.</span>\n";
	// date
	echo "$tabs<span class=\"bib-date\">";
	if ($item_hi["month"]) echo "<span class=\"bib-month\">".$item_hi["month"]." </span>";
	echo "<span class=\"bib-year\">".$item_hi["year"].".</span></span>\n";
	// files
	$item_files = bib_get_item_files($item);
	//if (count($item_files) > 0) {
		echo "$tabs<span class=\"bib-files\">\n";
		foreach ($item_files as $item_file) {
			echo "$tabs\t[<a href=\"".$item_file["url"]."\">".$item_file["ext"]."</a>]\n";
		}
		echo "$tabs</span>\n";
	//}
	
	// print any extra links requested
	if ($bib_list_item_extra_links) {
		echo "\t<span class=\"bib-extralinks\">\n";
		foreach ($bib_list_item_extra_links as $link_name => $link_url) {
			echo "\t\t[<a href=\"".preg_replace("/%k/", $item["key"], $link_url)."\">".$link_name."</a>]\n";
		}
		echo "\t</span>\n";
	}
	
	// print synopsis
	if ($item_hi["synopsis"]) {
		echo "$tabs<span class=\"bib-synop\">[".$item_hi["synopsis"]."]</span>\n";
	}
}

function bib_display_item_from_key($key, $link = NULL, $indent = 0)
{
	$item = bib_fetch_item($key);
	if ($key != NULL && $key != "") bib_display_item($item, $link, $indent);
}

//-----------------------------------------------------------------------------

// Display bibliography item.
// Item details passed in in array $item.
// Link for each item (optional) is given by $link, where %k expands to the key.

function bib_display_item_detailed($item, $link = NULL, $indent = 0)
{
	global $bib_images_dir;
	global $bib_images_url;
	$table_alt = 2;
	
	echo "<div class=\"bibbox\"><table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">\n";
	echo "\n<tr class=\"bibbox-row-alt$table_alt\">\n<td class=\"bibbox-td\">\n";
	echo "<div class=\"bibbox-details\">\n";
	bib_display_item($item, "");
	echo "</div>\n";
	
	$item_files = bib_get_item_files($item);
	if ($item_files) if (count($item_files) > 0) {
		echo "</td>\n</tr>\n";
		echo "\n<tr class=\"bibbox-row-alt".($table_alt = 3 - $table_alt)."\">\n<td class=\"bibbox-td\">\n";
		echo "<div class=\"bibbox-downloads\">\n";
		echo "<b>Downloads:</b>\n";
		foreach ($item_files as $item_file) {
			switch ($item_file["ext"]) {
				case "ps": $image = "ps.png"; break;
				case "ps.gz": $image = "ps.png"; break;
				case "pdf": $image = "pdf.png"; break;
				case "bib": $image = "tex.png"; break;
				default: $image = "file.gif"; break;
			}
			echo "&nbsp;";
			echo "<a href=\"".$item_file["url"]."\">";
			echo "<img border=\"0\" src=\"".$bib_images_url."/$image\" alt=\"".$item_file["ext"]."\" style=\"vertical-align:middle;\">";
			echo "</a> ";
			echo "<b>".$item_file["ext"]."</b> ";
			if (array_key_exists("filesize", $item_file)) if ($item_file["filesize"]) echo "(".$item_file["filesize"].") ";
			echo "\n";
		}
		echo "</div>";
	}
	
	$notes = array();
	if (array_key_exists("links", $item)) if ($item["links"]) {
		// replace citations of form [] with links
		$thispage = $_SERVER["PHP_SELF"];
		$item["links"] = preg_replace("/\[([A-Za-z0-9_\-+]+)\]/", "[<a href=\"$thispage?key=\\1\">\\1</a>]", $item["links"]);
		$notes[] = $item["links"];
	}
	if (array_key_exists("publisher", $item)) if (substr_count($item["publisher"], "Springer")) {
		$notes[] = "The original publication is available at <a href=\"http://www.springerlink.com/\">www.springerlink.com</a>.";
	}
	if ((array_key_exists("series", $item) && (strcasecmp($item["series"], "Electronic Notes in Theoretical Computer Science") === 0 || strcasecmp($item["series"], "ENTCS") === 0))
	    || (array_key_exists("journal", $item) && (strcasecmp($item["journal"], "Electronic Notes in Theoretical Computer Science") === 0 || strcasecmp($item["journal"], "ENTCS") === 0))) {
		$notes[] = "ENTCS is available at <a href=\"http://www.sciencedirect.com/science/journal/15710661\">www.sciencedirect.com/science/journal/15710661</a>.";
	}
	if (count($notes) > 0) {
		echo "</td>\n</tr>\n";
		echo "\n<tr class=\"bibbox-row-alt".($table_alt = 3 - $table_alt)."\">\n<td class=\"bibbox-td\">\n";
		echo "<div class=\"bibbox-notes\">\n";
		echo "<b>Notes:</b>\n";
		foreach ($notes as $note) {
			echo $note."\n";
		}
		echo "</div>";
	}
	
	/*if (array_key_exists("links", $item)) if ($item["links"]) {
		echo "</td>\n</tr>\n";
		echo "\n<tr class=\"bibbox-row-alt".($table_alt = 3 - $table_alt)."\">\n<td class=\"bibbox-td\">\n";
		echo "<div class=\"bibbox-links\">\n";
		// replace citations of form [] with links
		$thispage = $_SERVER["PHP_SELF"];
		$item["links"] = preg_replace("/\[([A-Za-z0-9_\-+]+)\]/", "[<a href=\"$thispage?key=\\1\">\\1</a>]", $item["links"]);
		echo $item["links"]."\n";
		echo "</div>";
	}*/
	
	echo "</td>\n</tr>\n";
	echo "\n<tr class=\"bibbox-row-alt".($table_alt = 3 - $table_alt)."\">\n<td class=\"bibbox-td\">\n";
	echo "<div class=\"bibbox-searches\">\n";
	echo "<b>Links:</b>\n";
	$title = ($item["type"] != "inbook") ? $item["title"] : $item["chapter"];
	$url = "http://www.google.com/search?q=\"".rawurlencode($title)."\"&ie=UTF-8&oe=UTF-8&hl=en&btnG=Search";
	echo "[<a href=\"".htmlentities($url, ENT_COMPAT, "UTF-8")."\">Google</a>]\n";
	$url = "http://scholar.google.com/scholar?q=\"".rawurlencode($title)."\"&ie=UTF-8&oe=UTF-8&hl=en&btnG=Search";
	echo "[<a href=\"".htmlentities($url, ENT_COMPAT, "UTF-8")."\">Google Scholar</a>]\n";
	$url = "http://www.google.com/search?q=site:citeseer.ist.psu.edu+".rawurlencode($title)."&ie=UTF-8&oe=UTF-8&hl=en&btnG=Search";
	echo "[<a href=\"".htmlentities($url, ENT_COMPAT, "UTF-8")."\">CiteSeer</a>]\n";
	echo "</div>";
	
	if (array_key_exists("url", $item)) if ($item["url"]) {
		echo "</td>\n</tr>\n";
		echo "\n<tr class=\"bibbox-row-alt".($table_alt = 3 - $table_alt)."\">\n<td class=\"bibbox-td\">\n";
		echo "<div class=\"bibbox-url\">\n";
		echo "<b>Available from:</b> \n";
		echo "<a href=\"".htmlentities($item["url"], ENT_COMPAT, "UTF-8")."\">".htmlentities($item["url"], ENT_COMPAT, "UTF-8")."</a>\n";
		echo "</div>";
	}
	
	if (array_key_exists("abstract", $item)) if ($item["abstract"]) {
		echo "</td>\n</tr>\n";
		echo "\n<tr class=\"bibbox-row-alt".($table_alt = 3 - $table_alt)."\">\n<td class=\"bibbox-td\">\n";
		echo "<div class=\"bibbox-abstract\">\n";
		if ($item["type"] == "book" || $item["type"] == "inbook")
			if (file_exists($bib_images_dir."/".$item["key"].".gif"))
				echo "<img style=\"border:0;float:left;margin:0px 10px 10px 10px;\" src=\"".$bib_images_url."/bib/".$item["key"].".gif\" alt=\"Front cover\">\n";
			if (file_exists($bib_images_dir."/".$item["key"].".jpg"))
				echo "<img style=\"border:0;float:left;margin:0px 10px 10px 10px;\" src=\"".$bib_images_url."/bib/".$item["key"].".jpg\" alt=\"Front cover\">\n";
			if (file_exists($bib_images_dir."/".$item["key"].".png"))
				echo "<img style=\"border:0;float:left;margin:0px 10px 10px 10px;\" src=\"".$bib_images_url."/bib/".$item["key"].".png\" alt=\"Front cover\">\n";
		echo "<b>Abstract.</b>\n".$item["abstract"]."\n";
		echo "</div>\n";
	}

	echo "</td>\n</tr>\n\n</table></div>\n";
}

//-----------------------------------------------------------------------------

// Get local files associated with bibliography item
// ($item only actually needs to contain "filename" and "key")
// (can specify if bibtex should be included too)

function bib_get_item_files($item, $bibtex = true)
{
	global $bib_files_dir;
	global $bib_bibtex_dir;
	global $bib_files_url;
	global $bib_bibtex_url;
	
	$item_files = array();
	
	if (array_key_exists("filename", $item)) if ($item["filename"]) {
		foreach (array("ps", "ps.gz", "pdf") as $ext) {
			$filename = "$bib_files_dir/".$item["filename"].".$ext";
			if (file_exists($filename)) {
				$url = $bib_files_url."/".$item["filename"].".$ext";
				$filesize = filesize($filename)/1024;
				$filesize = ($filesize > 1024) ? (round($filesize/1024, 2)." MB") : (round($filesize)." KB");
				$item_files []= array("filename"=>$filename, "url"=>$url, "ext"=>$ext, "filesize"=>$filesize);
			}
		}
	}
	if ($bibtex) {
		$filename = "$bib_bibtex_dir/".$item["key"].".bib";
		if (file_exists($filename)) {
			$url = $bib_bibtex_url."/".$item["key"].".bib";
			$item_files []= array("filename"=>$filename, "url"=>$url, "ext"=>"bib");
		}
	}
	
	return $item_files;
}

//-----------------------------------------------------------------------------
// Main API for creating/displaying lists of bibliography items
//-----------------------------------------------------------------------------

// Create a new list of bibliography items.

function bib_new_list($search = NULL, $fields = NULL)
{
	global $bib_list_sections;
	global $bib_list_search_fields_default;
	
	$bib_list_sections = array();
	bib_new_section();
	
	// if no list of search fields specified, use some sensible defaults
	if ($fields == NULL) $fields = $bib_list_search_fields_default;
	
	// if search info provided, do some processing of it
	if ($search != NULL) if ($search != "") bib_build_search_info($search, $fields);
}

// Create a new section within the current list of bibliography items.

function bib_new_section($header = "", $headerlink = "")
{
	global $bib_list_sections;
	
	// initialise section:
	// select is "false" - section is initially empty
	// list of sort operations is empty
	$section = array("header"=>$header, "headerlink"=>$headerlink, "select"=>"false", "sortby_list"=>array());
	$bib_list_sections []= $section;
}

//-----------------------------------------------------------------------------

// Add to the current list of bibliography items.
// First argument is field to select on.
// Subsequent arguments are possible values for field, e.g.:
//   bib_add("type", "article", "inproceedings").
// If first argument is "!", selection is negated, e.g.:
//   bib_add("type", "!", "techreport", "unpublished").

function bib_add()
{
	global $bib_list_sections;
	
	// construct corresponding sql clause
	$clause = bib_build_sql_clause(func_get_args());
	// modify select info for current section
	$section = &$bib_list_sections[count($bib_list_sections)-1];
	$section["select"] = "(".$section["select"].") OR (".$clause.")";
}

//-----------------------------------------------------------------------------

// Add to the current list of bibliography items based on an sql expression.

function bib_add_sql($sql)
{
	global $bib_list_sections;
	
	// modify select info for current section
	$section = &$bib_list_sections[count($bib_list_sections)-1];
	$section["select"] = "(".$section["select"].") OR (".$sql.")";
}

//-----------------------------------------------------------------------------

// Add all bibliography items to the current list.

function bib_add_all()
{
	global $bib_list_sections;
	
	// modify select info for current section
	$section = &$bib_list_sections[count($bib_list_sections)-1];
	$section["select"] = "true";
}

//-----------------------------------------------------------------------------

// Filter the current list of bibliography items.
// First argument is field to filter on.
// Subsequent arguments are possible values for field, e.g.:
//   bib_filter("type", "article", "inproceedings").
// If first argument is "!", filter is negated, e.g.:
//   bib_filter("type", "!", "techreport", "unpublished").

function bib_filter()
{
	global $bib_list_sections;
	
	// construct corresponding sql clause
	$clause = bib_build_sql_clause(func_get_args());
	// modify select info for current section
	$section = &$bib_list_sections[count($bib_list_sections)-1];
	$section["select"] = "(".$section["select"].") AND (".$clause.")";
}
//-----------------------------------------------------------------------------

// Filter the current list of bibliography items based on an sql expression.

function bib_filter_sql($sql)
{
	global $bib_list_sections;
	
	// modify select info for current section
	$section = &$bib_list_sections[count($bib_list_sections)-1];
	$section["select"] = "(".$section["select"].") AND (".$sql.")";
}

//-----------------------------------------------------------------------------

// Sort the current list of bibliography items.

function bib_sort_by($field, $dir = "asc" , $header = "")
{
	global $bib_item_fields;
	global $bib_list_sections;
	
	// check arguments for errors
	if (!($dir == "asc" || $dir == "desc" || $dir == "ASC" || $dir == "DESC")) {
		bib_log_error("Invalid sort direction \"$dir\" - must be \"asc\" or \"desc\"");
		return;
	}
	if (!(in_array($field, $bib_item_fields) || $field == "key")) {
		bib_log_error("Can't sort by field \"".$field."\"");
		return;
	}
	// for type, we actually sort by type index
	if ($field == "type") $field = "type_index";
	// store sort info in current section
	$section = &$bib_list_sections[count($bib_list_sections)-1];
	$section["sortby_list"][] = array("field"=>$field, "dir"=>$dir, "header"=>$header);
}

//-----------------------------------------------------------------------------

// Add a search box above the bib item list

function bib_search_box()
{
	// get search term
	$search = extract_var_from_post_get("search");
	
	// display search box
	echo "<div class=\"biblist-search-box\">\n";
	echo "<form method=\"get\" action=\"".$_SERVER["PHP_SELF"]."\">\n";
	echo "<input type=\"submit\" value=\"Search\">\n&nbsp;\n";
	echo "<input class=\"bib-search-field\" type=\"text\" size=\"30\" name=\"search\" value=\"";
	if ($search != NULL) echo htmlentities(trim($search), ENT_COMPAT, "UTF-8");
	echo "\">\n</form>\n";
	echo "</div>\n\n";
	
	return $search;
}

//-----------------------------------------------------------------------------

// Add links to do sorting above the bib item list
// (optionally takes in search query to add to links)
// (optionally, specify extra sort indices)
// (optionally, specify default sort index)

function bib_sort_links($search = "", $extra = NULL, $default = "date")
{
	// get sort index (default is date - see arg list above)
	$sort = extract_var_from_post_get("sort");
	if ($sort === NULL || $sort == "") $sort = $default;
	
	// display links
	echo "<p class=\"biblist-sort-links\">\n";
	echo "<b>Sort by: </b>";
	$sort_list = array("date", "type", "title");
	if ($extra) $sort_list = array_merge($sort_list, $extra);
	$first = true;
	foreach ($sort_list as $sort_type) {
		if ($first) $first = false; else echo ", ";
		if ($sort_type == $sort) echo "<span class=\"bib-highlight\">";
		echo "<a href=\"".$_SERVER["PHP_SELF"]."?";
		if ($search) echo "search=$search&amp;";
		echo "sort=$sort_type\">$sort_type</a>";
		if ($sort_type == $sort) echo "</span>";
	}
	echo "</p>\n";

	return $sort;
}

//-----------------------------------------------------------------------------

// Display the current list of bibliography items as a table.
// Link to be attached to each item is $link. "" => no link. %k expands to the key.
// Highly configurable via CSS, including bullets/counters/graphics/etc.
// This is the only way to get a numbered list where numbering runs across section.
// Fails silently on error.

function bib_display_list_table($link = "")
{
	bib_display_list(
		$link,
		"<div class=\"biblist-block\"><table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">",
		"</table></div>",
		"\t<tr class=\"biblist-row-alt%a\">\n".
		"\t<td class=\"biblist-bullet\">&nbsp;</td>\n".
		"\t<td class=\"biblist-count\">%i</td>\n".
		"\t<td class=\"biblist-item\">",
		"\t</tr>"
	);
}

// Display the current list of bibliography items as an unordered list.
// Link to be attached to each item is $link. "" => no link. %k expands to the key.
// List style configurable via CSS.
// Fails silently on error.

function bib_display_list_ul($link = "")
{
	bib_display_list($link, "<ul class=\"biblist-block\">", "</ul>", "<li class=\"biblist-item\">", "" );
}

// Display the current list of bibliography items as an ordered (numbered) list.
// Link to be attached to each item is $link. "" => no link. %k expands to the key.
// List style configurable via CSS.
// Note numbering does not runs across sections - use a table (see above).
// Fails silently on error.

function bib_display_list_ol($link = "")
{
	bib_display_list($link, "<ol class=\"biblist-block\">", "</ol>", "<li class=\"biblist-item\">", "" );
}

// Display the current list of bibliography items in an arbitrary fashion.
// Note you can do most things, presentation-wise, using the specific functions above and CSS.
// Link to be attached to each item is $link. "" => no link. %k expands to the key.
// HTML at start and end of each block/item can also be configured.
// In the latter, %i expands to a counter and %a alternates between 1 and 2.
// Fails silently on error.

function bib_display_list($link = "", $block_start = "", $block_end = "", $item_start = "", $item_end = "")
{
	global $bib_list_sections;
	global $bib_list_item_link;
	global $bib_list_block_start;
	global $bib_list_block_end;
	global $bib_list_item_start;
	global $bib_list_item_end;
	global $bib_list_search_enabled;
	global $bib_list_search_string;
	global $bib_list_search_sql;
	global $bib_list_counter;
	global $bib_list_alt;
	
	// store parameters globally so can access from recursive function below
	$bib_list_item_link = $link;
	$bib_list_item_start = $item_start;
	$bib_list_item_end = $item_end;
	$bib_list_block_start = $block_start;
	$bib_list_block_end = $block_end;
	// set up some loop vars
	$bib_list_counter = 1;
	$bib_list_alt = 1;
	
	// connect to database
	$dbconn = bib_connect_to_db();
	if (!$dbconn) { bib_log_error("Couldn't connect to database"); return; }
	
	echo "<div class=\"biblist\">\n\n";
	
	// display summary of search results
	if ($bib_list_search_enabled) {
		// count total matches (sum for all sections)
		$count = 0;
		foreach ($bib_list_sections as $index => $section) {
			$query = "SELECT count(key) FROM bib_items WHERE (".$bib_list_search_sql.")and(".$section["select"].")";
			$result = bib_db_query($query, $dbconn);
			$count += $result[0];
			// if no matches for this section, set "select" to "false"
			// to make sure it will be omitted from display
			if ($result[0] == 0) $bib_list_sections[$index]["select"] = "false";
		}
		echo "<div class=\"biblist-search-results\">\n";
		if ($count == 0) {
			echo "<p>No matches for <b>".htmlentities($bib_list_search_string, ENT_COMPAT, "UTF-8")."</b>.</p>\n\n";
		}
		else {
			echo "<p><b>".$count."</b> match".($count==1?"":"es")." for <b>".htmlentities($bib_list_search_string, ENT_COMPAT, "UTF-8")."</b>:</p>\n";
		}
		echo "</div>\n\n";
	}
	else {
		// count total matches (sum for all sections)
		$count = 0;
		foreach ($bib_list_sections as $index => $section) {
			$query = "SELECT count(key) FROM bib_items WHERE ".$section["select"];
			$result = bib_db_query($query, $dbconn);
			$count += $result[0];
		}
		echo "<div class=\"biblist-count\"><b>".$count."</b> publications:</div>\n";
	}
	
	// display each section
	foreach ($bib_list_sections as $section) {
		// skip empty sections
		//if ($section["header"] || $section["select"] != "false") {
		if ($section["select"] != "false") {
			echo "<div class=\"biblist-section\">\n\n";
			if ($section["headerlink"]) {
				echo "<a name=\"".$section["headerlink"]."\"></a>\n";
			}
			if ($section["header"]) {
				echo "<div class=\"biblist-section-header\">";
				echo $section["header"];
				echo "</div>\n\n";
			}
			$section_sql = ($bib_list_search_enabled ? ("(".$bib_list_search_sql.")AND") : "")."(".$section["select"].")";
			bib_display_sect_rec($dbconn, $section["sortby_list"], 0, $section_sql);
			echo "</div>\n\n";
		}
	}
	
	echo "</div>\n\n";
	
	// disconnect from database
	bib_disconnect_db($dbconn);
}

function bib_display_sect_rec($dbconn, $sortby_list, $depth = 0, $where = "", $orderby = "")
{
	global $bib_list_item_link;
	global $bib_list_item_start;
	global $bib_list_item_end;
	global $bib_list_block_start;
	global $bib_list_block_end;
	global $bib_list_counter;
	global $bib_list_alt;
	
	// base case
	if ($depth >= count($sortby_list))
	{
		// get items from database
		$query = "SELECT * FROM (bib_items INNER JOIN bib_types ON (bib_items.type=bib_types.type))";
		if ($where) $query .= " WHERE ($where)";
		if ($orderby) $query .= " ORDER BY $orderby";
		$result = bib_db_query($query, $dbconn);
		if (!$result) return;
		// print block prefix
		echo preg_replace(array("/%i/", "/%a/"), array($bib_list_counter, $bib_list_alt), $bib_list_block_start);
		echo "\n\n";
		// print each item
		foreach ($result as $item) {
			// print item prefix
			echo preg_replace(array("/%i/", "/%a/"), array($bib_list_counter, $bib_list_alt), $bib_list_item_start);
			echo "\n";
			// print item
			bib_display_item($item, $bib_list_item_link, 1);
			// print item suffix
			echo preg_replace(array("/%i/", "/%a/"), array($bib_list_counter, $bib_list_alt), $bib_list_item_end);
			echo "\n\n";
			// update loop vars
			$bib_list_counter++;
			$bib_list_alt = ($bib_list_alt == 1) ? 2 : 1;
		}
		// print block suffix
		echo preg_replace(array("/%i/", "/%a/"), array($bib_list_counter, $bib_list_alt), $bib_list_block_end);
		echo "\n\n";
	}
	// recursive case
	else {
		// get details of what we are sorting by
		$sortby = $sortby_list[$depth];
		// if there is no header to display, just recurse
		if ($sortby["header"] == "") {
			$new_orderby = ($orderby==""?"":$orderby.",").$sortby["field"]." ".$sortby["dir"];
			bib_display_sect_rec($dbconn, $sortby_list, $depth+1, $where, $new_orderby);
			return;
		}
		// otherwise, get (sorted) distinct values
		// (actually get pairs of values and their displayable names)
		// (month ids to month names can be sorted here, but aren't yet)
		$select = $sortby["field"];
		if ($sortby["field"] == "type_index") $select .= ",type_description";
		else $select .= ",".$sortby["field"];
		$query = "SELECT DISTINCT ".$select." FROM (bib_items INNER JOIN bib_types ON (bib_items.type=bib_types.type))";
		if ($where) $query .= " WHERE ($where)";
		$query .= " ORDER BY ".$sortby["field"]." ".$sortby["dir"];
		$result = bib_db_query($query, $dbconn);
		if (!$result) return;
		// loop over each value
		foreach ($result as $pair) {
			// print header
			echo "<a name=\"".$pair[1]."\"></a>\n";
			echo "<div class=\"biblist-block-header\">";
			echo preg_replace("/%s/", htmlentities($pair[1], ENT_COMPAT, "UTF-8"), $sortby["header"]);
			echo "</div>\n\n";
			// recurse
			$new_where = ($where==""?"":$where." AND ").$sortby["field"]."='".$pair[0]."'";
			bib_display_sect_rec($dbconn, $sortby_list, $depth+1, $new_where, $orderby);
		}
	}
}

//-----------------------------------------------------------------------------

// Get an array of all words in searchable fields in the current list
// (optionally, only return those starting with $term)
// (but ignore any list search info)

function bib_get_all_searchable_words($term = NULL)
{
	global $bib_list_sections;
	global $bib_list_search_fields_default;
	
	// connect to database
	$dbconn = bib_connect_to_db();
	if (!$dbconn) { bib_log_error("Couldn't connect to database"); return; }
	
	// extract all search fields from all items in the current list
	$where = "true";
	foreach ($bib_list_sections as $index => $section) {
		$where = "(".$where.")AND(".$section["select"].")";
	}
	$res = bib_db_query("SELECT ".implode(",", $bib_list_search_fields_default)." FROM bib_items WHERE ".$where, $dbconn);

	// go through all db fields returned, put into array
	$words = array();
	foreach($res as $entries) {
		$n = count($entries);
		for ($i = 0; array_key_exists($i, $entries); $i++) {
			$entry = $entries[$i];
			if ($entry && count($entry) > 0) {
				foreach (explode(" ", $entry) as $word) {
					// pick out those starting with $term and strip trailing punctuation
					$regexp = "/^";
					if ($term)
						$regexp .= $term;
					$regexp .= "[A-Za-z0-9\\-]+/i";
					if (preg_match($regexp, $word, $matches)) {
						$word = $matches[0];
						// reject just numbers
						if (preg_match("/[A-Za-z]/", $word)) {
							// reject 1-letter words
							if (strlen($word) > 1) {
								$words[$word] = 1;
							}
						}
					}
				}
			}
		}
	}
	
	// disconnect from database
	bib_disconnect_db($dbconn);
	
	return array_keys($words);
}

//-----------------------------------------------------------------------------

?>
