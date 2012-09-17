/**
* jQuery File Tree Plugin
*
* Version 1.02
*
* Cory S.N. LaViska
* A Beautiful Site (http:*abeautifulsite.net/)
* 24 March 2008
*
* Visit http:*abeautifulsite.net/notebook.php?article=58 for more information
*
* Usage: $('.fileTreeDemo').fileTree( options, callback )
*
* Options:  root           - root folder to display; default = /
*           script         - location of the serverside AJAX file to use; default = jqueryFileTree.php
*           folderEvent    - event to trigger expand/collapse; default = click
*           expandSpeed    - default = 500 (ms); use -1 for no animation
*           collapseSpeed  - default = 500 (ms); use -1 for no animation
*           expandEasing   - easing function to use on expand (optional)
*           collapseEasing - easing function to use on collapse (optional)
*           multiFolder    - whether or not to limit the browser to one subfolder at a time
*           loadMessage    - Message to display while initial tree loads (can be HTML)
*
* History:
*
* 1.02 - Updated by Serrano Pereira for MaSIS (17 Sept 2012)
*        - Comments and function descriptions added.
*        - The selected file is highlighted with a 'selected' class.
*        - The link element is also passed to the file handler.
*        - Other minor code updates.
* 1.01 - updated to work with foreign characters in directory/file names (12 April 2008)
* 1.00 - released (24 March 2008)
*
* TERMS OF USE
*
* This plugin is dual-licensed under the GNU General Public License and the MIT License and
* is copyright 2008 A Beautiful Site, LLC.
*/

if (jQuery) (function($){

	$.extend($.fn, {
		fileTree: function(options, callback) {
			// Defaults
			if ( !options ) var options = {};
			if ( options.root == undefined ) options.root = '/';
			if ( options.script == undefined ) options.script = 'jqueryFileTree.php';
			if ( options.folderEvent == undefined ) options.folderEvent = 'click';
			if ( options.expandSpeed == undefined ) options.expandSpeed= 500;
			if ( options.collapseSpeed == undefined ) options.collapseSpeed= 500;
			if ( options.expandEasing == undefined ) options.expandEasing = null;
			if ( options.collapseEasing == undefined ) options.collapseEasing = null;
			if ( options.multiFolder == undefined ) options.multiFolder = true;
			if ( options.loadMessage == undefined ) options.loadMessage = 'Loading...';

			$(this).each( function() {

                /**
                 * Open a directory tree.
                 *
                 * @param {Object} li_dir The directory element li.directory
                 * @param {String} dir_path The directory path
                 */
				function showTree(li_dir, dir_path) {
					$(li_dir).addClass('wait');
					$(".jqueryFileTree.start").remove();

                    $.ajax({
                      type: 'POST',
                      url: options.script,
                      data: { dir: dir_path },
                      success: function(data) {
                            $(li_dir).find('.start').html('');
                            $(li_dir).removeClass('wait').append(data);

                            if ( options.root == dir_path ) {
                                // Open the root directory by default.
                                $(li_dir).find('ul:hidden').show();
                            }
                            else {
                                // Open the directory with an animation.
                                $(li_dir).find('ul:hidden').slideDown({ duration: options.expandSpeed, easing: options.expandEasing });
                            }
                            bindTree(li_dir);
                        }
                    });
				}

                /**
                 * Bind events and handlers to the directory links.
                 *
                 * @param {Object} li_dir The directory element li.directory
                 * @param {String} dir_path The directory path
                 */
				function bindTree(li_dir) {
					$(li_dir).find('li a').bind(options.folderEvent, function() {
                        // Check if the current link is a directory link.
						if ( $(this).parent().hasClass('directory') ) {
                            // Directory. Decide whether to expand or callapse.
							if ( $(this).parent().hasClass('collapsed') ) {
								// Expand
								if ( !options.multiFolder ) {
                                    // If multi folder is disabled, collapse all directories
                                    // and removed the `expanded` classes.
									$(this).parent().parent().find('ul').slideUp({ duration: options.collapseSpeed, easing: options.collapseEasing });
									$(this).parent().parent().find('li.directory').removeClass('expanded').addClass('collapsed');
								}
								$(this).parent().find('ul').remove(); // cleanup
								showTree( $(this).parent(), escape($(this).attr('rel').match( /.*\// )) );
								$(this).parent().removeClass('collapsed').addClass('expanded');
							} else {
								// Collapse
								$(this).parent().find('ul').slideUp({ duration: options.collapseSpeed, easing: options.collapseEasing });
								$(this).parent().removeClass('expanded').addClass('collapsed');
							}
						} else {
                            // Not a directory, but a file was clicked. So call
                            // the file handler with two arguments:
                            //  - the file path
                            //  - the link element
							callback($(this).attr('rel'), this);

                            // Highlight the selected file by adding a `selected` to the a element.
                            $(this).parent().parent().find('li.file a').removeClass('selected');
                            $(this).addClass('selected');
						}
						return false;
					});
					// Prevent a from triggering the # on non-click events
					if ( options.folderEvent.toLowerCase != 'click' ) $(li_dir).find('li a').bind('click', function() { return false; });
				}
				// Loading message
				$(this).html('<ul class="jqueryFileTree start"><li class="wait">' + options.loadMessage + '<li></ul>');
				// Get the initial file list
				showTree( $(this), escape(options.root) );
			});
		}
	});

})(jQuery);
