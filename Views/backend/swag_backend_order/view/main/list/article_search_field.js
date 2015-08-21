//
//{block name="backend/create_backend_order/view/list/article_search_field"}
//
Ext.define('Shopware.apps.SwagBackendOrder.view.main.list.ArticleSearchField', {

    extend: 'Shopware.form.field.ArticleSearch',

    /**
     * override the initComponent method to implement the variant article search
     */
    initComponent: function () {
        var me = this;

        me.registerEvents();

        if (!(me.articleStore instanceof Ext.data.Store)) {
            me.articleStore = Ext.create('Shopware.apps.Base.store.Article');
        }

        // We need to filter the store on loading to prevent to show the first article in the store on startup
        me.dropDownStore = Ext.create('Shopware.apps.SwagBackendOrder.store.Article', {
            listeners: {
                single: true,
                load: function () {
                    me.loadArticleStore(me.articleStore);
                }
            }
        });

        //article store passed to the component?
        if (Ext.isObject(me.articleStore) && me.articleStore.data.items.length > 0) {
            me.loadArticleStore(me.articleStore);
        }

        me.hiddenField = me.createHiddenField();
        me.searchField = me.createSearchField();
        me.dropDownMenu = me.createDropDownMenu();
        me.items = [me.hiddenField, me.searchField, me.dropDownMenu];

        me.dropDownStore.on('datachanged', me.onSearchFinish, me);

        this.superclass.superclass.initComponent.call(this);
    }
});
//
//{/block}