localizator.grid.Lexicon = function (config) {
    config = config || {};
    if (!config.id) {
        config.id = 'localizator-grid-lexicon';
    }

    Ext.applyIf(config, {
        url: MODx.config.connector_url,
        fields: this.getFields(config),
        columns: this.getColumns(config),
        tbar: this.getTopBar(config),
        sm: new Ext.grid.CheckboxSelectionModel(),
        baseParams: {
            action: 'workspace/lexicon/getList',
			namespace: 'localizator',
			topic: 'site',
			language: MODx.config.manager_language || 'en'
        },
        listeners: {
            /* rowDblClick: function (grid, rowIndex, e) {
                var row = grid.store.getAt(rowIndex);
                this.updateItem(grid, e, row);
            } */
        },
        viewConfig: {
            forceFit: true,
            enableRowBody: true,
            autoFill: true,
            showPreview: true,
            scrollOffset: 0,
           /*  getRowClass: function (rec) {
                return !rec.data.active
                    ? 'localizator-grid-row-disabled'
                    : '';
            } */
        },
        paging: true,
        remoteSort: true,
        autoHeight: true,

		autosave: true,
		save_action: 'workspace/lexicon/updatefromgrid',

    });
    localizator.grid.Lexicon.superclass.constructor.call(this, config);

    // Clear selection on grid refresh
    this.store.on('load', function () {
        /* if (this._getSelectedIds().length) {
            this.getSelectionModel().clearSelections();
        } */
    }, this);
};

Ext.extend(localizator.grid.Lexicon, MODx.grid.Grid, {
    windows: {},

	getFields: function () {
        return ['name','value','namespace','topic','language','editedon','overridden'];
    },

    getColumns: function () {
        return [{
            header: _('localizator_lexicon_name'),
            dataIndex: 'name',
            sortable: true,
            width: 200,
        }, {
            header: _('localizator_lexicon_value'),
            dataIndex: 'value',
            sortable: true,
            width: 200,
			editor: {xtype: 'textarea'},
        }, {
            header: _('localizator_lexicon_language'),
            dataIndex: 'language',
            sortable: true,
            width: 200,
        }, {
            header: _('last_modified'),
            dataIndex: 'editedon',
            renderer: this._renderLastModDate,
        }];
    },

	getTopBar: function () {
        return [{
            text: '<i class="icon icon-globe"></i>&nbsp;' + _('localizator_lexicon_create'),
            handler: this.createItem,
            scope: this
        }, {
            text: '<i class="icon icon-language"></i>&nbsp;' + _('localizator_translate'),
            handler: this.translateItem,
            scope: this
        }, {
            xtype: 'modx-combo-language',
            name: 'language',
            id: 'localizator-lexicon-filter-language',
            itemId: 'language',
            value: MODx.config.manager_language || 'en',
            width: 100,
			url: localizator.config.connector_url,
            baseParams: {
                action: 'mgr/lexicon/getlanguages',
            },
            listeners: {
               select: {
					fn: function(v) {
						this.store.baseParams['language'] = v.getValue();
						this.getBottomToolbar().changePage(1);
					}, scope:this
				}
            }
        }, '->', {
            xtype: 'localizator-field-search',
            width: 250,
            listeners: {
                search: {
                    fn: function (field) {
                        this._doSearch(field);
                    }, scope: this
                },
                clear: {
                    fn: function (field) {
                        field.setValue('');
                        this._clearSearch();
                    }, scope: this
                },
            }
        }];
    },

	createItem: function (btn, e) {
        var w = MODx.load({
            xtype: 'localizator-lexicon-window-create',
            id: Ext.id(),
            listeners: {
                success: {
                    fn: function () {
                        this.refresh();
                    }, scope: this
                }
            }
        });
        w.reset();
        w.setValues({ language: this.store.baseParams['language'] });
        w.show(e.target);
    },

	translateItem: function (btn, e) {
        Ext.MessageBox.confirm(
            _('localizator_translate'),
            _('localizator_translate_confirm'),
            function (val) {
                if (val == 'yes') {
                    this._translation(0);
                }
            },
            this
        );
    },

	_translation: function (start) {
        if (!this._wait) {
            this._wait = Ext.MessageBox.wait(
                _('localizator_translate_wait'),
                _('please_wait')
            );
        }
        MODx.Ajax.request({
            url: localizator.config.connector_url,
            params: {
                action: 'mgr/lexicon/translate',
                start: start || 0,
            },
            listeners: {
                success: {
                    fn: function (response) {
						if (response.object['total'] == response.object['processed']) {
                            this._wait.hide();
                            this._wait = null;
                            this.refresh();
                        } else {
                            this._wait.updateText(
                                _('localizator_translate_wait_ext')
                                    .replace('[[+processed]]', response.object['processed'])
                                    .replace('[[+total]]', response.object['total'])
                            );
                            this._translation(response.object['processed']);
                        }

                        if (response.message) {
                            MODx.msg.status({
                                title: '',
                                message: response.message,
                                delay: 7
                            });
                        } else {
                            MODx.msg.status({
                                title: _('localizator_translate_wait_ext'),
                                message: _('localizator_translate_processed') + response.object['processed'],
                                delay: 3
                            });
                        }
 
                        

                    }, scope: this
                },
				failure: {
					fn: function(response) {
						this._wait.hide();
						this._wait = null;
						this.refresh();
						MODx.msg.alert(_('error'), response.message);
					}, scope: this
				}
            }
        });
    },

	_renderLastModDate: function(value) {
        if (Ext.isEmpty(value)) {
            return '—';
        }

        return new Date(value*1000).format(MODx.config.manager_date_format + ' ' + MODx.config.manager_time_format);
    },

	_doSearch: function (tf) {
        this.getStore().baseParams.search = tf.getValue();
        this.getBottomToolbar().changePage(1);
    },

    _clearSearch: function () {
        this.getStore().baseParams.search = '';
        this.getBottomToolbar().changePage(1);
    },

});
Ext.reg('localizator-grid-lexicon', localizator.grid.Lexicon);


localizator.window.CreateLexicon = function (config) {
    config = config || {};
    if (!config.id) {
        config.id = 'localizator-lexicon-window-create';
    }
    Ext.applyIf(config, {
        title: _('localizator_lexicon_create'),
        autoHeight: true,
        url: MODx.config.connector_url,
        action: 'workspace/lexicon/create',
        fields: this.getFields(config),
        keys: [{
            key: Ext.EventObject.ENTER, shift: true, fn: function () {
                this.submit()
            }, scope: this
        }]
    });
    localizator.window.CreateLexicon.superclass.constructor.call(this, config);

	this.on('show', function() {
		this.setValues({ namespace: 'localizator', topic: 'site' });
	}.bind(this));
};
Ext.extend(localizator.window.CreateLexicon, MODx.Window, {

    getFields: function (config) {
        return [{
			 xtype: 'hidden',
			 name: 'namespace',
			 id: config.id + '-namespace',
		}, {
			 xtype: 'hidden',
			 name: 'topic',
			 id: config.id + '-topic',
		}, {
            xtype: 'textfield',
			fieldLabel: _('localizator_lexicon_name'),
            name: 'name',
            id: config.id + '-name',
			allowBlank: false,
			anchor: '99%',
        }, {
            xtype: 'textfield',
			fieldLabel: _('localizator_lexicon_language'),
            name: 'language',
            id: config.id + '-language',
			allowBlank: false,
			anchor: '99%',
        }, {
			xtype: 'textarea',
			fieldLabel: _('localizator_lexicon_value'),
            name: 'value',
            id: config.id + '-value',
			anchor: '99%',
		}];
    },

});
Ext.reg('localizator-lexicon-window-create', localizator.window.CreateLexicon);