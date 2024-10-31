var tuneEditor, tuneSearch;

jQuery(function($) {
	var searchTimeout;
	tuneSearch.init();
	
	$('#ma-q').val('').bind('keyup', function() {
		clearTimeout(searchTimeout);
		searchTimeout = setTimeout(tuneSearch.search, 350);
	});
	$('#ma-query-entity').bind('change', tuneSearch.search);
	$('#ma-query-service').bind('change', tuneSearch.search);
	
	
	// handle row actions for inserting/updating shortcodes
	$('#ma-search-results').delegate('a.add-itunes-widget', 'click', function(e) {
		e.preventDefault();
		var itemId = $(this).attr('href').replace('#', '');
		tuneEditor.addShortcode('[itunes id="' + itemId + '"]', 'id', itemId);
	}).delegate('a.add-amazonmp3-widget', 'click', function(e) {
		e.preventDefault();
		var itemId = $(this).attr('href').replace('#', '');
		tuneEditor.addShortcode('[amazonmp3 asin="' + itemId + '"]', 'asin', itemId, ',');
	}).delegate('a.add-rdio-widget', 'click', function(e) {
		e.preventDefault();
		tuneEditor.addShortcode('[embed]' + $(this).attr('href') + '[/embed]', 'src', $(this).attr('href'));
	}).delegate('a.add-spotify-widget', 'click', function(e) {
		e.preventDefault();
		var itemUri = $(this).attr('href').replace('#', '');
		if ($(this).hasClass('append')) {
			tuneEditor.addShortcode('[spotify tracks="' + itemUri + '"]', 'tracks', itemUri, ',');
		} else {
			tuneEditor.addShortcode('[spotify uri="' + itemUri + '"]', 'src', itemUri);
		}
	}).delegate('a#ma-hide-results', 'click', function(e) {
		e.preventDefault();
		$('.ma-results').hide();
		$('#ma-q').val('');
	});
	
	
	// hide entity field for services that don't require/support it
	changeEntityVisibility();
	$('#ma-query-service').change(function(e) { changeEntityVisibility(); });
	function changeEntityVisibility() {
		($('#ma-query-service').val() == 'amazonmp3') ? $('#ma-query-entity').hide() :	$('#ma-query-entity').show();
	}
	
	
	// save user's search options
	$('#ma-link-target-blank, #ma-query-entity, #ma-query-service').bind('change', saveUserSearchOptions);
	function saveUserSearchOptions() {
		$.ajax({
			url: ajaxurl,
			data: {
				action: 'ma_save_user_search_options',
				uid: userSettings.uid,
				search_options : {
					link_target : ($('#ma-link-target-blank').attr('checked') == 'checked') ? 1 : 0,
					query_entity: $('#ma-query-entity').val(),
					query_service: $('#ma-query-service').val()
				}
			}
		});
	}
});

(function($) {
	var request;
	var requestInProgress = false;
	var settings;
	
	tuneSearch = {
		init : function( options ) {
			settings = {
				queryField : $('#ma-q'),
				queryEntityField : $('#ma-query-entity'),
				serviceField : $('#ma-query-service'),
				
				resultsContainer : $('#ma-search-results')
			};
			
			if (options) $.extend(settings, options);
			
			$(settings.resultsContainer).delegate('.ma-result', 'click', function(e) {
				var defaultText = $('.title strong', this).text();
				var attrs = {
					href : $(this).find('a').attr('href')
				}
				
				if ($('#ma-link-target-blank').attr('checked') == 'checked') {
					attrs.target = '_blank';
				}
				
				var linkHtml = $('<a></a>').attr( attrs ).text( defaultText ).wrap('<div></div>').parent('div');
				
				tuneEditor.sendLinkToEditor( linkHtml.html(), attrs, defaultText );
			});
			
			$(settings.resultsContainer).delegate('a', 'click', function(e) {
				e.stopPropagation();
			});
		},
		
		search : function() {
			settings.query = settings.queryField.val();
			settings.queryEntity = settings.queryEntityField.val();
			settings.service = settings.serviceField.val();
			
			if (settings.query.length >= 3) {
				$('.ma-ajax-feedback').show();
				
				if (settings.query == '') {
					settings.resultsContainer.html('');
					return false;
				}
		
				if (requestInProgress) {
					request.abort();
				}
		
				requestInProgress = true;
				request = $.ajax({
					url: ajaxurl,
					data: {
						action: 'ma_search',
						entity: settings.queryEntity,
						q: settings.query,
						service: settings.service
					},
					dataType: 'json',
					success: function(data) {
						$('#ma-search-form').addClass('active');
						requestInProgress = false;
						$('.ma-ajax-feedback').hide();
						
						if (data.isValid) {
							$('#ma-search-message').hide();
							settings.resultsContainer.html(data.results).show();
						} else {
							settings.resultsContainer.hide();
							$('#ma-search-message').html('<p>' + data.message + '</p>').show();
						}
					}
				});
			} else {
				$('.ma-ajax-feedback').hide();
				settings.resultsContainer.html('');
			}
		}
	};
})(jQuery);


(function($) {
	tuneEditor = {
		addToEditor : function(h) {
			var ed;
		
			if ( typeof tinyMCE != 'undefined' && ( ed = tinyMCE.activeEditor ) && !ed.isHidden() ) {
				// restore caret position on IE
				if ( tinymce.isIE && ed.windowManager.insertimagebookmark )
					ed.selection.moveToBookmark(ed.windowManager.insertimagebookmark);
				
				ed.execCommand('mceInsertContent', false, h);
			} else if ( typeof edInsertContent == 'function' ) {
				edInsertContent(edCanvas, h);
			} else {
				jQuery( edCanvas ).val( jQuery( edCanvas ).val() + h );
			}
		},
		
		getSelection : function () {
			var ed, selection = '';
		
			if ( typeof tinyMCE != 'undefined' && ( ed = tinyMCE.activeEditor ) && !ed.isHidden() ) {
				selection = ed.selection.getContent();
			} else if ( typeof edInsertContent == 'function' ) {
				var start = edCanvas.selectionStart,
					end = edCanvas.selectionEnd;
				
				if (end-start > 0) {
					selection = edCanvas.value.substring( start, end );
				}
			}
			
			return selection;
		},
		
		// append parameter used to determine whether the value should be appended to the attribute or if it should be replaced
		addShortcode : function(shortcode, attr, value, append) {
			var selection = this.getSelection();
			if (selection.length && selection.indexOf('attr')) {
				// update shortcode attribute rather than replace whole selection
				// preserves other attributes with the possibility of appending value
				if (typeof( append ) != 'undefined' && append.length) {
					var re = new RegExp(attr + '="([^"]+)"');
					shortcode = selection.replace(re, attr + '="$1' + append + value + '"');
				} else {
					var re = new RegExp(attr + '="([^"]+)"');
					shortcode = selection.replace(re, attr + '="' + value + '"');
				}
			}
			
			this.addToEditor(shortcode);
		},
		
		sendLinkToEditor : function(h, attrs, defaultText) {
			var ed;
		
			if ( typeof tinyMCE != 'undefined' && ( ed = tinyMCE.activeEditor ) && !ed.isHidden() ) {
				// restore caret position on IE
				if ( tinymce.isIE && ed.windowManager.insertimagebookmark )
					ed.selection.moveToBookmark(ed.windowManager.insertimagebookmark);
				
				var e, b;
				e = ed.dom.getParent(ed.selection.getNode(), 'A');
				
				if (ed.selection.getContent().length < 1) {
					ed.execCommand('mceInsertContent', false, h);
				} else if (e == null) {
					ed.getDoc().execCommand("unlink", false, null);
					tinyMCE.execCommand("CreateLink", false, "#mce_temp_url#", {skip_undo : 1});
					
					tinymce.each(ed.dom.select("a"), function(n) {
						if (ed.dom.getAttrib(n, 'href') == '#mce_temp_url#') {
							e = n;
							ed.dom.setAttribs(e, attrs);
						}
					});
	
					// Sometimes WebKit lets a user create a link where they shouldn't be able to. In this case, CreateLink injects "#mce_temp_url#" into their content. Fix it.
					if ( $(e).text() == '#mce_temp_url#' ) {
						ed.dom.remove(e);
						e = null;
					}
				} else {
					ed.dom.setAttribs(e, attrs);
				}
			} else if ( typeof edInsertContent == 'function' ) {
				var start = edCanvas.selectionStart,
					end = edCanvas.selectionEnd;
				
				if (end-start > 0) {
					h = h.replace(defaultText, edCanvas.value.substring( start, end ) );
				}
				
				edInsertContent(edCanvas, h);
			} else {
				jQuery( edCanvas ).val( jQuery( edCanvas ).val() + h );
			}
		}
	};
})(jQuery);