<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common/data" [
	<!ENTITY sys-module        'data'>
	<!ENTITY sys-method-type-view    'types'>
	<!ENTITY sys-method-type-add    'type_add'>
	<!ENTITY sys-method-type-edit    'type_edit'>
	<!ENTITY sys-method-type-del    'type_del'>
	<!ENTITY sys-method-del        'del'>
	<!ENTITY sys-method-trash-del    'trash_del'>
	<!ENTITY sys-method-restore    'trash_restore'>
	<!ENTITY sys-method-empty    'trash_empty'>
	<!ENTITY sys-method-guide-view    'guide_items'>
	<!ENTITY sys-method-guide-add    'guide_add'>
]>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:template match="data[@type = 'list' and @action = 'view']">
		<div class="tabs-content module">
		<div class="section selected">
			<div class="location">
				<div class="imgButtonWrapper loc-left">
					<a class="btn color-blue" href="{$lang-prefix}/admin/&sys-module;/&sys-method-type-add;/{$param0}/"
					   id="addType">&label-type-add;</a>
				</div>
				<a class="btn-action loc-right infoblock-show"><i class="small-ico i-info"></i><xsl:text>&help;</xsl:text></a>
			</div>


			<div class="layout">
				<div class="column">
					<xsl:call-template name="ui-smc-table">
						<xsl:with-param name="domains-show">1</xsl:with-param>
						<xsl:with-param name="content-type">types</xsl:with-param>
						<xsl:with-param name="disable-name-filter">true</xsl:with-param>
						<xsl:with-param name="enable-edit">false</xsl:with-param>
						<xsl:with-param name="js-value-callback"><![CDATA[
				function (value, name, item) {
					var data = item.getData();
					return data.title;
				}
			]]></xsl:with-param>
						<xsl:with-param name="menu"><![CDATA[
				var menu = [
					['edit-item', 'ico_edit', ContextMenu.itemHandlers.editItem],
					['delete',    'ico_del',  ContextMenu.itemHandlers.deleteItem]
				]
			]]></xsl:with-param>
						<xsl:with-param name="js-add-buttons">
							createAddButton(
							$('#addType')[0], oTable,
							'<xsl:value-of select="$lang-prefix"/>/admin/&sys-module;/&sys-method-type-add;/{id}/',
							['*',true]
							);
						</xsl:with-param>
					</xsl:call-template>
				</div>
				<div class="column">
					<div  class="infoblock">
						<h3>
							<xsl:text>&label-quick-help;</xsl:text>
						</h3>
						<div class="content" title="{$context-manul-url}">
						</div>
						<div class="infoblock-hide"></div>
					</div>
				</div>

			</div>

		</div>
		</div>
	</xsl:template>

	<xsl:template match="/result[@method = 'guides']/data[@type = 'list' and @action = 'view']">
		<div class="tabs-content module">
			<div class="section selected">
				<div class="location">
					<div class="imgButtonWrapper loc-left">
						<a class="btn color-blue" href="{$lang-prefix}/admin/&sys-module;/&sys-method-guide-add;/"
						   id="addType">&label-guide-add;</a>


					</div>
				<a class="btn-action loc-right infoblock-show">
					<i class="small-ico i-info"></i>
						<xsl:text>&help;</xsl:text></a>
				</div>


				<div class="layout">
					<div class="column">
						<xsl:call-template name="ui-smc-table">
							<xsl:with-param name="content-type">types</xsl:with-param>
							<xsl:with-param name="control-params">guides</xsl:with-param>
							<xsl:with-param name="js-value-callback"><![CDATA[
												function (value, name, item) {
													var data = item.getData();
													return data.title;
												}
											]]></xsl:with-param>
							<xsl:with-param name="menu"><![CDATA[
												var menu = [
													['view-guide-items', 'view',     ContextMenu.itemHandlers.guideViewItem],
													['edit-item',        'ico_edit', ContextMenu.itemHandlers.editItem],
													['delete',           'ico_del',  ContextMenu.itemHandlers.deleteItem]
												]
											]]></xsl:with-param>
						</xsl:call-template>
					</div>
					<div class="column">
						<div  class="infoblock">
							<h3>
								<xsl:text>&label-quick-help;</xsl:text>
							</h3>
							<div class="content" title="{$context-manul-url}">
							</div>
							<div class="infoblock-hide"></div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="/result[@method = 'trash']">
		<script type="text/javascript"><![CDATA[
			function showClearTrashConfirm() {
				openDialog(getLabel('js-trash-confirm-text'), getLabel('js-trash-confirm-title'), {
					confirmText: getLabel('js-trash-confirm-ok'),
					cancelButton: true,
					cancelText: getLabel('js-trash-confirm-cancel'),
					confirmCallback: function (popupName) {
						letsGo();
						closeDialog(popupName);
					}
				});
			}


			var letsGo = function() {
	
					var h  = '<div class="exchange_container">';
							h += '<div id="process-header">' + getLabel('js-trash-empty-help') + '</div>';
							h += '<div><img id="process-bar" src="/images/cms/admin/mac/process.gif" class="progress" /></div>';
							h += '<div class="status">' + getLabel('js-trash-deleted') + '<span id="deleted_counter">0</span></div>';
						h += '</div>';
						h += '<div class="eip_buttons custom">';
							h += '<input id="ok_btn" type="button" value="' + getLabel('js-trash-empty_ok') + '" class="ok" style="margin-left:5px;" disabled="disabled" />';
							h += '<input id="repeat_btn" type="button" value="' + getLabel('js-trash-empty_repeat') + '" class="repeat" disabled="disabled" />';
							h += '<input id="stop_btn" type="button" value="' + getLabel('js-trash-empty_stop') + '" class="stop" />';
							h += '<div style="clear: both;"/>';
						h += '</div>';


					openDialog('',  getLabel('js-trash-empty'), {
						stdButtons: false,
						html       : h,
						width      : 390,
						confirmCallback : function () {}
					});

					var i_deleted = 0;

					var b_canceled = false;

					var reportError = function(msg) {alert(msg)
						$('#errors_message').css('color', 'red');
						i_errors++;
						$('#errors_counter').html(i_errors);
						$('#import_log').append(msg + "<br />");
						$('#process-header').html(msg).css('color', 'red');
						$('#process-bar').css({'visibility' : 'hidden'});
						$('#repeat_btn').one("click", function() { i_deleted=0;b_canceled = false; processImport(); }).removeAttr('disabled');
						$('#ok_btn').one("click", function() { closeDialog(); }).removeAttr('disabled');
						$('#stop_btn').attr('disabled', 'disabled');

						if(window.session) {
							window.session.stopAutoActions();
						}

					}

					var processEmpty = function () {
						$('#process-bar').css({'visibility' : 'visible'});
						$('#process-header').html(getLabel('js-trash-empty-help')).css({'color' : ''});
						$('#repeat_btn').attr('disabled', 'disabled');
						$('#ok_btn').attr('disabled', 'disabled');
						$('#stop_btn').one("click", function() { b_canceled = true; $(this).attr('disabled', 'disabled'); }).removeAttr('disabled');

						if(window.session) {
							window.session.startAutoActions();
						}

						$.ajax({
							type: "GET",
							url: "/admin/data/trash_empty.xml",
							dataType: "xml",

							success: function(doc){
							
								$('#process-bar').css({'visibility' : 'hidden'});
								//var errors = doc.getElementsByTagName('error');
								//if (errors.length) {
								//	reportError(errors[0].firstChild.nodeValue)
								//	return;
								//}


								// updated counts
								var data_nl = doc.getElementsByTagName('data');
								if (!data_nl.length) {
									reportError(getLabel('js-trash-ajaxerror'));
									return false;
								}
								
								var data = data_nl[0]; 
								i_deleted += (parseInt(data.getAttribute('deleted')) || 0);
								
								$('#deleted_counter').html(i_deleted);

								var complete = data.getAttribute('complete') || false;

								if (complete === false) {
									reportError(getLabel('Parse data error. Required attribute complete not found'));
									exit();
								}

								if (complete == 1) {
									$('#process-header').html(getLabel('js-trash-empty-done')).css({'color' : 'green'});
									$('#stop_btn').attr('disabled', 'disabled');
									$('#ok_btn').one("click", function() { closeDialog(); window.location.href=window.pre_lang + '/admin/data/trash/';}).removeAttr('disabled');

									if(window.session) {
										window.session.stopAutoActions();
									}
								} else {
									if (b_canceled) {
										$('#repeat_btn').one("click", function() { i_deleted=0;b_canceled = false; processEmpty(); }).removeAttr('disabled');
										$('#ok_btn').one("click", function() { closeDialog(); }).removeAttr('disabled');
									} else {
										processEmpty();
									}
								}


							},

							error: function(event, XMLHttpRequest, ajaxOptions, thrownError) {
								if(window.session) {
									window.session.stopAutoActions();
								}

								reportError(getLabel('js-trash-ajaxerror'));
							}

						});
					}
					processEmpty();



				//	break;
				
			}



		]]></script>
		<div class="tabs-content module">
			<div class="section selected">
				<div class="location">
					<div class="imgButtonWrapper loc-left">
						<a href="javascript:void(0);" class="del btn color-blue"
						   onclick="javascript:showClearTrashConfirm();">&label-empty-all;</a>
					</div>
					<a class="btn-action loc-right infoblock-show"><i class="small-ico i-info"></i><xsl:text>&help;</xsl:text></a>
				</div>

				<div class="layout">
					<div class="column">
						<xsl:call-template name="ui-smc-table">
							<xsl:with-param name="control-params">trash</xsl:with-param>
							<xsl:with-param name="content-type">pages</xsl:with-param>
							<xsl:with-param name="flat-mode">1</xsl:with-param>
							<xsl:with-param name="show-toolbar">1</xsl:with-param>
							<xsl:with-param name="disable-csv-buttons">1</xsl:with-param>
							<xsl:with-param name="js-ignore-props-edit">['name']</xsl:with-param>
							<xsl:with-param name="enable-edit">false</xsl:with-param>
							<xsl:with-param name="toolbarmenu">
								<![CDATA[
									toolbarMenu = ['restoreButton','delButton'];
								]]>
							</xsl:with-param>
						</xsl:call-template>
					</div>
					<div class="column">
						<div  class="infoblock">
							<h3>
								<xsl:text>&label-quick-help;</xsl:text>
							</h3>
							<div class="content" title="{$context-manul-url}">
							</div>
							<div class="infoblock-hide"></div>
						</div>
					</div>
				</div>
			</div>

		</div>


	</xsl:template>

</xsl:stylesheet>