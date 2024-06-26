$(document).ready(() => {

    let module = ExternalModules.JFortriede.ReportFilters;
    let modalSettings = {};
    let html = {};
    
    if(module.debug_mode && module.debug_mode <= 20){   // Write if Debug_mode is Info or Debug
        console.log("Report Filters Module Loaded");
    }

    if(module.debug_mode && module.debug_mode <= 10){   // Write if Debug_mode is Debug
        console.debug(module);
    }
    /*
    Load existing settings and populate the choices onto the page
    */

    // console.log('module',module)

    const loadSettings = () => {
        if('settings' in module && 'columns' in module.settings){
            module.settings.columns.forEach((field) => {
                $('tr.field_row:has(input[name="field[]"][value="'+field+'"])').find("input.filterColumn").prop('checked', true);
            });

            if(module.debug_mode && module.debug_mode <= 10){   // Write if Debug_mode is Debug
                console.debug("Module Settings", module.settings);
            }
        }

    }

    /*
    Load existing settings and populate the choices onto the page
    */
    const saveSettings = () => {

        let settings = {'columns':[]};
        // Collect all current settings 
        $('tr.field_row:has(input.filterColumn:checked)').find("input[name='field[]']").each(function(){settings['columns'].push($(this).val())})
        // Post back to DB
        
        if(module.debug_mode && module.debug_mode <= 10){   // Write if Debug_mode is Debug
            console.debug("Save settings", settings);
        }

        $.ajax({
            method: 'POST',
            url: module.router,
            data: {
                route: 'saveConfig',
                report: module.getUrlParameter('report_id'),
                settings: JSON.stringify(settings),
                redcap_csrf_token: module.csrf
            },
            error: (jqXHR, textStatus, errorThrown) => console.error(`${jqXHR}\n${textStatus}\n${errorThrown}`),
            success: () => console.log("Report Filters Settings Saved")
        });
    }

    // Load the templates
    function addFilterColumnCheckbox(elem){
        if( $(elem).find('td:nth-child(3):has(a[style*="display:none"])').length > 0){
            return
        }
        var checkbox = $("<td class='labelrc'><input type='checkbox' name='filter_columns' value='' class='filterColumn' title='"+module.tt.add_filter_checkbox_title+"'/></td>")
        $(elem).append(checkbox)
    }
    
    function findRowsMissingCheckbox(){
        $(report_table_body).find('tr.field_row:not(:has(td input.filterColumn))').each(function(){
            addFilterColumnCheckbox(this)
        })
    
    }
    
    const report_table_body = document.querySelector("#create_report_table tbody");
    
      // Add Filter Column
    $(report_table_body).find("td.create_rprt_hdr:has(#add_form_field_dropdown)").after($("<td class='labelrc create_rprt_hdr'>"+ module.tt.add_filter+ "</td>"))
    
      // Options for the observer (which mutations to observe)
    const config = { attributes: true, childList: true, subtree: false };
    

      // Callback function to execute when mutations are observed
    const callback = (mutationList, report_table_observer) => {
        for (const mutation of mutationList) {
            if (mutation.type === "childList" && mutation.addedNodes.length > 0 && $(mutation.addedNodes[0]).hasClass('field_row')) {
                findRowsMissingCheckbox()

            }
        }
    };
    
      // Create an observer instance linked to the callback function
    const report_table_observer = new MutationObserver(callback);
    
      // Start observing the target node for configured mutations
    report_table_observer.observe(report_table_body, config);

    findRowsMissingCheckbox();

    // Load settings and prep them clicks (or, if new report, disable the buttons)
    loadSettings();
    if (module.getUrlParameter('report_id')) {
        $("#save-report-btn").on('click', saveSettings);
    } else {
        $("input[name^=filters_]").prop('disabled', true);
    }
});