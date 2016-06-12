define(['jquery', 'abstract/view', 'tools/geolocation', 'jquery-ui'], function( $, view, Geolocation ) {
    'use strict'

    var search = function( options ) {
        this.events = {};
        
        this.events[options.searchSelector + ' focus']  = 'onSearchBoxFocus';
        this.events[options.searchSelector + ' blur']   = 'onSearchBoxBlur';
        
        this.options = {
            autoComplete : true,
            searchMenu : false,
            autoCompleteUrl : '/search/autocomplete',
            autoCompleteMinLen : 1,
            searchBaseUrl : '/search/'
        };

        $.extend( this.options, options );

        this.init( options );
        this.bindEvents();

        return this;
    }

    search.prototype = new view();

    search.prototype.init = function ( options ) {
        this.parent = this.__proto__.__proto__;

        this.parent.init( options );

        this.searchBox          = this.$( this.options.searchSelector );
        this.searchHintBox      = this.$( this.options.searchHintSelector );
        this.searchBoxResults   = this.$( this.options.searchResultsSelector );
        this.searchLocations    = this.$( this.options.locationsSelector );
        this.submitButton       = this.$( this.options.submitSelector );

        if( _.isNull(this.options.geolocation) || _.isUndefined(this.options.geolocation) ) {
            this.geolocation        = new Geolocation( { 'locationBox' : this.searchLocations } );
        } else {
            this.geolocation = this.options.geolocation;
        }
        

        if ( this.options.autoComplete ) {
            this.initAutocomplete( this.options.autoCompleteUrl );
        }

        if ( this.geolocation.isGelocationAvailable( )) {
            var address = this.geolocation.getAddress(this.setLocation.bind(this));
        } else {
            this.geolocation.locationAutocomplete()
        }

        if ( this.options.searchMenu !== false ) {
            this.options.searchMenu.initQuickLinks(this.quickSearch.bind(this))
        }
    }

    search.prototype.initAutocomplete = function (url) {
        url = url || this.options.autoCompleteUrl;
        this.searchBox.autocomplete({
            'source': url,
            minLength: this.options.autoCompleteMinLen,
            select: this.onAutoCompleteSelect
        });
    }

    search.prototype.onAutoCompleteSelect = function ( event, ui ) {
        alert('olololo');
    }

    search.prototype.onSearchBoxFocus = function () {
        this.searchHintBox.show();
    }

    search.prototype.onSearchBoxBlur = function () {
        this.searchHintBox.hide();
    }

    search.prototype.setLocation = function ( data ) {
        this.searchLocations.val(data);
    }
    
    search.prototype.quickSearch = function ( searchQuery ) {
        this.searchBox.val( searchQuery );
        this.submitButton.first().click();
    }

    return search;
});