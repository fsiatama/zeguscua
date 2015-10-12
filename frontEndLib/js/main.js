(function($) {
	'use strict';

	/*********** preloader ************/
	$(window).load(function() {
	    $(".preloader").delay(400).fadeOut(500);
	});

	/*********** Navigation ************/
    $(document).ready(function() {
        $('.awesome-tooltip').tooltip({
            placement: 'left'
        });

        $(window).bind('scroll', function(e) {
            dotnavigation();
        });

        function dotnavigation() {

            var numSections = $('section').length;

            $('#dot-nav li a').removeClass('active').parent('li').removeClass('active');
            $('section').each(function(i, item) {
                var ele = $(item),
                    nextTop, thisTop;


                if (typeof ele.next().offset() != "undefined") {
                    nextTop = ele.next().offset().top;
                } else {
                    nextTop = $(document).height();
                }

                if (ele.offset() !== null) {
                    thisTop = ele.offset().top - ((nextTop - ele.offset().top) / numSections);
                } else {
                    thisTop = 0;
                }

                var docTop = $(document).scrollTop();

                if (docTop >= thisTop && (docTop < nextTop)) {
                    $('#dot-nav li').eq(i).addClass('active');
                }
            });
        }

        /* get clicks working */
        $('#dot-nav li').click(function() {

            var id = $(this).find('a').attr("href"),
                posi,
                ele,
                padding = 0;

            ele = $(id);
            posi = ($(ele).offset() || 0).top - padding;

            $('html, body').animate({
                scrollTop: posi
            }, 'slow');

            return false;
        });

        /* end dot nav */
    });
    /*********** Navigation Ends ************/
    /*********** google map ************/
    function initialize() {
        var location = new google.maps.LatLng(4.50425, -73.93970);
        var mapOptions = {
            center: location,
            scrollwheel: false,
            zoom: 12,
            /* colorize different sections of map */
            styles: [{
                featureType: 'water',
                stylers: [{
                    color: '#58B325'
                }, {
                    visibility: 'on'
                }]
            }, {
                featureType: 'landscape',
                stylers: [{
                    color: '#f2f2f2'
                }]
            }, {
                featureType: 'road',
                stylers: [{
                    saturation: -90
                }, {
                    lightness: 15
                }]
            }, {
                featureType: 'road.highway',
                stylers: [{
                    visibility: 'simplified'
                }]
            }, {
                featureType: 'road.arterial',
                elementType: 'labels.icon',
                stylers: [{
                    visibility: 'off'
                }]
            }, {
                featureType: 'administrative',
                elementType: 'labels.text.fill',
                stylers: [{
                    color: '#444444'
                }]
            }, {
                featureType: 'transit',
                stylers: [{
                    visibility: 'off'
                }]
            }, {
                featureType: 'poi',
                stylers: [{
                    visibility: 'off'
                }]
            }]

        };
        var mapElement = document.getElementById('map-canvas');
        var map = new google.maps.Map(mapElement, mapOptions);

        var position = new google.maps.LatLng(4.49980, -73.93558);

        var marker = new google.maps.Marker({
            position: position,
            map: map,
            title: 'Laguna de Ubaque, Cundinamarca, Colombia'
        });

    }

    google.maps.event.addDomListener(window, 'load', initialize);
    /*********** google map Ends ************/
    /*********** form Contact ************/
    $("input,textarea").jqBootstrapValidation({
        preventSubmit: true,
        submitError: function($form, event, errors) {
            // additional error messages or events
        },
        submitSuccess: function($form, event) {
            event.preventDefault(); // prevent default submit behaviour
            // get values from FORM
            var name = $("input#name").val();
            var email = $("input#email").val();
            var phone = $("input#phone").val();
            var message = $("textarea#message").val();
            var firstName = name; // For Success/Failure Message
            // Check for white space in name for Success/Fail message
            if (firstName.indexOf(' ') >= 0) {
                firstName = name.split(' ').slice(0, -1).join(' ');
            }
            $.ajax({
                url: "contact/form",
                type: "POST",
                data: {
                    name: name,
                    phone: phone,
                    email: email,
                    message: message
                },
                cache: false,
                success: function() {
                    // Success message
                    $('#success').html("<div class='alert alert-success'>");
                    $('#success > .alert-success').html("<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;")
                        .append("</button>");
                    $('#success > .alert-success')
                        .append("<strong>Your message has been sent. </strong>");
                    $('#success > .alert-success')
                        .append('</div>');

                    //clear all fields
                    $('#contactForm').trigger("reset");
                },
                error: function() {
                    // Fail message
                    $('#success').html("<div class='alert alert-danger'>");
                    $('#success > .alert-danger').html("<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;")
                        .append("</button>");
                    $('#success > .alert-danger').append("<strong>Sorry " + firstName + ", it seems that my mail server is not responding. Please try again later!");
                    $('#success > .alert-danger').append('</div>');
                    //clear all fields
                    $('#contactForm').trigger("reset");
                },
            })
        },
        filter: function() {
            return $(this).is(":visible");
        },
    });
    /*********** form Contact Ends ************/
})(jQuery);
