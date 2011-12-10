mxcCore.panel.Home = function(config) {
    config = config || {};
    Ext.apply(config,{
        border: false
        ,baseCls: 'modx-formpanel'
        ,items: [{
            html: '<img src="/mxcalendars/assets/components/mxcalendars/images/mxcalendar.png" alt="'+_('mxcalendars.management')+'" />'
            ,border: false
            ,cls: 'modx-page-header'
        },{
            xtype: 'modx-tabs'
            ,bodyStyle: 'padding: 10px'
            ,defaults: { border: false,autoHeight: true }
            ,border: true
            ,items: [{
                title: _('mxcalendars.tab_events')
                ,defaults: { autoHeight: true }
                ,items: [
					// ADD DESCRIPTION INFORMATION
					{
						html: '<p>'+_('mxcalendars.management_desc')+'</p><br />'
						,border: false
					},
					// ADD THE GRID CONTROLLER
					{
					   xtype: 'mxcalendars-grid-events'
					   ,preventRender: true
					}
					]
				},{
				// Second Tab
				title: _('mxcalendars.tab_categories')
                ,defaults: { autoHeight: true }
				,items: [{
                        html: '<p>'+_('mxcalendars.category_desc')+'</p><br />'
                       ,border: false
                    },{
                        xtype: 'mxcalendars-grid-categories'
                        ,preventRender: true
                    }]
				},{
				// Third Tab
				title: _('mxcalendars.tab_settings')
				,items: [{

                    }]
				}]
        }]
    });
    mxcCore.panel.Home.superclass.constructor.call(this,config);
};
Ext.extend(mxcCore.panel.Home, MODx.Panel);
Ext.reg('mxcalendars-panel-home', mxcCore.panel.Home);