define(['jquery', 'slick'], function( $, Slick ) {
    'use strict';

    $(document).ready(function () {
        $('.slider').slick({
            autoplay: true,
            autoplaySpeed: 5000,
            arrows: true,
            dots: true,
            responsive: true,
            mobileFirst: true,
            adaptiveHeight: false,
            variableWidth: false,
            slidesToShow: 1,
            prevArrow: '<span class="arrow prev"><i class="fa fa-arrow-left"></i></span>',
            nextArrow: '<span class="arrow next"><i class="fa fa-arrow-right"></i></span>'
        });
    });
});
