<?php

namespace JFortriede\ReportFilters;

use ExternalModules\AbstractExternalModule;
use REDCap;
use DataExport;
use RCView;

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

    
    private function getCitatationHTML(){
        global $lang;
        ## NOTICES FOR CITATIONS (GRANT AND/OR SHARED LIBRARY) AND DATE-SHIFT NOTICE
        $citationText = "";
        // Do not display grant statement unless $grant_cite has been set for this project.
        if ($grant_cite != "") {
            $citationText .= RCView::li(['class'=>'mb-2'],
                    "{$lang['data_export_tool_297']} <b>$grant_cite</b>{$lang['period']}"
                    );
        }

        // REDCap 2009 publication citation
        $citationText .= RCView::li(['class'=>'mb-2'],
            $lang['data_export_tool_298'] . " <a href='https://redcap.vanderbilt.edu/consortium/cite.php' target='_blank' style='text-decoration:underline;'>{$lang['data_export_tool_301']}</a>"
        );

        // Wrap all citations in an ordered list
        $citationText = RCView::fieldset(array('style'=>'margin-top:10px;padding-left:8px;background-color:#FFFFD3;border:1px solid #FFC869;color:#B00000;'),
            RCView::legend(array('class'=>'font-weight-bold fs14'),
                '<i class="fa-solid fa-book"></i> '.$lang['data_export_tool_295'] . ($grant_cite != "" ? " ".$lang['data_export_tool_296'] : "")
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
                "username" => ($this->getUser())->getUsername(),
                "APP_PATH_IMAGES" => APP_PATH_IMAGES,
                // "eventMap" => $this->makeEventMap()
            ]);
        }

        // Add in Report fields (variables)
        $details = DataExport::getReports($report);
        $data['report_fields'] = $details['fields'];
        $data['report_title'] = $details['title'];
        $data['report_display_data'] = $details['report_display_data'];
        $data['report_display_header'] = $details['report_display_header'];        
        $data['citation'] = $this->getCitatationHTML();

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
