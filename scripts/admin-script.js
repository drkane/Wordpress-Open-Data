jQuery(document).ready(function(){

    jQuery('#new_column').click(function(event) {
		var column_match = /\[([0-9]+)\]/gim;
		var column_number = jQuery('table#table_columns tbody tr').length + 1;
		var new_row = jQuery('table#table_columns tbody tr:last-child').clone();
		jQuery(new_row).find('td input').each( function (index) {
			jQuery(this).attr("name", function (index2, attr) {
				return attr.replace(column_match, '[' + column_number + ']');
			});
		});
		jQuery(new_row).attr("id", 'column_' + column_number);
		jQuery(new_row).find('td input[name$="[order]"]').val(column_number);
		jQuery(new_row).find('td input[name$="[previous_name]"]').val('');
		jQuery(new_row).find('td input[name$="[id]"]').val(column_number);
		jQuery(new_row).find('td input[name$="[id]"]').removeAttr("checked");
		jQuery(new_row).appendTo('table#table_columns tbody');
		event.preventDefault();
	});
	
    jQuery('table#table_columns tbody').on("click", '.column_delete', function(event) {
		if(jQuery('table#table_columns tbody tr').length>1){
			jQuery(event.currentTarget).parent().parent().css("display", "none");
			jQuery(event.currentTarget).parent().parent().find('input[name$="[delete]"]').val("true");
			jQuery(event.currentTarget).parent().parent().attr('id', function(index, existing){
				return existing + '_deleted';
			});
			reorder_columns();
		} else {
			alert('Cannot delete: table must have one column');
		}
		event.preventDefault();
	});
	
	function reorder_columns(){
		var column_match = /\[([0-9]+)\]/gim;
		var column_number = 1;
		jQuery('table#table_columns tbody tr').each( function(index2) {
			if(jQuery(this).css("display")!="none"){
				jQuery('[name^="[columns]["]', this).each( function(index) {
					var current_name = jQuery(this).attr("name");
					var new_name = current_name.replace(column_match, '[' + column_number + ']');
					jQuery(this).attr("name", new_name);
				});
				jQuery('[name$="[order]"]', this).each( function(index) {
					jQuery(this).val(column_number);
				});
				jQuery(this).attr("id", 'column_' + column_number);
				column_number++;
			}
		});
	}
	
	jQuery('table#table_columns tbody').sortable({
		stop: function() { reorder_columns(); }
	});
	
	jQuery('input#submit').click( function(event) {
		var columns = new Array();
		var errors = '';
		
		if(jQuery('#od_new_table_name').val()==''){
			errors += '<p>No table name given</p>';
		}
		
		jQuery('table#table_columns tbody tr input[name$="[nice_name]"]').each( function(index) {
			if(jQuery.inArray(jQuery(this).val(), columns)>=0){
				errors += '<p>Column name "' + jQuery(this).val() + '" already exists</p>';
			}
			columns.push(jQuery(this).val());
		});
		
		if(errors!=''){
			jQuery(this).before('<div class="error"><h3>Could not save</h3>' + errors + '</div>');
			event.preventDefault();
		}
	});
	
	jQuery("#tabs").tabs();
	
});