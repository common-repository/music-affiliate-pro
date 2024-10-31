jQuery(function($) {
	$('.bscard-newsletter-toggle a').click(function(e) {
		e.preventDefault();
		$(this).parents('.bscard').find('.bscard-newsletter').slideDown('fast').end().find(this).replaceWith( $(this).text() );
	});
	
	$('.bscard-form input[type="text"]').focus(function(e) { this.select(); });
	
	
	$('a[href^="#"]:not(.bscard-newsletter-toggle a,.show-settings)').click(function(e) {
		e.preventDefault();
		
		$('html, body').animate({
			scrollTop: $( $(this).attr('href') ).offset().top
		});
	});
	
	/*
	/* Newsletter Form
	*/
	$('#ma-newsletter input').bind('keydown', function(e) {
		var code = (e.keyCode ? e.keyCode : e.which);
		if (code == 13) {
			e.preventDefault();
			$('#ma-newsletter button').click();
		}
	});
	
	/*
	/* Search Settings
	*/
	$('#madiv-searchsettings input').change(function(e) {
		if ($(this).attr('checked')) {
			$('#config-' + $(this).val() ).show();
		} else {
			$('#config-' + $(this).val() ).hide();
		}
	}).change();
	
	
	/*
	/* Amazon Widget Settings
	*/
	reloadAmazonWidget();
	
	$('#aws-title').change(function() {
		swfObject.addVariable('Title', encodeURIComponent($(this).val()));
		reloadAmazonWidget();
	});
	
	$('#aws-size').change(function() {
		var newSize = $(this).val().split('x');
		
		swfObject.setAttribute('width', newSize[0]);
		swfObject.setAttribute('height', newSize[1]);
		swfObject.addVariable('Width', newSize[0]);
		swfObject.addVariable('Height', newSize[1]);
		
		reloadAmazonWidget();
	});
	
	function reloadAmazonWidget() {
		$('#amzn_widget').remove();
		$('#aws-preview').append(swfObject.getSWFHTML());
	}
	
	
	/*
	/* iTunes Widget Settings
	*/
	$('#iws-upper-left-color').updateItunesWidget('cul');
	$('#iws-upper-right-color').updateItunesWidget('cur');
	$('#iws-lower-left-color').updateItunesWidget('cll');
	$('#iws-lower-color').updateItunesWidget('clr');
	$('#iws-height').updateItunesWidget('wh', 300, 370).bind('itunesWidgetUpdate', function(e) {
		$('#itunes-widget').height( $(this).val() );
	});
	
	$('#iws-width').updateItunesWidget('ww', 250, 325).bind('itunesWidgetUpdate', function(e) {
		$('#itunes-widget').width( $(this).val() );
	});
	
	/*
	/* Spotify Widget Settings
	*/
	$('#sws-theme').updateSpotifyWidget({ attr:'src', param:'theme' });
	$('#sws-view').updateSpotifyWidget({ attr:'src', param:'view' });
	$('#sws-height').updateSpotifyWidget('height', 80, 720);
	$('#sws-width').updateSpotifyWidget('width', 250, 640);
});
	
(function($) {
	$.fn.updateItunesWidget = function(param, minVal, maxVal) {
		return this.each(function() {
			$(this).change(function(e) {
				// do some basic validation first
				if (typeof(minVal) != 'undefined' && Math.round($(this).val()) < minVal) {
					$(this).val(minVal);
				}
				
				if (typeof(maxVal) != 'undefined' && Math.round($(this).val()) > maxVal) {
					$(this).val(maxVal);
				}
				
				newSrc = addQueryArg(param, $(this).val(), $('#itunes-widget').attr('src'));
				$('#itunes-widget').attr( 'src', newSrc );
				$(this).trigger('itunesWidgetUpdate');
			});
		});
	}
})(jQuery);

// TODO: refactor and replace duplicated code
(function($) {
	$.fn.updateSpotifyWidget = function(arg, minVal, maxVal) {
		return this.each(function() {
			$(this).change(function(e) {
				var widget = $('#spotify-widget');
				
				// do some basic validation first
				if (typeof(minVal) != 'undefined' && Math.round($(this).val()) < minVal) {
					$(this).val(minVal);
				}
				
				if (typeof(maxVal) != 'undefined' && Math.round($(this).val()) > maxVal) {
					$(this).val(maxVal);
				}
				
				if (typeof(arg) == 'object' && 'src' == arg.attr) {
					newSrc = addQueryArg(arg.param, $(this).val(), widget.attr('src'));
					widget.attr( 'src', newSrc );
				} else {
					// update an attribute
					widget.attr( arg, $(this).val() ).attr( 'src', widget.attr('src') );
				}
				
				$(this).trigger('widgetUpdate');
			});
		});
	}
})(jQuery);

function addQueryArg(param, value, url) {
	var paramExists = false, urlBase, urlParts, urlQuery;
	
	urlParts = url.split('?');
	if (urlParts.length > 1) {
		urlBase = urlParts[0];
		urlQuery = urlParts[1].split('&');
		
		jQuery.each(urlQuery, function(i, item) {
			if (item.indexOf(param + '=') == 0) {
				paramExists = true;
				urlQuery[i] = param + '=' + value;
			}
		});
		
		if (!paramExists) {
			urlQuery.push(param + '=' + value);
		}
		
		url = urlBase + '?' + urlQuery.join('&');
	} else {
		url = url + '?' + param + '=' + value;
	}
	
	return url;
}