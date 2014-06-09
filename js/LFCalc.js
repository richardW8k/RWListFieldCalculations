gform.addAction( 'gform_calculation_events', function( mergeTag, formulaField, formId, calcObj ){
    var inputId = mergeTag[1],
        fieldId = parseInt( inputId ),
        fieldSelector = '#field_' + formId + '_' + fieldId;

    if ( jQuery( fieldSelector + ' table.gfield_list' ).length == 1 ) {
        jQuery( fieldSelector )
            .on( 'click', '.add_list_item', function () {
                jQuery( fieldSelector + ' .delete_list_item' ).removeProp( 'onclick' );
                calcObj.bindCalcEvent( inputId, formulaField, formId, 0 );
            })
            .on( 'click', '.delete_list_item', function () {
                gformDeleteListItem( this, 0 );
                calcObj.bindCalcEvent( inputId, formulaField, formId, 0 );
            });

        if ( mergeTag[2] != null ) {
            var columnNo = mergeTag[2].substr( 1 ),
                columnSelector = '.gfield_list_' + fieldId + '_cell' + columnNo + ' :input';
            jQuery( fieldSelector ).on( 'change', columnSelector, function () {
                calcObj.bindCalcEvent( inputId, formulaField, formId, 0 );
            });
        }
    }
});

gform.addFilter( 'gform_calculation_merge_tag_value', function( value, mergeTag, formulaField, formId ){
    var inputId = mergeTag[1],
        fieldId = parseInt( inputId ),
        fieldSelector = '#field_' + formId + '_' + fieldId,
        cellValue = 0;

    if ( jQuery( fieldSelector + ' table.gfield_list' ).length == 1 ) {

        if ( mergeTag[2] == null ) {
            // if no column specified count the rows instead
            value = jQuery( fieldSelector + ' table.gfield_list tbody tr' ).length;
        } else {
            var columnNo = mergeTag[2].substr( 1 ),
                columnSelector = '.gfield_list_' + fieldId + '_cell' + columnNo + ' :input';

            jQuery( columnSelector ).each( function () {
                cellValue = gformToNumber( jQuery( this ).val() );
                value += parseFloat( cellValue ) || 0;
            });
        }

    }

    return value;
});
