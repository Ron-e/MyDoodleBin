<?php

/*
include('cnf/db.php');
$z_db_user = $user;
$z_db_pass = $pass;
$z_db_host = $host;
$z_db_name = $dbname;
try {
    $zdbh = new db_driver("mysql:host=" . $z_db_host . ";dbname=" . $z_db_name . "", $z_db_user, $z_db_pass);
} catch (PDOException $e) {
    
}
*/
global $zdbh;

echo "BEGIN: Checking for domains without dns records...";
$alldomains = $zdbh->query("SELECT vh_name_vc  FROM x_vhosts WHERE vh_type_in IS '1'");
foreach ($alldomains as &$domain) {
    $result = mysql_query("SELECT dn_name_vc  FROM x_dns WHERE dn_name_vc = '$domain'");
	echo "Found ".mysql_num_rows($result)." records of domains without dns record.";
	if(mysql_num_rows($result) == 0) {
		echo "creating dns record for domain: ".$domainID;
		doCreateDefaultRecords($domainID);
	}
}

echo "END: All domains have dns records..";

//stolen from dns_manager controller and edited a little bit!!!!!!!!!!!!!!!!
/**
 * Creates a new DNS record from an array of key value pairs.
 * @param array $rec Array of record properties (uid, domainName, domainID, type, hostName, ttl, target)
 * @return void
 */
function createDNSRecord(array $rec){
	global $zdbh;
	$sql = $zdbh->prepare('INSERT INTO x_dns (dn_acc_fk,
					   dn_name_vc,
					   dn_vhost_fk,
					   dn_type_vc,
					   dn_host_vc,
					   dn_ttl_in,
					   dn_target_vc,
					   dn_priority_in,
					   dn_weight_in,
					   dn_port_in,
					   dn_created_ts) VALUES (
					   :userid,
					   :domainName,
					   :domainID,
					   :type_new,
					   :hostName_new,
					   :ttl_new,
					   :target_new,
					   :priority_new,
					   :weight_new,
					   :port_new,
					   :time)'
	);

	$priority_new = array_key_exists('priority', $rec) ? $rec['priority'] : 0;
	$weight_new = array_key_exists('weight', $rec) ? $rec['weight'] : 0;
	$port_new = array_key_exists('port', $rec) ? $rec['port'] : 0;
	$time = array_key_exists('time', $rec) ? $rec['time'] : time();

	$sql->bindParam(':userid', $rec['uid']);
	$sql->bindParam(':domainName', $rec['domainName']);
	$sql->bindParam(':domainID', $rec['domainID']);
	$sql->bindParam(':type_new', $rec['type']);
	$sql->bindParam(':hostName_new', $rec['hostName']);
	$sql->bindParam(':ttl_new', $rec['ttl']);
	$sql->bindParam(':target_new', $rec['target']);
	$sql->bindParam(':priority_new', $priority_new);
	$sql->bindParam(':weight_new', $weight_new);
	$sql->bindParam(':port_new', $port_new);
	$sql->bindParam(':time', $time);
	$sql->execute();

	TriggerDNSUpdate($rec['domainID']);
}

function doCreateDefaultRecords($domainID){
	global $zdbh;
	global $controller;
	runtime_csfr::Protect();

	//$domainID = $controller->GetControllerRequest('FORM', 'inDomain');
	$numrows = $zdbh->prepare('SELECT * FROM x_vhosts WHERE vh_id_pk=:domainID AND vh_type_in !=2 AND vh_deleted_ts IS NULL');
	$numrows->bindParam(':domainID', $domainID);
	$numrows->execute();
	$domainName = $numrows->fetch();
	$domainName = $domainName['vh_name_vc'];

	$userID = $controller->GetControllerRequest('FORM', 'inUserID');
	if (!fs_director::CheckForEmptyValue(ctrl_options::GetSystemOption('server_ip'))) {
		$targetIP = ctrl_options::GetSystemOption('server_ip');
	} else {
		$targetIP = $_SERVER["SERVER_ADDR"]; //This needs checking on windows 7 we may need to use LOCAL_ADDR :- Sam Mottley
	}
	//Get list of DNS rows to create
	$RowCount = $zdbh->prepare('SELECT count(*) FROM x_dns_create WHERE dc_acc_fk=:userId');
	$RowCount->bindparam(':userId', $userID);
	$RowCount->execute();
	if ($RowCount->fetchColumn() > 0) {
		//The current user have specifics entries, use them only
		$CreateList = $zdbh->prepare('SELECT * FROM x_dns_create WHERE dc_acc_fk=:userId');
		$CreateList->bindparam(':userId', $userID);
		$CreateList->execute();
	} else {
		//no entry specific to this user is present, use default entries (user number = 0)
		$CreateList = $zdbh->query('SELECT * FROM x_dns_create WHERE dc_acc_fk=0');
	}
	while ($CreateItem = $CreateList->fetch()) {
		$Target = str_replace(':IP:', $targetIP, $CreateItem['dc_target_vc']);
		$Target = str_replace(':DOMAIN:', $domainName, $Target);

		$Row = array(
			'uid' => $userID,
			'domainName' => $domainName,
			'domainID' => $domainID,
			'type' => $CreateItem['dc_type_vc'],
			'hostName' => $CreateItem['dc_host_vc'],
			'ttl' => $CreateItem['dc_ttl_in'],
			'target' => $Target);

		if (!empty($CreateItem['dc_priority_in']))
			$Row['priority'] = $CreateItem['dc_priority_in'];

		if (!empty($CreateItem['dc_weight_in']))
			$Row['weight'] = $CreateItem['dc_weight_in'];

		if (!empty($CreateItem['dc_port_in']))
			$Row['port'] = $CreateItem['dc_port_in'];

		createDNSRecord($Row);
	}

	$editdomain = $domainID;
	return;
}

function TriggerDNSUpdate($id){
	global $zdbh;
	global $controller;
	$records_list = ctrl_options::GetSystemOption('dns_hasupdates');
	$record_array = explode(',', $records_list);
	if (!in_array($id, $record_array)) {
		if (empty($records_list)) {
			$records_list .= $id;
		} else {
			$records_list .= ',' . $id;
		}
		$sql = "UPDATE x_settings SET so_value_tx=:newlist WHERE so_name_vc='dns_hasupdates'";
		$sql = $zdbh->prepare($sql);
		$sql->bindParam(':newlist', $records_list);
		$sql->execute();
		return true;
	}
}
?>

