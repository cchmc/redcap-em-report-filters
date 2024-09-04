<?php

namespace JFortriede\ReportFilters;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;
use REDCap;
use DataExport;
use RCView;
use REDCapConfigDTO;

class ReportFilters extends AbstractExternalModule
{

    private $jsGlobal = "";
    private $defaultSettings = ['includeEvent' => true];

    /*
    Primary Redcap Hook, loads config and Report pages
    */
    public function redcap_every_page_top($project_id)
    {
        // Bail if user isn't logged in
        if (!defined("USERID")) {
            return;
        }

        $report_id = $_GET['report_id'];

        // Custom Config page
        if ($this->isPage('ExternalModules/manager/project.php') && $project_id) {
            $this->loadSettings();
            $this->includeJs('config.js');
        }

        // Reports Page (Edit or View Report, Not the all-reports page or stats/charts)
        elseif ($this->isPage('DataExport/index.php') && $project_id && ($report_id || $_GET['create']) && !$_GET['stats_charts']) {
            $this->loadSettings($report_id);
            $this->includeCSS();
            if ($_GET['addedit']) {
                $this->includeJs('editFilters.js');
            } else {
                $this->includeJs('viewFilters.js');
            }
        }

        // Add to public survey
        elseif ($this->isSurveyPage() && $_GET['__report']) {
            list ($pid, $report_id, $report_title) = DataExport::getReportInfoFromPublicHash($_GET['__report']);
            $_GET['pid'] = $pid;
            $_GET['report_id'] = $report_id;
            if($report_id){
                $this->loadSettings($report_id);
                $this->includeCSS();
                $this->includeJs('viewFilters.js');
            }
        }

    }

    /*
    Save all report config to a single json setting for the EM. 
    Invoked via router/ajax
    */
    public function saveReportConfig()
    {
        $json = $this->getProjectSetting('json');
        $json = empty($json) ? array() : json_decode($json, true);

        // // Escape 3 feilds that are html enabled 
        $new = json_decode($_POST['settings'], true);

        $json[$_POST['report']] = $new;
        $this->setProjectSetting('json', json_encode($json));
    }

    /*
    Inits the ReportFilters global and loads the settings for
    a report ID. Also packs the Redcap JS object
    */

    function getDebugMode(){
        return $this->getProjectSetting( 'debug-mode' );
    }

    
    private function getCitationHTML($project_id){
        global $lang;
        ## NOTICES FOR CITATIONS (GRANT AND/OR SHARED LIBRARY) AND DATE-SHIFT NOTICE
        $citationText = "";
        $Proj = new \Project($project_id);

        // Do not display grant statement unless $grant_cite has been set for this project.
        if ($GLOBALS['grant_cite'] != "") {
            $citationText .= RCView::li(['class'=>'mb-2'],
                    "{$lang['data_export_tool_297']} <b>{$GLOBALS['grant_cite']}</b>{$lang['period']}"
                    );
        }

        // REDCap 2009 publication citation
        $citationText .= RCView::li(['class'=>'mb-2'],
            $lang['data_export_tool_298'] . " <a href='https://redcap.vanderbilt.edu/consortium/cite.php' target='_blank' style='text-decoration:underline;'>{$lang['data_export_tool_301']}</a>"
        );

                // Shared Library citation: If instruments have been downloaded from the Shared Library
        if ($Proj->formsFromLibrary()) {
            $dlg1 = "<code style='font-size:15px;color:#333;'>
                    Jihad S. Obeid, Catherine A. McGraw, Brenda L. Minor, José G. Conde, Robert Pawluk, Michael Lin, Janey Wang, Sean R. Banks, Sheree A. Hemphill, Rob Taylor, Paul A. Harris,
                    &quot;<b>Procurement of shared data instruments for Research Electronic Data Capture (REDCap)</b>&quot;, 
                    Journal of Biomedical Informatics,
                    Volume 46, Issue 2,
                    2013,
                    Pages 259-265,
                    ISSN 1532-0464.
                    <a target='_blank' style='text-decoration:underline;font-size:15px;' href='https://doi.org/10.1016/j.jbi.2012.10.006'>https://doi.org/10.1016/j.jbi.2012.10.006</a></code>";
            $citationText .= RCView::li(['class'=>'mb-2'],
                                "{$lang['data_export_tool_300']} <a href='javascript:;' style='text-decoration:underline;' onclick=\"simpleDialog('".js_escape($dlg1)."','".RCView::tt_js('data_export_tool_302')."',null,650);\">{$lang['data_export_tool_303']}</a>"
                            );
        }
        // CDIS citation
        if ($GLOBALS['realtime_webservice_type'] == 'FHIR' || $GLOBALS['datamart_enabled'] == '1') {
            $dlg1 = "<code style='font-size:15px;color:#333;'>
                    A.C. Cheng, S.N. Duda, R. Taylor, F. Delacqua, A.A. Lewis, T. Bosler, K.B. Johnson, P.A. Harris,
                    &quot;<b>REDCap on FHIR: Clinical Data Interoperability Services</b>&quot;, 
                    Journal of Biomedical Informatics,
                    Volume 121,
                    2021,
                    103871,
                    ISSN 1532-0464.
                    <a target='_blank' style='text-decoration:underline;font-size:15px;' href='https://doi.org/10.1016/j.jbi.2021.103871'>https://doi.org/10.1016/j.jbi.2021.103871</a></code>";
            $citationText .= RCView::li(['class'=>'mb-2'],
                "{$lang['data_export_tool_304']} <a href='javascript:;' style='text-decoration:underline;' onclick=\"simpleDialog('".js_escape($dlg1)."','".RCView::tt_js('data_export_tool_302')."',null,650);\">{$lang['data_export_tool_303']}</a>"
            );
        }
        // Not tested...
        // REDCap Mobile App citation
        $sql = "select 1 from redcap_mobile_app_log where event = 'INIT_PROJECT' and project_id = $project_id limit 1";
        if (db_num_rows(db_query($sql))) {
            $dlg1 = "<code style='font-size:15px;color:#333;'>
                    Paul A Harris, Giovanni Delacqua, Robert Taylor, Scott Pearson, Michelle Fernandez, Stephany N Duda,
                    &quot;<b>The REDCap Mobile Application: a data collection platform for research in regions or situations with internet scarcity</b>&quot;, 
                    JAMIA Open, Volume 4, Issue 3, July 2021, ooab078.
                    <a target='_blank' style='text-decoration:underline;font-size:15px;' href='https://doi.org/10.1093/jamiaopen/ooab078'>https://doi.org/10.1093/jamiaopen/ooab078</a></code>";
            $citationText .= RCView::li(['class'=>'mb-2'],
                "{$lang['data_export_tool_305']} <a href='javascript:;' style='text-decoration:underline;' onclick=\"simpleDialog('".js_escape($dlg1)."','".RCView::tt_js('data_export_tool_302')."',null,650);\">{$lang['data_export_tool_303']}</a>"
            );
        }
        // Not tested... OLD citation
        // MyCap citation
        $sql = "SELECT 1 FROM redcap_external_modules e, redcap_external_module_settings s
                WHERE e.directory_prefix = 'mycap' AND e.external_module_id = s.external_module_id and s.`key` = 'enabled' 
                and s.`value` = 'true' and s.project_id = $project_id limit 1";
        $mycapModuleEnabled = (db_num_rows(db_query($sql)));
        if ($mycapModuleEnabled || (isset($GLOBALS['mycap_enabled']) && $GLOBALS['mycap_enabled'] == '1')) {
            $dlg1 = "<code style='font-size:15px;color:#333;'>
                    Paul A Harris, Jonathan Swafford, Emily S Serdoz, Jessica Eidenmuller, Giovanni Delacqua, Vaishali Jagtap, Robert J Taylor, Alexander Gelbard, Alex C Cheng, Stephany N Duda,
                    &quot;<b>MyCap: a flexible and configurable platform for mobilizing the participant voice</b>&quot;, 
                    JAMIA Open, Volume 5, Issue 2, July 2022, ooac047.
                    <a target='_blank' style='text-decoration:underline;font-size:15px;' href='https://doi.org/10.1093/jamiaopen/ooac047'>https://doi.org/10.1093/jamiaopen/ooac047</a></code>";
            $citationText .= RCView::li(['class'=>'mb-2'],
                "{$lang['data_export_tool_306']} <a href='javascript:;' style='text-decoration:underline;' onclick=\"simpleDialog('".js_escape($dlg1)."','".RCView::tt_js('data_export_tool_302')."',null,650);\">{$lang['data_export_tool_303']}</a>"
            );
        }
        // E-Consent citation
        $sql = "select 1 from redcap_surveys s where s.pdf_auto_archive = 2 and s.project_id = $project_id limit 1";
        $econsentEnabled = db_num_rows(db_query($sql));
        if ($econsentEnabled) {
            $dlg1 = "<code style='font-size:15px;color:#333;'>
                    Lawrence CE, Dunkel L, McEver M, Israel T, Taylor R, Chiriboga G, Goins KV, Rahn EJ, Mudano AS, Roberson ED, Chambless C, Wadley VG, Danila MI, Fischer MA, Joosten Y, Saag KG, Allison JJ, Lemon SC, Harris PA,
                    &quot;<b>A REDCap-based model for electronic consent (eConsent): Moving toward a more personalized consent</b>&quot;, 
                    J Clin Transl Sci. 2020 Apr 3;4(4):345-353.
                    <a target='_blank' style='text-decoration:underline;font-size:15px;' href='https://doi.org/10.1017/cts.2020.30'>https://doi.org/10.1017/cts.2020.30</a></code>";
            $citationText .= RCView::li(['class'=>'mb-2'],
                "{$lang['data_export_tool_307']} <a href='javascript:;' style='text-decoration:underline;' onclick=\"simpleDialog('".js_escape($dlg1)."','".RCView::tt_js('data_export_tool_302')."',null,650);\">{$lang['data_export_tool_303']}</a>"
            );
        }

        // Wrap all citations in an ordered list
        $citationText = RCView::fieldset(array('style'=>'margin-top:10px;padding-left:8px;background-color:#FFFFD3;border:1px solid #FFC869;color:#B00000;'),
            RCView::legend(array('class'=>'font-weight-bold fs14'),
                '<i class="fa-solid fa-book"></i> '.$lang['data_export_tool_295'] . ($GLOBALS['grant_cite'] != "" ? " ".$lang['data_export_tool_296'] : "")
            ) .
            RCView::div(array('class'=>'p-1 mt-1'),
                $lang['data_export_tool_299']
            ) .
            RCView::ol(array('class'=>'ms-3 ps-1 pe-3 pt-2 pb-0'),
                $citationText
            )
        );
        return $citationText;


    }

    private function loadSettings($report = Null)
    {
        // Setup Redcap JS object
        $this->initializeJavascriptModuleObject();
        $this->tt_transferToJavascriptModuleObject();
        $this->jsGlobal = $this->getJavascriptModuleObjectName();
        $data = ["prefix" => $this->getPrefix()];
		$project_id = ExternalModules::getProjectId();
        $project = new \Project($this->project_id);

        if (!empty($report)) {

            // Get the EM's settings
            $json = ((array)json_decode($this->getProjectSetting('json')))[$report];
            $json = empty($json) ? $this->defaultSettings : $json;

            // Organize the strucutre
            $data = array_merge($data, [
                "isLong" => REDCap::isLongitudinal(),
                "csrf" => $this->getCSRFToken(),
                "router" => $this->getUrl('router.php'),
                "record_id" => REDCap::getRecordIdField(),
                "settings" => $json,
                "debug_mode" => $this->getDebugMode(),
                "project_title" => REDCAP::getProjectTitle(),
                "forms_from_library" => $project->formsFromLibrary(),
                "APP_PATH_IMAGES" => APP_PATH_IMAGES
                // "eventMap" => $this->makeEventMap()
            ]);
        }

        // Add in Report fields (variables)
        $details = DataExport::getReports($report);
        $data['report_fields'] = $details['fields'];
        $data['report_title'] = $details['title'];
        $data['report_display_data'] = $details['report_display_data'];
        $data['report_display_header'] = $details['report_display_header'];
        // $data['report'] = $details;
        
        $data['citation'] = $this->getCitationHTML($project_id);

        $data['tt']=[];

        $tt_array = array("add_filter", "add_filter_checkbox_title","button_download");
        
        foreach ($tt_array as $field){
            $data['tt'][$field]=$this->tt($field);
        }

        // Pass down to JS
        $data = json_encode($data);
        echo "<script>Object.assign({$this->jsGlobal}, {$data});</script>";
    }

    /*
    HTML to include some local JS file
    */
    private function includeJs($path)
    {
        echo "<script src={$this->getUrl('js/' .$path)}></script>";
    }

    /*
    HTML to include the local css file
    */
    private function includeCSS()
    {
        echo "<link rel='stylesheet' href={$this->getURL('style.css')}>";
    }
}
