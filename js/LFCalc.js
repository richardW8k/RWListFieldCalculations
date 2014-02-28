gform.addFilter( 'gform_calculation_result', function(result, formulaField, formId, calcObj) {
    //console.log(calcObj);
    console.log('addFilter: starting');
    for (var i = 0; i < LFCalc.length; i++) {
        console.log('addFilter: retrieving the LFCalc var =>');
        console.log(LFCalc[i]);

        var fieldId = LFCalc[i].fieldId,
            columnNo = LFCalc[i].columnNo,
            mergeTag = LFCalc[i].mergeTag,
            listField = '#field_' + formId + '_' + fieldId,
            columnSelector = '.gfield_list_' + fieldId + '_cell' + columnNo + ' :input',
            listTotal = 0,
            cellValue = 0;

        if( formulaField.formula.search(mergeTag) == -1 ) {
            console.log('addFilter: ' + mergeTag + ' not found in ' + formulaField.formula);
            continue;
        }

        if ( columnNo !== null ) {

            jQuery(columnSelector).each(function () {
                console.log('addFilter: counting the column input values');
                cellValue = gformToNumber(jQuery(this).val());
                listTotal += parseFloat(cellValue) || 0;
            });

            jQuery(listField).on('change', columnSelector, function () {
                console.log('addFilter: list field column input change detected');
                calcObj.runCalc(formulaField, formId);
            });

        } else {
            console.log('addFilter: counting the list field rows');
            listTotal = jQuery(listField).find('table.gfield_list tbody tr').length;
        }

        jQuery(listField).on('click', '.add_list_item', function () {
            console.log('addFilter: add row button clicked');
            jQuery(listField + ' .delete_list_item').removeProp('onclick');
            calcObj.runCalc(formulaField, formId);
        }).on('click', '.delete_list_item', function () {
            console.log('addFilter: delete row button clicked');
            gformDeleteListItem(this, 0);
            calcObj.runCalc(formulaField, formId);
        });

        var adjustedFormula = formulaField.formula.replace(mergeTag, listTotal);
        console.log('addFilter: replacing the merge tag with the list field column or row total');
        expr = calcObj.replaceFieldTags(formId, adjustedFormula, formulaField);

        if(calcObj.exprPatt.test(expr)) {
            try {
                //run calculation
                result = eval(expr);
            } catch (e) {}
        }

    }

    console.log('addFilter: returning the result: ' + result);
    return result;

});
