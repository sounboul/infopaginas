$( document ).ready( function() {
    $.each( ['#' + formId + '_serviceAreasType label', '#' + formId + '_serviceAreasType label ins'], function( index, fieldId ) {
        $( fieldId ).on( 'click', function() {
            var $self = $( this).parent().find( 'input[name="' + formId + '[serviceAreasType]"]' );
            var withinMilesOfMyBusinessField = $( '#' + formId + '_milesOfMyBusiness' );
            var withinMilesOfMyBusinessLabel = $( '#sonata-ba-field-container-' + formId + '_milesOfMyBusiness label' );
            var localitiesField = $( '#' + formId + '_localities' );
            var localitiesLabel = $( '#sonata-ba-field-container-' + formId + '_localities label' );
            var localitiesDropdown = localitiesField.parent( '.sonata-ba-field' ).find( '.select2-container-multi' );

            if ( $self.val() == 'area' ) {
                withinMilesOfMyBusinessField.removeAttr( 'disabled' );
                localitiesField.attr('disabled', 'disabled');

                withinMilesOfMyBusinessField.attr('required', 'required');

                if ( !withinMilesOfMyBusinessLabel.hasClass( 'required' ) ) {
                    withinMilesOfMyBusinessLabel.addClass( 'required' );
                }
            } else {
                localitiesField.removeAttr( 'disabled' );
                withinMilesOfMyBusinessField.attr( 'disabled', 'disabled' );

                withinMilesOfMyBusinessField.removeAttr( 'required' );
                localitiesDropdown.removeClass( 'select2-container-disabled' );
                withinMilesOfMyBusinessLabel.removeClass( 'required' );
            }
        } );
    } );
} );