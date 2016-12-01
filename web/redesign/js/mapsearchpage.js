define(
    ['jquery',  'abstract/view', 'underscore', 'tools/directions', 'tools/select', 'bootstrap', 'select2', 'tools/star-rating'],
    function ( $, view, _, directions, select ) {
    'use strict';

        var mapSearchRedesignPage = function () {

            var direct = new directions;
            direct.bindEventsDirections();

            var markersBlock = $( '#map-markers' );
            var markers = {};

            map = new google.maps.Map(  document.getElementById('map'), {
                center: new google.maps.LatLng( 18.2208, -66.5901 ),
                zoom: 8
            });
            var bounds = new google.maps.LatLngBounds();

            if ( markersBlock.html() ) {
                markers = JSON.parse( markersBlock.html() );
            }

            $.each(markers, function(key, value){
                var position = new google.maps.LatLng(value.latitude, value.longitude);

                bounds.extend(position);
                var marker = new google.maps.Marker({
                    position: position,
                    map: map,
                    title: value.name
                });
            });

            if (markers) {
                map.fitBounds(bounds);
            }

            var currentMapCenter = null;
            google.maps.event.addListener(map, 'resize', function () {
                currentMapCenter = map.getCenter();
            });

            google.maps.event.addListener(map, 'bounds_changed', function () {
                if (currentMapCenter) {
                    map.setCenter(currentMapCenter);
                }
                currentMapCenter = null;
            });

            return this;
        };

        return mapSearchRedesignPage;
});
