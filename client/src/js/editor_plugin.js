var shortcodable_editorplugin = {

    editor : null,

    getInfo: function () {
        return {
            longname: 'shortcodable_editorplugin - Shortcode UI plugin for SilverStripe',
            version: "2.0"
        };
    },

    init: function (editor) {
        // save some references
        this.editor = editor;
        if(!shortcodable.initialized) {
            shortcodable.init();
        }
    },

    // Plaintxt source [shortcodetags] -> WYSIWYG HTML: Substitute shortcodes with image placeholders
    fromSrc : function(source, editor){
        // return source;
        return shortcodable.shortcodesToPlaceholders(source, editor);
    },

    // Replace HTML placeholder tags (<img>) with shortcodes
    toSrc : function(source, editor){
        return shortcodable.placeholdersToShortcodes(source, editor);
    },

};

// TinyMCE plugin definitions
(function() {
    if (typeof tinymce !== 'undefined') {
        //
        // TinyMCE~3 definition @TODO (or not... Since we're on TinyMCE 4+ anyway in Silverstripe 4+)
        if(tinymce.majorVersion < 4){}
        // TinyMCE 4+ definition (front-end/newest version)
        else {

            tinymce.PluginManager.add('shortcodable', function(editor, url) {

                // init (set listeners etc)
                shortcodable_editorplugin.init(editor);

                // Set some listeners
                editor.on('beforeSetContent', function(e) {
                    e.content = shortcodable_editorplugin.fromSrc(e.content, editor);
                });
                editor.on('postProcess', function(e) {
                    e.content = shortcodable_editorplugin.toSrc(e.content, editor);
                });

                // add button
                editor.ui.registry.addButton('shortcodable', {
                    classes: 'shortcodable',
                    tooltip: 'Insert/edit shortcode',
                    onAction: shortcodable.openDialog,
                });

            });

        }
    }

})();
