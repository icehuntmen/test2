<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">
<xsl:stylesheet version="1.0"
				xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
				xmlns:php="http://php.net/xsl" xmlns:xslt="http://www.w3.org/1999/XSL/Transform"
				xmlns:umi="http://www.w3.org/1999/xhtml">

	<xsl:template match="/result[@method = 'cache' or @method = 'mails' or @method = 'watermark']/data">
		<div class="location">
			<a class="btn-action loc-right infoblock-show"><i class="small-ico i-info"/><xsl:text>&help;</xsl:text></a>
		</div>

		<div class="layout">
			<div class="column">
				<xsl:if test="../@method = 'watermark'">
					<xsl:call-template name="watermark_preview" />
				</xsl:if>
				<form id="{../@module}_{../@method}_form" action="do/" method="post">
					<xsl:apply-templates select="group" mode="settings.modify.table" />
					<div class="row">
						<xsl:call-template name="std-form-buttons-settings" />
					</div>
				</form>

				<xsl:apply-templates select="../@demo" mode="stopdoItInDemo" />
			</div>
			<div class="column">
				<div  class="infoblock">
					<h3><xsl:text>&label-quick-help;</xsl:text></h3>
					<div class="content" title="{$context-manul-url}">
					</div>
					<div class="infoblock-hide"/>
				</div>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="option[@type = 'email' and @name = 'admin_email']" mode="settings.modify-option">
		<input type="text" class="default" name="{@name}" value="{value}" id="{@name}"/>
	</xsl:template>

	<xsl:template name="watermark_preview">
		<xsl:param name="image" select="'./styles/skins/modern/design/img/watermark_preview.jpg'"/>
		<xsl:param name="width" select="533" />
		<xsl:param name="height" select="'auto'" />
		<xsl:variable name="change_filemtime" select="php:function('touch', string($image))" />

		<div id="preview_wrapper">
			<h3>Предпросмотр водяного знака</h3>
			<div class="preview">
				<xsl:variable name="preview_src"
							  select="document(concat('udata://system/makeThumbnailFull/(', $image ,')/', $width ,'/', $height ,'/void/0/0/5/1/100'))/udata/src" />
				<img src="{$preview_src}" />
			</div>
		</div>
	</xsl:template>

	<xsl:template match="/result[@method = 'main']//group" mode="settings.modify">
		<table class="btable btable-striped config-main" style="margin-bottom:200px;">
			<tbody>
				<xsl:apply-templates select="option" mode="settings.modify.table" >
					<xsl:with-param name="title_column_width" select="'65%'" />
					<xsl:with-param name="value_column_width" select="'35%'" />
				</xsl:apply-templates>
			</tbody>
		</table>
		<xsl:apply-templates select="/result/@demo" mode="stopdoItInDemo" />
	</xsl:template>

	<xsl:template match="/result[@method = 'cache']//group" mode="settings.modify.table">
		<xsl:if test="position() = 1">
			<script type="text/javascript" language="javascript" src="/js/jquery/jquery.cookie.js"/>
			<script type="text/javascript">
				<![CDATA[
					function setConfigCookie(){
						var date = new Date();
						date.setTime(date.getTime() + (60 * 60 * 1000));
						jQuery.cookie("umi_config_cookie", "Y",  { expires: date });
					}
					jQuery(document).ready(function(){
						var confirmId = Math.round( Math.random() * 100000 );
						var skin =
							"<div class=\"eip_win_head popupHeader\" onmousedown=\"jQuery('.eip_win').draggable(c)\">\n\
								<div class=\"eip_win_close popupClose\" onclick=\"javascript:jQuery.closePopupLayer('macConfirm"+confirmId+"'); setConfigCookie(); return false;\">&#160;</div>\n\
								<div class=\"eip_win_title\">" + getLabel('js-index-speedmark-message') + "</div>\n\
							</div>\n\
							<div class=\"eip_win_body popupBody\" onmousedown=\"jQuery('.eip_win').draggable().draggable('destroy')\">\n\
								<div class=\"popupText\" style=\"zoom:1;\">" + getLabel('js-index-speedmark-popup') + "</div>\n\
								<div class=\"eip_buttons\">\n\
									<input type=\"button\" class=\"back\" value=\"Закрыть\" onclick=\"confirmButtonCancelClick('macConfirm" + confirmId+"', "+confirmId+"); setConfigCookie(); return false;\" />\n\
									<div style=\"clear: both;\"/>\
								</div>\n\
							</div>";
						var param = {
							name : 'macConfirm' + confirmId,
							width : 300,
							data : skin,
							closeable : true
						};

						if (!jQuery.cookie("umi_config_cookie")) {
							jQuery.openPopupLayer(param);
						}
					});

					function SpeedMark(c) {
						this.iterations = c || 20;
						this.currentIteration = 0;

						this.error = false;

						this.blank_url = "/admin/config/speedtest/";

						this.started = null;

						var self = this;
						$(document).ajaxError(function(event, request, settings){
							if(settings.url.indexOf(self.blank_url) == 0) {
								self.error = true;

								self.end();
							}
						});
					}

					SpeedMark.prototype.start = function() {
						var self = this;

						if(this.started) {
							return false;
						}

						jQuery(".speedmark").show();
						jQuery("#speedmark_avg").html(getLabel('js-index-speedmark-wait'));

						this.time = 0;
						this.error = false;
						this.finished = 0;
						this.started = true;
						this.authorized = true;

						if (!self.makeRequest()) {
							return false;
						}

						return false;
					}

					SpeedMark.prototype.makeRequest = function() {
						var self = this;

						jQuery.ajax({
							url: self.blank_url + '?random=' + Math.random(),
							dataType: 'text',
							success: function(data) {
								var time = parseFloat(data);
								if(!time) {
									self.authorized = false;
								}

								self.time += time;
								self.currentIteration++;

								if (self.currentIteration <= self.iterations) {
									self.makeRequest();
								} else {
									self.end();
								}
							}
						});

						if(!this.authorized) {
							location.reload();
							return false;
						}

						return true;
					}

					SpeedMark.prototype.end = function() {
						this.started = false;
						this.currentIteration = 0;

						this.time = parseFloat(this.time);

						var avg_time = this.time / this.iterations;

						var mark = Math.round(1 / avg_time * 100)/100;
						var rate;
						if(mark < 10) {
							rate = getLabel('js-index-speedmark-less-10');
						} else if (mark < 20) {
							rate = getLabel('js-index-speedmark-less-20');
						} else if (mark < 30) {
							rate = getLabel('js-index-speedmark-less-30');
						} else if (mark < 40) {
							rate = getLabel('js-index-speedmark-less-40');
						} else if (mark >= 40) {
							rate = getLabel('js-index-speedmark-more-40');
						}

						var result = '<b>' + mark + '</b> - ' + rate;
						jQuery("#speedmark_avg").removeClass("error");

						if(!this.error) {
							jQuery("#speedmark_avg").html(result);
						}
						else{
							jQuery("#speedmark_avg").addClass("error").html(getLabel('js-index-speedmark-error'));
						}
					}

					var speedmark = new SpeedMark();
				]]>
			</script>
		</xsl:if>

		<div class="panel-settings">
			<div class="title">
				<h3>
					<xsl:value-of select="@label"/>
				</h3>
			</div>
			<div class="content">
				<table class="btable btable-striped middle-align">
					<tbody>
						<xsl:apply-templates select="option" mode="settings.modify.table"/>
						<xsl:if test="@name = 'test'">
							<tr>
								<td>
									<div class="speedmark-link">
										<a href="#"
										   onclick="return speedmark.start()">&js-check-speedmark;</a>
									</div>
									<div class="speedmark" style="display:none;">
										&js-system-speedmark;:
										<span id="speedmark_avg"/>
										<p>&js-index-speedmark;</p>
									</div>
								</td>
							</tr>
							<tr>
								<td>
									<div class="server_load">
										<xsl:value-of select="php:function('get_server_load','')"/>
									</div>
								</td>
							</tr>
						</xsl:if>
					</tbody>
				</table>
			</div>
		</div>

	</xsl:template>

	<xsl:template match="/result[@method = 'security']/data[@type = 'settings' and @action = 'modify']">
		<div class="tabs-content module">
			<div class="section selected">
				<div class="location">
					<a class="btn-action loc-right infoblock-show"><i class="small-ico i-info"/><xsl:text>&help;</xsl:text></a>
				</div>

				<div class="layout">
					<div class="column">
						<form method="post" action="do/" enctype="multipart/form-data">
							<xsl:apply-templates select="." mode="settings.modify" />
						</form>
					</div>
					<div class="column">
						<div class="infoblock">
							<h3><xsl:text>&label-quick-help;</xsl:text></h3>
							<div class="content" title="{$context-manul-url}">
							</div>
							<div class="infoblock-hide"/>
						</div>
					</div>
				</div>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="/result[@method = 'security']/data/group[@name='security-audit']" mode="settings.modify" >
		<script type="text/javascript" src="/styles/skins/modern/design/js/common/security.js"/>

		<div class="panel-settings">
			<div class="title">
				<h3>
					<xsl:value-of select="@label"/>
				</h3>
			</div>
			<div class="content">
				<table class="btable btable-striped" id="testsTable">
					<tbody>
						<xsl:apply-templates select="option" mode="settings.modify"/>
					</tbody>
				</table>
			</div>
			<div class="pull-right">
				<input type="button" class="btn color-blue" id="startSecurityTests"
					   value="&js-check-security;"/>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="group[@name='security-audit']/option" mode="settings.modify">
		<tr class="test">
			<xsl:attribute name="data-id">
				<xsl:value-of select="@type"/>
			</xsl:attribute>

			<td class="test-name"><xsl:value-of select="@label" /></td>
			<td class="test-value">&js-index-security-no;</td>
		</tr>
	</xsl:template>

	<xsl:template match="/result[@method = 'mails' or @method = 'watermark']//group" mode="settings.modify">
		<table class="btable btable-striped">
			<tbody>
				<xsl:apply-templates select="option" mode="settings.modify" />
			</tbody>
		</table>
	</xsl:template>

	<xsl:template match="option[@type = 'int' and @name = 'alpha']" mode="settings.modify-option" >
		<input type="number" class="default" name="{@name}" value="{value}" id="alpha" min="0" max="100"/>
	</xsl:template>

	<xsl:template match="option[@type = 'string' and @name = 'image']" mode="settings.modify-option">
		<xsl:variable name="filemanager-id" select="document(concat('uobject://',/result/@user-id))/udata//property[@name = 'filemanager']/value/item/@id" />
		<xsl:variable name="filemanager">
			<xsl:choose>
				<xsl:when test="not($filemanager-id)">
					<xsl:text>elfinder</xsl:text>
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="document(concat('uobject://',$filemanager-id))/udata//property[@name = 'fm_prefix']/value" />
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>

		<div class="file watermark" id="{generate-id()}" umi:input-name="{@name}" umi:field-type="image_file"
			 umi:name="{@name}"
			 umi:lang="{/result/@interface-lang}"
			 umi:filemanager="{$filemanager}"
			 umi:file="{value}"
			 umi:folder="./images/cms/data"
			 umi:on_get_file_function="onChooseWaterMark">

			<label for="fileControlContainer_{generate-id()}">
				<span class="layout-row-icon" id="fileControlContainer_{generate-id()}"/>
			</label>
		</div>
	</xsl:template>

	<xsl:template match="option[@type = 'status' and @name = 'reset']" mode="settings.modify.table">
		<tr>
			<td />
			<td>
				<input type="button" class="btn color-blue" value="{@label}" id="cache_reset" />
			</td>
		</tr>

		<xsl:if test="not($demo)">
			<script>
			jQuery('#cache_reset').click(function(){
				location.pathname = '<xsl:value-of select="$lang-prefix" />/admin/config/cache/reset/';
				return false;
			});
			</script>
		 </xsl:if>
		 <xsl:if test="$demo">
			<script>
			jQuery('#cache_reset').click(function(){
				jQuery.jGrowl('<p>В демонстрационном режиме эта функция недоступна</p>', {
					'header': 'UMI.CMS',
					'life': 10000
				});
				return false;
			});
			</script>
		 </xsl:if>
	</xsl:template>

	<xsl:template match="option[@type = 'status' and @name = 'branch']" mode="settings.modify">
		<tr>
			<td class="eq-col">
				<xsl:value-of select="@label" />
			</td>

			<td>
				<input type="button" class="btn color-blue btn-small" value="&option-branch; {value}%" onclick="doOptimizeDB()" />
			</td>
		</tr>
	</xsl:template>

	<!-- Вкладка "CAPTCHA" -->
	<xsl:template match="/result[@method = 'captcha']">
		<script type="text/javascript" src="/styles/skins/modern/data/modules/config/captcha.js"/>

		<div class="tabs-content module">
			<div class="section selected">
				<div class="location">
					<div class="save_size"/>
					<a class="btn-action loc-right infoblock-show">
						<i class="small-ico i-info"/>
						<xsl:text>&help;</xsl:text>
					</a>
				</div>

				<div class="layout">
					<div class="column">
						<form method="post" action="do/" enctype="multipart/form-data">
							<xsl:apply-templates select="//group" mode="captcha.settings.modify" />

							<div class="row">
								<xsl:call-template name="std-form-buttons-settings"/>
							</div>
						</form>
						<xsl:apply-templates select="/result/@demo" mode="stopdoItInDemo"/>
					</div>

					<div class="column">
						<div  class="infoblock">
							<h3>
								<xsl:text>&label-quick-help;</xsl:text>
							</h3>
							<div class="content" title="{$context-manul-url}">
							</div>
							<div class="infoblock-hide"/>
						</div>
					</div>
				</div>
			</div>
		</div>
	</xsl:template>

	<!-- Группа настроек капчи, общих для всех сайтов -->
	<xsl:template match="/result[@method = 'captcha']//group[@name = 'captcha']" mode="captcha.settings.modify">
		<div class="panel-settings">
			<div class="title">
				<h3><xsl:value-of select="@label" /></h3>
			</div>

			<div class="content">
				<xsl:apply-templates select="option" mode="settings.modify" />
			</div>
		</div>
	</xsl:template>

	<!-- Группа настроек капчи для конкретного сайта -->
	<xsl:template match="/result[@method = 'captcha']//group[@name != 'captcha']" mode="captcha.settings.modify">
		<xsl:variable name="domain" select="option[position() = 1]/value" />

		<div class="panel-settings">
			<div class="title">
				<h3><xsl:value-of select="concat($domain, $lang-prefix)" /></h3>
			</div>

			<div class="content">
				<xsl:apply-templates select="option[position() > 1]" mode="captcha.settings.modify" />
			</div>
		</div>
	</xsl:template>

	<!-- Отдельная настройка капчи для конкретного сайта -->
	<xsl:template match="option" mode="captcha.settings.modify">
		<!-- label без "-<id домена>" на конце -->
		<xsl:variable name="trimmedLabel">
			<xsl:value-of select="php:function('mb_substr', string(@label), 0, php:function('mb_strrpos', string(@label), '-'))" />
		</xsl:variable>

		<div class="row">
			<div class="col-md-4">
				<div class="title-edit">
					<xsl:value-of select="php:function('getLabel', $trimmedLabel)" />
				</div>
			</div>

			<div class="col-md-4">
				<xsl:apply-templates select="." mode="settings.modify-option" />
			</div>
		</div>
	</xsl:template>

	<xsl:template match="/result[@method = 'domain_mirrows']/data">
		<div class="tabs-content module">
			<div class="section selected">
				<div class="location">
					<div class="save_size"/>
					<a class="btn-action loc-right infoblock-show">
						<i class="small-ico i-info"/>
						<xsl:text>&help;</xsl:text>
					</a>
				</div>

				<div class="layout">
					<div class="column">
						<form method="post" action="do/" enctype="multipart/form-data">
							<xsl:apply-templates select="." mode="settings.modify"/>

							<table class="btable btable-striped bold-head middle-align">
								<thead>
									<tr>
										<th>
											<xsl:text>&label-domain-mirror-address;</xsl:text>
										</th>
										<th>
											<xsl:text>&label-delete;</xsl:text>
										</th>
									</tr>
								</thead>
								<tbody>
									<xsl:apply-templates select="domainMirrow" mode="settings-modify"/>
									<tr>
										<td>
											<input type="text" name="data[new][host]" class="default"/>
										</td>
										<td />
									</tr>
								</tbody>
							</table>

							<div class="row">
								<xsl:call-template name="std-form-buttons-settings"/>
							</div>
						</form>
					</div>

					<div class="column">
						<div  class="infoblock">
							<h3>
								<xsl:text>&label-quick-help;</xsl:text>
							</h3>
							<div class="content" title="{$context-manul-url}">
							</div>
							<div class="infoblock-hide"/>
						</div>
					</div>
				</div>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="domainMirrow" mode="settings-modify">
		<tr>
			<td>
				<input type="text" name="data[{@id}][host]" value="{@host}" class="default"/>
			</td>

			<td class="center">
				<div class="checkbox">
					<input type="checkbox" name="dels[]" value="{@id}" class="checkbox" />
				</div>
			</td>
		</tr>
	</xsl:template>

</xsl:stylesheet>
