function co_handle_combo_change() {
	var co_url = co_ajax_obj.ajax_url; 
	var co_offerlist = document.getElementById(co_ajax_obj.offerlist);
	var co_filter = document.getElementById(co_ajax_obj.filter);
    var loc = document.getElementById("co-location-filter");
    var cat = document.getElementById("co-category-filter");
    var fld = document.getElementById("co-field-filter");
    if ((loc === null) || (loc.selectedIndex == 0))
    	loc = "";
    else
    	loc = loc.value;
	if ((cat === null) || (cat.selectedIndex == 0))
    	cat = "";
    else
    	cat = cat.value;
    if ((fld === null) || (fld.selectedIndex == 0))
    	fld = "";
    else
    	fld = fld.value; 	   
    co_offerlist.innerHTML = '<div id="' + co_ajax_obj.offerlist + '">loading...</div>';
    //use in callback 
    // $ is not available here!
    jQuery.post(co_ajax_obj.ajax_url, {         //POST request
        _ajax_nonce: co_ajax_obj.nonce,    //nonce
        action: "co_filter_ajax",          //action
        cf_category: cat,				   //data	
        cf_location: loc,
        cf_field: fld
    }, function(data) {
    	var jsonObj = JSON.parse(data);
    	co_offerlist.innerHTML = jsonObj.offerlist;
    	co_filter.innerHTML = jsonObj.filter;
    	// Rehook new controles to the combo_change function ($ is not available here!)
    	jQuery(".co-filter-combo").change(function() {             
    		co_handle_combo_change();
        });
    });	
}

jQuery(document).ready(function($) {
	$(".co-filter-combo").change(function() {             //event
		co_handle_combo_change();
    });
});
