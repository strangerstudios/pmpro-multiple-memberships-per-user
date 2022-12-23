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
jQuery(document).ready(function ($) {
    "use strict";

    var add_new_elem = $('#add-new-group');
    var gn_element = $("#groupname");

    add_new_elem.insertAfter("h2 .add-new-h2");

    add_new_elem.click(function () {
        var dialog = $("#addeditgroupdialog").dialog({
            autoOpen: false,
            title: "Add Group",
            modal: true,
            buttons: {
                "Add": function () {
                    if (gn_element.val().length > 0) {
                        var groupname = $("#groupname").val();
                        var allowmult = 0;
                        if ($("#groupallowmult").attr("checked")) {
                            allowmult = 1;
                        }
                        dialog.dialog("close");
                        $.post(ajaxurl, {
                            action: "pmprommpu_add_group",
                            name: groupname,
                            mult: allowmult
                        }, function () {
                            window.location = pmprommpu.settings.level_page_url;
                        });
                    }
                },
                "Cancel": function () {
                    dialog.dialog("close");
                }
            }
        });
        dialog.dialog("open");
        $('.ui-dialog-buttonset .ui-button:first-child').addClass('button button-primary save alignright');
        $('.ui-dialog-buttonset .ui-button:nth-child(2)').addClass('button cancel alignleft');
    });

    $(".editgrpbutt").click(function () {

        var groupid = parseInt($(this).attr("data-groupid"), 10);
        var allow_multi = $("#groupallowmult");

        var $current_text  = $(this).closest('th').find('h2').text();
        var $current_multi = $(this).closest('th').find('.pmprommpu-allow-multi').val();

        window.console.log("Text for row: " + $current_text );

        if (groupid > 0) {
            gn_element.val($current_text);

            if (parseInt($current_multi) > 0) {
                allow_multi.attr('checked', true);
            } else {
                allow_multi.attr('checked', false);
            }

            var dialog = $("#addeditgroupdialog").dialog({
                autoOpen: false,
                title: "Edit Group",
                modal: true,
                buttons: {
                    "Save": function () {

                        var element_val = gn_element.val();

                        if (element_val.length > 0) {
                            var groupname = element_val;
                            var allowmult = 0;
                            if ( $("#groupallowmult:checked").length > 0 ) {
                                allowmult = 1;
                            }
                            dialog.dialog("close");
                            $.post(ajaxurl, {
                                action: "pmprommpu_edit_group",
                                group: groupid,
                                name: groupname,
                                mult: allowmult
                            }, function () {
                                window.location = pmprommpu.settings.level_page_url;
                            });
                        }
                    },
                    "Cancel": function () {
                        dialog.dialog("close");
                    }
                }
            });
            dialog.dialog("open");
            $('.ui-dialog-buttonset .ui-button:first-child').addClass('button button-primary save alignright');
            $('.ui-dialog-buttonset .ui-button:nth-child(2)').addClass('button cancel alignleft');
        }
    });
    $(".delgroupbutt").click(function () {
        var groupid = parseInt($(this).attr("data-groupid"), 10);
        if (groupid > 0) {
            var answer = window.confirm(pmprommpu.lang.confirm_delete);
            if (true === answer) {
                $.post(
                    ajaxurl,
                    {
                        action: "pmprommpu_del_group",
                        group: groupid
                    },
                    function () {
                        window.location.reload(true);
                    }
                );
            }
        }
    });

    // Return a helper with preserved width of cells
    // from http://www.foliotek.com/devblog/make-table-rows-sortable-using-jquery-ui-sortable/
    var fixHelper = function (e, ui) {
        ui.children().each(function () {
            $(this).width($(this).width());
        });
        return ui;
    };

    $("table.mmpu-membership-levels").sortable({
        helper: fixHelper,
        update: update_level_and_group_order
    });

    $("table.mmpu-membership-levels tbody").sortable({
        items: "tr.levelrow",
        helper: fixHelper,
        placeholder: 'testclass',
        forcePlaceholderSize: true,
        update: update_level_and_group_order
    });

    function update_level_and_group_order(event, ui) {
        var groupsnlevels = [];
        $("tbody").each(function () {
            var groupid = $(this).attr('data-groupid');
            var curlevels = [];
            $(this).children("tr.levelrow").each(function () {
                curlevels.push(parseInt($("td.levelid", this).text(), 10));
            });
            groupsnlevels.push({group: groupid, levels: curlevels});
        });

        var data = {
            action: 'pmprommpu_update_level_and_group_order',
            neworder: groupsnlevels
        };
        $.post(ajaxurl, data, function (response) {
        });
    }
});