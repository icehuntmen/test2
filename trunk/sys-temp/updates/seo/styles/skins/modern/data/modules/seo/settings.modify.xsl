<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:template match="option[@name = 'megaindex-password']" mode="settings.modify-option">
		<input class="default" type="password" name="{@name}" value="{value}" id="{@name}" />
	</xsl:template>

	<xsl:template match="/result[@method = 'config']/data[@type = 'settings' and @action = 'modify']">
		<div class="tabs-content module">
		<div class="section selected">
		<div class="location">
			<div class="save_size"></div>
			<a class="btn-action loc-right infoblock-show">
				<i class="small-ico i-info"></i>
				<xsl:text>&help;</xsl:text>
			</a>
		</div>

		<div class="layout">
		<div class="column">
		<form method="post" action="do/" enctype="multipart/form-data">
			<div class="panel-settings">
				<div class="title">
					<div class="round-toggle"></div>
					<h3><xsl:text>&header-seo-domains;</xsl:text></h3>
				</div>
				<div class="content">
					<xsl:apply-templates select="group[@name != 'yandex']" mode="settings-modify" />

				</div>
			</div>
			<div class="row">
				<xsl:call-template name="std-form-buttons-settings" />
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
					<div class="infoblock-hide"></div>
				</div>
			</div>
		</div>
		</div>
		</div>
		<xsl:apply-templates select="/result/@demo" mode="stopdoItInDemo" />

		<xsl:call-template name="error-checker" />
		<script>
			var form = $('form').eq(0);
			jQuery(form).submit(function() {
				return checkErrors({
					form: form,
					check: {
						empty: 'input.required',
					}
				});
			});
		</script>
	</xsl:template>

	<xsl:template match="/result[@method = 'config']//group" mode="settings-modify">
		<xsl:variable name="seo-title" select="option[starts-with(./@name, 'title')]" />
		<xsl:variable name="seo-default-title" select="option[starts-with(./@name, 'default')]" />
		<xsl:variable name="seo-keywords" select="option[starts-with(./@name, 'keywords')]" />
		<xsl:variable name="seo-description" select="option[starts-with(./@name, 'description')]" />
		<div class="row">
			<div class="col-md-12" style="font-size: 18px; margin-bottom:15px;">
				<strong><xsl:value-of select="option[@name = 'domain']/value"/>
				</strong>
			</div>
			<div class="col-md-6">
				<div class="title-edit">
					<acronym>&option-seo-title;</acronym>
				</div>
				<span>
					<input class="default" type="text" name="{$seo-title/@name}" value="{$seo-title/value}" id="{$seo-title/@name}" />
				</span>
			</div>
			<div class="col-md-6">
				<div class="title-edit">
					<acronym>&option-seo-default-title;</acronym>
				</div>
				<span>
					<input class="default" type="text" name="{$seo-default-title/@name}" value="{$seo-default-title/value}" id="{$seo-default-title/@name}" />
				</span>
			</div>
			<div class="col-md-6">
				<div class="title-edit">
					<acronym>&option-seo-keywords;</acronym>
				</div>
				<span>
					<input class="default" type="text" name="{$seo-keywords/@name}" value="{$seo-keywords/value}" id="{$seo-keywords/@name}" />
				</span>
			</div>
			<div class="col-md-6">
				<div class="title-edit">
					<acronym>&option-seo-description;</acronym>
				</div>
				<span>
					<input class="default" type="text" name="{$seo-description/@name}" value="{$seo-description/value}" id="{$seo-description/@name}" />
				</span>
			</div>
		</div>
		<hr/>
	</xsl:template>

</xsl:stylesheet>
