/*
 * License:

 Copyright 2016 - Stranger Studios, LLC

 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License, version 2, as
 published by the Free Software Foundation.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

jQuery(document).ready( function() {
    "use strict";

    var lastselectedlevel;

    var selectedlevels = pmprolvl.selectedlevels; // []; (array)
    var currentlevels = pmprolvl.currentlevels;
    var level_elements = pmprolvl.levelelements;
    var alllevels = pmprolvl.alllevels;

    for ( var i = 0, len = selectedlevels.length ; i < len ; i++ ) {

        jQuery( level_elements[i] ).prop('disabled', false).removeClass("unselected").addClass("selected").val( pmprolvl.lang.selected_label );
    }

    updateLevelSummary();

    // Configure page
    var removedlevels = {};
    var addedlevels = {};

    var level_select_input = jQuery(".pmpro_level-select input");

    level_select_input.bind('change', function () {

        var select = jQuery(this);

        var newlevelid;

        //they clicked on a checkbox
        var checked = select.is(':checked');
        var mygroup = select.attr('data-groupid');

        if (!checked) {

            // we are deselecting a level
            newlevelid = parseInt(select.attr('id').replace(/\D/g, ''));

            selectedlevels = removeFromArray(select.attr('id'), selectedlevels);

            select.parent().removeClass("pmpro_level-select-current");
            select.parent().removeClass("pmpro_level-select-selected");

            //? change wording of label?
            delete addedlevels[newlevelid];

            if (currentlevels.hasOwnProperty(newlevelid)) {
                removedlevels[newlevelid] = alllevels[newlevelid];
                jQuery(this).parent().addClass("pmpro_level-select-removed");
            }
        } else {
            // we selecting a level

            //if a one-of, deselect all levels in this group
            if (select.parents('div.selectone').length > 0) {

                var groupinputs = jQuery('input[data-groupid=' + mygroup + ']');

                //remove all levels from this group
                groupinputs.each( function() {

                    var item = jQuery(this);
                    var item_id = parseInt(item.attr('id').replace(/\D/g, ''));

                    //deselect
                    if (item.prop('checked') && currentlevels.hasOwnProperty(item_id)) {
                        item.parent().addClass("pmpro_level-select-removed");
                        removedlevels[item_id] = alllevels[item_id];
                    }
                    item.prop('checked', false);

                    //update arrays
                    selectedlevels = removeFromArray(item.attr('id'), selectedlevels);
                    delete addedlevels[item_id];

                    //update styles
                    item.parent().removeClass("pmpro_level-select-current");
                    item.parent().removeClass("pmpro_level-select-selected");
                })

            }

            //select the one we just clicked on
            select.prop('checked', true);
            selectedlevels.push(select.attr('id'));

            newlevelid = parseInt(select.attr('id').replace(/\D/g, ''));

            select.parent().removeClass("pmpro_level-select-removed");

            //? change the wording of the label?
            delete removedlevels[newlevelid];

            if (currentlevels.hasOwnProperty(newlevelid)) {

                delete addedlevels[newlevelid];
                select.parent().addClass("pmpro_level-select-current");
            } else {

                addedlevels[newlevelid] = alllevels[newlevelid];
                select.parent().addClass("pmpro_level-select-selected");
            }
        }

        updateLevelSummary();
    });
    jQuery(".pmpro_mmpu_checkout-button").click(function () {

        var addlevs = joinObjectKeys("+", addedlevels);
        var dellevs = joinObjectKeys("+", removedlevels);
        var url;

        if (jQuery.isEmptyObject(addedlevels) && !jQuery.isEmptyObject(removedlevels)) {

            //only removing, go to cancel
            url = pmprolvl.settings.cancel_lnk;

            if (url.indexOf('?') > -1) {

                url = url + '&levelstocancel=' + dellevs;
            } else {
                url = url + '?levelstocancel=' + dellevs;
            }
        } else {
            //go to checkout
            url = pmprolvl.settings.checkout_lnk;

            if (url.indexOf('?') > -1) {
                url = url + '&level=' + addlevs + '&dellevels=' + dellevs;
            } else {
                url = url + '?level=' + addlevs + '&dellevels=' + dellevs;
            }
        }

        window.location.href = url;
    });

    level_select_input.change();

    function updateLevelSummary() {

        var message = "";
        var cancheckout = false;

        if (numOfPropsInObject(currentlevels) < 1 && numOfPropsInObject(removedlevels) < 1 && numOfPropsInObject(addedlevels) < 1) {
            message = pmprolvl.lang.no_levels_selected;
        } else {
            if (numOfPropsInObject(currentlevels) > 0) {
                message += "<p class='mmpu_currentlevels'><label for='mmpu_currentlevels'>";
                message += pmprolvl.lang.current_levels;
                message += "</label>";
                message += joinObjectProps(", ", currentlevels);
                message += "</p>";
            } else {
                message += "<p class='mmpu_currentlevels'><label for='mmpu_currentlevels'>";
                message += pmprolvl.lang.current_levels;
                message += "</label>";
                message += pmprolvl.lang.none;
                message += ".</p>";
            }
            if (numOfPropsInObject(addedlevels) > 0) {
                message += "<p class='mmpu_addedlevels'><label for='mmpu_addedlevels'>";
                message += pmprolvl.lang.added_levels;
                message += "</label>";
                message += joinObjectProps(", ", addedlevels);
                message += "</p>";
                cancheckout = true;
            } else {
                message += "<p class='mmpu_addedlevels'><label for='mmpu_addedlevels'>";
                message += pmprolvl.lang.added_levels;
                message += "</label>";
                message += pmprolvl.lang.none;
                message += ".</p>";
            }
            if (numOfPropsInObject(removedlevels) > 0) {
                message += "<p class='mmpu_removedlevels'><label for='mmpu_removedlevels'>";
                message += pmprolvl.lang.removed_levels;
                message += "</label>";
                message += joinObjectProps(", ", removedlevels);
                message += "</p>";
                cancheckout = true;
            } else {
                message += "<p class='mmpu_removedlevels'><label for='mmpu_removedlevels'>";
                message += pmprolvl.lang.removed_levels;
                message += "</label>";
                message += pmprolvl.lang.none;
                message += ".</p>";
            }

        }
        jQuery("#pmpro_mmpu_level_summary").html(message);
        if (true === cancheckout) {
            jQuery('.pmpro_mmpu_checkout-button').prop('disabled', false);
        } else {
            jQuery('.pmpro_mmpu_checkout-button').prop('disabled', true);
        }
    }
    function removeFromArray(elemtoremove, array) {

        for (var arritem in array) {
            if ( array.hasOwnProperty(arritem) && array[arritem] === elemtoremove) {
                array.splice(arritem, 1);
            }
        }
        return array;
    }
    function numOfPropsInObject(object) {

        var count = 0;
        for (var k in object) {
            if (object.hasOwnProperty(k)) { ++count; }
        }
        return count;
    }
    function joinObjectProps(separator, object) {
        var result = "";
        for (var k in object) {
            if (object.hasOwnProperty(k)) {
                if (result.length > 0) {
                    result += separator;
                }
                result += object[k];
            }
        }
        return result;
    }
    function joinObjectKeys(separator, object) {

        var result = "";
        for (var k in object) {
            if (object.hasOwnProperty(k)) {
                if (result.length > 0) {
                    result += separator;
                }
                result += k;
            }
        }
        return result;
    }
});

