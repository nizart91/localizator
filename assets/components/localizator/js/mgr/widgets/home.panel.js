localizator.panel.Home = function (config) {
    config = config || {};
    Ext.apply(config, {
        baseCls: 'modx-formpanel',
        layout: 'anchor',
        /*
         stateful: true,
         stateId: 'localizator-panel-home',
         stateEvents: ['tabchange'],
         getState:function() {return {activeTab:this.items.indexOf(this.getActiveTab())};},
         */
        hideMode: 'offsets',
        items: [{
            html: '<h2>' + _('localizator') + '</h2>',
            cls: '',
            style: {margin: '15px'}
        }, {
            xtype: 'modx-tabs',
            defaults: {border: false, autoHeight: true},
            border: true,
            hideMode: 'offsets',
            items: [{
                title: _('localizator_languages'),
                layout: 'anchor',
                items: [{
                    xtype: 'localizator-grid-language',
                    cls: 'main-wrapper',
                }]
            }, {
                title: _('localizator_lexicon'),
                layout: 'anchor',
                items: [{
                    xtype: 'localizator-grid-lexicon',
                    cls: 'main-wrapper',
                }]
            }]
        }]
    });
    localizator.panel.Home.superclass.constructor.call(this, config);
};
Ext.extend(localizator.panel.Home, MODx.Panel);
Ext.reg('localizator-panel-home', localizator.panel.Home);
