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

    
    private function loadSettings($report = Null)
    {
        global $user_rights;
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
                'data_export_tool' => $user_rights['data_export_tool'],
                "forms_from_library" => $this->escape($project->formsFromLibrary()),
                "APP_PATH_IMAGES" => APP_PATH_IMAGES
                // "eventMap" => $this->makeEventMap()
            ]);
        }

        // Add in Report fields (variables)
        $details = DataExport::getReports($report);
        $data['report_fields'] = $this->escape($details['fields']);
        $data['report_title'] = $this->escape($details['title']);
        $data['report_display_data'] = $this->escape($details['report_display_data']);
        $data['report_display_header'] = $this->escape($details['report_display_header']);
        // $data['report'] = $details;
        
        $data['tt']=[];

        $tt_array = array("add_filter", "add_filter_checkbox_title","button_download");
        
        foreach ($tt_array as $field){
            $data['tt'][$field]=$this->escape($this->tt($field));
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
