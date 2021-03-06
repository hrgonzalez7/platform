define([
    './model-action'
], function(ModelAction) {
    'use strict';

    var AjaxAction;

    /**
     * Ajax action, triggers REST AJAX request
     *
     * @export  oro/datagrid/action/ajax-action
     * @class   oro.datagrid.action.AjaxAction
     * @extends oro.datagrid.action.ModelAction
     */
    AjaxAction = ModelAction.extend({
        /** @property {String} */
        requestType: 'POST',

        /**
         * @inheritDoc
         */
        constructor: function AjaxAction() {
            AjaxAction.__super__.constructor.apply(this, arguments);
        }
    });

    return AjaxAction;
});
