$(document).ready(() => {
    const module = ExternalModules.JFortriede.ReportFilters;
    module.settings.activeFilters=[]

    if(module.debug_mode && module.debug_mode <= 20){   // Write if Debug_mode is Info or Debug
        console.log("Report Filters Module Loaded");
    }

    if(module.debug_mode && module.debug_mode <= 10){   // Write if Debug_mode is Debug
        console.debug(module);
    }
    

    const isFilterColumn = (colIdx, offset) => {

        let report_field = module.report_fields[colIdx-offset];
        if("columns" in module.settings && module.settings.columns.includes(report_field)){
            return true
        }
        return false
    }

    const createDropdownFilter = (colIdx, col_header) => {
        // If only 1 value, do not include a dropdown filter
        if(
            rcDataTable   
                .column( colIdx )
                .cache( 'search' )
                .sort()
                .unique().length <= 1)
        {
            return 
        }
        // Get the search data for the first column and add to the select list

        var select = $('<select id="filter_col_'+col_header+'" style="width:100px"/>')
            .on( 'change', function () {
                let search_value = ''
                let urlParams = new URLSearchParams(window.location.search);
                if ($(this).val() != '[No Filter]'){
                    // Add id and value to url query string
                    urlParams.set(col_header, $(this).val());
                    module.settings.activeFilters[col_header] = $(this).val();
                    search_value = "^" + $(this).val() + "( \\\([^\)]\\\))*$"
                }
                else{
                    // Remove id and value from url query string
                    delete module.settings.activeFilters[col_header]
                    urlParams.delete(col_header);
                }
                window.history.replaceState({}, '', `${location.pathname}?${urlParams}`);
                rcDataTable
                    .column( colIdx )
                    .search(search_value, true, false, true)
                    .draw();
            } );
        select.append( $('<option value="[No Filter]">[No Filter]</option>') );
        
        rcDataTable   // Get the search data for the first column and add to the select list
            .column( colIdx )
            .cache( 'search' )
            .sort()
            .unique()
            .each( function ( d ) {
                d = d.replace(/ \([^)]+\)$/,'')
                select.append( $('<option value="'+d+'">'+d+'</option>') );
            } );
        return select

    }
    
    const getReportTableHeaderRow = () => {
        return $("#report_table_wrapper table:first.dataTable thead")
    }


    const getColumnLabel = (columnNo) => {
        let header = $("#report_table_wrapper table:first.dataTable thead").find("tr:first th")[columnNo]
        if (module.report_display_header == "BOTH"){
            return $(header).contents().filter(function(){ 
                return this.nodeType == Node.TEXT_NODE; 
            })[0].nodeValue 
        }
        return $(header).text()
    }

    const createFilterRow = () => {
        let header = getReportTableHeaderRow()
        copyHeader(header)
    }

    /*
    Performs very minor DOM manipulations to make the default search box
    and the enable/disable floating headers button appear uniform with the 
    new range search boxes at the top of report.
    */
    
    const getColumnIndexing = () => {
        let columnIndexes = {}
        if (module.report_display_header == "BOTH"){
            rcDataTable.columns().header().each(function(header, index){
                let header_text = $(header).contents().filter(function(){return this.nodeType == Node.TEXT_NODE;})[0].nodeValue
                columnIndexes[header_text] = index
            })
        }
        else{
            rcDataTable.columns().header().each(function(header, index){
                columnIndexes[$(header).text()] = index
            })
        }
        return columnIndexes
    }

    const copyHeader = (header) => {
        let columnIndexes = getColumnIndexing()
        let header_length = $(header).find("tr:first th").length;
        let filter_row = $("<tr id='filter_row'>")
        let header_column_offset = 0 
        let create_row = false;

        for(let i=0; i< header_length; i++){
            let new_th = $("<th>")
            let col_header = getColumnLabel(i)
            
            //
            if(['redcap_event_name','Event Name','Repeat Instrument','Repeat Instance','redcap_repeat_instrument','redcap_repeat_instance'].includes(col_header)){
                header_column_offset++
            }

            // console.log(i, i-header_column_offset, col_header, columnIndexes[col_header])
            if(isFilterColumn(i-header_column_offset,0)){
                // console.log("\tfilter")
                let select = createDropdownFilter(columnIndexes[col_header], module.report_fields[i])
                if(select){
                    create_row=true
                    new_th.append(select)
                }    
            }

            filter_row.append(new_th)
            
        }
        if(create_row){
            header.append(filter_row)
        }
    }

    /*
    Wait for page to finish loading the report before deploying our filters.
    Full build out of the EM occurs here, we re-invoke if changing pages
    on a multipage report. 
    */

    
    const insertDownloadBtn = () => {
        let html = '<a href="#" id="rfDownloadDataBtn" class="btn btn-secondary btn-sm mb-1" role="button"><i class="fas fa-download"></i></a>';
        if ($(".report_pagenum_div").length) { // Pagination
            $(".report_pagenum_div").first().before(html);
        } else { // One Page
            $("#report_table_wrapper").prepend(html);
        }
        $("#rfDownloadDataBtn").popover({
            content: module['tt']["button_download"],
            trigger: "hover"
        });
        $("#rfDownloadDataBtn").on("click", function(event){event.preventDefault(); downloadDataModal()});
    }


    const downloadData = () => {
        let rows = [];
        // TODO if #filter_row select value is not [No Filter] then add to rows
        rcDataTable.rows({filter: 'applied'}).every(function (rowIdx, data, node) {
                    rows.push(rowIdx)
                    // ... do something with data(), or this.node(), etc
                } );
        

        let url = app_path_webroot+"ExternalModules/?prefix=report_filters&page=export&pid="+pid
        let urlParams = new URLSearchParams(window.location.search);

        for (const [key, value] of urlParams){
            if( key.startsWith("lf")){
                url += "&"+key+"="+value
            }
        }

        $.post(url,
          {
            report_id: urlParams.get('report_id'),
            rows: rows,
            rawOrLabel: $('#report_display_data').val(),
          },
          function(data, status){
            // Create a Blob object with the CSV data
            let blob = new Blob([data], { type: 'text/csv;charset=utf-8;' });
            
            // Create a temporary URL for the Blob
            let url = URL.createObjectURL(blob);
            
            // Create a link element and set its attributes
            let link = document.createElement('a');
            link.href = url;
            link.download = module.project_title + '-' + module.report_title + '.csv';
            
            // Simulate a click on the link to trigger the download
            link.click();
            
            // Clean up the temporary URL
            URL.revokeObjectURL(url);
          });
        
    }

    const downloadDataModal = () => {
        dialog_content = "<font style='font-size:14px'>This report is available for download in CSV format. The downloaded data will follow REDCap User permissions for de-identification/export rights, but will also filter the results to only the rows that are shown in the table.<br/><br/>"
        if($("select.report_page_select").length>0){
            dialog_content += "<b>Note:</b> This report has multiple pages of data. Only the data on the current page will be downloaded. You will need to click to show all records for the filtering to work.<br/><br/>"
        }
        dialog_content += "</font>"
        dialog_content += "<font style='font-size:14px; margin-right:24px'>Export as: </font>"
        dialog_content += "<select id='report_display_data'>"
        dialog_content += "<option value='label'>Label</option>"
        dialog_content += "<option value='raw'>Raw Data</option>"
        dialog_content += "</select></br>"
        let download_button = '<a href="#" id="rfDownloadBtn" class="btn btn-secondary btn-sm mb-1" role="button" style="float: right; margin-top: 10px"><img src="'+module.APP_PATH_IMAGES+'download_csvdata.gif"></a>';

        // $("<div id='report_filter_export'>").innerHTML(ExternalModules.JFortriede.ReportFilters.export_dialog)
        
        citationHTML = ''
        $.ajax({ type: "GET",
            url : app_path_webroot+"ExternalModules/?prefix=report_filters&page=citation&pid="+pid,
            async: false,
            success : function(data){
                citationHTML = data
            }
        })


        simpleDialog(dialog_content+citationHTML+download_button,'Download Report','ReportFiltersDownloadModal',750)
        $("#rfDownloadBtn").on("click", downloadData);
    }   

    const waitForLoad = () => {
        
        if ($("#report_table thead").length == 0 ||
        !$.fn.DataTable.isDataTable("#report_table")) { // Still Loading
            window.requestAnimationFrame(waitForLoad);
            return;
        }

        if(!('columns' in module.settings) || length(module.settings.columns) == 0){
            return
        }
        createFilterRow();

        // Adjust table columns for new filter row 
        rcDataTable.columns.adjust().draw()
        // Get all url parameters
        let urlParams = new URLSearchParams(window.location.search);
        for (const [key, value] of urlParams) {
            if(module.settings.columns.includes(key)){
                // If select has an option with value of value, set that option to selected
                //Check if select has an option with value of value
                if ($("#filter_col_"+key).find("option[value='"+value+"']").length > 0) {
                    $("#filter_col_"+key).val(value);
                    $("#filter_col_"+key).change();
                }
            }
        }
        if(module.data_export_tool != 0){
            insertDownloadBtn()
        }

        //Add change event listeners to live filters to trigger redraw
        if(document.getElementById('lf1') != null){
            document.getElementById('lf1').addEventListener('change',function(){waitForLoad()})
        }
        if(document.getElementById('lf2') != null){
            document.getElementById('lf2').addEventListener('change',function(){waitForLoad()})
        }
        if(document.getElementById('lf3') != null){
            document.getElementById('lf3').addEventListener('change',function(){waitForLoad()})
        }

        if(document.querySelector("select.report_page_select")){
            document.querySelector("select.report_page_select").addEventListener('change',function(){waitForLoad()})
        }

        //Add click event listener to live filter reset button to trigger redraw
        if(document.querySelector("#report_div select[id^='lf'] ~ a") != null){
            document.querySelector("#report_div select[id^='lf'] ~ a").addEventListener('click',function(){waitForLoad()})
        }

        // Process Active Filters
        for (const [key, value] of Object.entries(module.settings.activeFilters)) {
            if(module.settings.columns.includes(key)){
                // If select has an option with value of value, set that option to selected
                //Check if select has an option with value of value
                if ($("#filter_col_"+key).find("option[value='"+value+"']").length > 0) {
                    $("#filter_col_"+key).val(value);
                    $("#filter_col_"+key).change();
                }
            }
        }

        //Report Tweaks

        rcDataTable.on('column-visibility', function(e, settings, column, state){
            console.debug("Column Visibility Changed")
            if(state)  {
                $('#filter_row th:eq('+column+')').show();
            }
            else {
                $('#filter_row th:eq('+column+')').hide();
            }
        });

        return 
    }

    waitForLoad();
});