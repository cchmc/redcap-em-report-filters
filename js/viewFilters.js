$(document).ready(() => {
    const module = ExternalModules.JFortriede.ReportFilters;
    
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
                    search_value = "^" + $(this).val() + "( \\\([^\)]\\\))*$"
                }
                else{
                    // Remove id and value from url query string
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
        
        return $(rcDataTable.column(columnNo).header()).contents().filter(function(){ 
            return this.nodeType == Node.TEXT_NODE; 
        })[0].nodeValue 
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
    const copyHeader = (header) => {
        let header_length = $(header).find("tr:first th").length;
        let filter_row = $("<tr id='filter_row'>")
        let header_column_offset = 0 
        
        for(let i=0; i< header_length; i++){
            let col_header = getColumnLabel(i)
            if('Event Name' == col_header){
                if(module.settings['event_name']){
                    filter_row.append("<th>")
                }
                header_column_offset++
            }
            else if(['Repeat Instrument','Repeat Instance'].includes(col_header)){
                if(module.settings['repeat_fields']){
                    header_column_offset++
                    filter_row.append("<th>")
                }
            }
            else{
                let new_th = $("<th>")
                if(isFilterColumn(i, header_column_offset)){
                    new_th.append(createDropdownFilter(i, module.settings.columns[i-header_column_offset-1]))
                }
                else{
                }
                filter_row.append(new_th)
            }

        }

        header.append(filter_row)
    }

    /*
    Wait for page to finish loading the report before deploying our filters.
    Full build out of the EM occurs here, we re-invoke if changing pages
    on a multipage report. 
    */
    const waitForLoad = () => {
        
        if ($("#report_table thead").length == 0 ||
        !$.fn.DataTable.isDataTable("#report_table")) { // Still Loading
            window.requestAnimationFrame(waitForLoad);
            return;
        }

        if(length(module.settings.columns) > 0){
            createFilterRow();
        }
        


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
        return 
    }

    waitForLoad();
});