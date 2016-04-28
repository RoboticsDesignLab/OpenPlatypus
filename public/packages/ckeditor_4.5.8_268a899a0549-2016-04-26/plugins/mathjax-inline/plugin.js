
( function () {

    var validInlineRegExp = /\$\$((\$(?!\$)|[^$])*)\$\$/m
    var validInlineRegExpGlobal = /\$\$((\$(?!\$)|[^$])*)\$\$/gm

    CKEDITOR.plugins.add( 'mathjax-inline', {

        init: function( editor ) {

            var mathjaxClass = editor.config.mathJaxClass || 'math-tex';
            var mathReplacement = '<span class="' + mathjaxClass + '" style="display:inline-block" data-cke-survive=1>\\($1\\)</span>';
        
        	var bookmark = null;
        
            editor.on( 'key', function (evt) {
            
            	if (evt.editor.getData().search(validInlineRegExp) < 0) {
            		return;
            	}
            
            	var changeCount = 0;
            	
            	var range = new CKEDITOR.dom.range(evt.editor.editable());
            	range.selectNodeContents( evt.editor.editable() );
            	
            	var walker = new CKEDITOR.dom.walker(range);
            	
            	walker.evaluator = function ( node ) {
            		if (node.type != CKEDITOR.NODE_TEXT) { return false; }
            		
            		//if (node.getText().search(validInlineRegExp) < 0) { return false; }
            		
            		return true;
            	}
            	
            	while ( (node = walker.next()) ) {
            	
            		var nn = node.getNext();
            		while( nn != null ) {
            			var handled = false;
            			if (nn.type == CKEDITOR.NODE_TEXT) {
            				node.setText(node.getText() + nn.getText());
            				nn.remove();
            				handled = true;
            			}
            			
            			if ((nn.getName instanceof Function) && nn.getName() == 'span' && nn.hasClass('scayt-misspell-word')) {
            				node.setText(node.getText() + nn.getChild(0).getText())
            				nn.remove();
            				handled = true;
            			}
            			
            			nn = handled ? node.getNext() : null;
            		}
            		
            		if (node.getText().search(validInlineRegExp) < 0) {
            			continue;
            		}
            	
            		var html = node.getText().replace(validInlineRegExp, mathReplacement);
            		var mathSpan = CKEDITOR.dom.element.createFromHtml('<span>' + html + '</span>');

                    mathSpan.replace(node); 

                    var mathRange = new CKEDITOR.dom.range(mathSpan.getParent());
                    mathRange.selectNodeContents( mathSpan.getParent() );

                    var mathNodeWalker = new CKEDITOR.dom.walker(mathRange);

                    mathNodeWalker.evaluator = function (node) {
                        if (!((node.getName instanceof Function) && node.getName() == 'span' && node.hasClass(mathjaxClass)))
                            return false;
                        if ( node.getChildCount() > 1 || node.getChild(0).type != CKEDITOR.NODE_TEXT )
                            return false;
                        
                        return true;
                    }

                    while (mathNode = mathNodeWalker.next()) {
                        var mathValue = CKEDITOR.tools.htmlDecode( mathNode.getChild(0).getText() );

                        mnClean = CKEDITOR.dom.element.createFromHtml('<span class="' + mathjaxClass + '" style="display:inline-block" data-cke-survive=1></span>');
                        mnClean.replace(mathNode)
                        var mathWidget = evt.editor.widgets.initOn(mnClean, 'mathjax');
                        mathWidget.setData('math', mathValue)               
                    }

                    evt.editor.widgets.checkWidgets();
            	}
            });
            
            editor.on( 'paste', function (evt) { 
            	var data = handleMathjaxCheck(evt.data.dataValue); 
            	if (data != null) {
					evt.data.dataValue = data;
				}
            });
            
            editor.on( 'dataReady', function (evt) {
            	if (bookmark != null) {
            		evt.editor.focus();
            		evt.editor.getSelection().selectBookmarks( bookmark );
            		bookmark = null;
            	}
            });

			function handleMathjaxCheck(data) {
			
				var updated = false;
                while (data.match(validInlineRegExpGlobal) != null) {
					updated = true;
                    data = data.replace(validInlineRegExp, mathReplacement);
                }

				if (!updated) {
					return null;
				}

                return data;
			}

        }
    });
}) ();
