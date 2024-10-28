/*  Copyright 2012  ROMAN MANUKYAN  (email : romanwebdev@gmail.com)

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

if (!Array.prototype.indexOf) {
  Array.prototype.indexOf = function (obj, fromIndex) {
    if (fromIndex == null) {
        fromIndex = 0;
    } else if (fromIndex < 0) {
        fromIndex = Math.max(0, this.length + fromIndex);
    }
    for (var i = fromIndex, j = this.length; i < j; i++) {
        if (this[i] === obj)
            return i;
    }
    return -1;
  };
}
function clone(obj) {
    if (null == obj || "object" != typeof obj) return obj;
    var copy = obj.constructor();
    for (var attr in obj) {
        if (obj.hasOwnProperty(attr)) copy[attr] = obj[attr];
    }
    return copy;
}
_log = function( obj1, obj2 ){
    
    if( window.console && console.log ){
        if( obj2 ){
            console.log(obj1, obj2);
        }else{
            console.log(obj1);
        }
    }
}


    
 GzalienAPO = Backbone.Model.extend({
     
    defaults: {  
        saveAction : 'gzalien_apo_save',
        methods:[], 
        methodCaptions:{},
        size:null,
        style:null,
        headerText : 'We Accept These Payment Methods',
        headerTextColor : '#000000',
        panelBgColor : '#efefef',
        panelBorder : '1px solid #333',
        panelCssClass : '',        
        onListChange: function(){ }
    },
    
    initialize: function(){

        /** if the ist changes, notify listeners about that */
        this.bind("change:methods", function(){            
            var cb = this.get('onListChange');
            cb();
        });
        
        this.bind("change:headerText", function(){            
            var cb = this.get('onListChange');
            cb();
        });        
    },
    
    addOption : function( option ){
        
        var newMethods        = clone( this.get('methods') );
        var newMethodCaptions = clone( this.get('methodCaptions') );
        
        /** if size or style changed, clear list before adding new item*/
        if( option.size != this.get('size') || option.style != this.get('style') ){
            newMethods = [];
            newMethodCaptions = {};
            
            this.set('size', option.size );
            this.set('style', option.style );
        }
        
        if( newMethods.indexOf( option.method, newMethods ) == -1 ){
            newMethods.push(option.method);
            newMethodCaptions[option.method] = option.caption;
        }
        
        this.set('methodCaptions', newMethodCaptions );
        this.set('methods', newMethods );        
    },
    
    removeOption: function( method ){
        
        /**No need to update method captions */
        
        var oldMethods = this.get('methods');
        
        var newMethods = [];
        
        jQuery(oldMethods).each(function(i, oMethod){
            if( oMethod != method ){
                newMethods.push( oMethod );
            }
        });
        
        this.set('methods', newMethods );   
    },
    
    
    isValid : function(){
                
        var self = this;
        var isValid = self.get('methods').length > 0 && 
                      self.get('size')!= null &&
                      self.get('style') != null;
        return isValid;   
        
    },
    
    getData : function(){
        
        var self = this;
        
        return {
            methods:self.get('methods'), 
            methodCaptions:self.get('methodCaptions'),
            size:self.get('size'),
            style:self.get('style'),
            headerText:self.get('headerText'),
            headerTextColor:self.get('headerTextColor'),
            panelBgColor:self.get('panelBgColor'),
            panelBorder:self.get('panelBorder'),
            panelCssClass:self.get('panelCssClass')    
        }
        
    },
    
    /** callback(isValid, isSuccess, errorMessage) */
    saveInfo : function( callback ){
        
        var self = this;
    
        if( !self.isValid() ){
            
            callback( false, null, null );
            return;
        }


        if(typeof(ajaxurl) == "undefined"){
            
            callback( true, false, 'Unexpected Error' );
            return;
        }

        var params = {
            action : self.get('saveAction'),
            cookie: encodeURIComponent(document.cookie),
            saveData: self.getData()
        };

        jQuery.post( ajaxurl, params, function(json){
            
            if( json.success == true ){
                callback( true, true, null );
            }else{
                callback( true, false, json.error );
            }
          
        }, "json").error(function() {
            callback( true, false, 'Saving Failed' );
        })
    }
    

});

    
GzalienAPOView = Backbone.View.extend({

    initialize: function( options ){

        var self = this;

        self.model.set('onListChange', function(){
            self.updatePreview();
        });

        
        /** Just binding to event */
        self.$('#apo-header-text, #apo-header-text-color, #apo-panel-bg-color, #apo-panel-border, #apo-panel-css-class'
        ).bind('textchange', function (event, previousText) {});


        self.updateModelValuesAndPreview();

    },
    events: {
        "click .tab-content div.thumbnail" : "addOption",
        "click #apo-save" : "saveAll",
        "click #preview ul li" : "removeOption",
        "textchange #apo-header-text"       : "updateModelValuesAndPreview",
        "textchange #apo-header-text-color" : "updateModelValuesAndPreview",
        "textchange #apo-panel-bg-color"    : "updateModelValuesAndPreview",
        "textchange #apo-panel-border"      : "updateModelValuesAndPreview",
        "textchange #apo-panel-css-class"   : "updateModelValuesAndPreview"
    },

    addOption: function( e ){

        var thumbnail = jQuery( e.currentTarget );

        var option = {
            size: thumbnail.attr('apo-size'),
            style : thumbnail.attr('apo-style'),
            method: thumbnail.attr('apo-method'),
            caption: thumbnail.attr('apo-caption')
        }
        this.model.addOption( option );

        return false;
    },

    removeOption : function( e ){           
        this.model.removeOption( jQuery( e.currentTarget ).attr('apo-method') );            
    },

    saveAll : function( e ){

        this.model.saveInfo( function(isValid, isSuccess, errorMessage){

            if( !isValid ){
                jQuery.growlUI(null, 'Please add at least one payment method.'); 
            }else{

                if( !isSuccess ){
                    jQuery.growlUI(null, errorMessage ); 
                }else{
                    jQuery.growlUI(null, 'Widget Settings Saved.'); 
                }                    
            }

        });
        return false;
    },

    updateModelValuesAndPreview : function(){

        var self = this;

        self.model.set( 'headerText',      self.$('#apo-header-text').val() );                
        self.model.set( 'headerTextColor', self.$('#apo-header-text-color').val() );                
        self.model.set( 'panelBgColor',    self.$('#apo-panel-bg-color').val() );                
        self.model.set( 'panelBorder',     self.$('#apo-panel-border').val() );       
        self.model.set( 'panelCssClass',   self.$('#apo-panel-css-class').val() );

        self.updatePreview();

    },     

    updatePreview: function(){

        var self = this;
        
        var methods        = this.model.get('methods');
        var methodCaptions = this.model.get('methodCaptions');
        var size           = this.model.get('size');
        
        this.$('#preview').empty();


        var h3 = jQuery('<h3/>').html(this.model.get('headerText')).css({ 
            color: this.model.get('headerTextColor')
        });
        var ul = jQuery('<ul/>').css({

            'background-color' : this.model.get('panelBgColor'),
            'border'           : this.model.get('panelBorder')

        });

        this.$('#preview').attr('class', this.model.get('panelCssClass') );

        /** Make all items in tabs visible and then hide one by one */
        this.$('.tab-content .thumbnail').show();

        jQuery(methods).each(function(i, method){

            var img = jQuery('<img/>').attr({ 
                src : self.options.imagesBase +'/'+self.model.get('size')+ '/' + method+'-'+self.model.get('style')+ '-' + self.model.get('size')+'px.png',
                alt : methodCaptions[ method ],
                title : methodCaptions[ method ]
            })
            var li = jQuery('<li/>').append( img );

            li.attr({'apo-method' : method});

            if( i == methods.length - 1 ){
                li.addClass('last');
            }

            ul.append( li );

            self.$("#images-tab .thumbnail[apo-method='" + method + "'][apo-size='" + size + "']").hide();

        });

        ul.append('<div class="clear"></div>');

        this.$('#preview').append( h3 );
        this.$('#preview').append( ul );                        
    }

});
