<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2014 The Cacti Group                                 |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2          |
 | of the License, or (at your option) any later version.                  |
 |                                                                         |
 | This program is distributed in the hope that it will be useful,         |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
 | GNU General Public License for more details.                            |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDTool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
 | This code is designed, written, and maintained by the Cacti Group. See  |
 | about.php and/or the AUTHORS file for specific developer information.   |
 +-------------------------------------------------------------------------+
 | http://www.cacti.net/                                                   |
 +-------------------------------------------------------------------------+
*/

chdir('../../');
include("./include/auth.php");
include_once("./plugins/mactrack/lib/mactrack_functions.php");

define("MAX_DISPLAY_PAGES", 21);

if (isset($_REQUEST["export_x"])) {
	mactrack_vmacs_export();
}else{
	include_once("./include/top_header.php");

	mactrack_vmacs();

	include_once("./include/bottom_footer.php");
}

function mactrack_vmacs_export() {
	global $site_actions, $config;

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var_request("page"));
	/* ==================================================== */

	/* clean up search string */
	if (isset($_REQUEST["filter"])) {
		$_REQUEST["filter"] = sanitize_search_string(get_request_var_request("filter"));
	}

	/* clean up sort_column */
	if (isset($_REQUEST["sort_column"])) {
		$_REQUEST["sort_column"] = sanitize_search_string(get_request_var_request("sort_column"));
	}

	/* clean up search string */
	if (isset($_REQUEST["sort_direction"])) {
		$_REQUEST["sort_direction"] = sanitize_search_string(get_request_var_request("sort_direction"));
	}

	/* remember these search fields in session vars so we don't have to keep passing them around */
	load_current_session_value("page", "sess_mactrack_vmacs_current_page", "1");
	load_current_session_value("filter", "sess_mactrack_vmacs_filter", "");
	load_current_session_value("sort_column", "sess_mactrack_vmacs_sort_column", "vendor_mac");
	load_current_session_value("sort_direction", "sess_mactrack_vmacs_sort_direction", "ASC");

	$sql_where = "";

	$vmacs = mactrack_vmacs_get_vmac_records($sql_where, 0, FALSE);

	$xport_array = array();
	array_push($xport_array, '"vendor_mac","vendor_name","vendor_address"');

	if (sizeof($vmacs)) {
		foreach($vmacs as $vmac) {
			array_push($xport_array,'"' . $vmac['vendor_mac'] . '","' .
			$vmac['vendor_name'] . '","' .
			$vmac['vendor_address'] . '"');
		}
	}

	header("Content-type: application/csv");
	header("Content-Disposition: attachment; filename=cacti_site_xport.csv");
	foreach($xport_array as $xport_line) {
		print $xport_line . "\n";
	}
}

function mactrack_vmacs_get_vmac_records(&$sql_where, $row_limit, $apply_limits = TRUE) {
	$sql_where = "";

	/* form the 'where' clause for our main sql query */
	if (strlen($_REQUEST["filter"])) {
		$sql_where = "WHERE (mac_track_oui_database.vendor_name LIKE '%%" . $_REQUEST["filter"] . "%%' OR " .
			"mac_track_oui_database.vendor_mac LIKE '%%" . $_REQUEST["filter"] . "%%' OR " .
			"mac_track_oui_database.vendor_address LIKE '%%" . $_REQUEST["filter"] . "%%')";
	}

	$query_string = "SELECT *
		FROM mac_track_oui_database
		$sql_where
		ORDER BY " . $_REQUEST["sort_column"] . " " . $_REQUEST["sort_direction"];

	if ($apply_limits) {
		$query_string .= " LIMIT " . ($row_limit*($_REQUEST["page"]-1)) . "," . $row_limit;
	}

	return db_fetch_assoc($query_string);
}

function mactrack_vmacs() {
	global $site_actions, $config, $item_rows;

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var_request("page"));
	input_validate_input_number(get_request_var_request("rows"));
	/* ==================================================== */

	/* clean up search string */
	if (isset($_REQUEST["filter"])) {
		$_REQUEST["filter"] = sanitize_search_string(get_request_var_request("filter"));
	}

	/* clean up sort_column */
	if (isset($_REQUEST["sort_column"])) {
		$_REQUEST["sort_column"] = sanitize_search_string(get_request_var_request("sort_column"));
	}

	/* clean up search string */
	if (isset($_REQUEST["sort_direction"])) {
		$_REQUEST["sort_direction"] = sanitize_search_string(get_request_var_request("sort_direction"));
	}

	/* if the user pushed the 'clear' button */
	if (isset($_REQUEST["clear_x"])) {
		kill_session_var("sess_mactrack_vmacs_current_page");
		kill_session_var("sess_mactrack_vmacs_filter");
		kill_session_var("sess_default_rows");
		kill_session_var("sess_mactrack_vmacs_sort_column");
		kill_session_var("sess_mactrack_vmacs_sort_direction");

		$_REQUEST["page"] = 1;
		unset($_REQUEST["filter"]);
		unset($_REQUEST["rows"]);
		unset($_REQUEST["sort_column"]);
		unset($_REQUEST["sort_direction"]);
	}else{
		/* if any of the settings changed, reset the page number */
		$changed = 0;
		$changed += mactrack_check_changed("filter", "sess_mactrack_vmacs_filter");
		$changed += mactrack_check_changed("rows", "sess_default_rows");

		if ($changed) {
			$_REQUEST["page"] = "1";
		}
	}

	/* remember these search fields in session vars so we don't have to keep passing them around */
	load_current_session_value("page", "sess_mactrack_vmacs_current_page", "1");
	load_current_session_value("filter", "sess_mactrack_vmacs_filter", "");
	load_current_session_value('rows', 'sess_default_rows', read_config_option('num_rows_table'));
	load_current_session_value("sort_column", "sess_mactrack_vmacs_sort_column", "vendor_mac");
	load_current_session_value("sort_direction", "sess_mactrack_vmacs_sort_direction", "ASC");

	if ($_REQUEST["rows"] == -1) {
		$row_limit = read_config_option("num_rows_table");
	}elseif ($_REQUEST["rows"] == -2) {
		$row_limit = 999999;
	}else{
		$row_limit = $_REQUEST["rows"];
	}

	html_start_box("<strong>MacTrack Vendor Mac Filter</strong>", "100%", "", "3", "center", "");
	mactrack_vmac_filter();
	html_end_box();
	html_start_box("", "100%", "", "3", "center", "");

	$sql_where = "";

	$vmacs = mactrack_vmacs_get_vmac_records($sql_where, $row_limit);

	$total_rows = db_fetch_cell("SELECT
			COUNT(*)
			FROM mac_track_oui_database
			$sql_where");

	$nav = html_nav_bar("mactrack_vendormacs.php", MAX_DISPLAY_PAGES, get_request_var_request("page"), $row_limit, $total_rows, 9, 'Vendor Macs');

	print $nav;

	$display_text = array(
		"vendor_mac" => array("Vendor MAC", "ASC"),
		"vendor_name" => array("Name", "ASC"),
		"vendor_address" => array("Address", "ASC"));

	html_header_sort($display_text, $_REQUEST["sort_column"], $_REQUEST["sort_direction"]);

	if (sizeof($vmacs) > 0) {
		foreach ($vmacs as $vmac) {
			form_alternate_row();
				?>
				<td class="linkEditMain"><?php print $vmac["vendor_mac"];?></td>
				<td><?php print (strlen($_REQUEST["filter"]) ? preg_replace("/(" . preg_quote($_REQUEST["filter"]) . ")/i", "<span class='filteredValue'>\\1</span>", $vmac["vendor_name"]) : $vmac["vendor_name"]);?></td>
				<td><?php print (strlen($_REQUEST["filter"]) ? preg_replace("/(" . preg_quote($_REQUEST["filter"]) . ")/i", "<span class='filteredValue'>\\1</span>", $vmac["vendor_address"]) : $vmac["vendor_address"]);?></td>
			</tr>
			<?php
		}

		/* put the nav bar on the bottom as well */
		print $nav;
	}else{
		print "<tr><td><em>No MacTrack Vendor MACS</em></td></tr>";
	}
	html_end_box(false);
}

?>
