
jQuery.fn.extend({
	findAll: function(selector) {
		return this.find(selector).addBack(selector);
	}
});

jQuery.fn.extend({
	exists: function() {
		return this.length !== 0;
	}
});

jQuery.fn.extend({
	hasAttr: function(name) {
		var attr = $(this).attr(name);
		return (typeof attr !== typeof undefined && attr !== false);
	}
});

jQuery.fn.extend({
	insertAtCaret : function(text) {
		return this.each(function() {
			if (document.selection && this.tagName == 'TEXTAREA') {
				//IE textarea support
				this.focus();
				sel = document.selection.createRange();
				sel.text = text;
				this.focus();
			} else if (this.selectionStart || this.selectionStart == '0') {
				//MOZILLA/NETSCAPE support
				startPos = this.selectionStart;
				endPos = this.selectionEnd;
				scrollTop = this.scrollTop;
				this.value = this.value.substring(0, startPos) + text + this.value.substring(endPos, this.value.length);
				this.focus();
				this.selectionStart = startPos + text.length;
				this.selectionEnd = startPos + text.length;
				this.scrollTop = scrollTop;
			} else {
				//IE input[type=text] and other browsers
				this.value += text;
				this.focus();
				this.value = this.value;    // forces cursor to end
			}
		});
	}
});


function registerEventHandlers(object) {

	// force the true value into forms to get around autocomplete issues
	$('input[data-true-value]').each(function() {
		item = $(this);
		item.val(item.attr('data-true-value'));
	});
	
	$(object).findAll(".alert").alert()

	//$(object).findAll(".ckeditor_manual").ckeditor();
	makeCkEditors($(object).findAll(".ckeditor_manual"));
		
    $(object).findAll("tr.clickableRow[data-url]").click(function(event) {
          window.document.location = $(this).data("url");
          event.preventDefault();
    });
	
	$(object).findAll('tr.clickableRow[data-toggle="collapse"]').on("click", function(event) {
		$('#' + $(this).data('target')).collapse('toggle');
	});    
    
	$(object).findAll(".ajaxFormWrapper").on("submit", "form", function(event) {
    	runAjaxFormWrapper(event, $(this));
    });	

	$(object).findAll(".ajaxFormWrapper").on("click", "a", function(event) {
    	runAjaxFormWrapper(event, $(this));
    });

	$(object).findAll(".autoupdate").on("autoupdate", function(event) {
    	runAjaxFormWrapper(event, $(this));
    });

	$(object).findAll(".ajaxFormWrapper, .ajaxUpdater").on("manualupdate", function(event) {
    	runAjaxFormWrapper(event, $(this));
    });
	
	$(object).findAll("textarea.autosaveText").on("change", function(event) {
		scheduleAutosaveText(event, $(this));
    });
	
	// a little hack to make button values visible in .ajaxFormWrapper handled forms
	$(object).findAll("button[name][value]").on("click", function(event) {
		if (! $(this).closest(".ajaxFormWrapper").exists()) return; // do nothing if we're not inside an ajaxformwrapper.
		
		var name = $(this).attr('name');
		var hidden = $(this).closest("form").find("input[type=hidden][name=" + name + "]");
		hidden.val($(this).attr('value'));
		setTimeout(function() {
			hidden.val('');
		},0);
	});

	$(object).findAll(".ajaxContentLoader").each( function(index, element) {
		setTimeout( function() { runAjaxContentLoader(element); }, 0 );
	});

	$(object).findAll("form[data-confirmationdialog]").on("submit", function(event) {
		handleConfirmationDialog(event, $(this));
	});

	$(object).findAll("a[data-confirmationdialog]").on("click", function(event) {
		handleConfirmationDialog(event, $(this));
	});

	$(object).findAll("button[data-confirmationdialog]").on("click", function(event) {
		handleConfirmationDialog(event, $(this));
	});

	$(object).findAll(".formSelectAllCheckboxes").on("click", function(event) {
		$(this).closest('form').find('input[type="checkbox"]').each(function(){ this.checked = true; });
		event.preventDefault();
	});

	$(object).findAll(".formSelectNoCheckboxes").on("click", function(event) {
		$(this).closest('form').find('input[type="checkbox"]').each(function(){ this.checked = false; });
		event.preventDefault();
	});
	
	
	$(object).findAll(".unhide-trigger").on("click", function(event) {
		unhideButtonTriggered(event, $(this));
    });


	$(object).findAll(".math-tex").each(function( i ) {
		MathJax.Hub.Queue(["Typeset",MathJax.Hub,this]);
	});
	
	$(object).findAll("pre > code").each(function( i ) {
	    hljs.highlightBlock(this);
	});
	
	$(object).findAll("table.stickyHeaders").stickyTableHeaders({fixedOffset: $('#mainNavigationBar')});
	
	$(object).findAll('.makeTooltip[data-toggle="tooltip"]').tooltip();
	
	$(object).findAll('[data-on-change-add-classes]').one("change", function(event) {
		$(this).addClass($(this).attr('data-on-change-add-classes'));
		updateIfExistsSelector();
	});
	
	updateIfExistsSelector(object);
}


function updateIfExistsSelector(object) {
	object = object || ':root';
	object = $(object);

	var updatesMade = false;

	object.findAll('.ifExistsSelector').each(function(index, element) {
		element = $(element);

		var classesExist = true;
		var evaluationMade = false;

		if (element.hasAttr('data-if-exists')) {
			evaluationMade = true;
			classesExist = $(element.attr('data-if-exists')).exists();
		}
		
//		// handle the data-if-exists-any attribute
//		if (element.hasAttr('data-if-exists-any')) {
//			classesExist = false;
//			classes = element.attr('data-if-exists-any').split(" ");
//			for (var i = 0; i< classes.length; i++) {
//				if (classes[i]=='') continue;
//				if ($("." + classes[i]).exists()) {
//					classesExist = true;
//					break;
//				}
//
//			}
//		}
//
		// handle the data-if-exists-all attribute
		if (element.hasAttr('data-if-exists-all')) {
			evaluationMade = true;
			classes = element.attr('data-if-exists-all').split(" ");
			for (var i = 0; i< classes.length; i++) {
				if (classes[i]=='') continue;
				if (!$(classes[i]).exists()) {
					classesExist = false;
					break;
				}
			}
		}
		
		if (!evaluationMade) {
			classesExist = false;
		}

		// check if there has been a change
		var oldValue = element.data('ifExistsSelectorCurrentState');
		if (classesExist && (oldValue == 'exists')) return;
		if (!classesExist && (oldValue == 'notExists')) return;

		// set the classes and data
		if(classesExist) {
			element.data('ifExistsSelectorCurrentState','exists');
			if (element.hasAttr('data-if-exists-classes')) element.addClass(element.attr('data-if-exists-classes'));
			if (element.hasAttr('data-if-not-exists-classes'))  element.removeClass(element.attr('data-if-not-exists-classes'));
		} else {
			element.data('ifExistsSelectorCurrentState','notExists');
			if (element.hasAttr('data-if-not-exists-classes')) element.addClass(element.attr('data-if-not-exists-classes'));
			if (element.hasAttr('data-if-exists-classes')) element.removeClass(element.attr('data-if-exists-classes'));			
		}

		//fire event handlers
		if (classesExist) {
			element.trigger('classesExist');
		} else {
			element.trigger('classesNotExist');
		}
		element.trigger('classesExistChange');		

		updatesMade = true;

	});

	if (updatesMade) {
		updateIfExistsSelector();
	}

}

var checkCkeditorCompatibility_checked = false;
function checkCkeditorCompatibility() {
	
	if (checkCkeditorCompatibility_checked) return; // we don't want to check this more than once.
	checkCkeditorCompatibility_checked = true;
	
	if (CKEDITOR.env.isCompatible) return; // we are compatible.
	
	if (CKEDITOR.env.ie) return;	// never support any incompatible versions of IE
	
	if (CKEDITOR.env.mobile) {
		// mobile browsers might be supported by CkEditor, but they are usually untested.
		CKEDITOR.env.isCompatible = confirm('The editor does not seem to be compatible with your device. However, you can choose to activate the editor anyway. Do you want to activate the editor at your own risk?');
		return;
	}
	
}


var makeCkEditors_ckEditorStylesAdded = false;

function makeCkEditors(objects) {
	
	if(!makeCkEditors_ckEditorStylesAdded) {
		CKEDITOR.stylesSet.add( 'platypus_styles', [{ name: 'small', element: 'small', attributes: {} }]);
		makeCkEditors_ckEditorStylesAdded = true;
	}
	
	$(objects).each(function(index, theElement) {
		var element = $(theElement);
		
		checkCkeditorCompatibility();
		
		if(!CKEDITOR.env.isCompatible) {
			element.hide();
			element.after('<div class="alert alert-danger add-top-margin"><span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>Our editor is not compatible with your device.</div>');
			return;
		}
		
		
		var editor;
		
		var config = new Object();
		config.height = 500;
		
		config.scayt_autoStartup = true;
		
		config.language = 'en-gb'; // Actually you don't have to set this but you might as well
		config.wsc_lang = 'en_GB'; // The default spell checker language
		config.scayt_sLang = 'en_GB'; // The SCAYT spell checker language

		
		var useInlineSave = false;
		if(element.hasAttr('data-inlinesave-url')) {
			useInlineSave = true;
			config.removeButtons = '';
		} else {
			config.removeButtons = 'Save';
		}
		
		
		
		if(element.hasAttr('data-ckeditor-file-selection-url')) {
			config.dataCkEditorFileSelectionUrl = element.attr('data-ckeditor-file-selection-url');
		}
		
		if($('#mathjaxScriptTag').exists()) {
			config.mathJaxLib = $('#mathjaxScriptTag').attr('src');
		}
		
		editor = CKEDITOR.replace( theElement, config);
		
		if(editor == null) {
			// something went wrong here...
			return;
		}
		
		// store a reference to the editor in the element
		element.data('editor_instance', editor);
		
		// make sure the 'change' event is fired on the original object.
		// ckeditor fires an initial change event at the beginning. We use checkDirty() to filter all change events until there has been an actual change.
		var isDirty = false;
		editor.on('change', function(event) {
			if (!isDirty) {
				isDirty = editor.checkDirty();
			}
			if (isDirty) {
				element.trigger('change');
			}
		});
		

		// register the onSave handler.
		if (useInlineSave) {
			editor.on('save', function(event) {
				runInlinesave(element, function() {
					isDirty = false; // make the next change event check the dirty flag again.
				});			
				return false;
			});
		} else {
			editor.on('save', function(event) {
				alertModal('This method of saving your data is not available here.');			
				return false;
			});
		}

		// register the STRL+S shortcut
		editor.setKeystroke( CKEDITOR.CTRL + 83, 'save' );
		
		
		editor.on('customConfigLoaded', function(event) {

			if(editor.config.hasOwnProperty("toolbarGroups")) {

				// we want to move the document toolbar to the front and within it the save button (document) should come first.
				for(var i=0; i < editor.config.toolbarGroups.length; i++) {
					if(editor.config.toolbarGroups[i].hasOwnProperty("name") && (editor.config.toolbarGroups[i].name == 'document') ) {
						editor.config.toolbarGroups.splice(0,0,editor.config.toolbarGroups.splice(i,1)[0]);
						for(var j=0; j < editor.config.toolbarGroups[0].groups.length; j++) {
							if (editor.config.toolbarGroups[0].groups[j] == 'document') {
								editor.config.toolbarGroups[0].groups.splice(0,0,editor.config.toolbarGroups[0].groups.splice(j,1)[0]);
								break;
							}
						}
						break;
					}
				} 
			}
			
			
		});
		
	});
}

// a little helper that allows to set a context (the "this" variable) for execution.  
function evalInContext(script, context) {
	(function() { eval(script);}).call(context);
}

function runAjaxFormWrapper(event, object) {

		object = $(object);

    	var container;
    	if (object.hasClass("ajaxUpdater")) {
    		container = object;
    	} else {
    		container = object.closest(".ajaxFormWrapper");
    	}

    	// make sure we don't react to events coming from a ckeditor instance.
		var ajaxPreventor =  object.closest(".cke");
		if (ajaxPreventor.exists() && $.contains(container.get(0), ajaxPreventor.get(0))) {
			return;
		}
    	
    	// also protect .noAjax containers.
    	if (!object.hasClass("doAjax")) {
			var ajaxPreventor =  object.closest(".noAjax");
				if (ajaxPreventor.exists() && $.contains(container.get(0), ajaxPreventor.get(0))) {
				return;
			}
    	}

    	
		if (object.is("a")) {
			if (!object.hasAttr("href") || (object.attr("href").substring(0, 1) == "#")) {
				if (!object.hasClass("ajaxPost")) {
					return;
				}
			}
		}

	
    	event.preventDefault();

    	object.closest(".modal").modal('hide');

    	
    	    	
    	if (container.hasClass("ajaxSubmissionInProgress")) return;
    	container.addClass("ajaxSubmissionInProgress");    	
    	
    	// we need to disable iframes before we start. We restore this in the end.
    	container.find('iframe').each(function() {
    		if($(this).hasAttr('src')) {
    			$(this).data('savedsrc',$(this).attr('src'));
    			$(this).attr('src','about:blank');
    		}
    	});
    	
    	var containerIsWrapped = false;
    	if(!container.hasClass("ajaxNoAnimation")) {
    		container.wrap( '<div class="temporarySizeSlideWrapper"></div>' );
    		var sizeWrapper = container.closest(".temporarySizeSlideWrapper");
    		containerIsWrapped = true;
    	}
		

		var uiIsBlocked = false;
		if (containerIsWrapped && !container.hasClass("ajaxNoBlockUi")) {
			sizeWrapper.block({ message: null, overlayCSS: { backgroundColor: '#fff'} });
			uiIsBlocked = true;
		}

		var isAutoUpdateEvent = (event.type == 'autoupdate');
		
		var ajaxSuccess = false;
		var ajaxJsonContent;

		var ajaxUrl = null;
		var ajaxMethod = null;
		var ajaxData = null;
		var ajaxProcessData = true;
		var ajaxContentType = 'application/x-www-form-urlencoded; charset=UTF-8';
		
		if (object.is("form")) {
			ajaxUrl = object.attr("action");
			ajaxMethod = object.attr("method");
			
			if(typeof FormData !== 'undefined') {
				// this works with any modern browser.
				ajaxData = new FormData($(object)[0]);
				ajaxProcessData = false;
				ajaxContentType = false;
			} else {
				// this works for IE 8&9 but doesn't support file uploads.
				ajaxData = object.serialize();
				alertModal('You are using an outdated browser. Some features will not work.');
			}
			
		} else if(object.is("a")) {
			ajaxUrl = $(object).attr("data-url");
			if ((typeof ajaxUrl === typeof undefined) || (ajaxUrl === false)) {
				ajaxUrl = object.attr("href");
			}
			if(object.hasClass("ajaxPost")) {
				ajaxMethod = 'post';
				ajaxData = object.data();
			} else {
				ajaxMethod = 'get';
				ajaxData = '';
			}
		} else if(object.is("textarea") && (event.type == 'inlinesave') ) {
			ajaxUrl = $(object).attr("data-inlinesave-url");
			ajaxMethod = 'post';
			ajaxData = $(object).closest('form').serialize();
			
		} else if(object.hasClass("ajaxFormWrapper")||object.hasClass("ajaxUpdater")) {
			ajaxUrl = object.attr("data-url");
			ajaxMethod = 'get';
			ajaxData = '';
		}
		
		
		$.ajax(ajaxUrl, {
			type: ajaxMethod,
			data: ajaxData,
			dataType: "json",
			processData: ajaxProcessData,
			contentType: ajaxContentType,
			error: function(jqXHR, textStatus, errorThrown) { 
				setTimeout(function() { alert("warning: communication to the server failed and your request could not be saved. Please try again later." + "Debug info: " + jqXHR.responseText);},0); 
			},
			success: function(data, textStatus, jqXHR) { ajaxJsonContent = data; ajaxSuccess = true; },
			complete: function() {

				if (containerIsWrapped) {
					sizeWrapper.css( { height: container.outerHeight(true) + 'px' } );
				}

	    		if (ajaxSuccess) {
		    		
	    			processJsonResponse(ajaxJsonContent, container, isAutoUpdateEvent);
		    		
		    	} else {
		    		container.addClass("alert alert-warning");
				}


	    		// start this in a timeout so the content has a chance to initialise (e.g. render ckeditors) 
	    		setTimeout(function(){ 			
	    			var animationCount = 3; // we declare one extra animation and trigger it finished right away in case there is no animation at all.

	    			var onAnimationFinished = function() {
    					animationCount--; 
    					if ( animationCount<=0) {
    						if (containerIsWrapped) {
    							container.unwrap();
    						}
    						
    				    	// restore the iframes.
    				    	container.find('iframe').each(function() {
    				    		var savedsrc = $(this).data('savedsrc');
    				    		if (typeof savedsrc !== typeof undefined && savedsrc !== false) {
    				    			$(this).attr('src',savedsrc);
    				    			$(this).removeData('savedsrc');
    				    		}
    				    			
    				    	});
    						
    						container.removeClass("ajaxSubmissionInProgress");
    					} 
	    			};
	    			
	    			if (uiIsBlocked) {
	    				sizeWrapper.unblock({ onUnblock: onAnimationFinished});
	    			} else {
	    				animationCount--;
	    			}

	    			if (containerIsWrapped && !container.hasClass("ajaxNoAnimation")) {
	    				sizeWrapper.animate( { height: container.outerHeight() + 'px' }, 'slow', 'swing', onAnimationFinished);
	    			} else {
	    				animationCount--;
	    			}
	    			
	    			onAnimationFinished();
	    			
	    		 }, 0);
			}
		});
}


function processJsonResponse(ajaxJsonContent, container, isAutoUpdateEvent) {
	
	var ajaxFormWrapper = container; // Make the container available as "ajaxFormWrapper" to scripts we receive.
	
	if( (!ajaxJsonContent.hasOwnProperty("success")) || (ajaxJsonContent.success)) {		
		container.removeClass("alert alert-warning");
	} else {
		container.addClass("alert alert-warning");
	}
	
	if(ajaxJsonContent.hasOwnProperty("html")) {
		container.empty().html(ajaxJsonContent.html);
		registerEventHandlers(container.children());
	}

	if(ajaxJsonContent.hasOwnProperty("order")) {
		var orderContainer = container;
		if(ajaxJsonContent.hasOwnProperty("orderother")) {
			orderContainer = $('#' + ajaxJsonContent.orderother);
		}
		
		var orderData = ajaxJsonContent.order;
		var oldContent = $(orderContainer.children().detach());
		oldContent.filter('*[data-resourceid="head"]').appendTo(container);

		for(var i=0; i < orderData.length; i++) {
			var dataRecord = orderData[i];
			var existing;
			if (dataRecord.hasOwnProperty("id")) {
				existing = oldContent.filter('*[data-resourceid="' + dataRecord.id + '"]');
			} else {
				existing = $([]);
			}
			if (existing.length > 0) {
				existing.appendTo(orderContainer);
			} else {
				var newChild = $.parseHTML(dataRecord.html);
				orderContainer.append(newChild);
				registerEventHandlers(newChild);
			}
		} 
			
		orderContainer.append(oldContent.filter('*[data-resourceid="foot"]'));
		
	}
	

	if(ajaxJsonContent.hasOwnProperty("update")) {
		var updateData = ajaxJsonContent.update;

		$.each(updateData, function(key, value) {
			var updateTarget = $('.updatable[data-resourceid="' + key + '"]');
			updateTarget.empty().html(value);
			registerEventHandlers(updateTarget);
		});
	}
	
	if(ajaxJsonContent.hasOwnProperty("script")) {
		//eval(ajaxJsonContent.script);
		evalInContext(ajaxJsonContent.script, container);
	}
	
	if(ajaxJsonContent.hasOwnProperty("growl")) {
		$.jGrowl(ajaxJsonContent.growl);
	}
	
	if(ajaxJsonContent.hasOwnProperty("alert")) {
		alertModal(ajaxJsonContent.alert);
	}

	if(ajaxJsonContent.hasOwnProperty("modal")) {
		largeModal(ajaxJsonContent.modal);
	}
			    	
	updateIfExistsSelector();
	
	if (!isAutoUpdateEvent) {
		setTimeout(function() {
			$('.autoupdate').trigger('autoupdate');
		},0);
	} 
		
	
}


function runAjaxContentLoader(object) {
	if (! $(object).hasClass("ajaxContentLoader") ) {
		return;
	}

	var ajaxUrl = $(object).attr("data-url");
	var ajaxMethod = 'get';

	var ajaxSuccess = false;
	var ajaxJsonContent;
	
	$.ajax(ajaxUrl, {
		type: ajaxMethod,
		error: function(jqXHR, textStatus, errorThrown) { 
			setTimeout(function() { alert("warning: communication to the server failed and your request could not be saved. Please try again later." + "Debug info: " + jqXHR.responseText);},0); 
		},
		success: function(data, textStatus, jqXHR) { ajaxJsonContent = data; ajaxSuccess = true; },
		complete: function() {	

			if (ajaxSuccess) {
	    		
    			if( (!ajaxJsonContent.hasOwnProperty("success")) || (ajaxJsonContent.success)) {		
    				$(object).removeClass("alert alert-warning");
    			} else {
    				$(object).addClass("alert alert-warning");
				}
				
	    		if(ajaxJsonContent.hasOwnProperty("html")) {
					var newChild = $.parseHTML(ajaxJsonContent.html);
					$(object).replaceWith(newChild);
					registerEventHandlers(newChild);
				} 
	    		
	    		if(ajaxJsonContent.hasOwnProperty("script")) {
	    			//eval(ajaxJsonContent.script);
	    			evalInContext(ajaxJsonContent.script, container);
	    		}
	    		
	    		if(ajaxJsonContent.hasOwnProperty("growl")) {
	    			$.jGrowl(ajaxJsonContent.growl);
	    		}
	    		
	    		updateIfExistsSelector();
	    		
	    	} else {
	    		$(object).addClass("alert alert-warning");
			}
		}
	});
	
}


function scheduleAutosaveText(event, object) {
	object.removeClass('dataCollectedForAutosave');
	if (object.hasClass('waitingforAutosave')) return;
	object.addClass('waitingforAutosave');
	setTimeout( function() {
		runAutosaveText(event, object);
	},45*1000);
}

function getTimeString() {
	var currentDate = new Date();
	var timeStampString = "" + currentDate.getHours() + ":";
	if (currentDate.getMinutes()<10) timeStampString += "0";
	timeStampString += currentDate.getMinutes() + ":";
	if (currentDate.getSeconds()<10) timeStampString += "0";
	timeStampString += currentDate.getSeconds(); 
	return timeStampString;
}

function runAutosaveText(event, object) {

	if (!object.closest(document.documentElement).exists()) {
		// we are no longer part of the dom, so don't do anything.
		object.removeClass('waitingforAutosave');
		return;
	}
	
	object = $(object);
	var form = object.closest('form');
	var ajaxSuccess = false;

	timeStampString = getTimeString();

	var onFinished = function() {
		object.removeClass('waitingforAutosave');
		if(ajaxSuccess) {
			object.data('lastSuccessfulAutosave', timeStampString);
			if(object.hasAttr('data-autosave-message-id')) {
				$(object.attr('data-autosave-message-id')).html('Draft saved at ' + timeStampString);
			}
		} else {
			if(object.hasAttr('data-autosave-message-id')) {
				var lastTime = object.data('lastSuccessfulAutosave');
				if (typeof lastTime !== typeof undefined && lastTime !== false) {
					$(object.attr('data-autosave-message-id')).html('Warning: automatic save failed. Last saved at ' + lastTime);
				} else {
					$(object.attr('data-autosave-message-id')).html('Warning: automatic save failed.');
				}
			}
		}
		
		if(!ajaxSuccess || !object.hasClass('dataCollectedForAutosave')) {
			scheduleAutosaveText(event, object);
		}
		
	};
	
	
	if(!form.exists() || !object.hasAttr('data-autosave-url')) {
		onFinished()
		return;
	}
	
	var editor = object.data('editor_instance');
	if (typeof editor !== typeof undefined && editor !== false) {
		object.val(editor.getData());		
	}
	
	var ajaxJsonContent;

	var ajaxUrl = object.attr('data-autosave-url');
	var ajaxMethod = 'post';
	
	
	var ajaxData = form.serialize();
	object.addClass('dataCollectedForAutosave');
	
	var ajaxProcessData = true;
	var ajaxContentType = 'application/x-www-form-urlencoded; charset=UTF-8';
	
	
	$.ajax(ajaxUrl, {
		type: ajaxMethod,
		data: ajaxData,
		dataType: "json",
		processData: ajaxProcessData,
		contentType: ajaxContentType,
		timeout : 30*1000,
		error: function(jqXHR, textStatus, errorThrown) {},
		success: function(data, textStatus, jqXHR) { ajaxJsonContent = data; ajaxSuccess = true; },
		complete: function() {
			onFinished();
		}
	});
}


function runInlinesave(object, onCompleted) {

	object = $(object);

	var container = object.closest(".ajaxFormWrapper");

	if (container.hasClass("ajaxSubmissionInProgress")) {
		alertModal("Your changes cannot be saved right now. Please try again later.");
		return;
	}
	container.addClass("ajaxSubmissionInProgress");    	
	

	var ajaxSuccess = false;
	var ajaxJsonContent;

	var ajaxUrl = null;
	var ajaxMethod = null;
	var ajaxData = null;
	var ajaxProcessData = true;
	var ajaxContentType = 'application/x-www-form-urlencoded; charset=UTF-8';
	
	if(!object.hasAttr("data-inlinesave-url")) {
		alertModal("Your changes cannot be saved. (No save destination was set. This is a bug.)");
		return;
	}
	
	ajaxUrl = object.attr("data-inlinesave-url");
	ajaxMethod = 'post';
	
	var editor = object.data('editor_instance');
	if (typeof editor !== typeof undefined && editor !== false) {
		object.val(editor.getData());		
	} else {
		alertModal("Your changes cannot be saved. (No editor was found. This is a bug.)");
		return;
	}
	
	var form = $(object).closest('form');
	if(!form.exists()) {
		alertModal("Your changes cannot be saved. (This is a bug.)");
		return;
	}	
	
	ajaxData = form.serialize();
	var valueWeUsedForSaving = object.val();
	var timeStampString = getTimeString();
	
	$.ajax(ajaxUrl, {
		type: ajaxMethod,
		data: ajaxData,
		dataType: "json",
		processData: ajaxProcessData,
		contentType: ajaxContentType,
		timeout: 15*1000,
		error: function(jqXHR, textStatus, errorThrown) { 
			setTimeout(function() { alertModal("Error: the communication to the server failed and your data could not be saved. Please try again later." + "Debug info: " + jqXHR.responseText);},0); 
		},
		success: function(data, textStatus, jqXHR) { ajaxJsonContent = data; ajaxSuccess = true; },
		complete: function() {

    		if (ajaxSuccess) {

    			if( ajaxJsonContent.hasOwnProperty("success") && ajaxJsonContent.success) {	
    				if(object.hasAttr('data-inlinesave-message-id')) {
    					$(object.attr('data-inlinesave-message-id')).html('Saved at ' + timeStampString);
    				}
    			}
    			
    			if(valueWeUsedForSaving == editor.getData()) {
    				editor.resetDirty(); 
    				if(object.hasAttr('data-on-change-add-classes')) {
    					object.removeClass(object.attr('data-on-change-add-classes'));
    					updateIfExistsSelector();
    					object.one("change", function(event) {
    						$(this).addClass($(this).attr('data-on-change-add-classes'));
    						updateIfExistsSelector();
    					});
    				}
    			}
    			
    			processJsonResponse(ajaxJsonContent, container, false);
	    		
	    	} else {
	    		container.addClass("alert alert-warning");
			}

			container.removeClass("ajaxSubmissionInProgress");
					
			onCompleted();

		}
	});
}


function unhideButtonTriggered(event, object) {
	var container = object.closest(".unhide-group");

	child = container.children(".unhide-item").first();

	child.slideDown();
	child.removeClass("unhide-item");

	if (!container.children(".unhide-item").exists()) {
		container.children(".unhide-control").slideUp();
	}
}

function runModalQueue() {
	setTimeout(function(){
		if(!$('.queuedModalShowing').exists()) {
			$('body').dequeue('modalQueue');
		}
	},0);
}

function queueModal(html) {
	$('body').queue('modalQueue', function() {
		if ($.type(html) === "string") {
			modal = $($.parseHTML(html));
		} else {
			modal = html;
		}
		modal.addClass('queuedModalShowing');
		modal.appendTo('body');
		registerEventHandlers(modal);
		modal.on('hidden.bs.modal', function (e) {
			modal.remove();
			runModalQueue();
		});
		$('.queuedModalShowing').modal('show');		
	});
	runModalQueue();
}

function alertModal(message) {
	var modal = $("#blueprint-alertModal").clone(false);
	modal.find("*").removeAttr("id");
	modal.find('.marker-modal-content').empty().html(message);
	queueModal(modal);
}

function largeModal(message) {
	var modal = $("#blueprint-largeModal").clone(false);
	modal.find("*").removeAttr("id");
	modal.find('.marker-modal-content').empty().html(message);
	queueModal(modal);
}

function runConfirmationModal(object) {
	object = $(object);
	
	if (object.hasAttr('data-confirmationdialog')) {

		// create the modal itself
		var modal = $("#blueprint-confirmationModal").clone(false);
		modal.find("*").removeAttr("id");
		
		// add the main message
		modal.find('.marker-modal-content').empty().html(object.data('confirmationdialog'));
		
		// change the button text
		if (object.hasAttr('data-confirmationbutton')) {
			modal.find('.confirmationButton').empty().html(object.data('confirmationbutton'));
		}
		
		// manage the existence of the checkbox
		var hasCheckbox = object.hasAttr('data-confirmationcheckbox');
		if (hasCheckbox) {
			modal.find('.checkboxLabel').empty().html(object.data('confirmationcheckbox'));
		} else {
			modal.find('.confirmationCheckboxContainer').hide();
		}
		
		modal.find('.confirmationButton').click(function(buttonEvent, buttonObject) {
			if (hasCheckbox) {
				if (modal.find('.confirmationCheckbox')[0].checked) {
					object.closest('form').prepend('<input type="hidden" name="doit" value="now">');
				} else {
					buttonEvent.preventDefault();
					buttonEvent.stopPropagation();
					modal.modal('hide');
					alertModal('Please check the confirmation box.');
					runConfirmationModal(object);
					return;
				}
			}			
			
			object.addClass('confirmedTrigger');
			object.filter('a').each(function() { this.click();});
			object.filter('button').each(function() { this.click();});
			object.filter('form').submit();
			object.removeClass('confirmedTrigger');
		});
		
		queueModal(modal);	
	}
}

function handleConfirmationDialog(event, object) {
	object = $(object);
	if (object.hasAttr('data-confirmationdialog') && object.attr('data-confirmationdialog') && !object.hasClass('confirmedTrigger')) {
		event.preventDefault();
		event.stopPropagation();
		runConfirmationModal(object);
	} 
}



// When opening a dialog, its "definition" is created for it, for
// each editor instance. The "dialogDefinition" event is then
// fired. We should use this event to make customizations to the
// definition of existing dialogs.
CKEDITOR.on( 'dialogDefinition', function( ev ) {
	// Take the dialog name and its definition from the event data.
	var dialogName = ev.data.name;
	var dialogDefinition = ev.data.definition;

	// Check if the definition is from the dialog we're
	// interested in the "image" dialog.
	if ( dialogName == 'image' ) {
		// Get a reference to the "Link Info" tab.
		var infoTab = dialogDefinition.getContents( 'info' );

		// Add a new tab to the Image dialog.
		dialogDefinition.addContents( {
			id: 'selectAttachmentTab',
			label: 'Select File',
			accessKey: 'S',
			elements: [
				{
					id: 'fileSelection',
					type: 'html',
					html: '<div class="ajaxFormWrapper">Loading...</div>',
					onLoad: function() {
						var dialogObj = this.getDialog();
						var editor = dialogObj.getParentEditor();
						var domObj = $(this.getElement().$); // as JQuery object
						var urlField = dialogObj.getContentElement('info','txtUrl');
						
						domObj.on("click", "a", function(event) {
							if(!$(this).hasClass('ckeditorFileChoice')) return;
							event.preventDefault();
							urlField.setValue($(this).attr('href'));
							dialogObj.selectPage('info');
					    });
						
						domObj.attr('data-url', editor.config.dataCkEditorFileSelectionUrl);
						registerEventHandlers(domObj);
					},
					onShow: function() {
						var domObj = $(this.getElement().$); // as JQuery object
						domObj.trigger('manualupdate');
						
						var dialogObj = this.getDialog();
						var urlField = dialogObj.getContentElement('info','txtUrl');
						if(urlField.getValue() == '') {
							dialogObj.selectPage('selectAttachmentTab');
						}
					},
				}
			]
		});
	}

	
	// Check if the definition is from the dialog we're
	// interested in the "mathjax" dialog.
	if ( dialogName == 'mathjax' ) {
		
		var infoTab = dialogDefinition.getContents( 'info' );
		
		infoTab.add( {
				id: 'mathMenu',
				type: 'html',
				html: '<div></div>',
				onLoad: function () {
					var domObj = $(this.getElement().$); // as JQuery object
					CKEDITOR.ajax.load( CKEDITOR.basePath + '../../assets/math-menu.htm', function( data ) {
						domObj.empty().html(data);
						registerEventHandlers(domObj);
					});
					
					var equationFieldCkEditor = this.getDialog().getContentElement('info','equation').getInputElement(); // as CkEditor object
					var equationFieldJQuery = $(this.getDialog().getContentElement('info','equation').getElement().$).find('textarea'); // as JQuery object
					domObj.on("click", "a", function(event) {
						var texCommand = $(this).attr('data-tex-command');
						if (typeof texCommand !== typeof undefined && texCommand !== false) {
							event.preventDefault();
							equationFieldJQuery.insertAtCaret(texCommand);
							equationFieldCkEditor.fire('keyup'); // fire the CKEditor keyup event. The Mathjax plugin listens to that to update the preview. 
						}
				    });					
				},
		}, 'equation');
	}	
	
});


function initialiseLogoResizer() {
	var processLogoScrolling = function() {
		$('.platypusLogo').each(function() {
			var logo = $(this);
			var maxHeight = Math.max(logo.data('minresizeheight'), logo.data('baseresizeheight') - (0.5 * $(document).scrollTop()));
			var maxWidth = maxHeight * logo.data('aspect');
			logo.css( "max-width",  maxWidth + "px" ).css( "max-height",  maxHeight + "px" );
		});
	};
	
	$(window).scroll(processLogoScrolling);
	processLogoScrolling();
}

jQuery(document).ready(function($) {
	
	try {
		registerEventHandlers($( ":root" ));
	
		window.onbeforeunload = function() {
			if($('.preventPageLeave').exists()) {
				return 'You have unsaved changes. Are you sure you want to leave this page now?';
			}
		}
		
		initialiseLogoResizer();
	} finally {
		$('body').removeClass("loading");
		//setTimeout(function(){$('body').removeClass("loading");},2000);
	}
	
});

new Clipboard('.btn-copy', {
	text: function(trigger) {
		e_name = trigger.getAttribute("data-copy-target");
		console.log(e_name);
		if (e_name == null) {
			console.log(trigger);
			return
		}
		return document.getElementById(e_name).innerHTML;
	}
});
