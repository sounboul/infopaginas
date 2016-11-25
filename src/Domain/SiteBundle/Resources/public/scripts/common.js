requirejs.config({
    baseUrl: '/bundles/',
    shim: {
        bootstrap : {
            deps: ['jquery'],
        },
        "underscore": {
            exports: "_"
        },
        "jquery" : {
            exports: "$"
        },
        'photo-gallery' : {
            deps: ['jquery']
        },
        'comparasion' : {
            deps: ['$']
        },
        'modal' : {
            deps: ['jquery']
        },
        'select' : {
            deps: ['$']
        },
        'slick' : {
            deps: ['jquery']
        },
        'slider' : {
            deps: ['$']
        },
        'lightbox': {
            deps: ['jquery']
        },
        'star-rating' : {
            deps: ['$']
        },
        'js-cookie' : {
            exports: 'Cookies'
        },
        'selectize' : {
            deps: ['jquery']
        }
    },
    paths: {
        modules         : 'domainsite/scripts/modules',
        tools           : 'domainsite/scripts/modules/tools',
        abstract        : 'domainsite/scripts/abstract',
        async           : 'domainsite/scripts/vendors/require/async',
        goog            : 'domainsite/scripts/vendors/require/goog',
        propertyParser  : 'domainsite/scripts/vendors/require/propertyParser',
        'jquery'        : 'domainsite/scripts/vendors/jquery.min',
        'jquery-ui'     : 'domainsite/scripts/vendors/jquery-ui.min',
        'jquery-mobile' : 'domainsite/scripts/vendors/jquery.mobile.custom.min',
        'js-cookie'     : 'domainsite/scripts/vendors/js.cookie.min',
        'bootstrap'     : 'domainsite/scripts/vendors/bootstrap.min',
        'underscore'    : 'domainsite/scripts/vendors/underscore-min',
        'alertify'      : 'domainsite/scripts/vendors/alertify.min',
        'spin'          : 'domainsite/scripts/vendors/spin.min',
        'slick'         : 'domainsite/scripts/vendors/slick.min',
        'photo-gallery' : 'domainsite/scripts/vendors/photo-gallery',
        'lightbox'      : 'domainsite/scripts/vendors/simple-lightbox.min',
        'select2'       : 'domainsite/scripts/vendors/select2.min',
        'iframetracker' : 'domainsite/scripts/vendors/jquery.iframetracker',
        'highcharts' : 'domainsite/scripts/vendors/highcharts',

        'business/modules' : 'domainbusiness/scripts/modules',
        'business/tools'   : 'domainbusiness/scripts/modules/tools',

        //redesign
        'profile-redesign': '/redesign/js/profile',
        'main-redesign': '/redesign/js/main',
        'selectize': '/redesign/js/vendor/min/selectize-min',
        'velocity': '/redesign/js/vendor/min/velocity-min',
        'velocity-ui': '/redesign/js/vendor/min/velocity-ui-min'
    }
});
