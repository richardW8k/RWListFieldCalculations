<?php
/*
Plugin Name: Gravity Forms List Field Calculations Add-On
Plugin URI:
Description: A simple add-on to enable the use of List fields in calculations.
Version: 0.7
Author: Richard Wawrzyniak
Author URI:

------------------------------------------------------------------------
Copyright 2014 Richard Wawrzyniak

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
*/

//------------------------------------------

add_action( 'gform_loaded', function(){
    GFForms::include_addon_framework();

    class RWListFieldCalculations extends GFAddOn {

        protected $_version = '0.7';
        protected $_min_gravityforms_version = '2.3';
        protected $_slug = 'RWListFieldCalculations';
        protected $_path = 'RWListFieldCalculations/RWListFieldCalculations.php';
        protected $_full_path = __FILE__;
        protected $_title = 'Gravity Forms List Field Calculations Add-On';
        protected $_short_title = 'List Field Calculations';

        public function init() {
            parent::init();
            add_action( 'gform_enqueue_scripts', array( $this, 'list_field_calculations_script' ), 10, 2 );
            add_filter( 'gform_calculation_formula', array( $this, 'list_field_calculations' ), 10, 4 );
            add_filter( 'gform_custom_merge_tags', array( $this, 'list_field_calculations_merge_tags' ), 10, 4 );
        }

        public static function has_list_field_merge_tag( $form ) {
            foreach ( $form['fields'] as $field ) {
                if ( ! $field->has_calculation() ) {
                    continue;
                }

                preg_match_all( '/{[^{]*?:(\d+)\.?(\d+)?}/mi', $field->calculationFormula, $matches, PREG_SET_ORDER );

                if ( is_array( $matches ) ) {

                    foreach ( $matches as $match ) {

                        // get the $field object for the provided id
                        $field_id = $match[1];
                        $lfield   = GFAPI::get_field( $form, $field_id );

                        // check that we have a field
                        if ( ! $lfield ) {
                            continue;
                        }

                        // check the field type as we only want the rest of the function to run if the field type is list
                        if ( $lfield->get_input_type() != 'list' ) {
                            continue;
                        }

                        return true;

                    }

                }

            }

            return false;
        }

        public function list_field_calculations_script( $form ) {

            if ( self::has_list_field_merge_tag( $form ) ) {
                wp_enqueue_script( 'LFCalc', $this->get_base_url() . '/js/LFCalc.js', array(
                    'jquery',
                    'gform_gravityforms'
                ), $this->_version, true );
            }

        }

        public function list_field_calculations( $formula, $field, $form, $lead ) {

            // {List:1.3} - {Label:ID.Column} - sum column values
            // {List:1} - {Label:ID} - count rows
            preg_match_all( '/{[^{]*?:(\d+)\.?(\d+)?}/mi', $formula, $matches, PREG_SET_ORDER );

            if ( is_array( $matches ) ) {

                foreach ( $matches as $match ) {

                    // $match[0] = merge tag e.g. {List:1.3}
                    // $match[1] = field id e.g. 1
                    // $match[2] = column number e.g. 3

                    // get the $field object for the provided id
                    $field_id = $match[1];
                    $field    = GFAPI::get_field( $form, $field_id );

                    // check that we have a field
                    if ( ! $field ) {
                        continue;
                    }

                    // check the field type as we only want the rest of the function to run if the field type is list
                    if ( $field->get_input_type() != 'list' ) {
                        continue;
                    }

                    // get the list fields values from the $lead
                    $list_values = empty( $lead[ $field_id ] ) ? array() : unserialize( $lead[ $field_id ] );
                    $count       = 0;

                    // if column number found sum column values otherwise count number of rows
                    if ( isset( $match[2] ) ) {

                        // count the actual number of columns
                        $column_count = count( $field->choices );

                        if ( $column_count > 1 ) {
                            // subtract 1 from column number as the choices array is zero based
                            $column_num = $match[2] - 1;
                            // get the column label so we can use that as the key to the multi-column values
                            $column = rgars( $field->choices, "{$column_num}/text" );
                        }

                        foreach ( $list_values as $value ) {
                            if ( $column_count == 1 ) {
                                $count += GFCommon::to_number( $value );
                            } else {
                                $count += GFCommon::to_number( $value[ $column ] );
                            }
                        }

                    } else {
                        $count = count( $list_values );
                    }

                    // replace the merge tag with the row count or column sum
                    $formula = str_replace( $match[0], $count, $formula );

                }

            }

            // return the modified formula so the rest of the calculation can be run by GF
            return $formula;
        }

        public function list_field_calculations_merge_tags( $merge_tags, $form_id, $fields, $element_id ) {

            // check the type of merge tag dropdown
            if ( $element_id != 'field_calculation_formula' ) {
                return $merge_tags;
            }

            foreach ( $fields as $field ) {

                // check the field type as we only want to generate merge tags for list fields
                if ( $field->get_input_type() != 'list' ) {
                    continue;
                }

                $label = $field->label;
                $tag = '{' . $label . ':' . $field->id;
                $column_count = count( $field->choices );

                if ( $column_count > 1 ) {

                    $i = 0;

                    foreach ( $field->choices as $column ) {
                        $merge_tags[] = array(
                            'label' => $label . ' - ' . $column['text'] . ' (Column sum)',
                            'tag'   => $tag . '.' . ++ $i . '}'
                        );
                    }

                } else {
                    $merge_tags[] = array( 'label' => $label . ' (Column sum)', 'tag' => $tag . '.1}' );
                }

                $merge_tags[] = array( 'label' => $label . ' (Row count)', 'tag' => $tag . '}' );

            }

            return $merge_tags;
        }

    }

    new RWListFieldCalculations();
}, 10, 0 );
