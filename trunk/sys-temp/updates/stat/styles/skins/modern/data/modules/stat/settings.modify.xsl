<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">


	<xsl:template match="/result[@method = 'config']/data[@type = 'settings' and @action = 'modify']">
		<div class="tabs-content module">
			<div class="section selected">
				<div class="location">
					<a class="btn-action loc-right infoblock-show">
						<i class="small-ico i-info"></i>
						<xsl:text>&help;</xsl:text>
					</a>
				</div>

				<div class="layout">
					<div class="column">
						<form method="post" action="do/" enctype="multipart/form-data">
							<xsl:apply-templates select="." mode="settings.modify"/>
							<div class="row">
								<xsl:call-template name="std-form-buttons-settings"/>
							</div>
						</form>
						<xsl:apply-templates select="/result/@demo" mode="stopdoItInDemo"/>
						<script type="text/javascript" language="javascript">
							<![CDATA[
							function ClearButtonClick () {
								var callback = function () {
									window.location.href = "]]><xsl:value-of select="$lang-prefix"/><![CDATA[/admin/stat/clear/do/";]]>
									<![CDATA[
								};

								openDialog('', "]]>&label-stat-clear;<![CDATA[", {
									html            : "]]>&label-stat-clear-confirm;<![CDATA[",
									confirmText     : "]]>&label-clear;<![CDATA[",
									cancelButton    : true,
									cancelText      : "]]>&label-cancel;<![CDATA[",
									confirmCallback	: callback
								});

								return false;
							}
						  ]]>
						</script>
						<div class="panel-settings">
							<div class="title">
								<div class="round-toggle"></div>
								<h3>&label-stat-clear;</h3>
							</div>
							<div class="content">
								&label-stat-clear-help;
								<div class="pull-right">
									<input type="button" value="&label-stat-clear;"
										   class="btn color-blue"
										   onclick="javascript:ClearButtonClick();"/>
								</div>
							</div>

						</div>
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
	
	<xsl:template match="/result/data/group[@name='statDomainConfig']" mode="settings.modify">
		<xsl:if test="count(option) > 1">
			<div class="panel-settings">
				<div class="title">
					<div class="round-toggle"></div>
					<h3>
						<xsl:value-of select="@label" />
					</h3>
				</div>
				<div class="content">
					<table class="btable btable-striped middle-align bold-head">
						<tbody>
							<xsl:apply-templates select="option" mode="settings.modify-nolabel" />
						</tbody>
					</table>

				</div>
			</div>
		</xsl:if>
	</xsl:template>
	
	<xsl:template match="option" mode="settings.modify-nolabel">
		<tr>
			<td class="eq-col">
				<label for="{@name}">
					<xsl:value-of select="substring-after(@label,'collect-')" />
				</label>
			</td>
			
			<td>
				<xsl:apply-templates select="." mode="settings.modify-option" />
			</td>
		</tr>
	</xsl:template>
	
	
</xsl:stylesheet>