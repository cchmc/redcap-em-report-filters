<?php

namespace JFortriede\ReportFilters;

use ExternalModules\AbstractExternalModule;
use REDCap;
use DataExport;
use RCView;
use REDCapConfigDTO;
use UserRights;


// Display the project header
// require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';
$user = $module->getUser()->getUsername();

global $Proj;

$user = $module->getUser();

$userid = $module->getUser()->getUsername();
$report_id = $_POST['report_id'];
$rows = $_POST['rows'];

// Get user rights
$user_rights_proj_user = UserRights::getPrivileges($project_id, $userid);
$user_rights = $user_rights_proj_user[$project_id][strtolower($userid)];
$ur = new UserRights();
$user_rights = $ur->setFormLevelPrivileges($user_rights);
unset($user_rights_proj_user);

$hashRecordID = (isset($user_rights['forms_export'][$Proj->firstForm]) && $user_rights['forms_export'][$Proj->firstForm] > 1 && $Proj->table_pk_phi);
$hashRecordID = false;

$exportCheckboxLabel=true;

$format = ($_POST['rawOrLabel'] == 'label') ? 'csvlabels' : 'csvraw';

list ($liveFilterLogic, $liveFilterGroupId, $liveFilterEventId) = DataExport::buildReportDynamicFilterLogic($report_id);


$content = DataExport::doReport($report_id, 'export', $format, ($_POST['rawOrLabel'] == 'label'), ($_POST['rawOrLabelHeaders'] == 'label'),
								false, false, null, $hashRecordID, null, null, null, false, false, array(), array(), false, $exportCheckboxLabel,
								false, false, true, $liveFilterLogic, $liveFilterGroupId, $liveFilterEventId, false, (isset($post['csvDelimiter']) ? $post['csvDelimiter'] : ","),
								(isset($post['decimalCharacter']) ? $post['decimalCharacter'] : null), array(),
								false, true, false, false, false, true, true);

if (count($rows)>0){
    $parse_content = preg_split("/\r\n|\n|\r/", $content);
    echo $parse_content[0]."\n";

    foreach ($rows as $x) {
        echo $parse_content[$x+1]."\n";
    }
} else {
    echo $content;
}


?>